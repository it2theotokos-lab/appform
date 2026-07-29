<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Auth;
use App\Core\Session;
use App\Core\View;

class AuthController extends Controller {
    public function showLogin() {
        View::render('auth/login', [], 'guest');
    }

    public function login() {
        $this->checkCsrf();

        $data = Request::all();
        $validated = $this->validate($data, [
            'username' => ['required'],
            'password' => ['required']
        ]);

        if (Auth::attempt($validated['username'], $validated['password'])) {
            $this->redirect('/dashboard');
        } else {
            // Check if account lockout message was flashed
            if (!Session::has('error')) {
                Session::flash('error', 'Λανθασμένο όνομα χρήστη ή κωδικός πρόσβασης.');
            }
            Session::flash('old', ['username' => $validated['username']]);
            $this->redirect('/login');
        }
    }

    public function logout() {
        Auth::logout();
        $this->redirect('/login');
    }

    public function showProfile() {
        $db = \App\Core\Database::getInstance();
        $stmt = $db->prepare("
            SELECT u.*, r.name as role_name, m.full_name as manager_name 
            FROM users u 
            JOIN roles r ON u.role_id = r.id 
            LEFT JOIN users m ON u.manager_id = m.id 
            WHERE u.id = ?
        ");
        $stmt->execute([Auth::id()]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        View::render('auth/profile', [
            'title' => 'Το Προφίλ μου',
            'user' => $user
        ]);
    }

    public function updateProfile() {
        if (!Auth::check()) {
            $this->redirect('/login');
            return;
        }
        $this->checkCsrf();
        $data = Request::all();
        $validated = $this->validate($data, [
            'full_name' => ['required'],
            'email' => ['required', 'email']
        ]);

        $db = \App\Core\Database::getInstance();
        $userId = Auth::id();

        // Email uniqueness check
        $chk = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
        $chk->execute([$validated['email'], $userId]);
        if ($chk->fetchColumn() > 0) {
            Session::flash('error', 'Το email χρησιμοποιείται ήδη από άλλον χρήστη.');
            $this->redirect('/admin/profile');
            return;
        }

        $avatarPath = null;
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['avatar'];
            
            // 1. Enforce 2 MB size limit
            if ($file['size'] > 2 * 1024 * 1024) {
                Session::flash('error', 'Το μέγεθος της φωτογραφίας δεν πρέπει να ξεπερνά τα 2 MB.');
                $this->redirect('/admin/profile');
                return;
            }
            
            // 2. Validate MIME type
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mime, $allowedMimes)) {
                Session::flash('error', 'Μη αποδεκτός τύπος αρχείου. Επιτρέπονται μόνο JPG, PNG και WEBP.');
                $this->redirect('/admin/profile');
                return;
            }
            
            // 3. Resolve file extension safely
            $ext = 'jpg';
            if ($mime === 'image/png') $ext = 'png';
            if ($mime === 'image/webp') $ext = 'webp';
            
            // 4. Create directory if not exists
            $uploadDir = dirname(dirname(__DIR__)) . '/public/storage/avatars';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // 5. Delete previous avatar if exists
            $stmtUser = $db->prepare("SELECT avatar_path FROM users WHERE id = ?");
            $stmtUser->execute([$userId]);
            $oldAvatar = $stmtUser->fetchColumn();
            if ($oldAvatar) {
                $oldFile = dirname(dirname(__DIR__)) . '/public' . $oldAvatar;
                if (file_exists($oldFile)) {
                    @unlink($oldFile);
                }
            }
            
            // 6. Save new avatar file
            $newFilename = 'avatar_' . $userId . '_' . time() . '_' . uniqid() . '.' . $ext;
            $newFilePath = $uploadDir . '/' . $newFilename;
            if (PHP_SAPI === 'cli' ? copy($file['tmp_name'], $newFilePath) : move_uploaded_file($file['tmp_name'], $newFilePath)) {
                $avatarPath = '/storage/avatars/' . $newFilename;
            } else {
                Session::flash('error', 'Αποτυχία αποθήκευσης της φωτογραφίας.');
                $this->redirect('/admin/profile');
                return;
            }
        }

        if ($avatarPath !== null) {
            $stmt = $db->prepare("UPDATE users SET full_name = ?, email = ?, avatar_path = ? WHERE id = ?");
            $stmt->execute([$validated['full_name'], $validated['email'], $avatarPath, $userId]);
            $_SESSION['user']['avatar_path'] = $avatarPath;
        } else {
            $stmt = $db->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
            $stmt->execute([$validated['full_name'], $validated['email'], $userId]);
        }

        // Update current session user data
        $_SESSION['user']['full_name'] = $validated['full_name'];
        $_SESSION['user']['email'] = $validated['email'];

        Session::flash('success', 'Τα στοιχεία του προφίλ ενημερώθηκαν επιτυχώς.');
        $this->redirect('/admin/profile');
    }

    public function updatePassword() {
        $this->checkCsrf();
        $data = Request::all();
        $validated = $this->validate($data, [
            'current_password' => ['required'],
            'new_password' => ['required']
        ]);

        $db = \App\Core\Database::getInstance();
        $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([Auth::id()]);
        $hash = $stmt->fetchColumn();

        if (!password_verify($validated['current_password'], $hash)) {
            Session::flash('error', 'Ο τρέχων κωδικός πρόσβασης είναι λανθασμένος.');
            $this->redirect('/admin/profile');
            return;
        }

        $newHash = password_hash($validated['new_password'], PASSWORD_BCRYPT);
        $upd = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $upd->execute([$newHash, Auth::id()]);

        Session::flash('success', 'Ο κωδικός πρόσβασης άλλαξε επιτυχώς.');
        $this->redirect('/dashboard');
    }
    public function removeAvatar() {
        if (!Auth::check()) {
            $this->redirect('/login');
            return;
        }
        $this->checkCsrf();

        $db = \App\Core\Database::getInstance();
        $userId = Auth::id();

        $stmtUser = $db->prepare("SELECT avatar_path FROM users WHERE id = ?");
        $stmtUser->execute([$userId]);
        $oldAvatar = $stmtUser->fetchColumn();

        if ($oldAvatar) {
            $oldFile = dirname(dirname(__DIR__)) . '/public' . $oldAvatar;
            if (file_exists($oldFile)) {
                @unlink($oldFile);
            }
            
            $stmt = $db->prepare("UPDATE users SET avatar_path = NULL WHERE id = ?");
            $stmt->execute([$userId]);
            
            $_SESSION['user']['avatar_path'] = null;
            Session::flash('success', 'Η φωτογραφία προφίλ αφαιρέθηκε επιτυχώς.');
        } else {
            Session::flash('error', 'Δεν βρέθηκε φωτογραφία προφίλ για διαγραφή.');
        }

        $this->redirect('/admin/profile');
    }
}
