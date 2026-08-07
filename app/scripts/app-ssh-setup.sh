#!/bin/bash

# Author: Nickita Zekov - nz84
# SSH Key Setup Script

set -e

if [ "$EUID" -eq 0 ]; then
    echo "Do not run this script with sudo."
    exit 1
fi

HOSTNAME=$(hostname)

case "$HOSTNAME" in
    app-dev)
        TARGET_IP="10.129.232.50"
        TARGET_NAME="QA"
        ;;
    app-qa)
        TARGET_IP="10.129.232.27"
        TARGET_NAME="Production"
        ;;
    app-prod)
        echo "app-prod does not need SSH key generation."
        echo "Production only receives keys from app-qa."
        exit 0
        ;;
    *)
        echo "ERROR: Unknown hostname '$HOSTNAME'."
        echo "Run app-setup.sh first to configure this VM."
        exit 1
        ;;
esac


# Verify SSH tools exist

if ! command -v ssh-keygen >/dev/null 2>&1; then
    echo "ERROR: ssh-keygen not found."
    echo "Run app-setup.sh first to install required packages."
    exit 1
fi

if ! command -v ssh-copy-id >/dev/null 2>&1; then
    echo "ERROR: ssh-copy-id not found."
    echo "Run app-setup.sh first to install required packages."
    exit 1
fi


# Verify target VM is reachable over ZeroTier network

echo "Checking connection to ${TARGET_NAME} VM (${TARGET_IP})..."

if ! ping -c 1 -W 3 "$TARGET_IP" >/dev/null 2>&1; then
    echo "ERROR: Cannot reach ${TARGET_NAME} VM at ${TARGET_IP}."
    echo "Verify both VMs are connected to the ZeroTier network."
    exit 1
fi

echo "Connection successful."


# Generate SSH key if needed

if [ ! -f "$HOME/.ssh/id_ed25519" ]; then
    echo "Generating SSH key..."
    ssh-keygen -t ed25519 -N "" -f "$HOME/.ssh/id_ed25519"
else
    echo "SSH key already exists, skipping generation."
fi


# Copy key

echo "Copying SSH key to ${TARGET_NAME} VM..."

if ssh-copy-id -i "$HOME/.ssh/id_ed25519.pub" "nz84@${TARGET_IP}"; then
    echo "SSH key successfully copied to ${TARGET_NAME}."
else
    echo "ERROR: Failed to copy SSH key."
    exit 1
fi


echo "SSH setup completed successfully."