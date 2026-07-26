<?php
namespace Plugins\ExampleReports;

use App\Services\AbstractPluginProvider;

class ExampleReportsProvider extends AbstractPluginProvider {
    public function register() {}
    public function boot() {}

    public function routes() {
        return [
            '/plugins/example-reports/list' => 'Plugins\\ExampleReports\\Controllers\\ReportsController@listReports'
        ];
    }

    public function permissions() {
        return [
            'example_reports.view' => 'Προβολή Λίστας Αναφορών',
            'example_reports.create' => 'Δημιουργία Νέας Αναφοράς',
            'example_reports.generate' => 'Εκτέλεση Παραγωγής Αναφοράς'
        ];
    }
}
