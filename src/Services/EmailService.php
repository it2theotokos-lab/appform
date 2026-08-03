<?php
namespace App\Services;

class EmailService {
    public static function getSettings(): array {
        $db = \App\Core\Database::getInstance();
        $stmt = $db->query("SELECT * FROM smtp_settings LIMIT 1");
        $res = $stmt->fetch();
        if (!$res) {
            return [
                'host' => 'localhost',
                'port' => 25,
                'encryption' => 'None',
                'username' => '',
                'from_email' => 'noreply@appform.local',
                'from_name' => 'AppForm System',
                'reply_to_email' => '',
                'timeout' => 30,
                'auth_enabled' => 0,
                'is_active' => 0
            ];
        }
        return $res;
    }

    public static function saveSettings(array $data) {
        $db = \App\Core\Database::getInstance();
        
        $host = $data['host'] ?? 'localhost';
        $port = (int)($data['port'] ?? 25);
        $encryption = $data['encryption'] ?? 'None';
        $username = $data['username'] ?? '';
        $from_email = $data['from_email'] ?? 'noreply@appform.local';
        $from_name = $data['from_name'] ?? 'AppForm System';
        $reply_to_email = $data['reply_to_email'] ?? '';
        $timeout = (int)($data['timeout'] ?? 30);
        $auth_enabled = isset($data['auth_enabled']) ? 1 : 0;
        $is_active = isset($data['is_active']) ? 1 : 0;
        
        // Simple secure key encryption of SMTP password
        $password = $data['password'] ?? '';
        $encryptedPass = null;
        if ($password !== '' && $password !== '••••••••') {
            $key = \App\Core\App::$config['app_key'] ?? 'appform_secret_encryption_key_hash';
            $encryptedPass = openssl_encrypt($password, 'AES-128-ECB', $key);
        }

        $stmtCheck = $db->query("SELECT id FROM smtp_settings LIMIT 1");
        $existing = $stmtCheck->fetch();

        if ($existing) {
            $sql = "
                UPDATE smtp_settings 
                SET host = ?, port = ?, encryption = ?, username = ?, from_email = ?, from_name = ?, reply_to_email = ?, timeout = ?, auth_enabled = ?, is_active = ?
            ";
            $params = [$host, $port, $encryption, $username, $from_email, $from_name, $reply_to_email, $timeout, $auth_enabled, $is_active];
            if ($encryptedPass !== null) {
                $sql .= ", encrypted_password = ?";
                $params[] = $encryptedPass;
            }
            $sql .= " WHERE id = ?";
            $params[] = (int)$existing['id'];
            
            $db->prepare($sql)->execute($params);
        } else {
            $stmt = $db->prepare("
                INSERT INTO smtp_settings (host, port, encryption, username, encrypted_password, from_email, from_name, reply_to_email, timeout, auth_enabled, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$host, $port, $encryption, $username, $encryptedPass ?: '', $from_email, $from_name, $reply_to_email, $timeout, $auth_enabled, $is_active]);
        }
    }

    public static function testConnection(array $config): array {
        $host = $config['host'] ?? 'localhost';
        $port = (int)($config['port'] ?? 25);
        $timeout = (int)($config['timeout'] ?? 5);

        // Safe socket test
        $errno = 0;
        $errstr = '';
        $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
        if (!$fp) {
            return [
                'success' => false,
                'message' => "Αδυναμία σύνδεσης: {$errstr} ({$errno})"
            ];
        }
        fclose($fp);

        return [
            'success' => true,
            'message' => 'Η σύνδεση socket με τον mail server πραγματοποιήθηκε επιτυχώς!'
        ];
    }

