# HR Certificate Manager

A web-based application for managing and generating HR certificates with templates.

## Quick Start

### Windows
Simply double-click `start.bat` to:
- Install dependencies automatically
- Initialize the database
- Start the web server
- Open the application in your browser

### macOS / Linux
```bash
./start.sh
```

## Requirements
- PHP 8.2 or higher
- Composer (for dependency management)
- SQLite (included with PHP)

## Features
- Create and manage certificate templates
- Define dynamic template fields
- Generate certificates in batch
- Export certificates as PDFs
- User-friendly web interface

## Default Access
- URL: http://localhost:8000
- Automatically opens in your default browser

## Troubleshooting

### "PHP not found"
Ensure PHP is installed and accessible from command line:
```
php --version
```

If not installed, download from: https://windows.php.net/download/

### Database Issues
Delete `database/database.sqlite` and rerun start.bat to reset the database.

### Port Already in Use
Edit the start script and change port 8000 to another available port.

## Support
For issues or questions, please refer to the Laravel documentation at https://laravel.com
