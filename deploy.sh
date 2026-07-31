#!/bin/bash
# Automated Laravel deployment script using rsync

# Exit on error
set -e

# Load credentials from config file if exists
CONFIG_FILE=".deploy_config"
if [ -f "$CONFIG_FILE" ]; then
    # Load configuration variables
    source "$CONFIG_FILE"
    echo "=== Loaded saved connection details for: $SSH_USER@$SSH_HOST ==="
    echo "Remote folder: $SSH_DIR"
else
    echo "=== Configure Deployment Server ==="
    read -p "Enter SSH server username: " SSH_USER
    read -p "Enter SSH server IP / hostname: " SSH_HOST
    read -p "Enter remote directory path (e.g., /var/www/html/project): " SSH_DIR
    
    # Save configuration
    echo "SSH_USER=\"$SSH_USER\"" > "$CONFIG_FILE"
    echo "SSH_HOST=\"$SSH_HOST\"" >> "$CONFIG_FILE"
    echo "SSH_DIR=\"$SSH_DIR\"" >> "$CONFIG_FILE"
    chmod 600 "$CONFIG_FILE"
    echo "Configuration saved to $CONFIG_FILE"
fi

# Check if a single file / folder path was passed as an argument
TARGET_PATH="$1"

if [ -n "$TARGET_PATH" ]; then
    # Check if target exists locally
    if [ ! -e "$TARGET_PATH" ]; then
        echo "Error: Path '$TARGET_PATH' does not exist locally."
        exit 1
    fi
    
    # Strip leading "./" if present
    TARGET_PATH="${TARGET_PATH#./}"
    
    echo ""
    echo "Pushing single target: $TARGET_PATH"
    echo "1) Dry Run (Preview upload)"
    echo "2) Deploy (Upload now)"
    read -p "Choice [1-2]: " CHOICE
    
    case $CHOICE in
        1)
            echo "Running dry-run transfer for $TARGET_PATH..."
            rsync -avzR --dry-run "$TARGET_PATH" "${SSH_USER}@${SSH_HOST}:${SSH_DIR}/"
            ;;
        2)
            echo "Uploading $TARGET_PATH..."
            rsync -avzR "$TARGET_PATH" "${SSH_USER}@${SSH_HOST}:${SSH_DIR}/"
            echo "Fixing remote permissions..."
            ssh "${SSH_USER}@${SSH_HOST}" "chown -R www-data:www-data ${SSH_DIR}/storage ${SSH_DIR}/bootstrap/cache && chmod -R 775 ${SSH_DIR}/storage ${SSH_DIR}/bootstrap/cache"
            echo "Single target deployment complete!"
            ;;
        *)
            echo "Invalid choice. Exiting."
            exit 1
            ;;
    esac
else
    # Standard full deploy
    # Verify .deployignore exists
    if [ ! -f .deployignore ]; then
        echo "Warning: .deployignore not found. Creating a default one..."
        cat <<EOT > .deployignore
.env
.git/
vendor/
node_modules/
/storage/framework/cache/data/*
/storage/framework/sessions/*
/storage/framework/views/*
/storage/logs/*
.DS_Store
EOT
    fi

    echo ""
    echo "Select Deployment Action:"
    echo "1) Dry Run (Preview which files will be uploaded, safe)"
    echo "2) Deploy (Synchronize files to server)"
    read -p "Choice [1-2]: " CHOICE

    case $CHOICE in
        1)
            echo "Running dry-run transfer..."
            rsync -avz --dry-run --exclude-from='.deployignore' ./ "${SSH_USER}@${SSH_HOST}:${SSH_DIR}/"
            echo "Dry run complete."
            ;;
        2)
            echo "Uploading files to server..."
            rsync -avz --exclude-from='.deployignore' ./ "${SSH_USER}@${SSH_HOST}:${SSH_DIR}/"
            echo "Fixing permissions on storage and bootstrap/cache..."
            ssh "${SSH_USER}@${SSH_HOST}" "chown -R www-data:www-data ${SSH_DIR}/storage ${SSH_DIR}/bootstrap/cache && chmod -R 775 ${SSH_DIR}/storage ${SSH_DIR}/bootstrap/cache"
            echo "Deployment complete!"
            ;;
        *)
            echo "Invalid choice. Exiting."
            exit 1
            ;;
    esac
fi
