CREATE TABLE posts (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug         VARCHAR(200) NOT NULL,
    title        VARCHAR(200) NOT NULL,
    description  VARCHAR(500) NOT NULL,
    body         MEDIUMTEXT   NOT NULL,
    image        VARCHAR(255) NULL,
    views        INT UNSIGNED NOT NULL DEFAULT 0,
    published_at DATETIME     NOT NULL,
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY posts_slug_unique (slug),
    KEY posts_published_at_index (published_at),
    KEY posts_views_index (views)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
