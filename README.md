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
- **Redis 7** - Caching layer
- **Drush 13.7.0** - Command-line tool
- **Docker & Docker Compose** - Containerization
- **PhpRedis Extension** - High-performance Redis client

### Project Structure
```
├── web/                   # Drupal web root
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

#### 🔥 Redis Cache Management

Redis is configured as a high-performance caching layer using PhpRedis extension for optimal performance with Drupal 11. The system is designed to be easily enabled or disabled for different environments.

**Current Status: 🚫 DISABLED (Development Mode)**
- **Mode**: Redis caching is disabled for local development
- **To Enable**: Follow the Production Setup steps below
- **Note**: Redis container remains running but is not used by Drupal

##### 🚀 Production Setup (Enable Redis Caching)

**Step 1: Environment Variables**
Uncomment Redis configuration in `docker-compose.yml`:
```yaml
environment:
  REDIS_HOST: redis  # Enable Redis connection
  # REDIS_PASSWORD: your-redis-password  # Optional: Set if Redis auth required
  # REDIS_PORT: 6379  # Optional: Override default port
```

**Step 2: Container Dependencies**
Uncomment Redis dependency:
```yaml
depends_on:
  - db
  - redis  # Enable Redis container dependency
```

**Step 3: Drupal Configuration**
Add this line to `web/sites/default/settings.php`:
```php
@include DRUPAL_ROOT . '/sites/default/settings.redis.php';
```

**Step 4: Restart Environment**
```bash
docker compose up -d
```

##### 🛠️ Development Setup (Disable Redis Caching) - ✅ CURRENT CONFIGURATION

**Step 1: Environment Variables**
Comment out Redis configuration in `docker-compose.yml`:
```yaml
environment:
  # REDIS_HOST: redis  # Comment to disable Redis
  # REDIS_PASSWORD:    # Comment to disable Redis auth
```

**Step 2: Container Dependencies**
Comment out Redis dependency:
```yaml
depends_on:
  - db
  # - redis  # Comment to disable Redis dependency
```

**Step 3: Drupal Configuration**
Remove or comment the include line in `settings.php`:
```php
// @include DRUPAL_ROOT . '/sites/default/settings.redis.php';
```

**Step 4: Restart Environment**
```bash
docker compose up -d
```

**✅ Already Configured for Development**
The current setup is already configured for development with Redis disabled. The Redis container remains available for easy re-enabling when needed.

##### ⚡ Performance Optimization

**PhpRedis Extension (Already Installed)**
- High-performance C extension for Redis
- Lower latency compared to Predis
- Better memory efficiency
- Production-ready performance

**Redis Configuration Features:**
- **Cache Bins**: `render`, `page`, `dynamic_page_cache`
- **Key Prefix**: `drupal_` (prevents conflicts in shared Redis instances)
- **TTL Management**: Automatic cache expiration
- **Lock Integration**: Redis-based locking for better concurrency
- **Compression**: Automatic serialization for large objects

**Production Best Practices:**

1. **Memory Management**
```bash
# Monitor memory usage
docker exec drupal-redis-1 redis-cli info memory | grep -E "used_memory_human|maxmemory_human"

# Set memory limits in docker-compose.yml
redis:
  image: redis:7-alpine
  command: redis-server --maxmemory 256mb --maxmemory-policy allkeys-lru
```

2. **Persistence (Optional)**
```bash
# Enable AOF for data persistence
redis:
  command: redis-server --appendonly yes --appendfsync everysec
```

3. **Security**
```bash
# Set Redis password
environment:
  REDIS_PASSWORD: your-secure-password

# Configure in settings.php
$settings['redis.connection']['password'] = getenv('REDIS_PASSWORD');
```

##### 📊 Redis Monitoring & Statistics

**Note**: These commands are available when Redis is enabled in production.

**Real-time Monitoring:**
```bash
# Live cache activity
docker exec drupal-redis-1 redis-cli monitor

# Performance stats
docker exec drupal-redis-1 redis-cli info stats | grep -E "keyspace_hits|keyspace_misses"

# Memory usage
docker exec drupal-redis-1 redis-cli info memory | grep -E "used_memory_human|used_memory_peak_human"

# Key count
docker exec drupal-redis-1 redis-cli dbsize

# Cache hit rate
docker exec drupal-redis-1 redis-cli info stats | awk -F: '/keyspace_hits/ {hits=$2} /keyspace_misses/ {misses=$2} END {if(hits+misses>0) printf "Hit Rate: %.1f%%\n", hits/(hits+misses)*100}'
```

**Cache Management Commands:**
```bash
# Clear all cache
docker exec drupal-redis-1 redis-cli flushdb

# Clear specific cache bin
docker exec drupal-redis-1 redis-cli eval "return redis.call('del', unpack(redis.call('keys', ARGV[1])))" 0 "drupal.redis.*.render:*"

# View cache keys by pattern
docker exec drupal-redis-1 redis-cli keys "drupal.redis.*.render:*" | head -10
```

**Performance Metrics:**
- **Target Hit Rate**: >80% (when enabled)
- **Memory Usage**: <256MB for small-medium sites
- **Response Time**: <5ms for cache reads
- **TTL**: Automatic expiration based on cache type

##### 🔧 Troubleshooting

**Common Issues:**

1. **Cache Not Working**
   - Verify Redis container is running: `docker compose ps`
   - Check Redis connection: `docker exec drupal-redis-1 redis-cli ping`
   - Verify environment variables: `docker exec drupal-drupal-1 env | grep REDIS`

2. **Performance Degradation**
   - Check memory usage: High memory usage may cause swapping
   - Monitor hit rate: Low hit rate indicates cache misses
   - Review cache configuration: Verify cache bins are properly configured

3. **Connection Issues**
   - Verify network connectivity between containers
   - Check Redis password if authentication is enabled
   - Ensure Redis extension is loaded in PHP

**Debug Commands:**
```bash
# Test Redis connection from Drupal container
docker exec drupal-drupal-1 php -r "var_dump(extension_loaded('redis'));"

# Check Redis extension version
docker exec drupal-drupal-1 php -i | grep -A 10 "Redis Support"

# Verify cache configuration
docker exec drupal-drupal-1 /opt/drupal/vendor/bin/drush ev "var_dump(\Drupal::cache()->get('test_key'));"
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

**🔧 Required: Permission Setup for Git Operations**
Due to Docker container file ownership, run this command after configuration export to enable git operations:
```bash
# Required: Fix configuration directory permissions for git
docker exec drupal-drupal-1 chown -R 1000:1000 /opt/drupal/web/sites/default/config/
docker exec drupal-drupal-1 chmod -R 644 /opt/drupal/web/sites/default/config/
```

This ensures the configuration files created by the container are accessible for git operations on the host machine.

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

## 🔐 Encryption

Field-level encryption is implemented for the UserConfidentialData module. See [ENCRYPTION_GUIDE.md](web/modules/custom/user_confidential_data/ENCRYPTION_GUIDE.md) for setup and usage instructions.

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
