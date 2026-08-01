<?php
/**
 * AppForm — Master Permissions Registry
 *
 * This file defines ALL system permissions.
 * Used by the installer (db/install.php) and the update migration 037_permissions_sync.sql
 * to ensure every permission exists in both fresh installations and updated instances.
 *
 * Format: 'slug' => 'Label (EL)'
 */
return [
    // ── Dashboard ─────────────────────────────────────────────────────────────
    'dashboard.view'                  => 'Προβολή Dashboard',

    // ── Users ──────────────────────────────────────────────────────────────────
    'users.view'                      => 'Προβολή Χρηστών',
    'users.create'                    => 'Δημιουργία Χρηστών',
    'users.edit'                      => 'Επεξεργασία Χρηστών',
    'users.delete'                    => 'Διαγραφή Χρηστών',

    // ── Roles & Permissions ────────────────────────────────────────────────────
    'roles.manage'                    => 'Διαχείριση Ρόλων',
    'permissions.manage'              => 'Διαχείριση Δικαιωμάτων',

    // ── Forms ──────────────────────────────────────────────────────────────────
    'forms.view'                      => 'Προβολή Φορμών',
    'forms.create'                    => 'Δημιουργία Φορμών',
    'forms.edit'                      => 'Επεξεργασία Φορμών',
    'forms.delete'                    => 'Διαγραφή Φορμών',
    'forms.publish'                   => 'Δημοσίευση Φορμών',
    'forms.submit'                    => 'Υποβολή Φορμών',
    'forms.manage'                    => 'Διαχείριση Στοιχείων Φορμών',
    'forms.assign'                    => 'Ανάθεση Φόρμας σε Ρόλους',

    // ── Submissions ────────────────────────────────────────────────────────────
    'submissions.view.own'            => 'Προβολή Δικών μου Υποβολών',
    'submissions.view.all'            => 'Προβολή Όλων των Υποβολών',
    'submissions.view.subordinates'   => 'Προβολή Υποβολών Υφισταμένων',
    'submissions.review'              => 'Αξιολόγηση Υποβολών',
    'submissions.create'              => 'Δημιουργία Υποβολής',
    'submissions.edit.own'            => 'Επεξεργασία Δικών μου Υποβολών',
    'submissions.edit.any'            => 'Επεξεργασία Οποιασδήποτε Υποβολής',
    'submissions.delete'              => 'Διαγραφή Υποβολών',
    'submissions.delete.own'          => 'Διαγραφή Δικών μου Υποβολών',
    'submissions.delete.any'          => 'Διαγραφή Οποιασδήποτε Υποβολής',
    'submissions.download.own'        => 'Λήψη Αρχείων Υποβολής',
    'submissions.approve'             => 'Έγκριση Υποβολής',
    'submissions.reject'              => 'Απόρριψη Υποβολής',
    'submissions.return_to_draft'     => 'Επιστροφή Υποβολής σε Πρόχειρο',
    'submissions.return_for_correction' => 'Επιστροφή Υποβολής για Διορθώσεις',
    'submissions.export'              => 'Εξαγωγή Υποβολών',
    'submissions.view_history'        => 'Προβολή Ιστορικού Υποβολής',

    // ── Repositories ───────────────────────────────────────────────────────────
    'repositories.manage'             => 'Διαχείριση Repositories',

    // ── Navigation Menus ───────────────────────────────────────────────────────
    'menus.manage'                    => 'Διαχείριση Μενού',

    // ── Analytics & Exports ────────────────────────────────────────────────────
    'analytics.view'                  => 'Προβολή Analytics',
    'exports.create'                  => 'Δημιουργία Εξαγωγών',

    // ── Document Templates ─────────────────────────────────────────────────────
    'document_templates.view'         => 'Προβολή Προτύπων Εγγράφων',
    'document_templates.create'       => 'Δημιουργία Προτύπων Εγγράφων',
    'document_templates.edit'         => 'Επεξεργασία Προτύπων Εγγράφων',
    'document_templates.publish'      => 'Δημοσίευση Προτύπων Εγγράφων',
    'document_templates.archive'      => 'Αρχειοθέτηση Προτύπων Εγγράφων',
    'document_templates.delete'       => 'Διαγραφή Προτύπων Εγγράφων',

    // ── Workflows ──────────────────────────────────────────────────────────────
    'workflows.view'                  => 'Προβολή Workflows',
    'workflows.create'                => 'Δημιουργία Workflows',
    'workflows.edit'                  => 'Επεξεργασία Workflows',
    'workflows.publish'               => 'Δημοσίευση Workflows',
    'workflows.archive'               => 'Αρχειοθέτηση Workflows',
    'workflow_tasks.view_all'         => 'Προβολή Όλων των Εργασιών Workflow',
    'workflow_tasks.reassign'         => 'Επανανάθεση Εργασιών Workflow',
    'workflow_instances.cancel'       => 'Ακύρωση Workflows',

    // ── Settings ───────────────────────────────────────────────────────────────
    'settings.view'                   => 'Προβολή Ρυθμίσεων Συστήματος',
    'settings.manage'                 => 'Διαχείριση Ρυθμίσεων Συστήματος',

    // ── Backups ────────────────────────────────────────────────────────────────
    'backups.view'                    => 'Προβολή Backup',
    'backups.create'                  => 'Δημιουργία Backup',
    'backups.delete'                  => 'Διαγραφή Backup',

    // ── Demo Data ──────────────────────────────────────────────────────────────
    'demo_data.manage'                => 'Διαχείριση Demo Data',

    // ── SMTP / Email ───────────────────────────────────────────────────────────
    'smtp.manage'                     => 'Διαχείριση Ρυθμίσεων SMTP & Email',

    // ── Restore Center ─────────────────────────────────────────────────────────
    'restore.view'                    => 'Προβολή Ιστορικού Επαναφοράς',
    'restore.execute'                 => 'Εκτέλεση Επαναφοράς Συστήματος',
    'restore.history'                 => 'Προβολή Ιστορικού Επαναφορών',

    // ── Job Queue ──────────────────────────────────────────────────────────────
    'queue.view'                      => 'Προβολή Ουράς Εργασιών',
    'queue.retry'                     => 'Επανεκτέλεση Εργασιών Ουράς',
    'queue.cancel'                    => 'Ακύρωση Εργασιών Ουράς',
    'queue.cleanup'                   => 'Καθαρισμός Ουράς Εργασιών',
    'queue.workers'                   => 'Διαχείριση Background Workers',

    // ── Plugins ────────────────────────────────────────────────────────────────
    'plugins.view'                    => 'Προβολή Πρόσθετων',
    'plugins.install'                 => 'Εγκατάσταση Πρόσθετων',
    'plugins.enable'                  => 'Ενεργοποίηση Πρόσθετων',
    'plugins.disable'                 => 'Απενεργοποίηση Πρόσθετων',
    'plugins.update'                  => 'Ενημέρωση Πρόσθετων',
    'plugins.rollback'                => 'Επαναφορά Πρόσθετων',
    'plugins.uninstall'               => 'Απεγκατάσταση Πρόσθετων',

    // ── Updates / Upgrades ─────────────────────────────────────────────────────
    'updates.view'                    => 'Προβολή Αναβαθμίσεων',
    'updates.manage'                  => 'Διαχείριση Αναβαθμίσεων',
    'updates.rollback'                => 'Επαναφορά Αναβαθμίσεων',

    // ── Audit Logs ─────────────────────────────────────────────────────────────
    'audit.view'                      => 'Προβολή Καταγραφών Ελέγχου (Audit Logs)',

    // ── Data Exchange ──────────────────────────────────────────────────────────
    'data_exchange.view'              => 'Προβολή Εισαγωγών/Εξαγωγών',
    'data_exchange.export'            => 'Εξαγωγή Δεδομένων',
    'data_exchange.import'            => 'Εισαγωγή Δεδομένων',
    'data_exchange.reports'           => 'Αναφορές PDF',
    'data_exchange.manage'            => 'Διαχείριση Data Exchange',
];
