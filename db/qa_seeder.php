<?php
/**
 * AppForm QA Full Demo Seeder
 * Creates Employee Feedback form + complete demo submissions, notifications, audit logs, saved reports
 * Safe to run multiple times (uses DELETE WHERE + idempotent inserts)
 */

require_once __DIR__ . '/../vendor/autoload.php';
new \App\Core\App();

use App\Core\Database;

$db = Database::getInstance();

echo "============================================================\n";
echo "  AppForm QA Full Demo Seeder\n";
echo "============================================================\n\n";

try {
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");

    // ──────────────────────────────────────────────
    // 1. Employee Feedback Form (3rd form)
    // ──────────────────────────────────────────────
    echo "[1] Employee Feedback Form...\n";

    // Remove if already exists to keep idempotent
    $old = $db->query("SELECT id FROM forms WHERE slug = 'employee-feedback'")->fetch();
    if ($old) {
        $fid = $old['id'];
        $db->exec("DELETE FROM form_submissions WHERE form_id = $fid");
        $db->exec("DELETE FROM form_versions WHERE form_id = $fid");
        $db->exec("DELETE FROM forms WHERE id = $fid");
    }

    $feedbackSchema = json_encode([
        'sections' => [
            [
                'id' => 'sec_1',
                'title' => 'Ανατροφοδότηση Προσωπικού',
                'fields' => [
                    [
                        'key' => 'department',
                        'type' => 'select',
                        'label' => 'Τμήμα (Department)',
                        'required' => true,
                        'data_source' => 'repository',
                        'repository_slug' => 'departments',
                        'help_text' => 'Επιλέξτε το τμήμα σας'
                    ],
                    [
                        'key' => 'overall_satisfaction',
                        'type' => 'radio',
                        'label' => 'Συνολική Ικανοποίηση (Overall Satisfaction)',
                        'required' => true,
                        'options' => [
                            ['value' => '1', 'label' => '1 - Πολύ Χαμηλή'],
                            ['value' => '2', 'label' => '2 - Χαμηλή'],
                            ['value' => '3', 'label' => '3 - Μέτρια'],
                            ['value' => '4', 'label' => '4 - Υψηλή'],
                            ['value' => '5', 'label' => '5 - Πολύ Υψηλή'],
                        ],
                        'help_text' => 'Αξιολογήστε τη συνολική ικανοποίησή σας'
                    ],
                    [
                        'key' => 'management_support',
                        'type' => 'radio',
                        'label' => 'Υποστήριξη Διοίκησης (Management Support)',
                        'required' => true,
                        'options' => [
                            ['value' => '1', 'label' => '1 - Καθόλου'],
                            ['value' => '2', 'label' => '2 - Λίγο'],
                            ['value' => '3', 'label' => '3 - Μέτρια'],
                            ['value' => '4', 'label' => '4 - Αρκετά'],
                            ['value' => '5', 'label' => '5 - Πλήρως'],
                        ]
                    ],
                    [
                        'key' => 'workplace_conditions',
                        'type' => 'radio',
                        'label' => 'Συνθήκες Εργασίας (Workplace Conditions)',
                        'required' => true,
                        'options' => [
                            ['value' => '1', 'label' => '1 - Κακές'],
                            ['value' => '2', 'label' => '2 - Χαμηλές'],
                            ['value' => '3', 'label' => '3 - Αποδεκτές'],
                            ['value' => '4', 'label' => '4 - Καλές'],
                            ['value' => '5', 'label' => '5 - Άριστες'],
                        ]
                    ],
                    [
                        'key' => 'comments',
                        'type' => 'textarea',
                        'label' => 'Σχόλια (Comments)',
                        'required' => false,
                        'placeholder' => 'Γράψτε τα σχόλιά σας εδώ...',
                        'help_text' => 'Προαιρετικά σχόλια ή παρατηρήσεις'
                    ],
                    [
                        'key' => 'anonymous',
                        'type' => 'checkbox',
                        'label' => 'Ανώνυμη Υποβολή (Anonymous Submission)',
                        'required' => false,
                        'help_text' => 'Αν επιλεγεί, το όνομά σας δεν θα εμφανιστεί στην αναφορά'
                    ]
                ]
            ]
        ]
    ]);

    $db->prepare("INSERT INTO forms (title, slug, description, is_active, created_by, status, current_version) VALUES (?, ?, ?, 1, 1, 'published', 1)")
       ->execute(['Employee Feedback', 'employee-feedback', 'Φόρμα Ανατροφοδότησης Προσωπικού']);
    $formId = $db->lastInsertId();

    $db->prepare("INSERT INTO form_versions (form_id, version_number, schema_json, created_by) VALUES (?, 1, ?, 1)")
       ->execute([$formId, $feedbackSchema]);
    $versionId = $db->lastInsertId();

    echo "    [+] Form ID=$formId, Version ID=$versionId\n";

    // ──────────────────────────────────────────────
    // 2. Demo Submissions (all 3 forms)
    // ──────────────────────────────────────────────
    echo "\n[2] Demo Submissions...\n";

    // Get form IDs
    $itForm = $db->query("SELECT f.id, fv.id as vid FROM forms f JOIN form_versions fv ON fv.form_id=f.id WHERE f.slug='it-support-request' LIMIT 1")->fetch();
    $procForm = $db->query("SELECT f.id, fv.id as vid FROM forms f JOIN form_versions fv ON fv.form_id=f.id WHERE f.slug='procurement-request' LIMIT 1")->fetch();
    $fbForm = $db->query("SELECT f.id, fv.id as vid FROM forms f JOIN form_versions fv ON fv.form_id=f.id WHERE f.slug='employee-feedback' LIMIT 1")->fetch();

    // User IDs
    $adminId = 1;
    $managerId = 2;
    $userId = $db->query("SELECT id FROM users WHERE username='user.demo1'")->fetchColumn() ?: 3;
    $user2Id = $db->query("SELECT id FROM users WHERE username='user.demo2'")->fetchColumn() ?: 3;

    $insSubmission = $db->prepare("INSERT INTO form_submissions (uuid, form_id, form_version_id, user_id, data_json, status, submitted_at) VALUES (UUID(), ?, ?, ?, ?, ?, NOW())");
    $insHistory = $db->prepare("INSERT INTO submission_status_history (submission_id, old_status, new_status, notes, changed_by) VALUES (?, ?, ?, ?, ?)");

    $submissions = [];

    // Submission 1: IT Support - approved
    $insSubmission->execute([
        $itForm['id'], $itForm['vid'], $userId,
        json_encode(['title' => 'Laptop δεν ξεκινά', 'dept' => 'it', 'type' => 'supp', 'priority' => 'high']),
        'approved'
    ]);
    $s1 = $db->lastInsertId();
    $submissions[] = $s1;
    $db->exec("UPDATE form_submissions SET reviewed_by=$managerId, reviewed_at=NOW(), review_notes='Εγκρίθηκε. Ο τεχνικός θα επικοινωνήσει.' WHERE id=$s1");
    $insHistory->execute([$s1, '', 'submitted', 'Αρχική υποβολή', $userId]);
    $insHistory->execute([$s1, 'submitted', 'under_review', 'Λήφθηκε για αξιολόγηση', $managerId]);
    $insHistory->execute([$s1, 'under_review', 'approved', 'Εγκρίθηκε. Ο τεχνικός θα επικοινωνήσει.', $managerId]);
    echo "    [+] IT Support submission #$s1 (approved)\n";

    // Submission 2: IT Support - rejected
    $insSubmission->execute([
        $itForm['id'], $itForm['vid'], $user2Id,
        json_encode(['title' => 'Εκτυπωτής εκτός λειτουργίας', 'dept' => 'adm', 'type' => 'maint', 'priority' => 'norm']),
        'rejected'
    ]);
    $s2 = $db->lastInsertId();
    $submissions[] = $s2;
    $db->exec("UPDATE form_submissions SET reviewed_by=$managerId, reviewed_at=NOW(), review_notes='Εκτός πεδίου IT. Παρακαλώ επικοινωνήστε με τη συντήρηση.' WHERE id=$s2");
    $insHistory->execute([$s2, '', 'submitted', 'Αρχική υποβολή', $user2Id]);
    $insHistory->execute([$s2, 'submitted', 'rejected', 'Εκτός πεδίου IT. Παρακαλώ επικοινωνήστε με τη συντήρηση.', $managerId]);
    echo "    [+] IT Support submission #$s2 (rejected)\n";

    // Submission 3: IT Support - draft
    $insSubmission->execute([
        $itForm['id'], $itForm['vid'], $userId,
        json_encode(['title' => 'VPN σύνδεση αδύνατη', 'dept' => 'it', 'type' => 'supp', 'priority' => 'urg']),
        'draft'
    ]);
    $s3 = $db->lastInsertId();
    $submissions[] = $s3;
    $insHistory->execute([$s3, '', 'draft', 'Αποθηκεύτηκε ως πρόχειρο', $userId]);
    echo "    [+] IT Support submission #$s3 (draft)\n";

    // Submission 4: Procurement - under_review
    $insSubmission->execute([
        $procForm['id'], $procForm['vid'], $userId,
        json_encode(['title' => 'Αγορά 10 laptop', 'dept' => 'it', 'cost' => 15000]),
        'under_review'
    ]);
    $s4 = $db->lastInsertId();
    $submissions[] = $s4;
    $db->exec("UPDATE form_submissions SET reviewed_by=$managerId, reviewed_at=NOW() WHERE id=$s4");
    $insHistory->execute([$s4, '', 'submitted', 'Αρχική υποβολή', $userId]);
    $insHistory->execute([$s4, 'submitted', 'under_review', 'Λήφθηκε για αξιολόγηση', $managerId]);
    echo "    [+] Procurement submission #$s4 (under_review)\n";

    // Submission 5: Procurement - returned for correction
    $insSubmission->execute([
        $procForm['id'], $procForm['vid'], $user2Id,
        json_encode(['title' => 'Αγορά εκτυπωτή', 'dept' => 'adm', 'cost' => 350]),
        'submitted'
    ]);
    $s5 = $db->lastInsertId();
    $submissions[] = $s5;
    $insHistory->execute([$s5, '', 'submitted', 'Αρχική υποβολή', $user2Id]);
    echo "    [+] Procurement submission #$s5 (submitted)\n";

    // Submission 6: Employee Feedback
    $insSubmission->execute([
        $fbForm['id'], $fbForm['vid'], $userId,
        json_encode([
            'department' => 'it',
            'overall_satisfaction' => '4',
            'management_support' => '5',
            'workplace_conditions' => '4',
            'comments' => 'Γενικά ικανοποιημένος με το εργασιακό περιβάλλον. Θα ήθελα καλύτερο εξοπλισμό.',
            'anonymous' => false
        ]),
        'approved'
    ]);
    $s6 = $db->lastInsertId();
    $submissions[] = $s6;
    $db->exec("UPDATE form_submissions SET reviewed_by=$managerId, reviewed_at=NOW(), review_notes='Ευχαριστούμε για τα σχόλιά σας.' WHERE id=$s6");
    $insHistory->execute([$s6, '', 'submitted', 'Αρχική υποβολή ανατροφοδότησης', $userId]);
    $insHistory->execute([$s6, 'submitted', 'approved', 'Ευχαριστούμε για τα σχόλιά σας.', $managerId]);
    echo "    [+] Employee Feedback submission #$s6 (approved)\n";

    // Submission 7: Employee Feedback - anonymous
    $insSubmission->execute([
        $fbForm['id'], $fbForm['vid'], $user2Id,
        json_encode([
            'department' => 'hr',
            'overall_satisfaction' => '3',
            'management_support' => '3',
            'workplace_conditions' => '3',
            'comments' => 'Χρειάζεται βελτίωση στην επικοινωνία μεταξύ τμημάτων.',
            'anonymous' => true
        ]),
        'submitted'
    ]);
    $s7 = $db->lastInsertId();
    $submissions[] = $s7;
    $insHistory->execute([$s7, '', 'submitted', 'Ανώνυμη υποβολή', $user2Id]);
    echo "    [+] Employee Feedback submission #$s7 (anonymous, submitted)\n";

    echo "    [+] Total submissions created: " . count($submissions) . "\n";

    // ──────────────────────────────────────────────
    // 3. Notifications
    // ──────────────────────────────────────────────
    echo "\n[3] Notifications...\n";

    $insNotif = $db->prepare("INSERT INTO notifications (user_id, type, title, message, link_url, is_read) VALUES (?, ?, ?, ?, ?, ?)");

    $notifs = [
        [$userId, 'submission_approved', 'Αίτημά σας εγκρίθηκε', 'Το αίτημα IT Support «Laptop δεν ξεκινά» εγκρίθηκε.', "/admin/submissions/$s1", 0],
        [$user2Id, 'submission_rejected', 'Αίτημά σας απορρίφθηκε', 'Το αίτημα «Εκτυπωτής εκτός λειτουργίας» απορρίφθηκε.', "/admin/submissions/$s2", 0],
        [$managerId, 'new_submission', 'Νέο αίτημα για αξιολόγηση', 'Υποβλήθηκε νέο αίτημα Procurement: «Αγορά 10 laptop».', "/admin/submissions/$s4", 1],
        [$adminId, 'system', 'Νέος χρήστης εγγράφηκε', 'Ο χρήστης user.demo1 εγγράφηκε στο σύστημα.', '/admin/users', 1],
        [$userId, 'submission_approved', 'Ανατροφοδότηση ελήφθη', 'Η ανατροφοδότησή σας καταγράφηκε. Ευχαριστούμε.', "/admin/submissions/$s6", 0],
    ];

    foreach ($notifs as $n) {
        $insNotif->execute($n);
    }
    echo "    [+] " . count($notifs) . " notifications created\n";

    // ──────────────────────────────────────────────
    // 4. Audit Log Entries
    // ──────────────────────────────────────────────
    echo "\n[4] Audit Logs...\n";

    $insAudit = $db->prepare("INSERT INTO audit_logs (user_id, action, entity_type, entity_id, metadata_json, ip_address) VALUES (?, ?, ?, ?, ?, ?)");

    $audits = [
        [$adminId, 'user.create', 'user', $userId, json_encode(['username' => 'user.demo1']), '127.0.0.1'],
        [$adminId, 'user.create', 'user', $user2Id, json_encode(['username' => 'user.demo2']), '127.0.0.1'],
        [$adminId, 'form.publish', 'form', $itForm['id'], json_encode(['form' => 'IT Support Request']), '127.0.0.1'],
        [$adminId, 'form.publish', 'form', $procForm['id'], json_encode(['form' => 'Procurement Request']), '127.0.0.1'],
        [$adminId, 'form.publish', 'form', $fbForm['id'], json_encode(['form' => 'Employee Feedback']), '127.0.0.1'],
        [$userId, 'submission.create', 'submission', $s1, json_encode(['form' => 'IT Support Request', 'status' => 'submitted']), '127.0.0.1'],
        [$managerId, 'submission.approve', 'submission', $s1, json_encode(['notes' => 'Εγκρίθηκε']), '127.0.0.1'],
        [$user2Id, 'submission.create', 'submission', $s2, json_encode(['form' => 'IT Support Request']), '127.0.0.1'],
        [$managerId, 'submission.reject', 'submission', $s2, json_encode(['reason' => 'Εκτός πεδίου']), '127.0.0.1'],
        [$userId, 'submission.create', 'submission', $s4, json_encode(['form' => 'Procurement Request']), '127.0.0.1'],
        [$userId, 'submission.create', 'submission', $s6, json_encode(['form' => 'Employee Feedback']), '127.0.0.1'],
        [$adminId, 'login', 'user', $adminId, json_encode(['ip' => '127.0.0.1']), '127.0.0.1'],
        [$adminId, 'repository.create', 'repository', 4, json_encode(['name' => 'Departments']), '127.0.0.1'],
        [$adminId, 'settings.update', 'settings', null, json_encode(['changed' => 'app_name']), '127.0.0.1'],
    ];

    foreach ($audits as $a) {
        $insAudit->execute($a);
    }
    echo "    [+] " . count($audits) . " audit log entries\n";

    // ──────────────────────────────────────────────
    // 5. Saved Reports
    // ──────────────────────────────────────────────
    echo "\n[5] Saved Reports...\n";

    $db->exec("DELETE FROM saved_reports WHERE owner_user_id = 1");
    $insReport = $db->prepare("INSERT INTO saved_reports (name, report_type, form_id, owner_user_id, filters_json, columns_json, is_shared) VALUES (?, 'submissions', ?, 1, ?, ?, 1)");

    $insReport->execute([
        'Pending IT Requests',
        $itForm['id'],
        json_encode(['status' => 'submitted', 'form_id' => $itForm['id']]),
        json_encode(['id', 'user_id', 'status', 'submitted_at', 'data_json'])
    ]);

    $insReport->execute([
        'Approved Procurement Requests',
        $procForm['id'],
        json_encode(['status' => 'approved', 'form_id' => $procForm['id']]),
        json_encode(['id', 'user_id', 'status', 'reviewed_at', 'data_json'])
    ]);

    echo "    [+] 2 saved reports created\n";

    // ──────────────────────────────────────────────
    // 6. Add Reviewer role (missing from seed)
    // ──────────────────────────────────────────────
    echo "\n[6] Roles check...\n";

    $reviewerRole = $db->query("SELECT id FROM roles WHERE slug='reviewer'")->fetch();
    if (!$reviewerRole) {
        $db->exec("INSERT INTO roles (name, slug, description) VALUES ('Reviewer', 'reviewer', 'Αξιολογητής αιτημάτων')");
        $reviewerRoleId = $db->lastInsertId();
        // Assign review permissions
        $reviewPerm = $db->query("SELECT id FROM permissions WHERE slug='submissions.review'")->fetchColumn();
        $viewAllPerm = $db->query("SELECT id FROM permissions WHERE slug='submissions.view.all'")->fetchColumn();
        $dashPerm = $db->query("SELECT id FROM permissions WHERE slug='dashboard.view'")->fetchColumn();
        if ($reviewPerm) $db->exec("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES ($reviewerRoleId, $reviewPerm)");
        if ($viewAllPerm) $db->exec("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES ($reviewerRoleId, $viewAllPerm)");
        if ($dashPerm) $db->exec("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES ($reviewerRoleId, $dashPerm)");
        echo "    [+] Reviewer role created (ID=$reviewerRoleId)\n";

        // Assign reviewer.demo to Reviewer role
        $db->exec("UPDATE users SET role_id=$reviewerRoleId WHERE username='reviewer.demo'");
        echo "    [+] reviewer.demo assigned to Reviewer role\n";
    } else {
        echo "    [+] Reviewer role already exists\n";
    }

    $db->exec("SET FOREIGN_KEY_CHECKS = 1");

    // ──────────────────────────────────────────────
    // FINAL SUMMARY
    // ──────────────────────────────────────────────
    echo "\n============================================================\n";
    echo "  SEEDING COMPLETE\n";
    echo "============================================================\n";

    $counts = $db->query("
        SELECT
            (SELECT COUNT(*) FROM users) as users,
            (SELECT COUNT(*) FROM roles) as roles,
            (SELECT COUNT(*) FROM permissions) as permissions,
            (SELECT COUNT(*) FROM repositories) as repositories,
            (SELECT COUNT(*) FROM forms) as forms,
            (SELECT COUNT(*) FROM form_versions) as form_versions,
            (SELECT COUNT(*) FROM form_submissions) as submissions,
            (SELECT COUNT(*) FROM submission_status_history) as status_history,
            (SELECT COUNT(*) FROM notifications) as notifications,
            (SELECT COUNT(*) FROM audit_logs) as audit_logs,
            (SELECT COUNT(*) FROM saved_reports) as saved_reports
    ")->fetch();

    foreach ($counts as $k => $v) {
        echo "  $k: $v\n";
    }

    echo "\n[+] SEEDING SUCCESSFUL!\n";
    exit(0);

} catch (\Exception $e) {
    echo "\n[-] ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
