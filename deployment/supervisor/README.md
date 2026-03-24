# Queue worker (Supervisor) – deploy on live server

1. **Find your Laravel app path on the server**  
   Example: `/var/www/edocs` or `/var/www/html/edocs` (where `artisan` lives).

2. **Edit the config**  
   Open `edocs-queue.conf` and replace **every** `/var/www/edocs` with your actual path.  
   Also set `user=` to the user that runs the app (often `www-data`).

3. **Copy to Supervisor**
   ```bash
   sudo cp edocs-queue.conf /etc/supervisor/conf.d/
   ```

4. **Reload and start**
   ```bash
   sudo supervisorctl reread
   sudo supervisorctl update
   sudo supervisorctl start edocs-queue:*
   ```

5. **Check status**
   ```bash
   sudo supervisorctl status edocs-queue:*
   ```

To view queue log: `tail -f /path/to/your/app/storage/logs/queue.log`
