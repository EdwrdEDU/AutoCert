# HR Certificate Manager - Windows Executable

A standalone Windows application for managing and generating HR certificates.

## Quick Start

1. **Double-click** AutoCert.exe to launch the application
2. The app will:
   - Check for PHP installation
   - Set up the application automatically
   - Initialize the database
   - Open in your web browser at http://localhost:8000

## Requirements

- PHP 8.2+ installed on your computer
  - Download from: https://windows.php.net/
  - Make sure to add PHP to your system PATH

## First Time Setup

When you first run AutoCert.exe:
- It will copy the application to: C:\Users\[YourUsername]\AppData\Roaming\AutoCert
- It will install required dependencies automatically
- The database will be initialized

## Features

- Create and manage certificate templates
- Define dynamic template fields
- Generate certificates in batch
- Export certificates as PDFs
- User-friendly web interface

## Troubleshooting

### PHP Not Found Error
If you get a "PHP not found" error:
1. Install PHP from: https://windows.php.net/download/
2. During installation, choose to add PHP to your PATH
3. Restart your computer
4. Run AutoCert.exe again

### Port Already in Use
If port 8000 is already in use:
1. Close any other applications using port 8000
2. Or edit the application to use a different port

### Clear Application Data
To reset the application:
1. Delete the folder: C:\Users\[YourUsername]\AppData\Roaming\AutoCert
2. Run AutoCert.exe again

## Support

For more information, visit:
- Laravel Documentation: https://laravel.com
- PHP Documentation: https://www.php.net
