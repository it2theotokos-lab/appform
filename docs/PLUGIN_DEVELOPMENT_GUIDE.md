# AppForm Plugin SDK Developer Guide

## 1. Directory Structure
```
plugins/
  ExamplePlugin/
    plugin.json
    src/
      ExamplePluginProvider.php
```

## 2. manifest.json Example
```json
{
  "id": "vendor.example-plugin",
  "name": "Example Plugin",
  "version": "1.0.0",
  "provider": "Plugins\\ExamplePlugin\\ExamplePluginProvider"
}
```

## 3. Provider Definition
```php
namespace Plugins\ExamplePlugin;

use App\Services\AbstractPluginProvider;

class ExamplePluginProvider extends AbstractPluginProvider {
    public function boot() {
        // Run plugin bootstrapping
    }
}
```
