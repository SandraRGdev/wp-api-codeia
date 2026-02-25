# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

WP API Codeia is a professional WordPress plugin that transforms WordPress into a configurable, customizable REST API. The plugin is currently in early development stage - architecture is defined in [ARCHITECTURE.md](ARCHITECTURE.md) but implementation has not begun.

## Architecture

The plugin follows a **modular service-oriented architecture** with clear separation of concerns:

```
Core/          → Infrastructure (DI container, service providers, config)
Auth/          → Authentication strategies (JWT, API Keys, App Passwords)
API/           → Dynamic REST endpoints, controllers, response formatters
Schema/        → CPT/field detection (ACF, JetEngine, MetaBox integrations)
Permissions/   → Role-based, endpoint-level, and field-level access control
Upload/        → Secure file upload with validation and processing
Docs/          → OpenAPI/Swagger documentation generation
Admin/         → WordPress dashboard interface
Utils/         → Logging, caching, helper functions
```

### Key Architectural Patterns

- **Service Layer**: Controllers delegate business logic to service classes
- **Strategy Pattern**: Multiple authentication strategies (JWT/API Key/App Password) are swappable
- **Repository Pattern**: Data access abstracted through repository classes
- **Middleware Pipeline**: Requests flow through Auth → Authorization → Rate Limit → Validation → Controller
- **Event System**: Custom event dispatcher for hook-based extensibility

### Critical Design Decisions

1. **No global state** - Use dependency injection container for all service classes
2. **Lazy module loading** - Each module (Auth, API, Schema, etc.) loads only its required files
3. **Schema-first approach** - All CPTs and fields are introspected at runtime, cached, then used to generate endpoints dynamically
4. **Permission layering**: Check happens in order: endpoint → operation → ownership → field-level
5. **Cache hierarchy**: Object cache (Redis/Memcached) → Transients → Database

## WordPress-Specific Context

- **Namespace**: All REST routes use `/wp-custom-api/v1/` prefix
- **Capabilities**: Plugin respects WordPress user roles/capabilities, extends them with custom permission matrices stored in options
- **Hook Integration**: Plugin hooks into `rest_api_init` for route registration, `registered_post_types` for schema detection
- **Database Tables**: Creates custom tables (`wp_codeia_tokens`, `wp_codeia_api_keys`) for auth storage

## Important Files

| File | Purpose |
|------|---------|
| [ARCHITECTURE.md](ARCHITECTURE.md) | Complete technical architecture - read before implementing |
| bootstrap.php | Plugin entry point, loads autoloader, initializes container |
| Core/Container.php | Dependency injection container, service bindings |
| Core/ServiceProvider.php | Registers all plugin services |

## Development Notes

- Plugin requires WordPress 5.8+ and PHP 7.4+ (PHP 8.0+ recommended)
- Uses Composer for autoloading (PSR-4)
- ACF, JetEngine, MetaBox integrations are optional - plugin works with any CPT registered via `register_post_type()`
- Schema is rebuilt on plugin activation, when CPTs change, or via WP-CLI: `wp codeia schema rebuild`

## Testing

No tests exist yet. When implemented:
- Unit tests go in `tests/Unit/`
- Integration tests in `tests/Integration/`
- Run with `composer test` (once configured)
