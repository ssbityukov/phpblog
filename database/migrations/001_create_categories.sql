CREATE TABLE categories (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug        VARCHAR(160) NOT NULL,
    name        VARCHAR(160) NOT NULL,
    description TEXT         NOT NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY categories_slug_unique (slug)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
