# Vyom Heritage Museum Platform

Museum tour scheduling and e-commerce experience built with a Vue.js frontend and a Laravel API backend. The project powers the Vyom Heritage Museum public site (tours, shop, about) along with authenticated admin dashboards for sales analytics and tour operations.

## At a Glance

- **Frontend**: Vue 3, Vite, Pinia, Axios, Playfair Display themed styling.
- **Backend**: Laravel 11 API, Eloquent ORM, MySQL, CORS-enabled.
- **Shop**: Product catalog, cart, checkout flow, rotating hero highlights.
- **Tours**: Capacity-aware registration (morning & afternoon slots), live availability checks.
- **Admin**: Basic-auth protected dashboards for analytics and tour registrations.
- **Deployment**: Frontend deployable on Vercel; backend currently served locally (artisan) with guidance for production hosting.

## Repository Structure

```
calico-local/
├── backend/      # Laravel API
└── frontend/     # Vue application
```

Key backend directories:

- `app/Http/Controllers/Api/` – API controllers (products, carts, checkout, admin analytics, tour registrations).
- `app/Models/` – Eloquent models including `TourRegistration`, `Product`, `Order`, etc.
- `database/migrations/` – Schema for tours, shop, about content.
- `database/seeders/` – Seed data for products, about page, etc.

Key frontend directories:

- `src/components/` – Core components (tour form, shared UI).
- `src/views/` – Route-level pages (Shop, ProductDetail, Cart, Checkout, About, Admin views).
- `src/stores/` – Pinia stores (cart management).
- `src/router/index.js` – Page routing & admin route guards.

## Prerequisites

- Node.js 18+
- npm 9+
- PHP 8.2+
- Composer 2+
- MySQL 8.x (using existing `loginauth` database by default)

## Environment Configuration

### Backend (`backend/.env`)

Set MySQL credentials and CORS origin:

```env
DB_DATABASE=loginauth
DB_USERNAME=root
DB_PASSWORD=your_password
CORS_ALLOWED_ORIGINS=http://localhost:5173
ADMIN_USERNAME=admin
ADMIN_PASSWORD=admin
```

> **Note:** If you point to a different database, ensure the migrations and seeders are rerun.

### Frontend (`frontend/.env`)

```env
VITE_API_BASE_URL=http://127.0.0.1:8000/api
```

Adjust the URL to match the deployed backend when needed.

## Local Development

### Backend API

```bash
cd backend
composer install
php artisan migrate --seed
php artisan serve --host=0.0.0.0 --port=8000
```

The API will be available at `http://127.0.0.1:8000`.

### Frontend App

```bash
cd frontend
npm install
npm run dev
```

Visit `http://localhost:5173` to access the site.

## Admin Access

- **Login**: `http://localhost:5173/admin/login`
- **Default credentials**: `admin / admin`

On authentication, credentials are stored client-side (basic auth token) to access:

- `/admin/dashboard` – Revenue, orders, carts, tours metrics, top products, recent orders.
- `/admin/tour-registrations` – Date-filtered tour bookings, slot capacity, visitor details.

Update the `ADMIN_USERNAME` / `ADMIN_PASSWORD` values in `backend/.env` for production.

## Feature Highlights

- **Tour Registration**
  - Morning (20 pax) & afternoon (15 pax) slot capacities.
  - Real-time availability lookup before submission.
  - Guided tour flag, notes, contact details captured.

- **Museum Shop**
  - Product hero rotation on home page.
  - Dark-themed product cards with responsive imagery.
  - Cart persistence via API, checkout placeholder for future payment integration.

- **About Us**
  - Content (paragraphs + hero image URL) served via database so museum staff can update copy without code changes.

- **Admin Consoles**
  - Basic auth middleware for analytics and tour endpoints.
  - Date filter & search for tour registrations; per-slot capacity color coding.

## Deployment Notes

- **Frontend**: Tested with Vercel. Use framework preset “Vite”, root `frontend`, build command `npm run build`, output `dist`.
- **Backend**: Requires PHP hosting (e.g., Laravel Forge, Laravel Vapor, Render, or a VPS). Ensure `.env` values match production database and add allowed origins for the hosted frontend.

## Common Tasks

- **Run lints/build**: `npm run build` (frontend), `php artisan test` (backend tests TBD).
- **Database seeding**: `php artisan db:seed` (populates products, about content).
- **Sync random product imagery**: Update URLs in `database/seeders/ProductSeeder.php` and rerun seeder.

## Admin Diagram (WhatsApp-Friendly)

```
Landing & Shop
  └─ /shop → browse, search, featured → product detail or tours

Product Detail
  └─ /shop/product/:slug → add to cart → cart/checkout

Cart & Checkout
  └─ /shop/cart → adjust → /shop/checkout → confirmation

Tour Registration
  └─ /tours → pick date/slot → availability badge → submit

About
  └─ /about → story & image from CMS

Admin Login
  └─ /admin/login → basic auth → dashboard

Admin Dashboard
  └─ /admin/dashboard → metrics + orders → tour tab

Admin Tours
  └─ /admin/tour-registrations → filter/search → capacity view
```

## License

Internal project for Vyom Heritage Museum. License terms TBD.


