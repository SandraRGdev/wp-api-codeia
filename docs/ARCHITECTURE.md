# WP API Codeia - Documentación Técnica de Arquitectura

## Tabla de Contenidos

1. [Introducción](#1-introducción)
2. [Visión General del Sistema](#2-visión-general-del-sistema)
3. [Sistema de Autenticación](#3-sistema-de-autenticación)
4. [Sistema de Endpoints Dinámicos](#4-sistema-de-endpoints-dinámicos)
5. [Detección Automática de CPT y Campos](#5-detección-automática-de-cpt-y-campos)
6. [Sistema de Permisos por Rol](#6-sistema-de-permisos-por-rol)
7. [Sistema de Subida de Imágenes](#7-sistema-de-subida-de-imágenes)
8. [Generación Automática de Swagger](#8-generación-automática-de-swagger)
9. [Rewrite Rules Automáticas](#9-rewrite-rules-automáticas)
10. [Dashboard Administrativo](#10-dashboard-administrativo)
11. [Arquitectura Técnica del Plugin](#11-arquitectura-técnica-del-plugin)
12. [Seguridad](#12-seguridad)
13. [Rendimiento](#13-rendimiento)
14. [Escalabilidad Futura](#14-escalabilidad-futura)

---

## 1. Introducción

### 1.1 Propósito del Documento

Este documento define la arquitectura técnica completa de **WP API Codeia**, un plugin profesional de WordPress que transforma una instalación estándar de WordPress en una API REST configurable y altamente personalizable.

### 1.2 Alcance

El documento cubre:

- Arquitectura de módulos y componentes
- Flujos de autenticación y autorización
- Sistema de endpoints dinámicos
- Detección automática de tipos de contenido
- Generación de documentación OpenAPI
- Estrategias de seguridad y rendimiento

### 1.3 Stack Tecnológico

```
┌─────────────────────────────────────────────────────────────┐
│                    WordPress Core                          │
├─────────────────────────────────────────────────────────────┤
│  WordPress REST API Infrastructure │ REST Authentication    │
├─────────────────────────────────────────────────────────────┤
│                    WP API Codeia                           │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐       │
│  │  Auth    │ │ Endpoints│ │ Schema   │ │  Admin   │       │
│  │  Module  │ │  Module  │ │  Module  │ │  Module  │       │
│  └──────────┘ └──────────┘ └──────────┘ └──────────┘       │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐       │
│  │  Cache   │ │  Logger  │ │ Permissions│  Docs   │       │
│  │  Layer   │ │  Module  │ │  Module  │ │  Module  │       │
│  └──────────┘ └──────────┘ └──────────┘ └──────────┘       │
├─────────────────────────────────────────────────────────────┤
│              Object Cache (Redis/Memcached)                │
├─────────────────────────────────────────────────────────────┤
│                 MySQL / MariaDB Database                   │
└─────────────────────────────────────────────────────────────┘
```

---

## 2. Visión General del Sistema

### 2.1 Arquitectura de Alto Nivel

```
┌────────────────────────────────────────────────────────────────────────┐
│                           CLIENTE API                                 │
│                    (Web, Mobile, Third-party)                        │
└─────────────────────────────┬────────────────────────────────────────┘
                              │
                              │ HTTPS Request
                              ▼
┌────────────────────────────────────────────────────────────────────────┐
│                      NGINX / APACHE                                 │
│                         (SSL Termination)                             │
└─────────────────────────────┬────────────────────────────────────────┘
                              │
                              ▼
┌────────────────────────────────────────────────────────────────────────┐
│                      WORDPRESS CORE                                   │
│  ┌──────────────────────────────────────────────────────────────┐    │
│  │                  WP API Codeia Plugin                         │    │
│  │                                                               │    │
│  │  ┌──────────┐    ┌──────────┐    ┌──────────┐    ┌────────┐ │    │
│  │  │ Request  │───▶│ Auth     │───▶│ Route    │───▶│Policy  │ │    │
│  │  │ Intercep │    │ Manager  │    │ Matcher  │    │Engine  │ │    │
│  │  └──────────┘    └──────────┘    └──────────┘    └────────┘ │    │
│  │                                    │                           │    │
│  │                                    ▼                           │    │
│  │  ┌──────────┐    ┌──────────┐    ┌──────────┐    ┌────────┐ │    │
│  │  │ Response │◀───│ Builder  │◀───│ Endpoint │◀───│ Schema │ │    │
│  │  │ Formatter│    │          │    │ Handler  │    │ Cache  │ │    │
│  │  └──────────┘    └──────────┘    └──────────┘    └────────┘ │    │
│  └──────────────────────────────────────────────────────────────┘    │
└────────────────────────────────────────────────────────────────────────┘
```

### 2.2 Flujo de Petición

```
1. CLIENT REQUEST
   │
   ├─▶ Method: GET/POST/PUT/PATCH/DELETE
   ├─▶ Headers: Authorization, Content-Type
   ├─▶ Body: JSON payload
   │
2. WORDPRESS BOOTSTRAP
   │
   ├─▶ Load wp-config.php
   ├─▶ Load wp-settings.php
   ├─▶ Load plugins
   │
3. WP API CODEIA INIT (plugins_loaded)
   │
   ├─▶ Load configuration
   ├─▶ Register rewrite rules
   ├─▶ Register REST routes
   │
4. AUTHENTICATION MIDDLEWARE
   │
   ├─▶ Extract credentials
   ├─▶ Validate token/api-key
   ├─▶ Verify user exists
   ├─▶ Check if token revoked
   ├─▶ Validate expiration
   │
5. AUTHORIZATION MIDDLEWARE
   │
   ├─▶ Get user role
   ├─▶ Check endpoint permission
   ├─▶ Check field-level permissions
   ├─▶ Check rate limits
   │
6. REQUEST PROCESSING
   │
   ├─▶ Parse query parameters
   ├─▶ Apply filters
   ├─▶ Execute WP_Query
   ├─▶ Transform response
   │
7. RESPONSE
   │
   ├─▶ Format to JSON
   ├─▶ Apply field filtering
   ├─▶ Set pagination headers
   ├─▶ Log request
```

### 2.3 Diseño Modular

El plugin sigue una arquitectura modular basada en responsabilidades:

```
wp-api-codeia/
│
├── Core/                    # Infraestructura base
│   ├── Container.php        # Dependency Injection Container
│   ├── ServiceProvider.php  # Registro de servicios
│   ├── Bootstrapper.php     # Inicialización
│   └── Config.php           # Gestión de configuración
│
├── Auth/                    # Módulo de autenticación
│   ├── Manager/             # Gestión de credenciales
│   ├── Strategy/            # Estrategias de autenticación
│   ├── Middleware/          # Middleware de autenticación
│   └── Token/               # Gestión de tokens
│
├── API/                     # Módulo de endpoints
│   ├── Router/              # Enrutador dinámico
│   ├── Controller/          # Controladores base
│   ├── Response/            # Formateadores de respuesta
│   └── Validator/           # Validadores de entrada
│
├── Schema/                  # Módulo de esquema
│   ├── Detector/            # Detectores de CPT y campos
│   ├── Builder/             # Constructores de esquema
│   ├── Cache/               # Caché de esquema
│   └── ACF/                 # Integración ACF
│   ├── JetEngine/           # Integración JetEngine
│   └── MetaBox/             # Integración MetaBox
│
├── Permissions/             # Módulo de permisos
│   ├── Manager/             # Gestor de permisos
│   ├── Role/                # Permisos por rol
│   ├── Endpoint/            # Permisos por endpoint
│   └── Field/               # Permisos por campo
│
├── Admin/                   # Módulo de administración
│   ├── Pages/               # Páginas del dashboard
│   ├── Settings/            # Configuración
│   ├── Assets/              # CSS/JS del admin
│   └── AJAX/                # Handlers AJAX
│
├── Docs/                    # Módulo de documentación
│   ├── OpenAPI/             # Generador OpenAPI
│   ├── SwaggerUI/           # Interfaz Swagger
│   └── Cache/               # Caché de docs
│
├── Upload/                  # Módulo de subida
│   ├── Handler/             # Procesador de subidas
│   ├── Validator/           # Validación de archivos
│   └── Security/            # Protecciones de seguridad
│
└── Utils/                   # Utilidades
    ├── Logger/              # Sistema de logging
    ├── Cache/               # Capa de caché
    └── Helpers/             # Funciones helper
```

---

## 3. Sistema de Autenticación

### 3.1 Arquitectura del Módulo de Autenticación

```
┌────────────────────────────────────────────────────────────────────────┐
│                        AUTHENTICATION LAYER                            │
│                                                                        │
│  ┌────────────────────────────────────────────────────────────────┐  │
│  │                    Auth Manager                                │  │
│  │  - Select authentication strategy                              │  │
│  │  - Coordinate authentication flow                              │  │
│  │  - Manage authentication state                                 │  │
│  └────────────────────────────────────────────────────────────────┘  │
│                                 │                                     │
│                                 ▼                                     │
│  ┌────────────────┐  ┌────────────────┐  ┌────────────────┐          │
│  │  JWT Strategy  │  │  API Key       │  │  App Password  │          │
│  │                │  │  Strategy      │  │  Strategy      │          │
│  │  - Encode/     │  │                │  │                │          │
│  │    Decode      │  │  - Validate     │  │  - Native WP   │          │
│  │  - Verify      │  │    key format   │  │    validation  │          │
│  │  - Refresh     │  │  - Check DB     │  │  - Rate limit   │          │
│  └────────────────┘  └────────────────┘  └────────────────┘          │
│           │                    │                    │                 │
│           └────────────────────┼────────────────────┘                 │
│                                ▼                                      │
│  ┌────────────────────────────────────────────────────────────────┐  │
│  │                    Token Store                                  │  │
│  │  - wp_codeia_tokens table                                       │  │
│  │  - wp_codeia_api_keys table                                     │  │
│  │  - wp_usermeta for refresh tokens                               │  │
│  └────────────────────────────────────────────────────────────────┘  │
│                                                                        │
└────────────────────────────────────────────────────────────────────────┘
```

### 3.2 Estrategias de Autenticación

#### 3.2.1 JWT (JSON Web Tokens)

**Configuración JWT:**

| Parámetro | Descripción | Valor por defecto |
|-----------|-------------|-------------------|
| `jwt_algorithm` | Algoritmo de firma | RS256 |
| `jwt_access_ttl` | Tiempo de vida access token | 3600s (1 hora) |
| `jwt_refresh_ttl` | Tiempo de vida refresh token | 2592000s (30 días) |
| `jwt_lease_ttl` | Tiempo de gracia para renovación | 300s (5 minutos) |
| `jwt_issuer` | Emisor del token | `wp-api-codeia` |
| `jwt_audience` | Audiencia del token | `wp-api-v1` |
| `jwt_key_rotation` | Rotación de claves | Mensual |
| `jwt_storage` | Almacenamiento de blacklist | Database |
| `jwt_blacklist_ttl` | Tiempo de vida en blacklist | 86400s (1 día) |

#### 3.2.2 API Keys Personalizadas

**Estructura de API Key:**

```
Formato: wack_{site_id}_{user_id}_{random}_{checksum}

Ejemplo: wack_1_42_a3f9e7d2c1b4f8a6_42
```

**Tabla de base de datos para API Keys:**

```sql
CREATE TABLE wp_codeia_api_keys (
    api_key_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    api_key VARCHAR(191) NOT NULL,
    user_id BIGINT(20) UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    scopes TEXT NOT NULL,
    last_used DATETIME DEFAULT NULL,
    last_ip VARCHAR(45) DEFAULT NULL,
    created_at DATETIME NOT NULL,
    expires_at DATETIME DEFAULT NULL,
    rate_limit INT DEFAULT 1000,
    rate_limit_window INT DEFAULT 3600,
    is_revoked TINYINT(1) DEFAULT 0,
    PRIMARY KEY (api_key_id),
    UNIQUE KEY (api_key),
    KEY (user_id),
    KEY (is_revoked),
    KEY (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 3.3 Endpoints de Autenticación

```
POST /wp-custom-api/v1/auth/login
POST /wp-custom-api/v1/auth/refresh
POST /wp-custom-api/v1/auth/logout
GET  /wp-custom-api/v1/auth/verify
```

---

## 4. Sistema de Endpoints Dinámicos

### 4.1 Estructura de Endpoints

Para cada post type habilitado se generan automáticamente:

```
Collection Endpoints:
GET    /wp-custom-api/v1/{post_type}
POST   /wp-custom-api/v1/{post_type}

Single Item Endpoints:
GET    /wp-custom-api/v1/{post_type}/{id}
PUT    /wp-custom-api/v1/{post_type}/{id}
PATCH  /wp-custom-api/v1/{post_type}/{id}
DELETE /wp-custom-api/v1/{post_type}/{id}

Meta Endpoints:
GET    /wp-custom-api/v1/{post_type}/{id}/meta
POST   /wp-custom-api/v1/{post_type}/{id}/meta
PUT    /wp-custom-api/v1/{post_type}/{id}/meta/{key}
DELETE /wp-custom-api/v1/{post_type}/{id}/meta/{key}

Taxonomy Endpoints:
GET    /wp-custom-api/v1/{post_type}/{id}/terms/{taxonomy}
POST   /wp-custom-api/v1/{post_type}/{id}/terms/{taxonomy}
```

### 4.2 Parámetros de Consulta Estándar

```
GET /wp-custom-api/v1/posts?{parameters}

Pagination:
  page          int     Default: 1
  per_page      int     Default: 10, Max: 100
  offset        int     Skip N items

Filtering:
  search        string  Full-text search
  status        string  Comma-separated: publish,draft,...
  author        int     Filter by author ID
  author__in    string  Comma-separated author IDs
  after         string  ISO 8601 date
  before        string  ISO 8601 date
  meta_key      string  Meta key name
  meta_value    string  Meta value
  meta_query    string  JSON encoded meta query

Sorting:
  orderby       string  date, modified, title, slug, author, relevance
  order         string  ASC, DESC

Field Selection:
  fields        string  Comma-separated field names
  exclude       string  Comma-separated fields to exclude

Embedding:
  _embed        bool    Include related resources
  _embed_media  bool    Include featured media
  _embed_terms  bool    Include all terms
  _embed_author bool    Include author info
```

---

## 5. Detección Automática de CPT y Campos

### 5.1 Motor de Introspección

```
┌────────────────────────────────────────────────────────────────────────┐
│                       SCHEMA DETECTION ENGINE                          │
│                                                                        │
│  ┌────────────────────────────────────────────────────────────────┐  │
│  │                    WordPress Core Hooks                        │  │
│  │  - registered_post_types                                       │  │
│  │  - registered_taxonomies                                       │  │
│  │  - registered_meta_keys                                        │  │
│  └────────────────────────────────────────────────────────────────┘  │
│                              │                                         │
│                              ▼                                         │
│  ┌────────────────────────────────────────────────────────────────┐  │
│  │                    Post Type Detector                          │  │
│  │  1. Scan get_post_types()                                      │  │
│  │  2. Filter built-in types (if excluded)                         │  │
│  │  3. Get post type object                                        │  │
│  │  4. Extract metadata                                            │  │
│  │  5. Detect registration source                                  │  │
│  └────────────────────────────────────────────────────────────────┘  │
│                              │                                         │
│                              ▼                                         │
│  ┌────────────────────────────────────────────────────────────────┐  │
│  │                    Field Detector                               │  │
│  │  ├── Native Fields (title, content, excerpt, etc)              │  │
│  │  ├── Meta Fields (get_post_meta)                               │  │
│  │  ├── ACF Fields (acf_get_fields)                               │  │
│  │  ├── JetEngine Fields (jet_engine)                              │  │
│  │  ├── MetaBox Fields (rwmb_meta)                                │  │
│  │  ├── Carbon Fields (carbon_fields)                             │  │
│  │  └── Custom REST Fields (register_rest_field)                  │  │
│  └────────────────────────────────────────────────────────────────┘  │
│                              │                                         │
│                              ▼                                         │
│  ┌────────────────────────────────────────────────────────────────┐  │
│  │                    Schema Cache                                │  │
│  │  - Store compiled schema                                       │  │
│  │  - Enable fast lookup                                          │  │
│  │  - Invalidate on changes                                       │  │
│  └────────────────────────────────────────────────────────────────┘  │
│                                                                        │
└────────────────────────────────────────────────────────────────────────┘
```

### 5.2 Metadata Extraída por CPT

| Campo | Fuente | Descripción |
|-------|--------|-------------|
| `name` | `$post_type->name` | Slug del post type |
| `label` | `$post_type->label` | Etiqueta singular |
| `description` | `$post_type->description` | Descripción |
| `public` | `$post_type->public` | Es público |
| `rest_base` | `$post_type->rest_base` | Base URL REST |
| `supports` | `$post_type->supports` | Características soportadas |
| `taxonomies` | `get_object_taxonomies()` | Taxonomías asociadas |

---

## 6. Sistema de Permisos por Rol

### 6.1 Matriz de Permisos

```
Storage Structure:
{
  "administrator": {
    "read": true,
    "create": true,
    "update": true,
    "delete": true,
    "fields": {
      "allowed": ["*"],
      "denied": []
    }
  },
  "editor": {
    "read": true,
    "create": true,
    "update": true,
    "delete": false,
    "own_only": true,
    "fields": {
      "allowed": ["*"],
      "denied": ["sensitive_field"]
    }
  },
  "subscriber": {
    "read": true,
    "create": false,
    "update": false,
    "delete": false,
    "fields": {
      "allowed": ["id", "title", "excerpt", "date"],
      "denied": []
    }
  }
}
```

### 6.2 Tabla de Capacidades

| Rol | read | create | update | delete | publish | upload_media |
|-----|------|--------|--------|--------|---------|--------------|
| **Administrator** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Editor** | ✅ | ✅ | ✅ | ⚠️ | ✅ | ✅ |
| **Author** | ✅ | ✅ | ⚠️ | ⚠️ | ✅ | ⚠️ |
| **Contributor** | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Subscriber** | ⚠️ | ❌ | ❌ | ❌ | ❌ | ❌ |

**Leyenda:** ✅ Permitido | ⚠️ Solo propio | ❌ Denegado

---

## 7. Sistema de Subida de Imágenes

### 7.1 Endpoint de Subida

```
POST /wp-custom-api/v1/media

Request Headers:
Content-Type: multipart/form-data
Authorization: Bearer {access_token}

Response 201:
{
  "id": 123,
  "date": "2024-01-15T10:00:00Z",
  "slug": "my-image",
  "media_type": "image",
  "mime_type": "image/jpeg",
  "media_details": {
    "width": 1920,
    "height": 1080,
    "sizes": { ... }
  },
  "source_url": "https://.../my-image.jpg"
}
```

### 7.2 Validaciones

**Tipos MIME Permitidos:**
- Images: image/jpeg, image/png, image/gif, image/webp, image/avif
- Documents: application/pdf
- Video: video/mp4, video/webm

**Límites por Rol:**

| Rol | Per File | Per Hour | Per Day |
|-----|----------|----------|---------|
| Admin | Unlimited | Unlimited | Unlimited |
| Editor | 50MB | 500MB | 2GB |
| Author | 10MB | 100MB | 500MB |

---

## 8. Generación Automática de Swagger

### 8.1 Endpoint de Documentación

```
GET /wp-custom-api/v1/docs        → OpenAPI JSON spec
GET /wp-custom-api/v1/docs.yaml   → OpenAPI YAML spec
GET /wp-custom-api/v1/docs/swagger → Swagger UI HTML
GET /wp-custom-api/v1/docs/redoc  → ReDoc HTML
```

### 8.2 Actualización Automática

```
Triggers for Rebuild:
- ACF field group saved
- JetEngine meta box updated
- Post type registration change
- Plugin settings changed
- Manual trigger (admin)
- Daily scheduled event
```

---

## 9. Rewrite Rules Automáticas

### 9.1 Estructura de Rutas

```
URL Structure:
/wp-custom-api/v{version}/{resource}[/{id}][/{sub-resource}]

Examples:
- /wp-custom-api/v1/posts
- /wp-custom-api/v1/posts/123
- /wp-custom-api/v1/posts/123/meta
- /wp-custom-api/v1/products/456/relations
```

### 9.2 Prevención de Conflictos

```
Route Conflict Detection:
1. Scan existing REST routes before registration
2. Check for exact match
3. Check for pattern overlap (regex)
4. Validate against WordPress reserved routes
5. Warn if conflict detected
6. Provide option to override (with disclaimer)
```

---

## 10. Dashboard Administrativo

### 10.1 Estructura del Dashboard

```
WP API Codeia Menu:
├── API Codeia
│   ├── Dashboard          → Overview & status
│   ├── Authentication     → Auth methods, tokens, API keys
│   ├── Endpoints          → Post types, fields, routes
│   ├── Permissions        → Roles, capabilities, access control
│   ├── Media Upload       → Upload settings, limits
│   ├── Documentation      → OpenAPI spec, Swagger UI
│   ├── Logs               → Request logs, error logs
│   ├── Settings           → General configuration
│   └── Tools              → Rebuild schema, flush cache
```

### 10.2 Seguridad del Dashboard

- Capability required: `manage_options`
- CSRF protection with nonces
- Input sanitization
- AJAX security
- Screen options per user

---

## 11. Arquitectura Técnica del Plugin

### 11.1 Estructura de Carpetas

```
wp-api-codeia/
├── Core/                  # Infrastructure layer
│   ├── Container.php
│   ├── ServiceProvider.php
│   ├── Config/
│   └── Interfaces/
├── Auth/                  # Authentication module
│   ├── Manager.php
│   ├── Strategies/
│   ├── Middleware/
│   └── Tokens/
├── API/                   # API module
│   ├── Router.php
│   ├── Controllers/
│   ├── Response/
│   └── Validators/
├── Schema/                # Schema module
│   ├── Detector/
│   ├── Integrations/
│   ├── Builder/
│   └── Cache/
├── Permissions/           # Permissions module
│   ├── Manager.php
│   ├── Roles/
│   ├── Endpoints/
│   └── Fields/
├── Upload/                # Upload module
│   ├── Handler.php
│   ├── Validators/
│   └── Security/
├── Docs/                  # Documentation module
│   ├── OpenAPI/
│   └── SwaggerUI/
├── Admin/                 # Admin interface
│   ├── Pages/
│   ├── Assets/
│   └── AJAX/
└── Utils/                 # Utilities
    ├── Logger/
    ├── Cache/
    └── Helpers/
```

### 11.2 Patrón Arquitectónico

- Service Layer Pattern
- Strategy Pattern (Authentication)
- Repository Pattern (Data Access)
- Factory Pattern (Controllers)
- Observer Pattern (Event System)
- Decorator Pattern (Caching)
- Middleware Pattern (Request Pipeline)

---

## 12. Seguridad

### 12.1 Headers de Seguridad

```
Security Headers (sent on all responses):
X-Content-Type-Options: nosniff
X-Frame-Options: SAMEORIGIN
X-XSS-Protection: 1; mode=block
Strict-Transport-Security: max-age=31536000; includeSubDomains
Content-Security-Policy: default-src 'self'
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: geolocation=(), microphone=(), camera=()
```

### 12.2 Validación de Entrada

- Integer: is_numeric(), range validation, cast to (int)
- String: is_string(), length validation, sanitize_text_field()
- Email: is_email(), sanitize_email()
- URL: esc_url_raw(), validate URL format
- Date/DateTime: Validate ISO 8601 format
- Array: is_array(), validate each element
- JSON: json_decode() with error checking

---

## 13. Rendimiento

### 13.1 Cache Keys

```
Format: codeia:{category}:{identifier}:{version}

Schema Cache:
- codeia:schema:post_types:{hash}
- codeia:schema:post_type:{post_type}:{hash}
- codeia:schema:openapi:{hash}

Data Cache:
- codeia:post:{id}:{fields_hash}
- codeia:posts:{query_hash}:{page}
- codeia:user:{id}:{fields_hash}

Permission Cache:
- codeia:permissions:user:{id}:{hash}
- codeia:permissions:role:{role}:{hash}
```

### 13.2 Lazy Loading

```
Default (no _embed):
- ID only for relationships
- IDs for media
- Minimal meta data
- Fast initial response

With _embed=true:
- Full relationship data
- Full media objects
- All meta fields
- Larger response but complete
```

---

## 14. Escalabilidad Futura

### 14.1 Multi-Site Support

- Network-activated plugin
- Shared configuration network-wide
- Per-site override capability
- Tokens include site_id claim
- Cross-site tokens (optional)

### 14.2 Versionado de API

```
URL-Based Versioning:
/wp-custom-api/v1/ → Current stable
/wp-custom-api/v2/ → Beta version
/wp-custom-api/v3/ → Future versions

Support Timeline:
- Stable: 12 months minimum
- Maintenance: 6 months after next stable
- Deprecated: 3 months before removal
- Total: ~21 months support per version
```

### 14.3 Headless WordPress Compatibility

- Complete API coverage
- Frontend-agnostic features
- CORS configuration
- Webhook support for builds
- Preview mode authentication
- Asset CDN integration

---

## 15. Apéndices

### 15.1 Códigos de Error

```
Authentication Errors (401):
- codeia_auth_missing      → No credentials provided
- codeia_auth_invalid      → Invalid credentials
- codeia_auth_expired      → Token expired
- codeia_auth_revoked      → Token revoked

Authorization Errors (403):
- codeia_forbidden         → Access denied
- codeia_cannot_read       → Read permission denied
- codeia_cannot_create     → Create permission denied
- codeia_cannot_update     → Update permission denied
- codeia_cannot_delete     → Delete permission denied

Validation Errors (400):
- codeia_validation_failed → Validation error
- codeia_missing_param     → Required parameter missing
- codeia_invalid_param     → Invalid parameter value

Rate Limit Errors (429):
- codeia_rate_limited      → Too many requests
```

### 15.2 WP-CLI Commands

```
wp codeia status                    # Show plugin status
wp codeia schema rebuild            # Rebuild schema cache
wp codeia docs generate             # Generate OpenAPI docs
wp codeia cache flush               # Flush all cache
wp codeia token generate <user_id>  # Generate new token
wp codeia config export             # Export configuration
wp codeia config import <file>      # Import configuration
```

### 15.3 Checklist de Desarrollo

**Phase 1: Core Infrastructure**
- [ ] Autoloader setup
- [ ] Dependency container
- [ ] Service provider system
- [ ] Configuration system
- [ ] Event dispatcher
- [ ] Logger system
- [ ] Cache layer

**Phase 2: Authentication**
- [ ] JWT strategy
- [ ] API Key strategy
- [ ] App Password strategy
- [ ] Token storage
- [ ] Token blacklist

**Phase 3: Schema Detection**
- [ ] Post type detector
- [ ] Native field detector
- [ ] ACF integration
- [ ] JetEngine integration
- [ ] MetaBox integration

**Phase 4: API Layer**
- [ ] Router
- [ ] Base controller
- [ ] Post type controller
- [ ] Response formatters
- [ ] Validators

**Phase 5: Permissions**
- [ ] Permission manager
- [ ] Role-based permissions
- [ ] Endpoint permissions
- [ ] Field permissions

---

## Conclusión

Este documento técnico proporciona una arquitectura completa y detallada para el desarrollo de **WP API Codeia**.

### Principios de Diseño

1. **Modularidad**: Cada componente tiene una responsabilidad clara
2. **Extensibilidad**: Hooks, filtros y eventos para integraciones
3. **Seguridad**: Múltiples capas de protección
4. **Rendimiento**: Caché inteligente y optimización
5. **Escalabilidad**: Preparado para multi-site y headless
6. **Mantenibilidad**: Código organizado y documentado

### Roadmap de Versiones

**v1.0.0 - Foundation (MVP)**
- JWT y API key authentication
- Endpoints dinámicos por post type
- Detección ACF + nativos
- Permisos por rol
- Subida de archivos
- OpenAPI 3.0

**v1.1.0 - Enhanced Integrations**
- JetEngine, MetaBox, Carbon Fields
- Permisos por campo
- Advanced query filters

**v1.2.0 - Headless Focus**
- Webhooks, CORS, Preview mode

**v2.0.0 - Platform Expansion**
- OAuth2, GraphQL, SaaS mode

---

**Documento Versión:** 1.0
**Fecha de Creación:** 2024-01-15
**Estado:** Aprobación Pendiente
