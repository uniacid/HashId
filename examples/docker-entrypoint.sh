#!/bin/sh
set -e

# Function to setup a Symfony application
setup_symfony() {
    local app_dir=$1
    local app_name=$2

    echo "Setting up $app_name..."
    cd "$app_dir"

    # Install dependencies if vendor directory doesn't exist
    if [ ! -d "vendor" ]; then
        echo "Installing dependencies for $app_name..."
        composer install --no-interaction --prefer-dist --optimize-autoloader
    fi

    # Create database if it doesn't exist
    if [ ! -f "var/data.db" ]; then
        echo "Creating database for $app_name..."
        php bin/console doctrine:database:create --if-not-exists
        php bin/console doctrine:schema:create

        # Create sample data
        php bin/console doctrine:query:sql "INSERT INTO \`order\` (order_number, customer_name, total_amount, status, created_at) VALUES
            ('ORD-001', 'John Doe', '99.99', 'pending', datetime('now')),
            ('ORD-002', 'Jane Smith', '149.50', 'shipped', datetime('now')),
            ('ORD-003', 'Bob Johnson', '75.25', 'delivered', datetime('now')),
            ('ORD-004', 'Alice Brown', '200.00', 'processing', datetime('now')),
            ('ORD-005', 'Charlie Wilson', '50.75', 'pending', datetime('now'))"
    fi

    # Clear cache
    php bin/console cache:clear

    echo "$app_name setup complete!"
}

# Setup both Symfony applications
setup_symfony "/app/symfony-6.4" "Symfony 6.4"
setup_symfony "/app/symfony-7.0" "Symfony 7.0"

# Execute the passed command
exec "$@"