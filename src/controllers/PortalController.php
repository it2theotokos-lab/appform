<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\View;
use App\Core\Database;
use App\Services\FormAccessService;
use App\Models\Form;
use App\Models\Submission;
use PDO;

class PortalController extends Controller {
    public function index() {
        $userId = Auth::id();
        $db = Database::getInstance();

        // 1. Fetch available forms with assignments
        $stmt = $db->query("SELECT * FROM forms WHERE status = 'published' AND is_active = 1");
        $allForms = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $availableForms = [];
        foreach ($allForms as $form) {
            if (FormAccessService::canUserViewForm($userId, $form['id'])) {
                $availableForms[] = $form;
            }
        }

        // 2. Fetch history
        $mySubmissions = Submission::getByUserId($userId);

        View::render('portal/dashboard', [
            'title' => 'Portal Χρήστη',
            'availableForms' => $availableForms,
            'mySubmissions' => $mySubmissions
        ]);
    }
}
