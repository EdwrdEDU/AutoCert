#!/bin/bash

echo ""
echo "=========================================="
echo "  HR Certificate Manager"
echo "=========================================="
echo ""

cd "$(dirname "$0")"

# Check if PHP is available
if ! command -v php &> /dev/null; then
    echo "Error: PHP not found in PATH"
    echo "Please ensure PHP is installed and added to your PATH"
    echo ""
    read -p "Press enter to exit..."
    exit 1
fi

# Install dependencies if vendor doesn't exist
if [ ! -d "vendor" ]; then
    echo "Installing dependencies..."
    composer install --no-dev
fi

# Clear cache
php artisan config:clear 2>/dev/null
php artisan cache:clear 2>/dev/null

# Migrate database if needed
if [ ! -f "database/database.sqlite" ]; then
    echo "Initializing database..."
    php artisan migrate --force
fi

echo ""
echo "✓ Starting application..."
echo "✓ Opening browser..."
echo ""

# Open browser
if command -v xdg-open &> /dev/null; then
    xdg-open http://localhost:8000 &
elif command -v open &> /dev/null; then
    open http://localhost:8000 &
fi

echo ""
echo "Press Ctrl+C to stop the server"
echo ""

php artisan serve --port=8000