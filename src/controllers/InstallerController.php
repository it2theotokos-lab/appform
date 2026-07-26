<?php
namespace App\Controllers;

use App\Services\InstallationService;
use App\Core\Csrf;
use App\Core\Session;
use App\Core\View;

class InstallerController {
    public function showInstall() {
        $step = $_GET['step'] ?? 1;
        if (is_numeric($step)) {
            $step = (int)$step;
        }

        if (InstallationService::isInstalled() && $step !== 'success') {
            http_response_code(403);
            exit('<h1>403 Forbidden</h1><p>Η εφαρμογή είναι ήδη εγκατεστημένη.</p>');
        }

        $error = '';
        $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validate()) {
                $error = 'Σφάλμα CSRF: Παρακαλώ δοκιμάστε ξανά.';
            } else {
                switch ($step) {
                    case 1:
                        $reqs = InstallationService::checkRequirements();
                        $allowProceed = true;
                        foreach ($reqs as $r) {
                            if (!$r['pass']) {
                                $allowProceed = false;
                            }
                        }
                        if ($allowProceed) {
                            header('Location: /install?step=2');
                            exit;
                        } else {
                            $error = 'Οι απαιτήσεις συστήματος δεν ικανοποιούνται.';
                        }
                        break;

                    case 2:
                        $dbData = $_POST['db'] ?? [];
                        $_SESSION['install']['db'] = $dbData;
                        
                        $test = InstallationService::testDbConnection($dbData);
                        if ($_POST['action'] === 'test') {
                            if ($test['success']) {
                                $success = $test['message'];
                            } else {
                                $error = $test['message'];
                            }
                        } else {
                            if ($test['success']) {
                                header('Location: /install?step=3');
                                exit;
                            } else {
                                $error = $test['message'];
                            }
                        }
                        break;

                    case 3:
                        $appData = $_POST['app'] ?? [];
                        $_SESSION['install']['app'] = $appData;
                        
                        if (empty($appData['url']) || !filter_var($appData['url'], FILTER_VALIDATE_URL)) {
                            $error = 'Παρακαλώ εισάγετε ένα έγκυρο Base URL.';
                        } else {
                            header('Location: /install?step=4');
                            exit;
                        }
                        break;

                    case 4:
                        $adminData = $_POST['admin'] ?? [];
                        $_SESSION['install']['admin'] = $adminData;

                        if (empty($adminData['username']) || empty($adminData['email']) || empty($adminData['password'])) {
                            $error = 'Όλα τα πεδία είναι υποχρεωτικά.';
                        } elseif ($adminData['password'] !== $adminData['password_confirmation']) {
                            $error = 'Οι κωδικοί πρόσβασης δεν ταιριάζουν.';
                        } elseif (!filter_var($adminData['email'], FILTER_VALIDATE_EMAIL)) {
                            $error = 'Παρακαλώ εισάγετε ένα έγκυρο Email.';
                        } else {
                            header('Location: /install?step=5');
                            exit;
                        }
                        break;

                    case 5:
                        $configData = [
                            'app' => $_SESSION['install']['app'] ?? [],
                            'db' => $_SESSION['install']['db'] ?? []
                        ];
                        $adminData = $_SESSION['install']['admin'] ?? [];

                        $result = InstallationService::runInstallation($configData, $adminData);
                        if ($result['success']) {
                            $adminUser = $adminData['username'];
                            $appUrl = $configData['app']['url'];
                            unset($_SESSION['install']);
                            $_SESSION['install_success'] = [
                                'admin_username' => $adminUser,
                                'app_url' => $appUrl
                            ];
                            header('Location: /install?step=success');
                            exit;
                        } else {
                            $error = $result['message'];
                        }
                        break;
                }
            }
        }

        // Render views based on step
        ob_start();
        $db = $_SESSION['install']['db'] ?? [];
        $app = $_SESSION['install']['app'] ?? [];
        $admin = $_SESSION['install']['admin'] ?? [];

        switch ($step) {
            case 1:
                $requirements = InstallationService::checkRequirements();
                $allowProceed = true;
                foreach ($requirements as $r) {
                    if (!$r['pass']) {
                        $allowProceed = false;
                    }
                }
                include __DIR__ . '/../Views/install/requirements.php';
                break;
            case 2:
                include __DIR__ . '/../Views/install/database.php';
                break;
            case 3:
                // Auto-detect base URL
                if (empty($app['url'])) {
                    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                    $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';
                    $app['url'] = "$protocol://$host";
                }
                include __DIR__ . '/../Views/install/config.php';
                break;
            case 4:
                include __DIR__ . '/../Views/install/admin.php';
                break;
            case 5:
                include __DIR__ . '/../Views/install/review.php';
                break;
            case 'success':
                $successData = $_SESSION['install_success'] ?? [];
                if (empty($successData)) {
                    header('Location: /install?step=1');
                    exit;
                }
                $admin_username = $successData['admin_username'];
                $app_url = $successData['app_url'];
                include __DIR__ . '/../Views/install/success.php';
                break;
            default:
                header('Location: /install?step=1');
                exit;
        }
        $content = ob_get_clean();

        // Render with layout
        include __DIR__ . '/../Views/install/layout.php';
    }
}
