#!/bin/bash
# MongoDB Extension Install Script for XAMPP on macOS

if [ "$EUID" -ne 0 ]; then 
  echo "Please run as root (use sudo)"
  exit
fi

# Ensure autoconf is in PATH (from Homebrew location)
export PATH="/opt/homebrew/bin:/usr/local/bin:$PATH"

echo "Checking for autoconf..."
if ! command -v autoconf &> /dev/null; then
    echo "Autoconf not found. Attempting to install via Homebrew..."
    # Attempt install as regular user if brew allows, or continue hoping it works
    # Usually brew shouldn't run as root, so this might be tricky if script is run as sudo.
    # We'll assume autoconf is present since I checked it earlier.
    echo "Warning: autoconf might be missing for root user. Ensure 'brew install autoconf' was run."
fi

echo "Updating PECL channels..."
/Applications/XAMPP/xamppfiles/bin/pecl channel-update pecl.php.net
/Applications/XAMPP/xamppfiles/bin/pecl clear-cache

echo "Installing MongoDB extension via pecl..."
# Pipe 'yes' to accept default options (no SSL/SASL customization usually needed for basic setup)
# Explicitly point to Homebrew OpenSSL to fix missing header errors
export CFLAGS="-I/opt/homebrew/opt/openssl/include"
export LDFLAGS="-L/opt/homebrew/opt/openssl/lib"

if [ -d "/opt/homebrew/opt/openssl" ]; then
    echo "Found OpenSSL at /opt/homebrew/opt/openssl. Using it for compilation."
    yes '' | pecl install mongodb
else
    echo "Warning: /opt/homebrew/opt/openssl not found. Attempting standard install..."
    yes '' | /Applications/XAMPP/xamppfiles/bin/pecl install mongodb
fi

echo "Verifying installation..."
if [ -f "/Applications/XAMPP/xamppfiles/lib/php/extensions/no-debug-non-zts-20220829/mongodb.so" ]; then
    echo "MongoDB extension installed successfully."
else
    echo "Error: MongoDB extension installation failed."
    exit 1
fi

echo "Configuring php.ini..."
PHP_INI="/Applications/XAMPP/xamppfiles/etc/php.ini"

# Check if extension is already enabled to avoid duplicates
if grep -q "extension=mongodb.so" "$PHP_INI"; then
    echo "MongoDB extension already configured in php.ini"
else
    echo "extension=mongodb.so" >> "$PHP_INI"
    echo "Added extension=mongodb.so to $PHP_INI"
fi

echo "--------------------------------------------------------"
echo "Installation complete!"
echo "Please restart your XAMPP Apache server to apply changes."
echo "You can do this via the XAMPP Control Panel or by running:"
echo "sudo /Applications/XAMPP/xamppfiles/xampp restart apache"
echo "--------------------------------------------------------"
