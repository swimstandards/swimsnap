# SwimSnap

**SwimSnap** is a fast, mobile-friendly alternative to MeetMobile. It allows anyone to upload swim meet setup files and view psych sheets, time standards, event schedules, and results in a modern, searchable format — from any browser, on any device.

This is a **community-driven project**, built by and for swimmers, coaches, and families. You can contribute by uploading meet setup ZIPs or pasting PDF content — helping others access meet info even when MeetMobile is delayed, hard to navigate, or missing features.

SwimSnap powers [swimsnap.com](https://swimsnap.com), but the code is open so that others can study, contribute, and improve it. It is not intended as a reusable framework — it’s a full app focused on one mission: making swim meet data accessible.

---

## Features

- 🏁 Upload ZIP files containing `.hyv` (time standards) and `.ev3` (event schedule) files
- 📊 View event schedules and time cuts in responsive, sortable tables
- 📂 Community-supported upload system — no login needed
- 🔍 Fast browsing, searching, and filtering
- 📱 Fully responsive: works great on mobile and desktop
- 🗃️ File-based system for simplicity — **MongoDB backed metadata support**

---

## Folder Structure

<pre>
webroot/              → Public-facing app
  ├── index.php
  ├── events/ 
  ├── heat-sheets/
  ├── psych-sheets/
  ├── results/
  ├── css/
  ├── images/ 

lib/                  → Backend logic and helpers
  ├── bootstrap.php
  ├── utils.php
  └── parser/
      ├── parser.php 
      ├── events_parser.php
      ├── standards_parser.php
      ├── psych_sheets_parser.php
      ├── heat_sheets_parser.php
      └── results_parser.php

examples/             → Sample meet files for testing (raw inputs and metadata)
  ├── raw/
  │   └── 2025-ncsa-sample.txt
  ├── meta/
  │   └── 2025-ncsa-meta.json


raw/                  → Uploaded text files (.txt, .hyv, .ev3)
meta/                 → JSON metadata per meet (if not using MongoDB)
upload/               → Temporary unzip directory
templates/            → Plates view templates
vendor/               → Composer dependencies

.env                  → Default environment config
.env.local            → Local overrides (ignored by Git)
README.md
</pre>

---

## Naming Conventions

| Type         | Convention        | Example                         |
| ------------ | ----------------- | ------------------------------- |
| File Names   | `snake_case.php`  | `events_parser.php`             |
| Variables    | `snake_case`      | `$event_sessions`, `$meet_info` |
| Functions    | `snake_case()`    | `extract_event_info()`          |
| Constants    | `UPPER_SNAKE`     | `RAW_DIR`, `META_DIR`           |
| Templates    | `hyphen-case.php` | `standards-view.php`            |
| Routes/Slugs | `slug-case`       | `2025-ncsa-summer-champs`       |

---

## Setup

### 📦 Prerequisites

Make sure you have the following installed:

- PHP 8.0+ with common extensions (e.g., `mbstring`, `zip`, `json`, `fileinfo`)
- [Composer](https://getcomposer.org/) for PHP dependency management
- A web server:
  - Apache (recommended, with `mod_rewrite`)
  - or Nginx (with PHP-FPM)
- (Optional) [MongoDB](https://www.mongodb.com/try/download/community) — required if you want to store metadata in a database instead of JSON files

To install the `zip` extension:

#### Ubuntu/Debian
```bash
sudo apt install php-zip
# or for PHP 8.2 specifically:
sudo apt install php8.2-zip
sudo systemctl restart php8.2-fpm
```

#### macOS (Homebrew)
```bash
brew install php
# or, if already installed:
brew reinstall php
```
Then verify:
```bash
php -m | grep zip

---

## 💻 Recommended Editor Setup

We recommend using **Visual Studio Code (VSCode)** for consistent formatting and development.

### 🔌 Required Extensions

- [Prettier – Code Formatter](https://marketplace.visualstudio.com/items?itemName=esbenp.prettier-vscode)
- [PHP Intelephense](https://marketplace.visualstudio.com/items?itemName=bmewburn.vscode-intelephense-client)

### ⚙️ VSCode Settings

See `.vscode/settings.json` in the project for recommended editor configuration.

### ✨ Prettier Settings

See `.prettierrc` in the project for recommended editor configuration.


### 🔧 Installation

1. **Clone the repository:**

   ```bash
   git clone https://github.com/YOUR_USERNAME/SwimSnap.git
   cd SwimSnap
   ```

2. **Install PHP Dependencies:**

   ```bash
   composer install
   ```

3. **Environment Configuration:**

   ```bash
   cp .env.example .env
   ```

   Then edit `.env`:

   ```env
   BASE_URL=http://localhost/swim-snap
   USE_MONGODB=false         # Set to true if using MongoDB
   MONGODB_URI=mongodb://localhost:27017
   ```

4. **Configure Apache (if using):**

   Set `DocumentRoot` to `webroot/`, enable `mod_rewrite`, and allow overrides:

   ```apache
   <Directory "/path/to/SwimSnap/webroot">
      AllowOverride All
      Require all granted
   </Directory>
   ```

5. **(Optional) Configure Nginx + PHP-FPM:**

   If using Nginx, make sure you route requests to `index.php` and pass to PHP-FPM. You can adapt your config based on standard PHP web apps.

6. **Set Folder Permissions**

Make sure the following folders are **writable by the web server user** (e.g., `www-data` on Ubuntu, `apache` on CentOS). You can also use `chmod 777` if you're testing locally (not recommended for production):

    chmod -R 777 raw/ meta/ upload/

These folders are used to store uploaded files and metadata if MongoDB is not enabled. And the `upload` folder is used as a temporary space during file uploads and is cleared immediately after use.

7. **(Optional) Load Example Data**

To try the app with sample meet files:

    cp examples/raw/* raw/
    cp examples/meta/* meta/

This will copy example input files and metadata so you can explore how SwimSnap works without uploading your own files first.

---

1. **Clone the repository:**

   ```bash
   git clone https://github.com/YOUR_USERNAME/SwimSnap.git
   cd SwimSnap
   ```

2. **Install PHP Dependencies**

   ```bash
   composer install
   ```

3. **Environment Configuration**

   ```bash
   cp .env.example .env
   ```

   Customize `.env` (or `.env.local`) with:

   ```bash
   BASE_URL=http://localhost/swim-snap
   ```

4. **Configure Apache**

   Set `DocumentRoot` to `webroot/`, enable mod_rewrite and AllowOverride.

   ```apache
   <Directory "/path/to/SwimSnap/webroot">
       AllowOverride All
       Require all granted
   </Directory>
   ```

---

## Supported Meet Document Types

| Data Type      | Format | Description                                         |
| -------------- | ------ | --------------------------------------------------- |
| Time Standards | `.hyv` | Qualification times per event/age (included in ZIP) |
| Event Schedule | `.ev3` | Event/session structure (included in ZIP)           |
| Psych Sheets   | Pasted | Athlete entry lists from PDF                        |
| Meet Programs  | Pasted | Heat and lane assignments from PDF (Heat Sheets)    |
| Meet Results   | Pasted | Final results from PDF                              |

---

## Upload Methods

- ✅ ZIP upload for `.hyv` and `.ev3`
- ⌨️ Paste psych sheet / program / result as plain text
- ✅ Auto metadata detection
- ✅ MongoDB-backed deduplication (or fallback to meta.json)

---

## Contribution

SwimSnap is open to all contributions — whether you're a developer, swim parent, coach, or data enthusiast:
- Contribute code to enhance search, parsing, tagging, or other core features
- Upload meet files to expand coverage
- Report bugs in parsing or data display
- Suggest UI or usability improvements — even if you don’t code

To contribute code:

1. Fork the repo on GitHub
2. Create a new branch for your change: `git checkout -b feature/improve-parser`
3. Make your edits and push to your fork
4. Open a pull request with a short description of your change

We welcome even small contributions like parser fixes, better mobile styling, or support for more LSCs.

---

## Roadmap

Planned features and improvements:

- 🧠 **Swimmer Search**  
  Implement full-text swimmer search powered by Elasticsearch, using data from uploaded files (raw + metadata) or structured JSON via parser.

- 🤝 **Community Tagging**  
  Enable users to tag meets by type, level, or location — making search and filtering faster and more intuitive.

- 🔗 **Federated Feeds**  
  Connect to LSC and team websites to auto-ingest public meet files.
