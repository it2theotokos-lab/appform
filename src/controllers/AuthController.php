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

        $stmt = $db->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
        $stmt->execute([$validated['full_name'], $validated['email'], $userId]);

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
}
