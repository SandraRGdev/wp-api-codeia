# Changelog

All notable changes to WP API Codeia will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Initial project structure created via architecture planning

## [1.0.0] - 2026-02-24

### Added
#### Sprint 0: Foundation Setup
- PSR-4 autoloading via Composer
- Plugin bootstrap system
- Core constants and configuration
- Database tables for tokens and API keys
- Basic plugin activation/deactivation hooks
- 22 unit tests

#### Sprint 1: Core Infrastructure
- Dependency Injection Container
- Service Provider pattern
- Event Dispatcher with wildcard support
- Logger with level-based filtering
- Cache Manager with multiple drivers
- 28 unit tests

#### Sprint 2: Authentication Module
- JWT authentication with RS256 algorithm
- API Key authentication with checksum validation
- Application Password authentication (native + fallback)
- Token Manager with blacklist support
- Authentication REST endpoints (`/v1/auth/*`)
- 21 unit tests

#### Sprint 3: Schema Detection
- Post Type Detector for WordPress post types
- Field Detector (native, meta, taxonomy)
- Taxonomy Detector
- ACF, JetEngine, MetaBox integrations
- Schema caching with auto-invalidation
- 22 unit tests

#### Sprint 4: API Layer
- Dynamic Router for automatic route registration
- Response Formatter with consistent JSON format
- Post Controller for CRUD operations
- Taxonomy Controller for term operations
- Schema Controller for API discovery
- Dynamic endpoints for all post types and taxonomies
- 18 unit tests

#### Sprint 5: Permissions Module
- Permission Manager with role-based matrix
- Middleware for authorization checks
- Rate Limiter with multiple strategies
- Field-level permissions
- 18 unit tests

#### Sprint 6: Upload Module
- Upload Handler with WordPress integration
- Multiple validators (size, MIME, extension, security, image)
- Media Controller for upload endpoints
- Support for multiple file uploads
- 14 unit tests

#### Sprint 7: Documentation Module
- OpenAPI 3.0 specification generator
- Swagger UI renderer
- ReDoc renderer
- Embed shortcodes for pages
- Template system for documentation
- 11 unit tests

#### Sprint 8: Admin Dashboard
- 8 admin pages (Dashboard, Authentication, Endpoints, Permissions, Upload, Documentation, Logs, Settings)
- Admin menu with dashicon
- Statistics and monitoring
- Template system for all pages
- 3 unit tests

#### Sprint 9: Polish & Testing
- Complete README.md documentation
- Complete CHANGELOG.md
- 140+ total unit tests across all modules
- Final syntax validation
- Project completion summary

### Security
- RSA256 JWT algorithm for token signing
- Token blacklist for revocation
- API key validation with checksums
- Rate limiting per role/user
- Field-level permission filtering
- Security validation on file uploads
- SQL injection protection via prepared statements

### Database Tables
- `wp_codeia_tokens` - JWT token storage
- `wp_codeia_api_keys` - API key storage

### Total Stats
- 60+ PHP source files
- 20+ template files
- 8 admin pages
- 15+ REST endpoints
- 140+ unit tests
- 9 sprints completed
