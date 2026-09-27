-- Metin2 website CMS schema (fresh install). Post-release changes: add 002_*.sql, etc.

CREATE TABLE IF NOT EXISTS admins (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    login VARCHAR(64) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(32) NOT NULL DEFAULT 'super',
    use_custom_acl TINYINT(1) NOT NULL DEFAULT 0,
    totp_secret VARCHAR(255) NULL DEFAULT NULL,
    totp_enabled TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_login (login)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_roles (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug VARCHAR(32) NOT NULL,
    label VARCHAR(64) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_admin_roles_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_totp_recovery_codes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    admin_id INT UNSIGNED NOT NULL,
    code_hash VARCHAR(255) NOT NULL,
    used_at DATETIME NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_admin_totp_recovery_admin (admin_id),
    CONSTRAINT fk_admin_totp_recovery_admin
        FOREIGN KEY (admin_id) REFERENCES admins (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS acl_role_sections (
    role VARCHAR(32) NOT NULL,
    section_id VARCHAR(64) NOT NULL,
    PRIMARY KEY (role, section_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS acl_admin_sections (
    admin_id INT UNSIGNED NOT NULL,
    section_id VARCHAR(64) NOT NULL,
    PRIMARY KEY (admin_id, section_id),
    CONSTRAINT fk_acl_admin_sections_admin
        FOREIGN KEY (admin_id) REFERENCES admins (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS acl_role_resources (
    role VARCHAR(32) NOT NULL,
    resource_id VARCHAR(128) NOT NULL,
    PRIMARY KEY (role, resource_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS acl_admin_resources (
    admin_id INT UNSIGNED NOT NULL,
    resource_id VARCHAR(128) NOT NULL,
    PRIMARY KEY (admin_id, resource_id),
    CONSTRAINT fk_acl_admin_resources_admin
        FOREIGN KEY (admin_id) REFERENCES admins (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(64) NOT NULL,
    setting_value TEXT NOT NULL,
    PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS news (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    body MEDIUMTEXT NOT NULL,
    cover_image VARCHAR(255) NULL,
    author_admin_id INT UNSIGNED NOT NULL,
    author_login VARCHAR(64) NOT NULL,
    status ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
    comments_enabled TINYINT(1) NOT NULL DEFAULT 1,
    views INT UNSIGNED NOT NULL DEFAULT 0,
    published_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    seo_title VARCHAR(70) NULL,
    seo_description VARCHAR(320) NULL,
    seo_og_image VARCHAR(255) NULL,
    PRIMARY KEY (id),
    KEY idx_news_status_published (status, published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS news_comments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    news_id INT UNSIGNED NOT NULL,
    account_id INT UNSIGNED NOT NULL,
    account_login VARCHAR(64) NOT NULL,
    body TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_news_comments_news (news_id, status),
    KEY idx_news_comments_status (status),
    CONSTRAINT fk_news_comments_news FOREIGN KEY (news_id) REFERENCES news (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tickets (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    account_id INT UNSIGNED NOT NULL,
    account_login VARCHAR(64) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    status ENUM('open', 'answered', 'closed') NOT NULL DEFAULT 'open',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tickets_account (account_id),
    KEY idx_tickets_status (status, updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ticket_messages (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    ticket_id INT UNSIGNED NOT NULL,
    author_type ENUM('user', 'admin') NOT NULL,
    author_id INT UNSIGNED NOT NULL,
    author_login VARCHAR(64) NOT NULL,
    body TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_ticket_messages_ticket (ticket_id, created_at),
    CONSTRAINT fk_ticket_messages_ticket FOREIGN KEY (ticket_id) REFERENCES tickets (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ticket_attachments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    ticket_id INT UNSIGNED NOT NULL,
    message_id INT UNSIGNED NOT NULL,
    stored_name VARCHAR(64) NOT NULL,
    original_name VARCHAR(180) NOT NULL,
    mime VARCHAR(64) NOT NULL,
    size_bytes INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_ticket_attachment_stored (stored_name),
    KEY idx_ticket_attachments_ticket (ticket_id),
    KEY idx_ticket_attachments_message (message_id),
    CONSTRAINT fk_ticket_attachments_ticket FOREIGN KEY (ticket_id) REFERENCES tickets (id) ON DELETE CASCADE,
    CONSTRAINT fk_ticket_attachments_message FOREIGN KEY (message_id) REFERENCES ticket_messages (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS item_shop_categories (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    parent_id INT UNSIGNED NULL,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(140) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    enabled TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_item_shop_category_slug (slug),
    KEY idx_item_shop_categories_parent (parent_id, sort_order, id),
    KEY idx_item_shop_categories_sort (enabled, sort_order, id),
    CONSTRAINT fk_item_shop_categories_parent
        FOREIGN KEY (parent_id) REFERENCES item_shop_categories (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS item_shop_products (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    category_id INT UNSIGNED NOT NULL,
    vnum INT UNSIGNED NOT NULL,
    count INT UNSIGNED NOT NULL DEFAULT 1,
    price INT UNSIGNED NOT NULL,
    socket0 INT NOT NULL DEFAULT 0,
    socket1 INT NOT NULL DEFAULT 0,
    socket2 INT NOT NULL DEFAULT 0,
    enabled TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_item_shop_products_category (category_id, enabled, sort_order, id),
    KEY idx_item_shop_products_enabled (enabled, sort_order, id),
    CONSTRAINT fk_item_shop_products_category
        FOREIGN KEY (category_id) REFERENCES item_shop_categories (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS item_shop_orders (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    account_id INT UNSIGNED NOT NULL,
    account_login VARCHAR(30) NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    vnum INT UNSIGNED NOT NULL,
    count INT UNSIGNED NOT NULL,
    price INT UNSIGNED NOT NULL,
    socket0 INT NOT NULL DEFAULT 0,
    socket1 INT NOT NULL DEFAULT 0,
    socket2 INT NOT NULL DEFAULT 0,
    item_award_id INT UNSIGNED NULL,
    cash_debited TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('pending', 'completed', 'failed') NOT NULL DEFAULT 'pending',
    idempotency_key VARCHAR(64) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_item_shop_order_idempotency (idempotency_key),
    KEY idx_item_shop_orders_account (account_id, created_at),
    KEY idx_item_shop_orders_status (status, created_at),
    KEY idx_item_shop_orders_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_audit_log (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    admin_id INT UNSIGNED NOT NULL,
    login VARCHAR(64) NOT NULL,
    action VARCHAR(64) NOT NULL,
    target_type VARCHAR(64) NOT NULL,
    target_id INT UNSIGNED NULL,
    meta JSON NULL,
    ip VARCHAR(45) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_admin_audit_admin (admin_id, created_at),
    KEY idx_admin_audit_action (action, created_at),
    KEY idx_admin_audit_target (target_type, target_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_account_email (
    account_id INT UNSIGNED NOT NULL,
    email VARCHAR(255) NOT NULL,
    verified_at DATETIME NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (account_id),
    KEY idx_cms_account_email_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_email_tokens (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    account_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    type ENUM('verify', 'reset', 'change_email') NOT NULL,
    email VARCHAR(255) NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_cms_email_tokens_hash (token_hash),
    KEY idx_cms_email_tokens_account (account_id, type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_server_channels (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(64) NOT NULL,
    label VARCHAR(128) NOT NULL,
    host VARCHAR(255) NULL,
    port INT UNSIGNED NULL,
    sort_order INT NOT NULL DEFAULT 0,
    enabled TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_downloads (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    category ENUM('client', 'patch', 'tools', 'other') NOT NULL DEFAULT 'client',
    description TEXT NULL,
    external_url VARCHAR(512) NULL,
    stored_name VARCHAR(64) NULL,
    original_name VARCHAR(255) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    enabled TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_cms_downloads_category (category, enabled, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_bans (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    account_id INT UNSIGNED NOT NULL,
    account_login VARCHAR(30) NOT NULL,
    reason VARCHAR(512) NOT NULL,
    expires_at DATETIME NULL,
    created_by_admin_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    lifted_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_cms_bans_account (account_id, lifted_at),
    KEY idx_cms_bans_expires (expires_at, lifted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_cash_packages (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(128) NOT NULL,
    cash_amount INT UNSIGNED NOT NULL,
    price_cents INT UNSIGNED NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'USD',
    sort_order INT NOT NULL DEFAULT 0,
    enabled TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_payments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    account_id INT UNSIGNED NOT NULL,
    account_login VARCHAR(30) NOT NULL,
    package_id INT UNSIGNED NOT NULL,
    provider VARCHAR(32) NOT NULL,
    provider_ref VARCHAR(128) NOT NULL,
    amount_cents INT UNSIGNED NOT NULL,
    currency CHAR(3) NOT NULL,
    cash_amount INT UNSIGNED NOT NULL,
    status ENUM('pending', 'paid', 'failed', 'refunded', 'expired') NOT NULL DEFAULT 'pending',
    credited_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_cms_payments_provider_ref (provider, provider_ref),
    KEY idx_cms_payments_account (account_id, created_at),
    KEY idx_cms_payments_status (status, credited_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_payment_events (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    payment_id INT UNSIGNED NULL,
    provider VARCHAR(32) NOT NULL,
    provider_event_id VARCHAR(191) NOT NULL,
    event_type VARCHAR(64) NOT NULL DEFAULT '',
    headers_json TEXT NOT NULL,
    raw_body MEDIUMTEXT NOT NULL,
    status ENUM('queued', 'processing', 'processed', 'failed', 'rejected') NOT NULL DEFAULT 'queued',
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    available_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_error VARCHAR(255) NULL,
    processed_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_cms_payment_events_provider_event (provider, provider_event_id),
    KEY idx_cms_payment_events_queue (status, available_at, attempts),
    KEY idx_cms_payment_events_payment (payment_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_events (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    body TEXT NOT NULL,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NULL,
    published TINYINT(1) NOT NULL DEFAULT 0,
    published_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    seo_title VARCHAR(70) NULL,
    seo_description VARCHAR(320) NULL,
    seo_og_image VARCHAR(255) NULL,
    PRIMARY KEY (id),
    KEY idx_cms_events_published (published, starts_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_referral_codes (
    account_id INT UNSIGNED NOT NULL,
    code VARCHAR(32) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (account_id),
    UNIQUE KEY idx_cms_referral_codes_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_referrals (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    referrer_id INT UNSIGNED NOT NULL,
    referred_id INT UNSIGNED NOT NULL,
    rewarded_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY idx_cms_referrals_referred (referred_id),
    KEY idx_cms_referrals_referrer (referrer_id, rewarded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_unstuck_cooldowns (
    player_id INT UNSIGNED NOT NULL,
    account_id INT UNSIGNED NOT NULL,
    unstuck_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (player_id),
    KEY idx_cms_unstuck_cooldowns_account (account_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_banners (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(120) NOT NULL DEFAULT '',
    alt VARCHAR(180) NOT NULL DEFAULT '',
    link_url VARCHAR(500) NULL,
    original_path VARCHAR(255) NOT NULL,
    variants_json JSON NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    enabled TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_banners_enabled_sort (enabled, sort_order, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_notifications (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    account_id INT UNSIGNED NOT NULL,
    type VARCHAR(32) NOT NULL,
    ref VARCHAR(64) NOT NULL DEFAULT '',
    payload TEXT NOT NULL,
    read_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_cms_notifications_ref (account_id, type, ref),
    KEY idx_cms_notifications_account (account_id, read_at, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS item_census (
    vnum INT UNSIGNED NOT NULL,
    units BIGINT UNSIGNED NOT NULL DEFAULT 0,
    stacks INT UNSIGNED NOT NULL DEFAULT 0,
    holders_players INT UNSIGNED NOT NULL DEFAULT 0,
    holders_accounts INT UNSIGNED NOT NULL DEFAULT 0,
    units_player BIGINT UNSIGNED NOT NULL DEFAULT 0,
    units_safebox BIGINT UNSIGNED NOT NULL DEFAULT 0,
    units_mall BIGINT UNSIGNED NOT NULL DEFAULT 0,
    units_pending BIGINT UNSIGNED NOT NULL DEFAULT 0,
    stacks_pending INT UNSIGNED NOT NULL DEFAULT 0,
    captured_at DATETIME NOT NULL,
    PRIMARY KEY (vnum),
    KEY idx_item_census_units (units),
    KEY idx_item_census_captured (captured_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS item_census_daily (
    day DATE NOT NULL,
    vnum INT UNSIGNED NOT NULL,
    units BIGINT UNSIGNED NOT NULL DEFAULT 0,
    stacks INT UNSIGNED NOT NULL DEFAULT 0,
    holders_players INT UNSIGNED NOT NULL DEFAULT 0,
    holders_accounts INT UNSIGNED NOT NULL DEFAULT 0,
    units_player BIGINT UNSIGNED NOT NULL DEFAULT 0,
    units_safebox BIGINT UNSIGNED NOT NULL DEFAULT 0,
    units_mall BIGINT UNSIGNED NOT NULL DEFAULT 0,
    units_pending BIGINT UNSIGNED NOT NULL DEFAULT 0,
    units_delta BIGINT NOT NULL DEFAULT 0,
    PRIMARY KEY (day, vnum),
    KEY idx_item_census_daily_vnum (vnum, day)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS economy_yang_daily (
    day DATE NOT NULL,
    player_yang BIGINT NOT NULL DEFAULT 0,
    safebox_yang BIGINT NOT NULL DEFAULT 0,
    guild_yang BIGINT NOT NULL DEFAULT 0,
    money_monster BIGINT NOT NULL DEFAULT 0,
    money_drop BIGINT NOT NULL DEFAULT 0,
    money_shop BIGINT NOT NULL DEFAULT 0,
    money_refine BIGINT NOT NULL DEFAULT 0,
    money_quest BIGINT NOT NULL DEFAULT 0,
    money_guild BIGINT NOT NULL DEFAULT 0,
    money_misc BIGINT NOT NULL DEFAULT 0,
    money_kill BIGINT NOT NULL DEFAULT 0,
    money_created BIGINT NOT NULL DEFAULT 0,
    money_destroyed BIGINT NOT NULL DEFAULT 0,
    captured_at DATETIME NOT NULL,
    PRIMARY KEY (day)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS item_market_trades (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    trade_key CHAR(40) NOT NULL,
    vnum INT UNSIGNED NOT NULL,
    count INT UNSIGNED NOT NULL DEFAULT 1,
    price_yang BIGINT NOT NULL,
    unit_price BIGINT NOT NULL,
    sold_at DATETIME NOT NULL,
    source VARCHAR(16) NOT NULL DEFAULT 'goldlog',
    seller_pid INT UNSIGNED NULL,
    buyer_pid INT UNSIGNED NULL,
    channel VARCHAR(16) NOT NULL DEFAULT 'shop',
    PRIMARY KEY (id),
    UNIQUE KEY uniq_item_market_trades_key (trade_key),
    KEY idx_item_market_trades_vnum_sold (vnum, sold_at),
    KEY idx_item_market_trades_sold (sold_at),
    KEY idx_item_market_trades_channel_sold (channel, sold_at),
    KEY idx_item_market_trades_seller (seller_pid, sold_at),
    KEY idx_item_market_trades_buyer (buyer_pid, sold_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS item_market_daily (
    day DATE NOT NULL,
    vnum INT UNSIGNED NOT NULL,
    trades INT UNSIGNED NOT NULL DEFAULT 0,
    units INT UNSIGNED NOT NULL DEFAULT 0,
    volume_yang BIGINT UNSIGNED NOT NULL DEFAULT 0,
    unique_sellers INT UNSIGNED NOT NULL DEFAULT 0,
    unique_buyers INT UNSIGNED NOT NULL DEFAULT 0,
    median_price BIGINT UNSIGNED NULL,
    p25_price BIGINT UNSIGNED NULL,
    p75_price BIGINT UNSIGNED NULL,
    source VARCHAR(16) NOT NULL DEFAULT 'goldlog',
    PRIMARY KEY (day, vnum),
    KEY idx_item_market_daily_vnum (vnum, day)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS economy_watchlist (
    vnum INT UNSIGNED NOT NULL,
    crash_pct SMALLINT UNSIGNED NULL,
    spike_pct SMALLINT UNSIGNED NULL,
    min_sample SMALLINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (vnum)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS economy_alerts (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    day DATE NOT NULL,
    subject_type VARCHAR(16) NOT NULL DEFAULT 'item',
    subject_id INT UNSIGNED NOT NULL DEFAULT 0,
    vnum INT UNSIGNED NOT NULL,
    kind VARCHAR(32) NOT NULL,
    payload TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    acked_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_economy_alerts_day_subject_kind (day, subject_type, subject_id, kind),
    KEY idx_economy_alerts_unacked (acked_at, created_at),
    KEY idx_economy_alerts_vnum (vnum, created_at),
    KEY idx_economy_alerts_subject (subject_type, subject_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS economy_tick_state (
    state_key VARCHAR(64) NOT NULL,
    state_value VARCHAR(255) NOT NULL DEFAULT '',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (state_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS economy_yang_transfers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    transfer_key CHAR(40) NOT NULL,
    from_pid INT UNSIGNED NOT NULL DEFAULT 0,
    to_pid INT UNSIGNED NOT NULL DEFAULT 0,
    yang_amount BIGINT NOT NULL,
    transferred_at DATETIME NOT NULL,
    source VARCHAR(16) NOT NULL DEFAULT 'goldlog',
    PRIMARY KEY (id),
    UNIQUE KEY uniq_economy_yang_transfers_key (transfer_key),
    KEY idx_economy_yang_transfers_to (to_pid, transferred_at),
    KEY idx_economy_yang_transfers_from (from_pid, transferred_at),
    KEY idx_economy_yang_transfers_at (transferred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS economy_wealth_daily (
    day DATE NOT NULL,
    player_count INT UNSIGNED NOT NULL DEFAULT 0,
    total_yang BIGINT NOT NULL DEFAULT 0,
    top1_pct DECIMAL(6,2) NOT NULL DEFAULT 0,
    top5_pct DECIMAL(6,2) NOT NULL DEFAULT 0,
    top10_pct DECIMAL(6,2) NOT NULL DEFAULT 0,
    captured_at DATETIME NOT NULL,
    PRIMARY KEY (day)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS economy_wealth_top_daily (
    day DATE NOT NULL,
    rank_pos SMALLINT UNSIGNED NOT NULL,
    pid INT UNSIGNED NOT NULL,
    yang BIGINT NOT NULL DEFAULT 0,
    PRIMARY KEY (day, rank_pos),
    KEY idx_economy_wealth_top_pid (pid, day)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
    ('admin_roles_defaults_seeded', '1'),
    ('banner_interval_ms', '5500'),
    ('banner_autoplay', '1'),
    ('banner_show_dots', '1'),
    ('banner_show_arrows', '1'),
    ('paypal_pending_minutes', '30'),
    ('paypal_enabled', '1'),
    ('mp_enabled', '1'),
    ('seo_index_enabled', '1');
