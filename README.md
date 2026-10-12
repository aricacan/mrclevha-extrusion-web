<h1 align="center">MRC Levha · Corporate Website</h1>

<p align="center">
  Corporate website for <strong>MRC Levha</strong>, a manufacturer of co-extruded thermoplastic sheets<br/>
  (ABS, PMMA acrylic, PE and ABS/PMMA). Built in PHP with Twig templates and a JSON API.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php&logoColor=fff" alt="PHP 8.1+" />
  <img src="https://img.shields.io/badge/Twig-3-BACF29?logo=symfony&logoColor=000" alt="Twig 3" />
  <img src="https://img.shields.io/badge/PHPMailer-6-2E7D32" alt="PHPMailer 6" />
  <img src="https://img.shields.io/badge/MySQL-MariaDB-4479A1?logo=mysql&logoColor=fff" alt="MySQL / MariaDB" />
  <img src="https://img.shields.io/badge/Composer-managed-885630?logo=composer&logoColor=fff" alt="Composer" />
</p>

---

## Overview

The site presents MRC Levha's sheet products, production process, sectors served and catalogs, publishes blog content, and collects contact, quotation and newsletter requests. Content is managed from an admin panel backed by MySQL. A JSON API exposes the same content, and the form records, to the company's ERP.

| | |
|---|---|
| **Company** | MRC Levha, thermoplastic sheet extrusion (Türkiye) |
| **Website** | [mrclevha.com](https://mrclevha.com) |
| **Scope** | Public website, Twig templates, contact and quotation flow, newsletter, JSON API, hosting configuration |
| **Audience** | Industrial buyers of plastic sheets in automotive, furniture, refrigeration, signage and other sectors |
| **Languages** | Turkish, English |

> **Note:** The admin panel, database dump and `.env` file are not part of this repository, so the site cannot run from it alone.

## Highlights

- **MVC-style structure.** Lightweight controllers in `app/controller/` render Twig views from `app/views/`.
- **Bilingual.** Turkish and English dictionaries in `app/lang/`, with language-aware URLs.
- **Contact and quotation forms.** Protected by reCAPTCHA, stored in the database and sent by e-mail through PHPMailer over SMTP. An SMS service class is also included.
- **Newsletter.** Subscriber sign-up stored in the database.
- **Catalogs, products, sectors, blog and search** pages driven by the database.
- **JSON API for the ERP.** Read endpoints for content, plus key-protected endpoints for form records, subscribers and statistics.
- **Environment-aware.** Site URL, database and indexing are configured through `.env`. Staging hosts are kept out of search engines automatically.
- **Security headers.** Forced HTTPS, HSTS, `nosniff`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, and blocked access to sensitive files.
- **Cache busting.** Static assets are versioned with `ASSET_VERSION`.

## Pages

| Page | Controller |
|---|---|
| Home | `home` |
| Products · Categories | `urun` · `kategori` |
| Services / Sectors | `hizmetler` |
| Catalogs | `kataloglar` |
| Blog · Post | `yazilar` |
| Search | `arama` |
| Contact · Quotation | `iletisim` · `teklif` |
| Content pages | `sayfa` |
| Privacy (KVKK) · Privacy Policy · Terms · Legal | `kvkk` · `gizlilik` · `kullanim-sartlari` · `legal` |

## API

All endpoints are served under `/api/{resource}/{slug}` and return JSON.

| Resource | Access | Purpose |
|---|---|---|
| `blog`, `products`, `categories`, `catalogs`, `sectors`, `pages`, `search`, `all` | Public read | Site content |
| `tickets` | API key (all methods) | Contact and quotation records |
| `subscribers` | API key | Newsletter subscribers |
| `stats` | API key | Summary counters for the ERP dashboard |

Protected requests send the key in the `X-Api-Key` header. The key is managed from the admin panel settings. Cross-origin access is limited to `API_ALLOWED_ORIGIN`.

## Tech stack

| Layer | Technology |
|---|---|
| Language | PHP 8.1+ |
| Templates | Twig 3 |
| E-mail | PHPMailer 6 (SMTP) |
| Database | MySQL / MariaDB |
| Web server | Apache with `mod_rewrite` and `mod_headers` |
| Front end | HTML, CSS, JavaScript (Bootstrap, jQuery) |

## Getting started

**Requirements:** PHP 8.1 or later, Composer, MySQL / MariaDB, and the database export with the admin panel.

1. Install dependencies:

   ```bash
   composer install
   ```

2. Create a `.env` file in the project root:

   ```bash
   APP_URL=
   DB_HOST=
   DB_NAME=
   DB_USER=
   DB_PASS=
   DB_CHARSET=utf8mb4
   API_ALLOWED_ORIGIN=
   SESSION_COOKIE_SECURE=1
   INDEXABLE=0
   ASSET_VERSION=1
   ```

3. Point the web server's document root to the project folder. All requests are routed through `index.php`.

Set `INDEXABLE=1` only on the production domain.

## Project structure

```
index.php          Front controller and router
app/
  init.php         Bootstrap: environment, database, settings, language
  config.php       Constants (URL, indexing, asset version)
  env.php          .env loader
  controller/      Page controllers
  views/           Twig templates
  api/             JSON API (router, resources, helpers)
  classes/         Mail and SMS services
  helper/          Shared helpers
  lang/            Turkish and English dictionaries
assets_new/        Front-end CSS, JavaScript and images
assets/            Shared assets and uploads
```

## Security

- Credentials live in `.env`, which is excluded by `.gitignore`. SMTP and reCAPTCHA settings are stored in the database.
- Customer data (form records, subscribers) is only available through key-protected API endpoints.
- Turn off `display_errors` in `app/config.php` on production servers.

## Rights

© MRC Levha. All rights reserved. The MRC Levha name, logo, photography, catalogs and copy belong to MRC Levha and may not be reused without permission. Third-party libraries, templates, fonts and icon sets remain under their own licenses.
