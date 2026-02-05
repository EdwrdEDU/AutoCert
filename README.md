# AutoCert - Automated Certificate Generation System

A powerful Laravel-based web application for automated certificate generation from templates. AutoCert allows organizations to create professional certificates in bulk using Word (.docx) or PowerPoint (.pptx) templates with dynamic field replacement.

## 🌟 Features

### Template Management
- **Upload Custom Templates**: Support for DOCX and PPTX file formats
- **Dynamic Field Detection**: Automatically detects placeholder fields in templates (e.g., `{{name}}`, `{{date}}`)
- **Field Configuration**: Configure field types (text, date, auto-generated)
- **Auto-Generated Fields**: Support for auto-increment IDs, timestamps, and custom formulas
- **Template Status**: Draft, Active, and Archived states

### Certificate Generation
- **Single Certificate**: Generate individual certificates through web form
- **Batch Generation**: Process multiple certificates from CSV/Excel imports
- **Smart Data Import**: Support for CSV and Excel (.xlsx) files with automatic column mapping
- **PDF Export**: All certificates are automatically converted to PDF format
- **Merged Output**: Batch generations create both individual PDFs and a merged PDF file

### Certificate Management
- **View History**: Browse all generated certificates
- **Search & Filter**: Find certificates by recipient name or template
- **Download Options**:
  - Individual certificate download
  - Bulk ZIP export of selected certificates
  - Merged PDF of multiple certificates
- **Preview**: View certificate details before downloading

### Windows Launcher
- **Standalone Executables**: Pre-built `.exe` files for easy deployment
- **Zero Configuration**: Automatic setup and environment detection
- **System Checks**: Validates PHP, Composer, and Node.js installation
- **Auto-Update**: Runs migrations and installs dependencies automatically

## 🛠️ Technology Stack

### Backend
- **Framework**: Laravel 12.x
- **PHP**: ^8.2
- **PDF Generation**: Laravel Snappy (wkhtmltopdf)
- **Excel Processing**: PhpOffice/PhpSpreadsheet
- **Database**: SQLite (default) / MySQL / PostgreSQL

### Frontend
- **Build Tool**: Vite 7.x
- **CSS Framework**: Tailwind CSS 4.x
- **JavaScript**: Axios for API requests

### Deployment
- **Launchers**: Python, PowerShell, Batch, and C# executables
- **Packaging**: PyInstaller for standalone Windows executables

## 📋 Requirements

### Development Environment
- PHP 8.2 or higher
- Composer
- Node.js 18+ and NPM
- SQLite extension enabled in PHP

### Production Environment (Windows)
- PHP 8.2+ (with CLI)
- wkhtmltopdf (for PDF generation)
- Optional: Composer and Node.js (for updates)

### PHP Extensions Required
- PDO
- SQLite
- Mbstring
- XML
- Zip
- GD or Imagick

## 🚀 Installation

### Method 1: Quick Start (Recommended)
```bash
# Clone the repository
git clone <repository-url> autocert
cd autocert

# Run automated setup
composer setup
```

This single command will:
- Install all PHP dependencies
- Create `.env` file from template
- Generate application key
- Run database migrations
- Install NPM packages
- Build frontend assets

### Method 2: Manual Installation
```bash
# 1. Install PHP dependencies
composer install

# 2. Create environment file
cp .env.example .env

# 3. Generate application key
php artisan key:generate

# 4. Run database migrations
php artisan migrate

# 5. Install and build frontend assets
npm install
npm run build
```

### Method 3: Windows Executable Launcher
1. Download the latest `AutoCert.exe` from releases
2. Place it in the AutoCert directory
3. Double-click to run
4. The launcher will automatically:
   - Check for PHP installation
   - Install dependencies
   - Run migrations
   - Start the application server

## 🎯 Usage

### Starting the Application

#### Development Mode
```bash
composer dev
```
This starts:
- Laravel development server (port 8000)
- Queue worker
- Log viewer (Pail)
- Vite dev server (hot reload)

#### Production Mode
```bash
# Start the web server
php artisan serve

# In another terminal, start the queue worker
php artisan queue:listen
```

Access the application at: `http://localhost:8000`

### Creating a Template

1. Navigate to **Templates** → **Create New**
2. Fill in template details:
   - **Name**: Descriptive name (e.g., "Employee Training Certificate")
   - **Template File**: Upload DOCX or PPTX file
3. Use placeholders in your template file:
   - Format: `{{field_name}}`
   - Example: `{{employee_name}}`, `{{course_title}}`, `{{completion_date}}`