    public static function sendEmail(string $recipient, string $subject, string $body): bool {
        $settings = self::getSettings();
        
        $host = $settings['host'] ?? 'localhost';
        $port = (int)($settings['port'] ?? 25);
        $encryption = $settings['encryption'] ?? 'None';
        $timeout = (int)($settings['timeout'] ?? 30);
        $authEnabled = (int)($settings['auth_enabled'] ?? 0);
        $username = $settings['username'] ?? '';
        $fromEmail = $settings['from_email'] ?? 'noreply@appform.local';
        $fromName = $settings['from_name'] ?? 'AppForm System';

        // Decrypt SMTP password if set and encrypted
        $password = '';
        if (!empty($settings['encrypted_password'])) {
            $key = \App\Core\App::$config['app_key'] ?? 'appform_secret_encryption_key_hash';
            $password = openssl_decrypt($settings['encrypted_password'], 'AES-128-ECB', $key) ?: '';
        }

        $logPath = 'storage/logs/mail_delivery.log';
        $logDir = dirname($logPath);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        // Connection helper wrapper to fetch SMTP server lines
        $smtpResponse = '';
        $getResponse = function($socket) use (&$smtpResponse) {
            $response = '';
            while (($line = fgets($socket, 515)) !== false) {
                $response .= $line;
                if (substr($line, 3, 1) === ' ') {
                    break;
                }
            }
            $smtpResponse .= $response;
            return $response;
        };

        $sendCommand = function($socket, $cmd) use ($getResponse, &$smtpResponse) {
            $smtpResponse .= "> {$cmd}\n";
            fputs($socket, $cmd . "\r\n");
            return $getResponse($socket);
        };

        try {
            // Apply secure context wrappers if TLS/SSL chosen
            $remoteSocket = $host;
            if (strtolower($encryption) === 'ssl') {
                $remoteSocket = 'ssl://' . $host;
            }

            $errno = 0;
            $errstr = '';
            $socket = @stream_socket_client(
                "tcp://{$host}:{$port}",
                $errno,
                $errstr,
                $timeout,
                STREAM_CLIENT_CONNECT
            );

            if (!$socket) {
                throw new \Exception("Connection Failed: {$errstr} ({$errno})");
            }

            stream_set_timeout($socket, $timeout);

            // Read welcome banner
            $banner = $getResponse($socket);
            if (substr($banner, 0, 3) !== '220') {
                throw new \Exception("Invalid SMTP Banner: " . trim($banner));
            }

            // Say EHLO
            $ehlo = $sendCommand($socket, "EHLO " . gethostname());
            if (substr($ehlo, 0, 3) !== '250') {
                throw new \Exception("EHLO Rejected: " . trim($ehlo));
            }

            // Handle STARTTLS transition if selected
            if (strcasecmp($encryption, 'tls') === 0 || strcasecmp($encryption, 'starttls') === 0) {
                $tlsResponse = $sendCommand($socket, "STARTTLS");
                if (substr($tlsResponse, 0, 3) !== '220') {
                    throw new \Exception("STARTTLS Rejected: " . trim($tlsResponse));
                }

                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new \Exception("TLS Handshake Failed.");
                }

                // Say EHLO again over TLS channel
                $ehlo = $sendCommand($socket, "EHLO " . gethostname());
                if (substr($ehlo, 0, 3) !== '250') {
                    throw new \Exception("EHLO Over TLS Rejected: " . trim($ehlo));
                }
            }

            // Perform AUTH LOGIN if enabled
            if ($authEnabled) {
                $authResponse = $sendCommand($socket, "AUTH LOGIN");
                if (substr($authResponse, 0, 3) !== '334') {
                    throw new \Exception("AUTH LOGIN Method Rejected: " . trim($authResponse));
                }

                $userResponse = $sendCommand($socket, base64_encode($username));
                if (substr($userResponse, 0, 3) !== '334') {
                    throw new \Exception("Username Rejected: " . trim($userResponse));
                }

                $passResponse = $sendCommand($socket, base64_encode($password));
                if (substr($passResponse, 0, 3) !== '235') {
                    throw new \Exception("Authentication Failed: " . trim($passResponse));
                }
            }

            // Send MAIL FROM
            $mailFrom = $sendCommand($socket, "MAIL FROM:<{$fromEmail}>");
            if (substr($mailFrom, 0, 3) !== '250') {
                throw new \Exception("MAIL FROM Rejected: " . trim($mailFrom));
            }

            // Send RCPT TO
            $rcptTo = $sendCommand($socket, "RCPT TO:<{$recipient}>");
            if (substr($rcptTo, 0, 3) !== '250') {
                throw new \Exception("RCPT TO Rejected: " . trim($rcptTo));
            }

            // Send DATA
            $dataResponse = $sendCommand($socket, "DATA");
            if (substr($dataResponse, 0, 3) !== '354') {
                throw new \Exception("DATA command Rejected: " . trim($dataResponse));
            }

            // Format headers and body content safely with multipart/alternative support
            $boundary = "----=_Part_" . md5(uniqid(microtime(), true));

            $headers = [];
            $headers[] = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$fromEmail}>";
            $headers[] = "To: <{$recipient}>";
            $headers[] = "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=";
            $headers[] = "MIME-Version: 1.0";
            $headers[] = "Content-Type: multipart/alternative; boundary=\"{$boundary}\"";
            $headers[] = "Date: " . date('r');
            $headers[] = "Message-ID: <" . md5(uniqid(microtime(), true)) . "@" . $host . ">";

