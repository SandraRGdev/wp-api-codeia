-- API Keys table for WP API Codeia
-- Stores API keys for authentication

CREATE TABLE {prefix}codeia_api_keys (
    api_key_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    api_key varchar(191) NOT NULL,
    user_id bigint(20) UNSIGNED NOT NULL,
    name varchar(255) NOT NULL,
    scopes text NOT NULL,
    last_used datetime DEFAULT NULL,
    last_ip varchar(45) DEFAULT NULL,
    created_at datetime NOT NULL,
    expires_at datetime DEFAULT NULL,
    rate_limit int DEFAULT 1000,
    rate_limit_window int DEFAULT 3600,
    is_revoked tinyint(1) DEFAULT 0,
    PRIMARY KEY  (api_key_id),
    UNIQUE KEY api_key (api_key),
    KEY user_id (user_id),
    KEY is_revoked (is_revoked),
    KEY expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add indexes for performance
ALTER TABLE {prefix}codeia_api_keys ADD INDEX idx_user_revoked (user_id, is_revoked);
ALTER TABLE {prefix}codeia_api_keys ADD INDEX idx_expires_revoked (expires_at, is_revoked);
