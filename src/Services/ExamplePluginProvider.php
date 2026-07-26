<?php
namespace Plugins\ExamplePlugin;

use App\Services\AbstractPluginProvider;

class ExamplePluginProvider extends AbstractPluginProvider {
    public function register() {
        // Register plugin services or parameters configurations
    }

    public function boot() {
        // Boot routes or views namespaces registration
    }

    public function routes() {
        return [
            '/plugins/example-plugin/reports' => 'Plugins\\ExamplePlugin\\Controllers\\ReportController@index'
        ];
    }

    public function permissions() {
        return [
            'example.view' => 'Προβολή Αναφορών Example Plugin',
            'example.manage' => 'Διαχείριση Example Plugin'
        ];
    }
}