            // Format Plain Text fallback
            $plainText = preg_replace('/<br\s*\/?>/i', "\n", $body);
            $plainText = preg_replace('/<\/p>/i', "\n\n", $plainText);
            $plainText = html_entity_decode(strip_tags($plainText), ENT_QUOTES, 'UTF-8');

            $mimePayload = [];
            $mimePayload[] = "This is a multi-part message in MIME format.";
            $mimePayload[] = "--{$boundary}";
            $mimePayload[] = "Content-Type: text/plain; charset=UTF-8";
            $mimePayload[] = "Content-Transfer-Encoding: 8bit";
            $mimePayload[] = "";
            $mimePayload[] = trim($plainText);
            $mimePayload[] = "--{$boundary}";
            $mimePayload[] = "Content-Type: text/html; charset=UTF-8";
            $mimePayload[] = "Content-Transfer-Encoding: 8bit";
            $mimePayload[] = "";
            $mimePayload[] = $body;
            $mimePayload[] = "--{$boundary}--";

            $payload = implode("\r\n", $headers) . "\r\n\r\n" . implode("\r\n", $mimePayload);
            // Prevent double dot encapsulation rule (SMTP transparency)
            $payload = str_replace("\n.", "\n..", $payload);

            $sendBody = $sendCommand($socket, $payload . "\r\n.");
            if (substr($sendBody, 0, 3) !== '250') {
                throw new \Exception("Email payload Rejected: " . trim($sendBody));
            }

            // Quit
            $sendCommand($socket, "QUIT");
            fclose($socket);

            $logMsg = sprintf("[%s] SUCCESS | TO: %s | SUBJECT: %s | SMTP RESPONSE: %s\n", date('Y-m-d H:i:s'), $recipient, $subject, trim(str_replace("\n", " | ", $smtpResponse)));
            file_put_contents($logPath, $logMsg, FILE_APPEND);

            // Log details in database tables if tables exist
            try {
                $db = \App\Core\Database::getInstance();
                $stmtLog = $db->prepare("
                    INSERT INTO form_notification_logs (form_id, recipient_summary, status, response_message)
                    VALUES (0, ?, 'delivered', ?)
                ");
                $stmtLog->execute([$recipient, trim($smtpResponse)]);
            } catch (\Exception $eDbLog) {}

            return true;

        } catch (\Exception $e) {
            if (isset($socket) && is_resource($socket)) {
                @fclose($socket);
            }

            $logMsg = sprintf("[%s] FAILURE | TO: %s | SUBJECT: %s | ERROR: %s | SMTP RESPONSE: %s\n", date('Y-m-d H:i:s'), $recipient, $subject, $e->getMessage(), trim(str_replace("\n", " | ", $smtpResponse)));
            file_put_contents($logPath, $logMsg, FILE_APPEND);

            try {
                $db = \App\Core\Database::getInstance();
                $stmtLog = $db->prepare("
                    INSERT INTO form_notification_logs (form_id, recipient_summary, status, response_message)
                    VALUES (0, ?, 'failed', ?)
                ");
                $stmtLog->execute([$recipient, $e->getMessage() . " | " . trim($smtpResponse)]);
            } catch (\Exception $eDbLog) {}

            throw new \Exception($e->getMessage() . " | Details: " . trim($smtpResponse));
        }
    }
}
