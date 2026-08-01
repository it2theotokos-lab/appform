<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\View;
use App\Core\Database;
use PDO;

class DashboardController extends Controller {
    public function index() {
        $user = Auth::user();
        $db = Database::getInstance();

        $canViewAll = Auth::hasPermission('submissions.view.all') || $user['role_slug'] === 'administrator';
        $canViewSubordinates = Auth::hasPermission('submissions.view.subordinates') || Auth::hasPermission('submissions.review') || $user['role_slug'] === 'manager';

        if ($canViewAll || $canViewSubordinates) {
            // Determine permitted user IDs for dashboard counts & latest submissions
            $whereUser = "";
            $params = [];

            if (!$canViewAll && $canViewSubordinates) {
                $subordinateIds = \App\Services\OrganizationalScopeService::getSubordinateIds((int)$user['id']);
                $allowedUserIds = array_values(array_unique(array_merge([(int)$user['id']], $subordinateIds)));
                $inClause = implode(',', array_fill(0, count($allowedUserIds), '?'));
                $whereUser = " WHERE s.user_id IN ({$inClause}) ";
                $params = $allowedUserIds;
            }

            // Admin / Manager stats
            $formsCount = $db->query("SELECT COUNT(*) FROM forms")->fetchColumn();
            
            $stmtSubCount = $db->prepare("SELECT COUNT(*) FROM form_submissions s " . $whereUser);
            $stmtSubCount->execute($params);
            $subsCount = $stmtSubCount->fetchColumn();

            $usersCount = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
            
            // Latest submissions respecting visibility scope
            $stmt = $db->prepare("
                SELECT s.*, COALESCE(f.title, 'Αρχειοθετημένη/Διαγραμμένη Φόρμα') as form_title, u.username 
                FROM form_submissions s 
                LEFT JOIN forms f ON s.form_id = f.id 
                JOIN users u ON s.user_id = u.id 
                {$whereUser}
                ORDER BY s.created_at DESC LIMIT 5
            ");
            $stmt->execute($params);
            $latestSubmissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            View::render('dashboard/admin', [
                'formsCount' => $formsCount,
                'subsCount' => $subsCount,
                'usersCount' => $usersCount,
                'latestSubmissions' => $latestSubmissions,
                'scope' => !$canViewAll && $canViewSubordinates ? 'subordinates' : 'all'
            ]);
        } else {
            // User view: available forms and personal history metrics
            $userId = (int)$user['id'];

            // 1. Available published forms
            $stmt = $db->prepare("
                SELECT f.* FROM forms f 
                WHERE f.status = 'published' AND f.is_active = 1
                ORDER BY f.created_at DESC
            ");
            $stmt->execute();
            $availableForms = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 2. Personal form submissions
            $stmt2 = $db->prepare("
                SELECT s.*, COALESCE(f.title, 'Αρχειοθετημένη/Διαγραμμένη Φόρμα') as form_title, f.slug as form_slug 
                FROM form_submissions s 
                LEFT JOIN forms f ON s.form_id = f.id 
                WHERE s.user_id = ? 
                ORDER BY s.updated_at DESC, s.id DESC
            ");
            $stmt2->execute([$userId]);
            $mySubmissions = $stmt2->fetchAll(PDO::FETCH_ASSOC);

            // Calculate personal widgets counts
            $formsCount = count($availableForms);
            $draftsCount = 0;
            $submittedCount = 0;
            $returnedCount = 0;

            $recentDrafts = [];
            $recentReturned = [];

            foreach ($mySubmissions as $sub) {
                if ($sub['status'] === 'draft') {
                    $draftsCount++;
                    if (count($recentDrafts) < 5) $recentDrafts[] = $sub;
                } elseif ($sub['status'] === 'returned') {
                    $returnedCount++;
                    if (count($recentReturned) < 5) $recentReturned[] = $sub;
                } else {
                    $submittedCount++;
                }
            }

            // Fetch unread notifications count
            $stmtNotif = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
            $stmtNotif->execute([$userId]);
            $unreadNotificationsCount = (int)$stmtNotif->fetchColumn();

            View::render('dashboard/user', [
                'availableForms' => $availableForms,
                'mySubmissions' => $mySubmissions,
                'formsCount' => $formsCount,
                'draftsCount' => $draftsCount,
                'submittedCount' => $submittedCount,
                'returnedCount' => $returnedCount,
                'unreadNotificationsCount' => $unreadNotificationsCount,
                'recentDrafts' => $recentDrafts,
                'recentReturned' => $recentReturned
            ]);
        }
    }

    public function showHealthDashboard() {
        View::render('admin/health', [
            'title' => 'System Health Check'
        ]);
    }
}
