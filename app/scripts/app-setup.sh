#!/bin/bash
# Author: Nickita Zekov - nz84
# Setup Script for App VM (Milestone 3)

set -e

# Find project root directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
CONFIG_DIR="$PROJECT_DIR/config"

run() {
    local command="$1"

    echo "Running '$command'"

    if eval "$command" > /dev/null 2>&1; then
        echo "'$command' completed successfully"
    else
        echo "'$command' failed to complete"
        return 1
    fi
}

create_rmq_config() {
    local example_file="$1"
    local output_file="$2"

    if [ ! -f "$example_file" ]; then
        echo "Missing example file: $example_file"
        exit 1
    fi

    cp "$example_file" "$output_file"

    sed -i "s/^USER=.*/USER=$RMQ_USER/" "$output_file"
    sed -i "s/^PASSWORD=.*/PASSWORD=$RMQ_PASSWORD/" "$output_file"
    sed -i "s/^BROKER_HOST=.*/BROKER_HOST=$RMQ_BROKER_HOST/" "$output_file"

    chmod 600 "$output_file"

    echo "Created $output_file"
}

# Changes to environment-specific hostname 
while true; do
    read -rp "Enter VM environment (dev, qa, prod): " env

    case "${env,,}" in
        dev|qa|prod)
            sudo hostnamectl set-hostname "app-${env,,}"
            break
            ;;
        *)
            echo "Invalid input. Please enter 'dev', 'qa', or 'prod'."
            ;;
    esac
done

# Updates preinstalled packages
run "sudo apt update"

# Installs necessary packages
run "sudo apt install -y git"
run "sudo apt install -y composer" 
run "sudo apt install -y php"
run "sudo apt install -y ssh"
run "sudo apt install -y php-cli"

# Installs zerotier and joins group network if not already installed
if ! command -v zerotier-cli >/dev/null 2>&1; then
    run "curl -s https://install.zerotier.com/ | sudo bash"
    run "sudo zerotier-cli join cf719fd540fc6df4"
else
    echo "ZeroTier is already installed, skipping installation"
fi

# RabbitMQ configuration setup
echo "Configuring RabbitMQ credentials"

read -p "RabbitMQ broker host IP: " RMQ_BROKER_HOST
read -p "RabbitMQ username: " RMQ_USER
read -s -p "RabbitMQ password: " RMQ_PASSWORD
echo

create_rmq_config \
    "$CONFIG_DIR/adminRabbitMQ.ini.example" \
    "$CONFIG_DIR/adminRabbitMQ.ini"

create_rmq_config \
    "$CONFIG_DIR/authRabbitMQ.ini.example" \
    "$CONFIG_DIR/authRabbitMQ.ini"

create_rmq_config \
    "$CONFIG_DIR/gameRabbitMQ.ini.example" \
    "$CONFIG_DIR/gameRabbitMQ.ini"

echo "RabbitMQ configuration files created successfully"

cd "$PROJECT_DIR"
run "composer install"

echo "Setup script completed successfully"