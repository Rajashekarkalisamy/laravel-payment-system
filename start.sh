#!/bin/bash

IP=${IP:-127.0.0.1}

# List of domain names to add
DOMAINS="adminer.dv lps-api.dv"

# Determine hosts file based on OS
if [[ "$OSTYPE" == "linux-gnu"* || "$OSTYPE" == "darwin"* ]]; then
    HOSTS_FILE="/etc/hosts"
elif [[ "$OSTYPE" == "msys" || "$OSTYPE" == "cygwin" ]]; then
    HOSTS_FILE="/c/Windows/System32/drivers/etc/hosts"
else
    echo "Unsupported OS: $OSTYPE"
    exit 1
fi

# Check if line with all domains and IP already exists
if grep -qE "^$IP[[:space:]]+.*adminer\.dv.*lps-api\.dv" "$HOSTS_FILE"; then
    echo "Entry already exists: $IP $DOMAINS"
else
    echo "Adding entry to $HOSTS_FILE: $IP $DOMAINS"
    
    if [[ "$OSTYPE" == "linux-gnu"* || "$OSTYPE" == "darwin"* ]]; then
        echo "$IP $DOMAINS" | sudo tee -a "$HOSTS_FILE" > /dev/null
    else
        echo "$IP $DOMAINS" >> "$HOSTS_FILE"
    fi
    
    echo "Entry added successfully."
fi

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo "Docker not found. Running ./docker.sh to install or configure Docker..."
    
    if [[ -f "./docker.sh" ]]; then
        chmod +x ./docker.sh
        ./docker.sh
    else
        echo "Error: docker.sh not found in current directory."
        exit 1
    fi
else
    echo "Docker is installed."
fi

# Create Docker network if not exists
if ! docker network ls | grep -q "common-network"; then
    echo "Creating Docker network: common-network"
    docker network create common-network
else
    echo "Docker network 'common-network' already exists."
fi


# Copy .env.local to .env
if [[ -f ".env.local" ]]; then
    cp .env.local .env
    echo ".env.local copied to .env"
else
    echo "Warning: .env.local not found!"
fi


# Check if Dockerfile or docker-compose.yml is newer than the latest built image
NEEDS_BUILD=false
IMAGE_NAME="laravel_app"  # match this to your docker-compose service name

# Get image creation time (in seconds since epoch)
IMAGE_CREATED=$(docker inspect -f '{{.Created}}' "$IMAGE_NAME" 2>/dev/null | xargs -I{} date -d {} +%s)

# Get last modified time of Dockerfile and docker-compose.yml
DOCKERFILE_UPDATED=$(date -r Dockerfile +%s)
COMPOSEFILE_UPDATED=$(date -r docker-compose.yml +%s)

if [[ $DOCKERFILE_UPDATED -gt $IMAGE_CREATED || $COMPOSEFILE_UPDATED -gt $IMAGE_CREATED ]]; then
    NEEDS_BUILD=true
fi

# Run based on condition
if [ "$NEEDS_BUILD" = true ]; then
    echo "Changes detected. Running docker compose up --build -d"
    docker compose up --build -d
else
    echo "No rebuild needed. Running docker compose up -d"
    docker compose up -d
fi


DB_CONTAINER="laravel_db"
APP_CONTAINER="laravel_app"
DB_NAME="laravel_payment_system"
MYSQL_ROOT_PASSWORD="Root@3456"

# Wait for MySQL to be ready
echo "Waiting for MySQL to start..."
until docker exec $DB_CONTAINER mysqladmin ping -h "localhost" --silent; do
  sleep 2
done

# Step 1: Create database if not exists (optional, Laravel will handle this if .env is correct)
echo "Creating database $DB_NAME inside MySQL container..."
docker exec -i $DB_CONTAINER mysql -uroot -p$MYSQL_ROOT_PASSWORD -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\`;"

# Step 2: Run Laravel migrations
echo "Running Laravel migrations..."
docker exec -it $APP_CONTAINER php artisan migrate --force
