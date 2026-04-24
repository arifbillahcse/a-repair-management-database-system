-- =============================================================================
-- REPAIR MANAGEMENT SYSTEM - DATABASE SCHEMA
-- Compatible with: MySQL 5.7+
-- Engine: InnoDB (for foreign key support and ACID compliance)
-- Charset: utf8mb4 (full Unicode support, including emoji)
-- =============================================================================
--
-- ERD OVERVIEW (Entity Relationship Diagram)
-- ==========================================
--
--  [company_settings]
--
--  [staff] ──────────────┐
--       │                │
--       └──► [users]     │
--                        │
--  [customers] ──────────┼──► [repairs] ──► [invoices] ──► [invoice_items]
--                        │         │
--  [products] ────────────         └──► [invoice_items]
--
--  [activity_log] ──► (references users, and any entity by entity_type + entity_id)
--
-- TABLE RELATIONSHIPS
-- ===================
--  customers   (1) ──► (N) repairs
--  customers   (1) ──► (N) invoices
--  staff       (1) ──► (N) repairs         (assigned technician)
--  staff       (1) ──── (1) users          (login account)
--  repairs     (1) ──► (N) invoices
--  invoices    (1) ──► (N) invoice_items
--  users       (1) ──► (N) repairs         (created_by)
--  users       (1) ──► (N) activity_log
--  products    (M) ──► (N) invoice_items   (referenced by description/sku)
--
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =============================================================================
-- 1. COMPANY SETTINGS TABLE
--    Single-row configuration for the repair shop
-- =============================================================================

