# Maidenov Passwords Website Backend

This repository contains the backend for the Maidenov Passwords website, built with Drupal 11.

## 🚀 Quick Start

```bash
# Start the development environment
docker compose up -d

# Access the site
open http://localhost:8080

# Admin credentials
username: admin
password: admin
```

## 📋 Development Environment

### Technology Stack
- **Drupal 11.2.8** - CMS platform
- **PostgreSQL 16** - Database
- **Redis 7** - Caching (optional)
- **Drush 13.7.0** - Command-line tool
- **Docker & Docker Compose** - Containerization

### Project Structure
```
├── web/                    # Drupal web root
│   ├── core/              # Drupal core files
│   ├── modules/           # Custom and contrib modules
│   ├── themes/            # Custom and contrib themes
│   ├── sites/             # Site-specific files & settings.php
│   └── ...
├── vendor/                # PHP dependencies (Composer)
├── composer.json          # PHP dependencies
├── composer.lock          # Locked dependency versions
└── docker-compose.yml     # Docker configuration
```

### Available Commands

#### Redis Cache Management

Redis is configured as an optional caching layer that can be easily enabled or disabled for development and testing.

**Enable Redis Caching:**
1. Uncomment the Redis environment variables in `docker-compose.yml`:
   ```yaml
   environment:
     # REDIS_HOST: redis  # Uncomment this line to enable Redis
     # REDIS_PASSWORD:    # Uncomment and set if Redis auth is needed
   ```
2. Uncomment the Redis dependency:
   ```yaml
   depends_on:
     - db
     # - redis  # Uncomment this line to enable Redis dependency
   ```
3. Add this line to `web/sites/default/settings.php`:
   ```php
   @include DRUPAL_ROOT . '/sites/default/settings.redis.php';
   ```
4. Restart containers: `docker compose up -d`

**Disable Redis Caching:**
1. Comment out the Redis environment variables in `docker-compose.yml`
2. Comment out the Redis dependency
3. Remove or comment the `@include` line in `settings.php`
4. Restart containers: `docker compose up -d`

**Redis Statistics:**
```bash
# Check Redis memory usage
docker exec drupal-redis-1 redis-cli info memory

# View Redis key count
docker exec drupal-redis-1 redis-cli dbsize

# Monitor Redis in real-time
docker exec drupal-redis-1 redis-cli monitor
```

#### Docker Management
```bash
# Start containers
docker compose up -d

# Stop containers
docker compose down

# View logs
docker compose logs drupal
docker compose logs db
```

#### Drush Commands
```bash
# Check site status
docker exec drupal-drupal-1 /opt/drupal/vendor/bin/drush status

# Clear all caches
docker exec drupal-drupal-1 /opt/drupal/vendor/bin/drush cr

# Generate admin login link
docker exec drupal-drupal-1 /opt/drupal/vendor/bin/drush uli

# Run database updates
docker exec drupal-drupal-1 /opt/drupal/vendor/bin/drush updatedb

# Import configuration
docker exec drupal-drupal-1 /opt/drupal/vendor/bin/drush cim

# Export configuration
docker exec drupal-drupal-1 /opt/drupal/vendor/bin/drush cex
```

## 🛠️ Development Workflow

### File Structure for Development
All development happens in the mounted `/var/www/drupal/` directory on your host machine. Changes are immediately reflected in the container.

### Module Development
- Custom modules: `web/modules/custom/`
- Contributed modules: `web/modules/contrib/`

### Theme Development
- Custom themes: `web/themes/custom/`
- Contributed themes: `web/themes/contrib/`

### Configuration Management
- **Active configuration**: `web/sites/default/files/config_*/`
- **Sync directory**: `web/sites/default/config/sync/` (for version control)
- **Export command**: `docker exec drupal-drupal-1 /opt/drupal/vendor/bin/drush cex`
- **Import command**: `docker exec drupal-drupal-1 /opt/drupal/vendor/bin/drush cim`

**Configuration files are now version-controlled in `web/sites/default/config/sync/` for site-specific deployments.**

### 🔐 OAuth2 Authentication (Headless)

Drupal 11 is configured with OAuth2 authentication for headless frontend applications using Simple OAuth.

**Available OAuth2 Endpoints:**
- **Token Endpoint**: `POST http://localhost:8080/oauth/token`
- **Authorization**: `GET http://localhost:8080/oauth/authorize`
- **User Info**: `GET http://localhost:8080/oauth/userinfo`
- **JWKS**: `GET http://localhost:8080/oauth/jwks`

**Frontend App Credentials:**
- **Client ID**: `test-frontend`
- **Client Secret**: `test-secret-key-12345`
- **Grant Types**: `password`, `refresh_token`
- **Scope**: `basic`

**Frontend Login Example:**
```bash
curl -X POST http://localhost:8080/oauth/token \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "grant_type=password&client_id=test-frontend&client_secret=test-secret-key-12345&username=admin&password=admin"
```

**Expected Response:**
```json
{
  "token_type": "Bearer",
  "expires_in": 300,
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
  "refresh_token": "def50200a1b2c3..."
}
```

**Using the Access Token:**
```bash
curl -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..." \
  http://localhost:8080/jsonapi/node/article
```

## 🔧 Installation

### Prerequisites
- Docker
- Docker Compose

### First Time Setup
1. Clone this repository
2. Navigate to the project directory: `cd /var/www/drupal`
3. Start the containers: `docker compose up -d`
4. Wait for containers to be ready (check with `docker compose ps`)
5. Access http://localhost:8080 to verify installation

## 📝 Configuration

### Database
- **Host**: `db` (internal Docker network)
- **Port**: `5432`
- **Database**: `drupal`
- **Username**: `drupal`
- **Password**: `drupal`

### Site Information
- **URL**: http://localhost:8080
- **Admin User**: `admin`
- **Admin Password**: `admin`

## 🚀 Deployment

*Deployment instructions will be added in future sections.*

## 📚 Future Sections

This README will be expanded with:
- [ ] Deployment Guide
- [ ] API Documentation
- [ ] Module Development Guidelines
- [ ] Theme Development Guidelines
- [ ] Testing Procedures
- [ ] Performance Optimization
- [ ] Security Best Practices
- [ ] Troubleshooting Guide

## 🤝 Contributing

*Contributing guidelines will be added in future sections.*

## 📄 License

*License information will be added in future sections.*