# AppForm IIS Production Deployment

Deploying AppForm on Windows Server IIS:

1. **Prerequisites**:
   - IIS 8 or newer with CGI module.
   - PHP 8.2+ configured via FastCGI.
   - URL Rewrite Module 2.0.

2. **IIS Site Configuration**:
   - Create a new Site pointing to the `public/` folder of the project.
   - Ensure the application pool is configured with `No Managed Code`.

3. **Folder Permissions**:
   - Grant `IIS_IUSRS` Write access to `storage/` and `logs/` directories.
