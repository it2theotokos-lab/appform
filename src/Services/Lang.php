<?php
namespace App\Services;

use App\Core\Session;

/**
 * Lang — lightweight EL/EN translation helper for AppForm.
 *
 * Usage:  echo __('Save');  // returns 'Αποθήκευση' (EL) or 'Save' (EN)
 *
 * Rules:
 *  - Default language: 'el'
 *  - Language is stored in $_SESSION['app_lang']
 *  - Only static UI strings are translated; user-entered data is NEVER passed here.
 */
class Lang {

    private static ?string $locale = null;

    /** @var array<string,array<string,string>> */
    private static array $dict = [
        // ── Common Actions ────────────────────────────────────────────
        'Save'             => ['en' => 'Save',             'el' => 'Αποθήκευση'],
        'Cancel'           => ['en' => 'Cancel',           'el' => 'Ακύρωση'],
        'Confirm'          => ['en' => 'Confirm',          'el' => 'Επιβεβαίωση'],
        'Delete'           => ['en' => 'Delete',           'el' => 'Διαγραφή'],
        'Edit'             => ['en' => 'Edit',             'el' => 'Επεξεργασία'],
        'Close'            => ['en' => 'Close',            'el' => 'Κλείσιμο'],
        'Back'             => ['en' => 'Back',             'el' => 'Πίσω'],
        'Create'           => ['en' => 'Create',           'el' => 'Δημιουργία'],
        'Submit'           => ['en' => 'Submit',           'el' => 'Υποβολή'],
        'Upload'           => ['en' => 'Upload',           'el' => 'Μεταφόρτωση'],
        'Download'         => ['en' => 'Download',         'el' => 'Λήψη'],
        'Search'           => ['en' => 'Search',           'el' => 'Αναζήτηση'],
        'Filter'           => ['en' => 'Filter',           'el' => 'Φίλτρο'],
        'Actions'          => ['en' => 'Actions',          'el' => 'Ενέργειες'],
        'Preview'          => ['en' => 'Preview',          'el' => 'Προεπισκόπηση'],
        'Restore'          => ['en' => 'Restore',          'el' => 'Επαναφορά'],
        'Verify'           => ['en' => 'Verify',           'el' => 'Επαλήθευση'],
        'Yes'              => ['en' => 'Yes',              'el' => 'Ναι'],
        'No'               => ['en' => 'No',               'el' => 'Όχι'],
        'Add'              => ['en' => 'Add',              'el' => 'Προσθήκη'],
        'Remove'           => ['en' => 'Remove',           'el' => 'Αφαίρεση'],
        'Loading…'         => ['en' => 'Loading…',         'el' => 'Φόρτωση…'],
        'Processing…'      => ['en' => 'Processing…',      'el' => 'Επεξεργασία…'],

        // ── Navigation — Sidebar section titles ───────────────────────
        'General'          => ['en' => 'General',          'el' => 'Γενικά'],
        'Administration'   => ['en' => 'Administration',   'el' => 'Διαχείριση'],
        'Forms'            => ['en' => 'Forms',            'el' => 'Φόρμες'],
        'Workflow'         => ['en' => 'Workflow',         'el' => 'Workflow'],
        'Reports'          => ['en' => 'Reports',          'el' => 'Αναφορές'],
        'System'           => ['en' => 'System',           'el' => 'Σύστημα'],
        'Documents'        => ['en' => 'Documents',        'el' => 'Έγγραφα'],
        'Documents (PDF)'  => ['en' => 'Documents (PDF)',  'el' => 'Έγγραφα (PDF)'],
        'Navigation Links' => ['en' => 'Navigation Links', 'el' => 'Σύνδεσμοι Πλοήγησης'],

        // ── Navigation — Sidebar menu item labels ─────────────────────
        'Dashboard'                   => ['en' => 'Dashboard',                   'el' => 'Dashboard'],
        'Users'                       => ['en' => 'Users',                       'el' => 'Χρήστες'],
        'Roles'                       => ['en' => 'Roles',                       'el' => 'Ρόλοι'],
        'Repositories'                => ['en' => 'Repositories',                'el' => 'Repositories'],
        'Document Templates'          => ['en' => 'Document Templates',          'el' => 'Πρότυπα Εγγράφων'],
        'Workflow Design'             => ['en' => 'Workflow Design',             'el' => 'Σχεδιασμός Workflow'],
        'Navigation'                  => ['en' => 'Navigation',                  'el' => 'Navigation'],
        'Submissions'                 => ['en' => 'Submissions',                 'el' => 'Υποβολές'],
        'Notifications'               => ['en' => 'Notifications',               'el' => 'Notifications'],
        'Analytics'                   => ['en' => 'Analytics',                   'el' => 'Analytics'],
        'Imports/Exports'             => ['en' => 'Imports/Exports',             'el' => 'Εισαγωγές/Εξαγωγές'],
        'Audit Logs'                  => ['en' => 'Audit Logs',                  'el' => 'Audit Logs'],
        'Settings'                    => ['en' => 'Settings',                    'el' => 'Ρυθμίσεις'],
        'System Health'               => ['en' => 'System Health',               'el' => 'System Health'],
        'New Document'                => ['en' => 'New Document',                'el' => 'Νέο Έγγραφο'],
        'My Drafts'                   => ['en' => 'My Drafts',                   'el' => 'Τα Πρόχειρά μου'],
        'My Submissions'              => ['en' => 'My Submissions',              'el' => 'Οι Υποβολές μου'],
        'Approval Tasks'              => ['en' => 'Approval Tasks',              'el' => 'Εργασίες Έγκρισης'],
        'My Form Submissions'         => ['en' => 'My Form Submissions',         'el' => 'Οι Υποβολές Φορμών μου'],
        'My Draft Documents'          => ['en' => 'My Draft Documents',          'el' => 'Τα Πρόχειρα Εγγράφων μου'],
        'My Submitted Documents'      => ['en' => 'My Submitted Documents',      'el' => 'Τα Υποβληθέντα Έγγραφά μου'],

        // ── Header dropdown ───────────────────────────────────────────
        'My Profile'          => ['en' => 'My Profile',          'el' => 'Το Προφίλ μου'],
        'Logout'              => ['en' => 'Logout',              'el' => 'Αποσύνδεση'],
        'User Menu'           => ['en' => 'User Menu',           'el' => 'Μενού χρήστη'],
        'Usage Guide'         => ['en' => 'Usage Guide',         'el' => 'Οδηγίες Χρήσης'],

        // ── Header labels ─────────────────────────────────────────────
        'Light Theme'   => ['en' => 'Light Theme',   'el' => 'Φωτεινό θέμα'],
        'Dark Theme'    => ['en' => 'Dark Theme',    'el' => 'Σκοτεινό θέμα'],
        'System Theme'  => ['en' => 'System Theme',  'el' => 'Θέμα συστήματος'],
        'unread notifications'  => ['en' => 'unread notifications',  'el' => 'αδιάβαστες ειδοποιήσεις'],

        // ── Confirm Modal ─────────────────────────────────────────────
        'Action Confirmation'        => ['en' => 'Action Confirmation',        'el' => 'Επιβεβαίωση ενέργειας'],
        'Are you sure you want to continue?' => ['en' => 'Are you sure you want to continue?', 'el' => 'Είστε σίγουροι ότι θέλετε να συνεχίσετε;'],

        // ── Signature Modal ───────────────────────────────────────────
        'Digital Document Signature'  => ['en' => 'Digital Document Signature',  'el' => 'Ψηφιακή Υπογραφή Εγγράφου'],
        'Draw your signature on the surface below using a mouse, touch or stylus.' => [
            'en' => 'Draw your signature on the surface below using a mouse, touch or stylus.',
            'el' => 'Σχεδιάστε την υπογραφή σας στην παρακάτω επιφάνεια χρησιμοποιώντας το ποντίκι, την αφή ή τη γραφίδα σας.'
        ],
        'Accept'     => ['en' => 'Accept',     'el' => 'Αποδοχή'],

        // ── Settings Tabs ─────────────────────────────────────────────
        'General Settings'        => ['en' => 'General Settings',        'el' => 'Γενικές Ρυθμίσεις'],
        'Backup'                  => ['en' => 'Backup',                  'el' => 'Backup'],
        'SMTP & Email'            => ['en' => 'SMTP & Email',            'el' => 'SMTP & Email'],
        'Global Notifications'    => ['en' => 'Global Notifications',    'el' => 'Global Notifications'],
        'Restore'                 => ['en' => 'Restore',                 'el' => 'Επαναφορά'],
        'Cloud Backup'            => ['en' => 'Cloud Backup',            'el' => 'Cloud Backup'],
        'Job Queue'               => ['en' => 'Job Queue',               'el' => 'Ουρά Εργασιών'],
        'Upgrade'                 => ['en' => 'Upgrade',                 'el' => 'Αναβάθμιση'],
        'Action Logs'             => ['en' => 'Action Logs',             'el' => 'Καταγραφές Ενεργειών'],

        // ── Settings — General panel ──────────────────────────────────
        'System Management Center'    => ['en' => 'System Management Center',    'el' => 'Κέντρο Διαχείρισης Συστήματος'],
        'Configure general parameters, backups, SMTP settings and demo data.' => [
            'en' => 'Configure general parameters, backups, SMTP settings and demo data.',
            'el' => 'Διαμόρφωση γενικών παραμέτρων, λήψη αντιγράφων ασφαλείας, ρυθμίσεις SMTP και demo data.'
        ],
        'Configure basic portal operation parameters.' => [
            'en' => 'Configure basic portal operation parameters.',
            'el' => 'Διαμόρφωση βασικών παραμέτρων λειτουργίας της πύλης.'
        ],
        'Enter the parameter value for key' => ['en' => 'Enter the parameter value for key', 'el' => 'Καταχωρήστε την τιμή παραμέτρου για το κλειδί'],

        // ── Settings — Backup panel ───────────────────────────────────
        'Backup Management'           => ['en' => 'Backup Management',           'el' => 'Διαχείριση Αντιγράφων Ασφαλείας'],
        'Create Database Backup'      => ['en' => 'Create Database Backup',      'el' => 'Δημιουργία Backup Βάσης'],
        'Create Files Backup'         => ['en' => 'Create Files Backup',         'el' => 'Δημιουργία Backup Αρχείων'],
        'Create Full Backup'          => ['en' => 'Create Full Backup',          'el' => 'Δημιουργία Πλήρους Backup'],
        'No local backups found.'     => ['en' => 'No local backups found.',     'el' => 'Δεν βρέθηκαν τοπικά αντίγραφα ασφαλείας.'],
        'Name'                        => ['en' => 'Name',                        'el' => 'Όνομα'],
        'Type'                        => ['en' => 'Type',                        'el' => 'Τύπος'],
        'Size'                        => ['en' => 'Size',                        'el' => 'Μέγεθος'],
        'Created'                     => ['en' => 'Created',                     'el' => 'Δημιουργήθηκε'],
        'Status'                      => ['en' => 'Status',                      'el' => 'Κατάσταση'],
        'Verified'                    => ['en' => 'Verified',                    'el' => 'Επαληθεύτηκε'],
        'Completed'                   => ['en' => 'Completed',                   'el' => 'Ολοκληρώθηκε'],
        'Failed'                      => ['en' => 'Failed',                      'el' => 'Απέτυχε'],
        'Pending'                     => ['en' => 'Pending',                     'el' => 'Σε Αναμονή'],
        'Are you sure you want to permanently delete this backup?' => [
            'en' => 'Are you sure you want to permanently delete this backup?',
            'el' => 'Θέλετε να διαγράψετε οριστικά αυτό το backup;'
        ],

        // ── Settings — Logo ───────────────────────────────────────────
        'Application Logo'            => ['en' => 'Application Logo',            'el' => 'Λογότυπο Εφαρμογής'],
        'Upload a custom logo (JPG, PNG, WEBP · max 2 MB). The current default logo is used as fallback.' => [
            'en' => 'Upload a custom logo (JPG, PNG, WEBP · max 2 MB). The current default logo is used as fallback.',
            'el' => 'Μεταφόρτωση προσαρμοσμένου logo (JPG, PNG, WEBP · μέγ. 2 MB). Το προεπιλεγμένο λογότυπο χρησιμοποιείται ως fallback.'
        ],
        'Current Logo'                => ['en' => 'Current Logo',                'el' => 'Τρέχον Logo'],
        'Upload New Logo'             => ['en' => 'Upload New Logo',             'el' => 'Μεταφόρτωση Νέου Logo'],
        'Delete Logo'                 => ['en' => 'Delete Logo',                 'el' => 'Διαγραφή Logo'],
        'Reset to Default Logo'       => ['en' => 'Reset to Default Logo',       'el' => 'Επαναφορά Προεπιλεγμένου Logo'],
        'Are you sure you want to delete the custom logo and revert to the default?' => [
            'en' => 'Are you sure you want to delete the custom logo and revert to the default?',
            'el' => 'Θέλετε να διαγράψετε το custom logo και να επαναφέρετε το προεπιλεγμένο;'
        ],
        'Logo uploaded successfully.' => ['en' => 'Logo uploaded successfully.', 'el' => 'Το logo μεταφορτώθηκε επιτυχώς.'],
        'Logo deleted successfully.'  => ['en' => 'Logo deleted successfully.',  'el' => 'Το logo διαγράφηκε επιτυχώς.'],
        'Invalid file type. Only JPG, PNG and WEBP are allowed.' => [
            'en' => 'Invalid file type. Only JPG, PNG and WEBP are allowed.',
            'el' => 'Μη έγκυρος τύπος αρχείου. Επιτρέπονται μόνο JPG, PNG και WEBP.'
        ],
        'File too large. Maximum allowed size is 2 MB.' => [
            'en' => 'File too large. Maximum allowed size is 2 MB.',
            'el' => 'Το αρχείο είναι πολύ μεγάλο. Μέγιστο επιτρεπόμενο μέγεθος είναι 2 MB.'
        ],
        'Invalid image content (server-side validation failed).' => [
            'en' => 'Invalid image content (server-side validation failed).',
            'el' => 'Μη έγκυρο περιεχόμενο εικόνας (αποτυχία server-side επαλήθευσης).'
        ],
        'Logo upload failed. Please try again.' => [
            'en' => 'Logo upload failed. Please try again.',
            'el' => 'Αποτυχία μεταφόρτωσης logo. Δοκιμάστε ξανά.'
        ],
        'No custom logo is set.'      => ['en' => 'No custom logo is set.',      'el' => 'Δεν έχει οριστεί custom logo.'],

        // ── Auth / Login ──────────────────────────────────────────────
        'Session timed out. Please log in again.' => [
            'en' => 'Session timed out. Please log in again.',
            'el' => 'Η συνεδρία έληξε. Παρακαλώ συνδεθείτε ξανά.'
        ],
        'Invalid credentials.'        => ['en' => 'Invalid credentials.',        'el' => 'Λανθασμένα στοιχεία σύνδεσης.'],
        'Account is disabled.'        => ['en' => 'Account is disabled.',        'el' => 'Ο λογαριασμός είναι απενεργοποιημένος.'],

        // ── Validation ────────────────────────────────────────────────
        'This field is required.'     => ['en' => 'This field is required.',     'el' => 'Το πεδίο είναι υποχρεωτικό.'],
        'Invalid email address.'      => ['en' => 'Invalid email address.',      'el' => 'Μη έγκυρη διεύθυνση email.'],
        'Passwords do not match.'     => ['en' => 'Passwords do not match.',     'el' => 'Οι κωδικοί δεν ταιριάζουν.'],
        'Settings saved successfully.' => ['en' => 'Settings saved successfully.', 'el' => 'Οι ρυθμίσεις αποθηκεύτηκαν επιτυχώς.'],

        // ── Errors ────────────────────────────────────────────────────
        'An error occurred. Please try again.' => [
            'en' => 'An error occurred. Please try again.',
            'el' => 'Παρουσιάστηκε σφάλμα. Δοκιμάστε ξανά.'
        ],
        'Access denied.'              => ['en' => 'Access denied.',              'el' => 'Δεν έχετε πρόσβαση.'],
        'Page not found.'             => ['en' => 'Page not found.',             'el' => 'Η σελίδα δεν βρέθηκε.'],

        // ── Footer ────────────────────────────────────────────────────
        'Open menu'     => ['en' => 'Open menu',     'el' => 'Άνοιγμα μενού'],
        'Disconnect from the system' => ['en' => 'Disconnect from the system', 'el' => 'Αποσύνδεση από το σύστημα'],
        'Main menu'     => ['en' => 'Main menu',     'el' => 'Κύριο μενού'],
        'AppForm - Home' => ['en' => 'AppForm - Home', 'el' => 'AppForm - Αρχική'],
    ];

    /** Return the currently active locale (el|en). */
    public static function locale(): string {
        if (self::$locale !== null) {
            return self::$locale;
        }
        // Read from session; default 'el'
        self::$locale = Session::get('app_lang', 'el');
        if (!in_array(self::$locale, ['el', 'en'])) {
            self::$locale = 'el';
        }
        return self::$locale;
    }

    /** Set and persist the locale for this session. */
    public static function setLocale(string $locale): void {
        $locale = in_array($locale, ['el', 'en']) ? $locale : 'el';
        self::$locale = $locale;
        Session::set('app_lang', $locale);
    }

    /**
     * Translate a string key.
     *
     * @param  string $key     The Greek (EL) canonical key string.
     * @param  array  $replace Optional sprintf-style replacements (unused currently).
     * @return string          Translated string, or $key if no translation found.
     */
    public static function get(string $key, array $replace = []): string {
        $locale = self::locale();
        if ($locale === 'el') {
            // EL is canonical — return the key itself (or dict el value)
            return $key;
        }
        // EN: look up translation
        if (isset(self::$dict[$key][$locale])) {
            return self::$dict[$key][$locale];
        }
        // Fallback: return key unchanged
        return $key;
    }
}