4. Click **Upload**
5. Configure detected fields:
   - **Required**: Mark mandatory fields
   - **Type**: Text, Date, or Auto-generated
   - **Auto-generation**: Configure ID patterns, date formats, etc.
6. **Activate** the template when ready

### Generating Certificates

#### Single Certificate
1. Select a template from the dashboard
2. Click **Generate Single**
3. Fill in the form with recipient data
4. Click **Generate Certificate**
5. Download the generated PDF

#### Batch Generation
1. Select a template
2. Click **Import CSV/Excel**
3. Upload a file with columns matching template fields
4. System will process all rows and generate:
   - Individual PDFs for each recipient
   - Merged PDF with all certificates
   - Summary report
5. Download individual or merged files

### CSV/Excel Format Example
```csv
employee_name,course_title,completion_date,score
John Doe,Advanced Excel Training,2026-02-05,95
Jane Smith,Project Management,2026-02-03,88
```

### Managing Certificates

- **View All**: Browse all generated certificates
- **Search**: Filter by recipient name or template
- **Download**: Individual PDFs or bulk export
- **Delete**: Remove unwanted certificates
- **Merge**: Combine multiple certificates into one PDF

## 📁 Directory Structure

```
autocert/
├── app/
│   ├── Http/Controllers/      # Request handlers
│   ├── Models/                # Database models
│   ├── Services/              # Business logic
│   └── Providers/             # Service providers
├── config/                    # Configuration files
├── database/
│   ├── migrations/            # Database schema
│   └── seeders/               # Sample data
├── public/                    # Web root
├── resources/
│   ├── css/                   # Stylesheets
│   ├── js/                    # JavaScript
│   └── views/                 # Blade templates
├── routes/
│   └── web.php                # Web routes
├── storage/
│   ├── app/
│   │   ├── public/            # User uploads (templates)
│   │   └── private/           # Generated certificates
│   └── logs/                  # Application logs
├── AutoCert.exe               # Windows launcher
├── composer.json              # PHP dependencies
├── package.json               # NPM dependencies
└── .env                       # Environment configuration
```

## 🔧 Configuration

### Environment Variables
Key settings in `.env`:

```env
APP_NAME="AutoCert"
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite
# DB_DATABASE=/absolute/path/to/database.sqlite

# Queue driver (sync, database, redis)
QUEUE_CONNECTION=sync

# File storage
FILESYSTEM_DISK=local
```

### Storage Setup
```bash
# Create required storage directories
php artisan storage:link
```

### PDF Generation
Ensure `wkhtmltopdf` is installed:

**Windows**: Download from https://wkhtmltopdf.org/downloads.html

**Linux**:
```bash
sudo apt-get install wkhtmltopdf
```

**macOS**:
```bash
brew install wkhtmltopdf
```

## 🧪 Testing

```bash
# Run all tests
composer test

# Run specific test suite
php artisan test --filter=CertificateTest
```

## 📦 Building Windows Executable

```bash
# Install PyInstaller
pip install pyinstaller

# Build AutoCert.exe
pyinstaller AutoCert.spec

# Or HRCert.exe
pyinstaller HRCert.spec

# Executables will be in dist/
```

## 🐛 Troubleshooting

### Common Issues

**PHP not found**
- Ensure PHP is installed and added to system PATH
- Verify: `php --version`

**Permission errors**
```bash
# Linux/Mac - Fix storage permissions
chmod -R 775 storage bootstrap/cache
```

**PDF generation fails**
- Install wkhtmltopdf
- Check path in config/snappy.php

**Queue not processing**
```bash
# Clear failed jobs
php artisan queue:flush

# Restart queue worker
php artisan queue:restart
```

**Database not found**
```bash
# Create SQLite database
touch database/database.sqlite

# Run migrations
php artisan migrate
```

## 🔐 Security

- Never commit `.env` file
- Keep `APP_KEY` secret and unique per installation
- Store certificates in non-public directory (`storage/app/private`)
- Validate and sanitize all user inputs
- Use HTTPS in production

## 📝 License

MIT License - See LICENSE file for details

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📧 Support

For issues, questions, or suggestions:
- Create an issue on GitHub
- Check documentation in `/docs` folder
- Review existing issues and discussions

## 🎉 Acknowledgments

- Laravel Framework
- Tailwind CSS
- PhpSpreadsheet
- Laravel Snappy
- All open-source contributors

---

**Version**: 1.0.0  
**Last Updated**: February 2026  
**Status**: Active Development
