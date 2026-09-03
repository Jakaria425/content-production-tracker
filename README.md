# Content Production Tracker

## Technology Stack

- Laravel
- Vue.js
- TypeScript
- MySQL

## Requirements

Make sure the following are installed before setting up the project:

- PHP 8.2+
- Composer
- Node.js 20+
- npm
- MySQL

## Installation Process

### 1. Download or Clone the Repository

Download the project or clone the repository:

```bash
git clone <repository-url>
cd content-production-tracker
```

### 2. Install PHP Dependencies

Run:

```bash
composer install
```

### 3. Install Frontend Dependencies

Run:

```bash
npm install
```

### 4. Configure Environment

Copy `.env.example` to `.env`.

On Windows:

```cmd
copy .env.example .env
```

On macOS/Linux:

```bash
cp .env.example .env
```

### 5. Configure the Database

Open the `.env` file and configure your MySQL database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=content_production_tracker
DB_USERNAME=your_database_username
DB_PASSWORD=your_database_password
```

Make sure the `content_production_tracker` database has been created in MySQL.

### 6. Run Database Migrations

Run:

```bash
php artisan migrate
```

### 7. Start the Development Server

Start the Laravel backend:

```bash
php artisan serve
```

In a separate terminal, start the frontend development server:

```bash
npm run dev
```

### 8. Open the Application

Open the URL shown by Laravel in your browser, usually:

```text
http://127.0.0.1:8000
```

## TypeScript Check

Before continuing development, verify that there are no TypeScript errors:

```bash
npm run type-check
```

The command should finish without any TypeScript errors.
