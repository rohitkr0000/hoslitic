# Holistic Global Services – Jointex store

Core PHP + MySQL online store (no framework, no Composer).

## Requirements

- PHP 8.0 or newer with the `pdo_mysql`, `mbstring`, `curl` and `openssl` extensions
- MySQL 5.7+ or MariaDB 10.3+
- Apache (the `.htaccess` files protect `config/`, `includes/`, `database/` and `uploads/`)

## Install

1. Copy this folder to your web root (e.g. `C:\xampp\htdocs\store` or `public_html` on hosting).
2. On shared hosting, create an empty MySQL database and user in cPanel first. On XAMPP/Laragon the installer creates the database itself.
3. Open `http://your-site/install.php`, enter the database details and choose the admin email and password.
4. Delete `install.php`.
5. Log in at `/admin/` and complete **Settings**.

To reinstall, delete `config/config.php` and open `install.php` again.

## Before going live

- **Products**: set the real prices, MRP, stock, GST rate and ingredients (starter values are placeholders).
- **Shipping**: set the real rates, free-shipping limits and COD fees per zone.
- **Settings › Store**: phone, WhatsApp, email.
- **Settings › Payments**: Razorpay Key ID and Secret. Test first with `rzp_test_…` keys. Add the webhook URL shown there in the Razorpay dashboard.
- **Settings › Delhivery shipping**: API token and pickup location (warehouse) name. Leave "Live mode" unticked while testing with a staging token. Shipments, labels and cancellations are then done from each order screen, tracking shows on the Track Order page, and checkout checks that the PIN code is serviceable.
- **Pages**: review the policy pages.
- Serve the site over HTTPS.

## Folders

| Path | Purpose |
| --- | --- |
| `*.php` (root) | Storefront pages |
| `admin/` | Admin panel |
| `includes/` | Shared code: database, cart, Razorpay, Delhivery, header/footer |
| `database/` | `schema.sql` and starter data |
| `config/` | `config.php` (created by the installer) |
| `uploads/products/` | Product images |
