~# Code Quest local setup

## Requirements

- XAMPP for Linux with MySQL enabled
- PHP 8.2+
- Composer
- Node.js and npm

## 1. Create the MySQL database

Start XAMPP MySQL, then create the database in phpMyAdmin or a terminal:

```bash
sudo /opt/lampp/lampp sta
rtmysql
/opt/lampp/bin/mysql -u root -e "CREATE DATABASE IF NOT EXISTS code_quest CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

If your XAMPP root account has a password, add `-p` to the MySQL command and set `DB_PASSWORD` in `backend/.env`.

## 2. Configure Laravel

The backend is in `backend/`. Its `.env` is configured for:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=code_quest
DB_USERNAME=root
DB_PASSWORD=
```

Install dependencies and generate the app key if needed:

```bash
cd backend
composer install
php artisan key:generate
php artisan migrate --seed
```

The seeder creates the existing OOP, DSA, and DBMS course/chapter outline records and an admin account:

```text
admin@codequest.local / password
```

Replace that password before using the application beyond local development.

## 3. Start Laravel

```bash
cd backend
php artisan serve --host=127.0.0.1 --port=8000
```

The API is available at `http://127.0.0.1:8000/api`.

## 4. Start React

In a second terminal:

```bash
npm install
npm run dev
```

The frontend `.env` uses:

```env
VITE_API_URL=http://127.0.0.1:8000/api
```

Open the Vite URL shown in the terminal, commonly `http://localhost:5173/` or `http://localhost:5174/`.

## Verification

```bash
npm run typecheck
npm run lint
npm run build
cd backend
php artisan test
```

The current migration can also be checked with SQLite when MySQL is unavailable:

```bash
DB_CONNECTION=sqlite DB_DATABASE=database/database.sqlite php artisan migrate:fresh --seed
```

That fallback is for validation only. The required runtime database is MySQL through XAMPP.
