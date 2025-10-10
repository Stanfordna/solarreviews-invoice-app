# SolarReviews Invoice App

This is a small Laravel application that provides a simple invoice management UI (Vue + Vite) and an API for creating, editing, viewing, and deleting invoices.

Clone, install, and run

```bash
# Clone the repo (SSH)
git clone git@github.com:Stanfordna/solarreviews-invoice-app.git
# or HTTPS
git clone https://github.com/Stanfordna/solarreviews-invoice-app.git

cd solarreviews-invoice-app

# Install PHP dependencies
composer install

# Install JavaScript dependencies
npm install

# Copy env and generate app key
cp .env.example .env
php artisan key:generate

# If you want to use the included SQLite DB, ensure the file exists:
mkdir -p database
touch database/database.sqlite

# Run migrations (and seeders if present)
php artisan migrate:fresh --seed

# Start the Vite dev server (frontend) and the Laravel dev server (backend)
composer run dev

# Open http://127.0.0.1:8000 in your browser
```

- For a production build run `npm run build` and configure a web server to serve the `public/` directory.

