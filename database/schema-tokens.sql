-- Tokens table for WP API Codeia
-- Stores JWT access and refresh tokens

CREATE TABLE {prefix}codeia_tokens (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    token_id varchar(191) NOT NULL,
    user_id bigint(20) UNSIGNED NOT NULL,
    token_type varchar(20) NOT NULL DEFAULT 'access',
    expires_at datetime DEFAULT NULL,
    created_at datetime NOT NULL,
    PRIMARY KEY  (id),
    UNIQUE KEY token_id (token_id),
    KEY user_id (user_id),
    KEY token_type (token_type),
    KEY expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add indexes for performance
ALTER TABLE {prefix}codeia_tokens ADD INDEX idx_user_expires (user_id, expires_at);
ALTER TABLE {prefix}codeia_tokens ADD INDEX idx_type_expires (token_type, expires_at);
