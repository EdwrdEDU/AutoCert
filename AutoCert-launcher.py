#!/usr/bin/env python3
"""
HR Certificate Manager - Windows Executable Wrapper
Creates a standalone exe that launches the Laravel application
"""

import subprocess
import sys
import os
import shutil
from pathlib import Path
import ctypes

def show_error(title, message):
    """Show error message box on Windows"""
    ctypes.windll.user32.MessageBoxW(0, message, title, 0x10)  # 0x10 = MB_ICONERROR

def main():
    app_name = "HR Certificate Manager"
    print("\n" + "=" * 50)
    print(f"  {app_name}")
    print("=" * 50 + "\n")
    
    # Get directories
    # When running as PyInstaller exe, use sys.executable to get the actual .exe location
    if getattr(sys, 'frozen', False):
        # Running as compiled executable
        script_dir = Path(sys.executable).parent
    else:
        # Running as script
        script_dir = Path(__file__).parent
    
    app_home = Path(os.environ.get("APPDATA")) / "AutoCert"
    app_home.mkdir(parents=True, exist_ok=True)
    
    # Check PHP
    try:
        subprocess.run(["php", "--version"], capture_output=True, check=True, timeout=5)
        print("✓ PHP found")
    except (subprocess.CalledProcessError, FileNotFoundError, subprocess.TimeoutExpired):
        error_msg = (
            "PHP is not installed or not in PATH.\n\n"
            "Please install PHP from:\n"
            "https://windows.php.net/\n\n"
            "Installation steps:\n"
            "1. Download PHP from https://windows.php.net/download/\n"
            "2. Extract to C:\\php\n"
            "3. Add C:\\php to your system PATH\n"
            "4. Restart this application"
        )
        show_error("PHP Not Found - AutoCert", error_msg)
        return 1
    
    # Copy files on first run
    if not (app_home / "artisan").exists():
        print("✓ Preparing application files...")
        
        for folder in ["app", "bootstrap", "config", "database", "resources", "routes", "storage", "public"]:
            src = script_dir / folder
            dst = app_home / folder
            if src.exists():
                if dst.exists():
                    shutil.rmtree(dst)
                shutil.copytree(src, dst)
        
        for file in [".env", "artisan", "composer.json"]:
            src = script_dir / file
            dst = app_home / file
            if src.exists():
                shutil.copy2(src, dst)
    
    # Install dependencies
    if not (app_home / "vendor").exists():
        print("Installing dependencies (this may take a minute)...")
        
        # Try to copy vendor from installation directory first
        vendor_src = script_dir / "vendor"
        if vendor_src.exists():
            print("Copying dependencies from installation...")
            shutil.copytree(vendor_src, app_home / "vendor")
            print("✓ Dependencies installed")
        else:
            # Fallback: try composer if available
            try:
                result = subprocess.run(
                    ["composer", "--version"],
                    capture_output=True,
                    check=False,
                    timeout=5
                )
                if result.returncode == 0:
                    print("Installing dependencies with Composer...")
                    subprocess.run(
                        ["composer", "install", "--no-dev", "--no-interaction"],
                        cwd=app_home,
                        stdout=subprocess.DEVNULL,
                        stderr=subprocess.DEVNULL,
                        timeout=300
                    )
                    print("✓ Dependencies installed")
                else:
                    error_msg = (
                        "Dependencies (vendor folder) not found.\n\n"
                        "Please ensure the 'vendor' folder is in the same directory as AutoCert.exe\n\n"
                        "Or install Composer from: https://getcomposer.org/"
                    )
                    show_error("Missing Dependencies - AutoCert", error_msg)
                    return 1
            except (FileNotFoundError, subprocess.TimeoutExpired):
                error_msg = (
                    "Dependencies (vendor folder) not found.\n\n"
                    "Please ensure the 'vendor' folder is in the same directory as AutoCert.exe\n\n"
                    "Or install Composer from: https://getcomposer.org/"
                )
                show_error("Missing Dependencies - AutoCert", error_msg)
                return 1
    
    # Setup database
    os.chdir(app_home)
    subprocess.run(["php", "artisan", "config:clear"], capture_output=True)
    
    if not (app_home / "database" / "database.sqlite").exists():
        print("✓ Initializing database...")
        subprocess.run(
            ["php", "artisan", "migrate", "--force", "--no-interaction"],
            capture_output=True
        )
    
    # Start application
    print("\n✓ Starting web server on http://localhost:8000")
    print("✓ Opening in browser...")
    print("\nServer running - Press Ctrl+C to stop\n")
    
    # Open browser
    import webbrowser
    webbrowser.open("http://localhost:8000")
    
    # Start Laravel server
    subprocess.run(["php", "artisan", "serve", "--port=8000"])
    
    return 0

if __name__ == "__main__":
    sys.exit(main())
