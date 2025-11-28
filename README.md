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
- Active configuration: `web/sites/default/files/config_*/`
- Sync directory: Configured automatically

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