CREATE TABLE IF NOT EXISTS `company_settings` (
    `setting_id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `company_name`        VARCHAR(150)    NOT NULL,
    `company_address`     VARCHAR(255)    NOT NULL DEFAULT '',
    `company_phone`       VARCHAR(30)     NOT NULL DEFAULT '',
    `company_email`       VARCHAR(150)    NOT NULL DEFAULT '',
    `vat_number`          VARCHAR(20)     NOT NULL DEFAULT '',  -- Partita IVA
    `tax_id`              VARCHAR(20)     NOT NULL DEFAULT '',  -- Codice Fiscale
    `logo_path`           VARCHAR(500)             DEFAULT NULL,
    `invoice_prefix`      VARCHAR(10)     NOT NULL DEFAULT 'INV',
    `invoice_next_number` INT UNSIGNED    NOT NULL DEFAULT 1,
    `currency`            VARCHAR(3)      NOT NULL DEFAULT 'EUR',
    `tax_percentage`      DECIMAL(5,2)    NOT NULL DEFAULT 22.00, -- Italian IVA default
    `signature1`          VARCHAR(300)    NOT NULL DEFAULT '',
    `signature2`          VARCHAR(300)    NOT NULL DEFAULT '',
    `signature3`          VARCHAR(300)    NOT NULL DEFAULT '',
    `created_at`          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`setting_id`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Single-row shop configuration (always setting_id = 1)';


-- =============================================================================
-- 2. STAFF / COLLEAGUES TABLE
--    Technicians and other employees
-- =============================================================================

CREATE TABLE IF NOT EXISTS `staff` (
    `staff_id`    INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `first_name`  VARCHAR(80)     NOT NULL,
    `last_name`   VARCHAR(80)     NOT NULL,
    `email`       VARCHAR(150)             DEFAULT NULL,
    `phone`       VARCHAR(30)              DEFAULT NULL,
    `role`        ENUM(
                      'technician',
                      'manager',
                      'admin',
                      'receptionist'
                  )               NOT NULL DEFAULT 'technician',
    `is_active`   TINYINT(1)      NOT NULL DEFAULT 1,
    `notes`       TEXT                     DEFAULT NULL,
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`staff_id`),
    KEY `idx_staff_role`      (`role`),
    KEY `idx_staff_is_active` (`is_active`),
    KEY `idx_staff_email`     (`email`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Employees / technicians who handle repairs';


-- =============================================================================
-- 3. USERS TABLE
--    Login accounts linked 1-to-1 with a staff record
-- =============================================================================

CREATE TABLE IF NOT EXISTS `users` (
    `user_id`       INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `username`      VARCHAR(60)     NOT NULL,
    `email`         VARCHAR(150)    NOT NULL,
    `password_hash` VARCHAR(255)    NOT NULL,               -- bcrypt output
    `staff_id`      INT UNSIGNED             DEFAULT NULL,
    `role`          ENUM(
                        'admin',
                        'manager',
                        'staff',
                        'technician'
                    )               NOT NULL DEFAULT 'technician',
    `is_active`     TINYINT(1)      NOT NULL DEFAULT 1,
    `last_login`    DATETIME                 DEFAULT NULL,
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`user_id`),
    UNIQUE KEY `uq_users_username` (`username`),
    UNIQUE KEY `uq_users_email`    (`email`),
    KEY `fk_users_staff_idx`       (`staff_id`),
    KEY `idx_users_role`           (`role`),
    KEY `idx_users_is_active`      (`is_active`),

    CONSTRAINT `fk_users_staff`
        FOREIGN KEY (`staff_id`)
        REFERENCES `staff` (`staff_id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Login accounts; one user per staff member';


-- =============================================================================
-- 4. CUSTOMERS TABLE
--    Based on tblClients (19,368 records)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `customers` (
    `customer_id`     INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    -- full_name stores company names or combined names (denominazione)
    `full_name`       VARCHAR(200)    NOT NULL,
    `client_type`     ENUM(
                          'individual',   -- Privato
                          'company',      -- Azienda
                          'freelancer'    -- Libero professionista
                      )               NOT NULL DEFAULT 'individual',
    -- Address
    `address`         VARCHAR(255)             DEFAULT NULL,  -- Via + civico
    `postal_code`     VARCHAR(10)              DEFAULT NULL,  -- CAP
    `city`            VARCHAR(100)             DEFAULT NULL,
    `province`        VARCHAR(5)               DEFAULT NULL,  -- Sigla prov. (RM, MI …)
    -- Contacts
    `phone_landline`  VARCHAR(30)              DEFAULT NULL,  -- Tel fisso
    `phone_mobile`    VARCHAR(30)              DEFAULT NULL,  -- Cellulare
    `email`           VARCHAR(150)             DEFAULT NULL,
    -- Tax / business info
    `vat_number`      VARCHAR(20)              DEFAULT NULL,  -- Partita IVA
    `tax_id`          VARCHAR(20)              DEFAULT NULL,  -- Codice Fiscale
    -- Meta
    `notes`           TEXT                     DEFAULT NULL,
    `status`          ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `customer_since`  DATE                     DEFAULT NULL,
    `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`customer_id`),
    KEY `idx_customers_full_name`   (`full_name`(100)),
    KEY `idx_customers_email`       (`email`),
    KEY `idx_customers_phone_mob`   (`phone_mobile`),
    KEY `idx_customers_status`      (`status`),
    KEY `idx_customers_province`    (`province`),
    KEY `idx_customers_vat`         (`vat_number`),
    KEY `idx_customers_tax_id`      (`tax_id`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='End customers who bring in devices for repair';


-- =============================================================================
-- 5. PRODUCTS / DEVICES TABLE
--    Based on tblProducts (120 items) — spare parts, accessories, services
-- =============================================================================

CREATE TABLE IF NOT EXISTS `products` (
    `product_id`       INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `sku`              VARCHAR(60)              DEFAULT NULL,  -- Codice prodotto
    `barcode`          VARCHAR(60)              DEFAULT NULL,  -- EAN / QR
    `name`             VARCHAR(200)    NOT NULL,              -- Descrizione
    `category_id`      INT UNSIGNED             DEFAULT NULL, -- Tipologia (bare INT; add categories table if needed)
    `description`      TEXT                     DEFAULT NULL,
    `selling_price`    DECIMAL(10,2)   NOT NULL DEFAULT 0.00, -- Prezzo vendita
    `cost_price`       DECIMAL(10,2)            DEFAULT NULL, -- Costo acquisto
    `quantity_on_hand` INT             NOT NULL DEFAULT 0,    -- Stock
    `notes`            TEXT                     DEFAULT NULL,
    `is_active`        TINYINT(1)      NOT NULL DEFAULT 1,
    `created_at`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`product_id`),
    UNIQUE KEY `uq_products_sku`     (`sku`),
    KEY `idx_products_barcode`       (`barcode`),
    KEY `idx_products_name`          (`name`(100)),
    KEY `idx_products_category`      (`category_id`),
    KEY `idx_products_is_active`     (`is_active`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Spare parts, accessories and labour service lines';


-- =============================================================================
-- 6. REPAIRS TABLE
--    Main module — based on tblRepairs (811 records)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `repairs` (
    `repair_id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `customer_id`        INT UNSIGNED    NOT NULL,
    `staff_id`           INT UNSIGNED             DEFAULT NULL, -- Assigned technician
    -- Device info supplied by customer
    `device_brand`       VARCHAR(100)             DEFAULT NULL,
    `device_model`       VARCHAR(200)    NOT NULL,
    `device_serial_number` VARCHAR(100)           DEFAULT NULL,
    `device_condition`   TEXT                     DEFAULT NULL,
    `device_password`    VARCHAR(100)             DEFAULT NULL,
    -- Dates
    `date_in`            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,  -- Data entrata
    `date_out`           DATETIME                 DEFAULT NULL,               -- Data uscita (actual)
    `collection_date`    DATE                     DEFAULT NULL,               -- Expected pickup
    -- Diagnostics & work
    `problem_description` TEXT                    DEFAULT NULL, -- Customer complaint
    `diagnosis`           TEXT                    DEFAULT NULL, -- Technician assessment
    `work_done`           TEXT                    DEFAULT NULL, -- Lavoro svolto
    -- Financials
    `estimate_amount`    DECIMAL(10,2)            DEFAULT NULL, -- Quoted price
    `actual_amount`      DECIMAL(10,2)            DEFAULT NULL, -- Final price charged
    -- Tracking
    `status`             ENUM(
                             'in_progress',
                             'on_hold',
                             'waiting_for_parts',
                             'ready_for_pickup',
                             'completed',
                             'collected',
                             'cancelled'
                         )               NOT NULL DEFAULT 'in_progress',
    `priority`           VARCHAR(10)     NOT NULL DEFAULT 'normal',
    `photo_path`         VARCHAR(500)             DEFAULT NULL, -- Path/URL to image(s)
    `qr_code`            VARCHAR(100)             DEFAULT NULL, -- Unique QR / barcode
    `notes`              TEXT                     DEFAULT NULL,
    `internal_notes`     TEXT                     DEFAULT NULL,
    `created_by`         INT UNSIGNED             DEFAULT NULL, -- User who created the record
    `created_at`         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`repair_id`),
    UNIQUE KEY `uq_repairs_qr_code`     (`qr_code`),
    KEY `idx_repairs_customer`          (`customer_id`),
    KEY `idx_repairs_staff`             (`staff_id`),
    KEY `idx_repairs_status`            (`status`),
    KEY `idx_repairs_date_in`           (`date_in`),
    KEY `idx_repairs_collection_date`   (`collection_date`),
    KEY `idx_repairs_created_by`        (`created_by`),
    KEY `idx_repairs_device_model`      (`device_model`(80)),

    CONSTRAINT `fk_repairs_customer`
        FOREIGN KEY (`customer_id`)
        REFERENCES `customers` (`customer_id`)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    CONSTRAINT `fk_repairs_staff`
        FOREIGN KEY (`staff_id`)
        REFERENCES `staff` (`staff_id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE,

    CONSTRAINT `fk_repairs_created_by`
        FOREIGN KEY (`created_by`)
        REFERENCES `users` (`user_id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Core repair jobs — one row per device brought in';


-- =============================================================================
-- 7. INVOICES TABLE
--    Generated from a repair job
-- =============================================================================

CREATE TABLE IF NOT EXISTS `invoices` (
    `invoice_id`     INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `repair_id`      INT UNSIGNED             DEFAULT NULL,
    `customer_id`    INT UNSIGNED    NOT NULL,
    `invoice_number` VARCHAR(30)     NOT NULL,               -- e.g. INV-2024-00042
    `invoice_date`   DATE            NOT NULL,
    `due_date`       DATE                     DEFAULT NULL,
    `subtotal`       DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    `tax_percentage` DECIMAL(5,2)    NOT NULL DEFAULT 22.00,
    `tax_amount`     DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    `total_amount`   DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    `amount_paid`    DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    `status`         ENUM(
                         'draft',
                         'sent',
                         'paid',
                         'partially_paid',
                         'overdue',
                         'cancelled'
                     )               NOT NULL DEFAULT 'draft',
    `notes`          TEXT                     DEFAULT NULL,
    `created_by`     INT UNSIGNED             DEFAULT NULL,
    `created_at`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`invoice_id`),
    UNIQUE KEY `uq_invoices_number`   (`invoice_number`),
    KEY `idx_invoices_repair`         (`repair_id`),
    KEY `idx_invoices_customer`       (`customer_id`),
    KEY `idx_invoices_status`         (`status`),
    KEY `idx_invoices_invoice_date`   (`invoice_date`),
    KEY `idx_invoices_created_by`     (`created_by`),

    CONSTRAINT `fk_invoices_repair`
        FOREIGN KEY (`repair_id`)
        REFERENCES `repairs` (`repair_id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE,

    CONSTRAINT `fk_invoices_customer`
        FOREIGN KEY (`customer_id`)
        REFERENCES `customers` (`customer_id`)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    CONSTRAINT `fk_invoices_created_by`
        FOREIGN KEY (`created_by`)
        REFERENCES `users` (`user_id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Invoices generated from repair jobs';


-- =============================================================================
-- 8. INVOICE ITEMS TABLE
--    Line items (labour, parts, services) on each invoice
-- =============================================================================

CREATE TABLE IF NOT EXISTS `invoice_items` (
    `invoice_item_id` INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `invoice_id`      INT UNSIGNED    NOT NULL,
    `product_id`      INT UNSIGNED             DEFAULT NULL,  -- Optional link to products
    `description`     VARCHAR(500)    NOT NULL,
    `quantity`        DECIMAL(10,3)   NOT NULL DEFAULT 1.000,
    `unit_price`      DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    `tax_percentage`  DECIMAL(5,2)    NOT NULL DEFAULT 22.00,
    `discount_pct`    DECIMAL(5,2)    NOT NULL DEFAULT 0.00,  -- Line-level discount %
    `line_total`      DECIMAL(10,2)   NOT NULL DEFAULT 0.00,  -- qty * unit_price * (1 - discount)
    `sort_order`      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`invoice_item_id`),
    KEY `idx_invoice_items_invoice`  (`invoice_id`),
    KEY `idx_invoice_items_product`  (`product_id`),

    CONSTRAINT `fk_invoice_items_invoice`
        FOREIGN KEY (`invoice_id`)
        REFERENCES `invoices` (`invoice_id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT `fk_invoice_items_product`
        FOREIGN KEY (`product_id`)
        REFERENCES `products` (`product_id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Individual line items within an invoice';


-- =============================================================================
-- 9. ACTIVITY LOG TABLE
--    Full audit trail for every significant action
-- =============================================================================

CREATE TABLE IF NOT EXISTS `activity_log` (
    `log_id`      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`     INT UNSIGNED             DEFAULT NULL,
    `action`      ENUM(
                      'created',
                      'updated',
                      'deleted',
                      'viewed',
                      'login',
                      'logout',
                      'status_changed',
                      'exported'
                  )               NOT NULL,
    `entity_type` VARCHAR(50)     NOT NULL,  -- 'repair', 'customer', 'invoice', 'product' …
    `entity_id`   INT UNSIGNED             DEFAULT NULL,
    `old_values`  JSON                     DEFAULT NULL,  -- Snapshot before change
    `new_values`  JSON                     DEFAULT NULL,  -- Snapshot after change
    `ip_address`  VARCHAR(45)              DEFAULT NULL,  -- IPv4 or IPv6
    `user_agent`  VARCHAR(500)             DEFAULT NULL,
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`log_id`),
    KEY `idx_log_user`        (`user_id`),
    KEY `idx_log_entity`      (`entity_type`, `entity_id`),
    KEY `idx_log_action`      (`action`),
    KEY `idx_log_created_at`  (`created_at`),

    CONSTRAINT `fk_log_user`
        FOREIGN KEY (`user_id`)
        REFERENCES `users` (`user_id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Immutable audit trail of all system actions';


-- =============================================================================
-- RE-ENABLE FOREIGN KEY CHECKS
-- =============================================================================
SET FOREIGN_KEY_CHECKS = 1;


-- =============================================================================
-- SAMPLE DATA
-- =============================================================================

-- ── Company Settings (single row) ────────────────────────────────────────────
INSERT INTO `company_settings`
    (`company_name`, `company_address`, `company_phone`, `company_email`,
     `vat_number`, `tax_id`, `invoice_prefix`, `invoice_next_number`,
     `currency`, `tax_percentage`)
VALUES
    ('TechFix Srl', 'Via Roma 42, 00100 Roma RM', '+39 06 1234567',
     'info@techfix.it', 'IT12345678901', 'TCFFXX80A01H501Z',
     'TF', 1, 'EUR', 22.00);


-- ── Staff ─────────────────────────────────────────────────────────────────────
INSERT INTO `staff` (`first_name`, `last_name`, `email`, `phone`, `role`) VALUES
    ('Mario',     'Rossi',    'mario.rossi@techfix.it',    '+39 333 1111111', 'admin'),
    ('Luigi',     'Bianchi',  'luigi.bianchi@techfix.it',  '+39 333 2222222', 'technician'),
    ('Giulia',    'Verdi',    'giulia.verdi@techfix.it',   '+39 333 3333333', 'technician'),
    ('Federica',  'Neri',     'federica.neri@techfix.it',  '+39 333 4444444', 'receptionist'),
    ('Antonio',   'Esposito', 'antonio.e@techfix.it',      '+39 333 5555555', 'manager');


-- ── Users (passwords are bcrypt of "Password1!" — replace in production) ─────
INSERT INTO `users` (`username`, `email`, `password_hash`, `staff_id`, `role`) VALUES
    ('admin',     'mario.rossi@techfix.it',   '$2y$12$exampleHashAdmin000000000000000000000000000000000000000', 1, 'admin'),
    ('luigi_b',   'luigi.bianchi@techfix.it', '$2y$12$exampleHashLuigi000000000000000000000000000000000000000', 2, 'technician'),
    ('giulia_v',  'giulia.verdi@techfix.it',  '$2y$12$exampleHashGiulia00000000000000000000000000000000000000', 3, 'technician'),
    ('federica_n','federica.neri@techfix.it', '$2y$12$exampleHashFeder00000000000000000000000000000000000000', 4, 'staff'),
    ('antonio_e', 'antonio.e@techfix.it',     '$2y$12$exampleHashAnton00000000000000000000000000000000000000', 5, 'manager');


-- ── Customers ─────────────────────────────────────────────────────────────────
INSERT INTO `customers`
    (`first_name`, `last_name`, `full_name`, `client_type`,
     `address`, `postal_code`, `city`, `province`,
     `phone_landline`, `phone_mobile`, `email`,
     `vat_number`, `tax_id`, `customer_since`, `status`)
VALUES
    ('Marco',    'Ferrari',  'Marco Ferrari',        'individual',
     'Via Garibaldi 10',  '20121', 'Milano',  'MI',
     '02 1234567', '+39 347 1234567', 'marco.ferrari@email.it',
     NULL, 'FRRM RC80A01F205K', '2022-01-15', 'active'),

    ('Anna',     'Colombo',  'Anna Colombo',         'individual',
     'Corso Buenos Aires 5', '20124', 'Milano', 'MI',
     NULL, '+39 320 9876543', 'anna.colombo@email.it',
     NULL, 'CLMNN A85M41F205R', '2022-03-20', 'active'),

    (NULL,       NULL,       'Tecno Solutions Srl',  'company',
     'Via dell\'Industria 88', '35100', 'Padova', 'PD',
     '049 9988776', '+39 348 5556677', 'info@tecnosolutions.it',
     'IT04567891234', 'TCNSLZ00A01G224M', '2021-11-01', 'active'),

    ('Giuseppe', 'Ricci',    'Giuseppe Ricci',       'individual',
     'Via Nazionale 33',  '00184', 'Roma',    'RM',
     '06 3456789', '+39 333 7778899', 'giuseppe.ricci@email.it',
     NULL, 'RCCGPP 78B15H501Z', '2023-06-10', 'active'),

    ('Sofia',    'Marino',   'Sofia Marino',         'freelancer',
     'Via Toledo 120',    '80132', 'Napoli',  'NA',
     '081 2345678', '+39 339 4445566', 'sofia.marino@libero.it',
     'IT09876543210', 'MRNSFO 90D55F839X', '2023-09-05', 'active');


-- ── Products ──────────────────────────────────────────────────────────────────
INSERT INTO `products`
    (`sku`, `barcode`, `name`, `selling_price`, `cost_price`,
     `quantity_on_hand`, `description`, `is_active`)
VALUES
    ('SCRN-IP13-OEM', '8901234560001', 'Display iPhone 13 OEM',    89.00,  52.00, 5,  'Schermo completo di vetro per iPhone 13, qualità OEM', 1),
    ('BAT-SGS22',     '8901234560002', 'Batteria Samsung Galaxy S22', 35.00, 18.00, 12, 'Batteria originale per Samsung Galaxy S22', 1),
    ('SCRN-SGS22',    '8901234560003', 'Display Samsung Galaxy S22', 75.00, 44.00, 3,  'Schermo AMOLED completo per Samsung Galaxy S22', 1),
    ('SVC-LABOUR',    NULL,            'Manodopera / Labour',        0.00,   0.00, 0,  'Voce generica per manodopera tecnica', 1),
    ('BAT-IP13',      '8901234560005', 'Batteria iPhone 13',         29.00,  14.00, 8,  'Batteria Apple iPhone 13 alta capacità', 1),
    ('CHG-USBC-20W',  '8901234560006', 'Caricatore USB-C 20W',       19.90,   8.50, 20, 'Alimentatore USB-C 20W universale', 1),
    ('CASE-SILIC-UN', '8901234560007', 'Cover Silicone Universale',   6.90,   2.10, 50, 'Custodia in silicone trasparente universale', 1);


-- ── Repairs ───────────────────────────────────────────────────────────────────
INSERT INTO `repairs`
    (`customer_id`, `staff_id`, `device_model`, `device_serial_number`,
     `date_in`, `collection_date`,
     `problem_description`, `diagnosis`, `work_done`,
     `estimate_amount`, `actual_amount`,
     `status`, `qr_code`, `created_by`)
VALUES
    -- Repair 1: completed
    (1, 2, 'iPhone 13', 'DNQVX123456789',
     '2024-11-01 09:30:00', '2024-11-04',
     'Schermo rotto dopo caduta',
     'Display completamente danneggiato, touch non funzionante',
     'Sostituzione display con ricambio OEM',
     89.00, 109.00,
     'completed', 'QR-2024-00001', 1),

    -- Repair 2: in progress
    (2, 3, 'Samsung Galaxy S22', 'RF8NA123456B',
     '2024-11-05 10:15:00', '2024-11-08',
     'La batteria si scarica molto velocemente',
     'Batteria gonfia, capacità residua 41%',
     NULL,
     35.00, NULL,
     'in_progress', 'QR-2024-00002', 1),

    -- Repair 3: waiting for parts
    (3, 2, 'MacBook Pro 14" 2021', 'C02XK123MD6T',
     '2024-11-06 14:00:00', '2024-11-15',
     'Tasto spazio non funziona, tastiera danneggiata da liquido',
     'Corrosione sotto la tastiera, necessaria sostituzione tastiera completa',
     NULL,
     180.00, NULL,
     'waiting_for_parts', 'QR-2024-00003', 4),

    -- Repair 4: ready for pickup
    (4, 3, 'iPhone 11', 'F17XK3456789',
     '2024-11-03 08:45:00', '2024-11-06',
     'Fotocamera posteriore non funziona, immagini sfuocate',
     'Modulo fotocamera danneggiato',
     'Sostituzione modulo fotocamera posteriore',
     60.00, 60.00,
     'ready_for_pickup', 'QR-2024-00004', 1),

    -- Repair 5: collected
    (5, 2, 'Xiaomi Redmi Note 11', 'R38A456789012',
     '2024-10-28 11:00:00', '2024-11-01',
     'Telefono non si accende dopo bagno',
     'Ossidazione sulla scheda madre, corto circuito sulla sezione alimentazione',
     'Pulizia ultrasuoni, sostituzione componenti SMD sezione alimentazione',
     90.00, 110.00,
     'collected', 'QR-2024-00005', 1),

    -- Repair 6: on hold
    (1, NULL, 'iPad Air 4th gen', 'DMPXK456789AB',
     '2024-11-07 16:30:00', NULL,
     'Touch screen non risponde in alcune zone',
     'In attesa di autorizzazione preventivo dal cliente',
     NULL,
     75.00, NULL,
     'on_hold', 'QR-2024-00006', 4);


-- ── Invoices ──────────────────────────────────────────────────────────────────
INSERT INTO `invoices`
    (`repair_id`, `customer_id`, `invoice_number`, `invoice_date`, `due_date`,
     `subtotal`, `tax_percentage`, `tax_amount`, `total_amount`, `amount_paid`,
     `status`, `created_by`)
VALUES
    -- Invoice for repair 1
    (1, 1, 'TF-2024-0001', '2024-11-04', '2024-11-18',
     89.35, 22.00, 19.66, 109.01, 109.01, 'paid', 1),

    -- Invoice for repair 5 (collected)
    (5, 5, 'TF-2024-0002', '2024-11-01', '2024-11-15',
     90.16, 22.00, 19.84, 110.00, 60.00, 'partially_paid', 1),

    -- Draft invoice for repair 4 (ready for pickup, not yet collected)
    (4, 4, 'TF-2024-0003', '2024-11-06', '2024-11-20',
     49.18, 22.00, 10.82, 60.00, 0.00, 'draft', 1);


-- ── Invoice Items ─────────────────────────────────────────────────────────────
INSERT INTO `invoice_items`
    (`invoice_id`, `product_id`, `description`, `quantity`, `unit_price`,
     `tax_percentage`, `discount_pct`, `line_total`, `sort_order`)
VALUES
    -- Items for invoice 1 (repair 1: iPhone 13 screen)
    (1, 1, 'Display iPhone 13 OEM',          1, 89.00, 22.00, 0.00, 89.00, 1),
    (1, 4, 'Manodopera sostituzione schermo', 1, 20.00, 22.00, 0.00, 20.00, 2),

    -- Items for invoice 2 (repair 5: Xiaomi water damage)
    (2, 4, 'Pulizia ultrasuoni scheda madre', 1,  40.00, 22.00, 0.00,  40.00, 1),
    (2, 4, 'Componenti SMD e manodopera',     1,  70.00, 22.00, 0.00,  70.00, 2),

    -- Items for invoice 3 (repair 4: iPhone 11 camera)
    (3, 4, 'Sostituzione modulo fotocamera posteriore iPhone 11', 1, 60.00, 22.00, 0.00, 60.00, 1);


-- ── Activity Log samples ──────────────────────────────────────────────────────
INSERT INTO `activity_log`
    (`user_id`, `action`, `entity_type`, `entity_id`, `old_values`, `new_values`, `ip_address`)
VALUES
    (1, 'login',          'user',     1,    NULL, NULL, '192.168.1.10'),
    (1, 'created',        'customer', 1,    NULL,
        '{"full_name":"Marco Ferrari","email":"marco.ferrari@email.it"}',
        '192.168.1.10'),
    (1, 'created',        'repair',   1,    NULL,
        '{"device_model":"iPhone 13","status":"in_progress"}',
        '192.168.1.10'),
    (2, 'status_changed', 'repair',   1,
        '{"status":"in_progress"}',
        '{"status":"completed"}',
        '192.168.1.11'),
    (1, 'created',        'invoice',  1,    NULL,
        '{"invoice_number":"TF-2024-0001","total_amount":109.01}',
        '192.168.1.10'),
    (4, 'created',        'repair',   3,    NULL,
        '{"device_model":"MacBook Pro 14\" 2021","status":"in_progress"}',
        '192.168.1.12'),
    (2, 'status_changed', 'repair',   5,
        '{"status":"in_progress"}',
        '{"status":"collected"}',
        '192.168.1.11');


-- =============================================================================
-- ADDITIONAL USEFUL VIEWS (optional but recommended)
-- =============================================================================

-- Active repairs with customer and technician names
CREATE OR REPLACE VIEW `v_repairs_overview` AS
SELECT
    r.repair_id,
    r.qr_code,
    r.status,
    r.device_model,
    r.date_in,
    r.collection_date,
    r.estimate_amount,
    r.actual_amount,
    CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
    c.phone_mobile                          AS customer_phone,
    CONCAT(s.first_name, ' ', s.last_name) AS technician_name
FROM repairs r
JOIN customers c ON c.customer_id = r.customer_id
LEFT JOIN staff s ON s.staff_id = r.staff_id;


-- Invoice totals per customer
CREATE OR REPLACE VIEW `v_customer_invoice_summary` AS
SELECT
    c.customer_id,
    c.full_name,
    COUNT(i.invoice_id)        AS total_invoices,
    SUM(i.total_amount)        AS total_billed,
    SUM(i.amount_paid)         AS total_paid,
    SUM(i.total_amount - i.amount_paid) AS balance_due
FROM customers c
LEFT JOIN invoices i ON i.customer_id = c.customer_id
GROUP BY c.customer_id, c.full_name;


-- =============================================================================
-- END OF SCHEMA
-- =============================================================================
