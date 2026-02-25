# WP API Codeia

A professional WordPress plugin that transforms WordPress into a configurable REST API.

## Version

1.0.0

## Description

WP API Codeia provides a complete REST API solution for WordPress with:
- Dynamic endpoints for all post types and taxonomies
- JWT, API Key, and App Password authentication
- Role-based permissions system
- Automatic OpenAPI 3.0 documentation
- Full admin dashboard

## Features

### Authentication
- **JWT**: RS256 algorithm with access/refresh tokens
- **API Keys**: Format `wack_{site_id}_{user_id}_{random}_{checksum}`
- **App Passwords**: Compatible with WordPress native and custom implementation

### API Endpoints
- Dynamic CRUD endpoints for all post types
- Dynamic CRUD endpoints for all taxonomies
- Media upload and management
- Schema discovery endpoints
- OpenAPI documentation endpoints

### Permissions
- Role-based permission matrix
- Field-level access control
- Rate limiting per role
- Ownership-based access

### Documentation
- Automatic OpenAPI 3.0 specification
- Swagger UI interface
- ReDoc interface
- Embed with shortcodes

### Admin Dashboard
- 8 admin pages with full management
- API statistics and monitoring
- Token and API key management
- Permission configuration
- Request logs

## Requirements

- PHP 7.4 or higher
- WordPress 5.6 or higher
- MySQL 5.6 or higher

## Installation

1. Upload the plugin files to the `/wp-content/plugins/wp-api-codeia/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Configure the API through the 'API Codeia' menu.

## API Endpoints

### Authentication

- `POST /wp-custom-api/v1/auth/login` - User login
- `POST /wp-custom-api/v1/auth/refresh` - Refresh token
- `POST /wp-custom-api/v1/auth/logout` - Logout
- `GET /wp-custom-api/v1/auth/verify` - Verify authentication

### Dynamic Post Type Endpoints

- `GET /wp-custom-api/v1/{post_type}` - List items
- `POST /wp-custom-api/v1/{post_type}` - Create item
- `GET /wp-custom-api/v1/{post_type}/{id}` - Get item
- `PUT /wp-custom-api/v1/{post_type}/{id}` - Update item
- `DELETE /wp-custom-api/v1/{post_type}/{id}` - Delete item
- `GET /wp-custom-api/v1/{post_type}/{id}/meta` - Get meta fields
- `POST /wp-custom-api/v1/{post_type}/{id}/meta` - Update meta fields
- `GET /wp-custom-api/v1/{post_type}/{id}/terms/{taxonomy}` - Get terms
- `POST /wp-custom-api/v1/{post_type}/{id}/terms/{taxonomy}` - Set terms

### Taxonomy Endpoints

- `GET /wp-custom-api/v1/{taxonomy}` - List terms
- `POST /wp-custom-api/v1/{taxonomy}` - Create term
- `GET /wp-custom-api/v1/{taxonomy}/{id}` - Get term
- `PUT /wp-custom-api/v1/{taxonomy}/{id}` - Update term
- `DELETE /wp-custom-api/v1/{taxonomy}/{id}` - Delete term

### Media

- `POST /wp-custom-api/v1/media` - Upload file
- `GET /wp-custom-api/v1/media/{id}` - Get media item

### Documentation

- `GET /wp-custom-api/v1/docs` - OpenAPI JSON spec
- `GET /wp-custom-api/v1/docs/swagger` - Swagger UI
- `GET /wp-custom-api/v1/docs/redoc` - ReDoc
- `GET /wp-custom-api/v1/schema` - API schema
- `GET /wp-custom-api/v1/info` - API information

## Authentication

### JWT

```bash
curl -X POST https://example.com/wp-json/wp-custom-api/v1/auth/login \\
  -H "Content-Type: application/json" \\
  -d '{"username":"admin","password":"password","strategy":"jwt"}'
```

### API Key

```bash
curl -X GET https://example.com/wp-json/wp-custom-api/v1/posts \\
  -H "X-API-Key: wack_1_1_xxx_checksum"
```

### App Password

```bash
curl -X GET https://example.com/wp-json/wp-custom-api/v1/posts \\
  -u "username:application_password"
```

## Response Format

### Success Response

```json
{
  "success": true,
  "data": { /* resource data */ },
  "meta": {
    "timestamp": "2026-02-24T10:30:00Z",
    "request_id": "req_abc123xyz",
    "version": "v1"
  }
}
```

### Error Response

```json
{
  "success": false,
  "error": {
    "code": "codeia_auth_invalid",
    "message": "Invalid authentication credentials"
  }
}
```

## Development

### Running Tests

```bash
composer install
vendor/bin/phpunit
```

### Code Structure

- `src/Core/` - Core infrastructure
- `src/Auth/` - Authentication module
- `src/API/` - API layer and controllers
- `src/Schema/` - Schema detection
- `src/Permissions/` - Permissions system
- `src/Upload/` - File upload handling
- `src/Documentation/` - OpenAPI generator
- `src/Admin/` - Admin dashboard
- `src/Utils/` - Utility classes
- `templates/` - Template files

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history.

## License

GPL v2 or later.

## Credits

Developed with Claude Code (Anthropic)

## Support

For support and documentation, see [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md)
