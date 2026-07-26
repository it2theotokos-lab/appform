# AppForm Apache Production Deployment

Deploying AppForm on Linux with Apache:

1. **Virtual Host Configuration**:
   ```apache
   <VirtualHost *:80>
       ServerName appform.local
       DocumentRoot /var/www/Appform/public
       <Directory /var/www/Appform/public>
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```

2. **Permissions**:
   - Grant `www-data` ownership on `storage/` and `logs/` folders.
