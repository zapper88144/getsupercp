# Sudo Setup for SuperCP Daemon

The `super-daemon` requires specific `sudo` permissions to manage Nginx and PHP-FPM configurations. Since the daemon runs as the `super` user, you must grant it passwordless access to the following commands.

## Instructions

1. Log in to your server as a user with `sudo` access (e.g., `root`).
2. Run `visudo` to edit the sudoers file:
   ```bash
   sudo visudo
   ```
3. Add the following lines to the end of the file:

```sudoers
# SuperCP Daemon Permissions
super ALL=(ALL) NOPASSWD: /usr/bin/mkdir -p /home/*/web/*
super ALL=(ALL) NOPASSWD: /usr/bin/mv /tmp/nginx_*.conf /etc/nginx/sites-available/*
super ALL=(ALL) NOPASSWD: /usr/bin/ln -sf /etc/nginx/sites-available/* /etc/nginx/sites-enabled/*
super ALL=(ALL) NOPASSWD: /usr/bin/mv /tmp/php_*.conf /etc/php/*/fpm/pool.d/*.conf
super ALL=(ALL) NOPASSWD: /usr/bin/rm -f /etc/nginx/sites-enabled/*
super ALL=(ALL) NOPASSWD: /usr/bin/rm -f /etc/nginx/sites-available/*
super ALL=(ALL) NOPASSWD: /usr/bin/rm -f /etc/php/*/fpm/pool.d/*.conf
super ALL=(ALL) NOPASSWD: /usr/bin/systemctl reload nginx
super ALL=(ALL) NOPASSWD: /usr/bin/systemctl reload php8.4-fpm
super ALL=(ALL) NOPASSWD: /usr/bin/systemctl restart nginx
super ALL=(ALL) NOPASSWD: /usr/bin/systemctl restart php8.4-fpm
super ALL=(ALL) NOPASSWD: /usr/bin/certbot *
```

4. Save and exit (in `nano`, press `Ctrl+O`, `Enter`, then `Ctrl+X`).

## Why is this needed?

The daemon needs to:
- Create web directories for new domains.
- Write Nginx virtual host configurations.
- Write PHP-FPM pool configurations.
- Reload services to apply changes.
- Request SSL certificates via Certbot.

Without these permissions, domain creation and SSL management will fail with "Ensure daemon has sudo access" errors.
