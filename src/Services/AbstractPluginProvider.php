<?php
namespace App\Services;

abstract class AbstractPluginProvider implements PluginProviderInterface {
    public function register() {}
    public function boot() {}
    public function routes() {}
    public function permissions() { return []; }
    public function menus() { return []; }
    public function helpContexts() { return []; }
    public function healthChecks() { return []; }
}
