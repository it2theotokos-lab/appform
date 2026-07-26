<?php
namespace App\Services;

interface PluginProviderInterface {
    public function register();
    public function boot();
    public function routes();
    public function permissions();
    public function menus();
    public function helpContexts();
    public function healthChecks();
}
