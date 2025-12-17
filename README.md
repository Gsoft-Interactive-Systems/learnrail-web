# Learnrail Web App

**Flutter Web Build for Learnrail E-Learning Platform**

This repository contains the production build of the Learnrail Flutter web application.

---

## Deployment

### Quick Deploy

1. Clone this repository to your web server:
   ```bash
   cd /var/www/app.learnrail.org
   git clone https://github.com/Gsoft-Interactive-Systems/learnrail-web.git .
   ```

2. Configure your web server (see below)

3. Access at `https://app.learnrail.org`

---

## Server Configuration

### Apache

Create `/etc/apache2/sites-available/app.learnrail.org.conf`:

```apache
<VirtualHost *:80>
    ServerName app.learnrail.org
    DocumentRoot /var/www/app.learnrail.org

    <Directory /var/www/app.learnrail.org>
        AllowOverride All
        Require all granted

        # Handle Flutter routing
        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^ index.html [L]
    </Directory>
</VirtualHost>
```

Enable and restart:
```bash
sudo a2ensite app.learnrail.org.conf
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### Nginx

Create `/etc/nginx/sites-available/app.learnrail.org`:

```nginx
server {
    listen 80;
    server_name app.learnrail.org;
    root /var/www/app.learnrail.org;
    index index.html;

    # Handle Flutter routing
    location / {
        try_files $uri $uri/ /index.html;
    }

    # Cache static assets
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|wasm)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

Enable and restart:
```bash
sudo ln -s /etc/nginx/sites-available/app.learnrail.org /etc/nginx/sites-enabled/
sudo systemctl restart nginx
```

---

## Updating

To update the web app after a new build:

```bash
cd /var/www/app.learnrail.org
git pull origin main
```

---

## API Configuration

This build is configured to use:
- **Production API:** `https://api.learnrail.org/api`

---

## Related Repositories

| Repository | Description |
|------------|-------------|
| [learnrail](https://github.com/BluLTD/learnrail) | Flutter source code (Android, iOS, Web) |
| [learnrail-api](https://github.com/gsoftinteractive/learnrail-api) | PHP REST API backend |
| [learnrail-landing](https://github.com/BluLTD/learnrail-landing) | Landing page (learnrail.org) |

---

## Tech Stack

- Flutter 3.38.5
- Dart 3.10.4
- CanvasKit renderer

---

## License

Proprietary software developed for Blue Horizon Ltd. All rights reserved.

---

**Developed by Gsoft Interactive Systems Ltd**
