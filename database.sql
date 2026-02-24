-- ============================================================
-- Melody Masters - Music Instrument Shop Database
-- ============================================================

CREATE DATABASE IF NOT EXISTS melody_masters CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE melody_masters;

-- ============================================================
-- TABLE: users
-- ============================================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('guest','customer','staff','admin') NOT NULL DEFAULT 'customer',
    phone VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: categories
-- ============================================================
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: products
-- ============================================================
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(200) NOT NULL UNIQUE,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    brand VARCHAR(100),
    image VARCHAR(255),
    is_digital TINYINT(1) NOT NULL DEFAULT 0,
    featured TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: digital_products
-- ============================================================
CREATE TABLE digital_products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL UNIQUE,
    file_path VARCHAR(255) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: orders
-- ============================================================
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    shipping DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status ENUM('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
    shipping_name VARCHAR(100),
    shipping_address TEXT,
    shipping_city VARCHAR(100),
    shipping_postcode VARCHAR(20),
    shipping_phone VARCHAR(20),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: order_items
-- ============================================================
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: reviews
-- ============================================================
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- DUMMY DATA
-- ============================================================

-- Users (passwords: Admin@123 | Customer@123)
INSERT INTO users (name, email, password, role, phone, address) VALUES
('Admin User',    'admin@melodymaster.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin',    '07700900001', '1 Admin Lane, London'),
('Staff Member',  'staff@melodymaster.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff',    '07700900002', '2 Staff Road, London'),
('James Wilson',  'james@example.com',         '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', '07700900003', '3 Music Street, Birmingham'),
('Sophie Clark',  'sophie@example.com',        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', '07700900004', '4 Harmony Ave, Manchester');

-- Categories
INSERT INTO categories (name, slug, description, image) VALUES
('Guitars',        'guitars',        'Acoustic, electric and bass guitars from top brands.',          'cat_guitars.jpg'),
('Keyboards',      'keyboards',      'Digital pianos, synthesizers and MIDI controllers.',             'cat_keyboards.jpg'),
('Drums',          'drums',          'Drum kits, electronic drums and percussion instruments.',        'cat_drums.jpg'),
('Wind & Brass',   'wind-brass',     'Saxophones, trumpets, flutes and more.',                         'cat_wind.jpg'),
('Studio & DJ',    'studio-dj',      'Audio interfaces, mixers, headphones, and DJ equipment.',        'cat_studio.jpg'),
('Sheet Music',    'sheet-music',    'Digital sheet music and tutorial packs (instant download).',     'cat_sheet.jpg');

-- Products
INSERT INTO products (category_id, name, slug, description, price, stock, brand, image, is_digital, featured) VALUES
-- Guitars
(1, 'Fender Player Stratocaster',    'fender-player-stratocaster',    'Iconic 3-pickup electric guitar with maple neck and smooth playability. Professional Tex-Mex pickups deliver searing tone.',      549.99, 12, 'Fender',    'fender_strat.jpg',      0, 1),
(1, 'Gibson Les Paul Standard',      'gibson-les-paul-standard',      'The quintessential rock guitar with humbuckers, mahogany body and maple top. Timeless tone and sustain.',                      2499.00,  5, 'Gibson',    'gibson_lp.jpg',         0, 1),
(1, 'Yamaha FG800 Acoustic',         'yamaha-fg800-acoustic',         'Solid spruce top acoustic guitar perfect for beginners and intermediate players. Rich, full-bodied sound.',                     219.99, 20, 'Yamaha',    'yamaha_fg800.jpg',      0, 1),
(1, 'Taylor 214ce Acoustic-Electric','taylor-214ce',                  'Premium acoustic-electric guitar with solid Sitka spruce top, layered rosewood back and sides. ES2 electronics.',              999.00,  8, 'Taylor',    'taylor_214ce.jpg',      0, 0),
(1, 'Squier Classic Vibe Telecaster','squier-cv-telecaster',          'Vintage-inspired Telecaster with alder body, alnico V pickups and gloss finish. Great beginner to intermediate option.',       349.99, 15, 'Squier',    'squier_tele.jpg',       0, 0),

-- Keyboards
(2, 'Roland FP-30X Digital Piano',   'roland-fp30x',                  '88-key weighted action digital piano with SuperNATURAL sound engine. Built-in Bluetooth for app connectivity.',               699.00,  9, 'Roland',    'roland_fp30.jpg',       0, 1),
(2, 'Korg Minilogue XD',             'korg-minilogue-xd',             '4-voice polyphonic analogue synthesizer with multi-engine, digital delay and reverb effects. Stunning retro aesthetic.',       549.00,  7, 'Korg',      'korg_minilogue.jpg',    0, 1),
(2, 'Arturia KeyLab 61 MkII',        'arturia-keylab-61',             '61-key MIDI controller with semi-weighted keybed, aftertouch, pads and full DAW integration. Bundled with Arturia software.', 349.99, 11, 'Arturia',   'arturia_keylab.jpg',    0, 0),
(2, 'Nord Stage 4 Compact',          'nord-stage-4-compact',          'Professional stage keyboard with award-winning piano, organ and synth sections. 73-key semi-weighted keybed.',               3299.00,  3, 'Nord',      'nord_stage4.jpg',       0, 0),

-- Drums
(3, 'Pearl Export EXX 5-Piece Kit',  'pearl-export-exx',              'Professional 5-piece drum kit with hardware and cymbals included. Power toms and durable Poplar/Asian Mahogany shells.',      599.99,  6, 'Pearl',     'pearl_export.jpg',      0, 1),
(3, 'Roland TD-17KVX E-Drum Kit',    'roland-td17kvx',                'Electronic drum kit with mesh heads, TD-17 module, 50+ built-in kits and Bluetooth audio. Near-silent practice.',           1299.00,  4, 'Roland',    'roland_td17.jpg',       0, 1),
(3, 'Zildjian A Custom Cymbal Pack', 'zildjian-a-custom-pack',        'Premium 3-cymbal pack: 14" HiHat, 16" Crash and 20" Ride. Brilliant finish, warm complex tones.',                            499.00, 10, 'Zildjian',  'zildjian_a_custom.jpg', 0, 0),

-- Wind & Brass
(4, 'Yamaha YAS-280 Alto Saxophone', 'yamaha-yas-280',                'Student alto saxophone with yellow brass body, G1 neck and smooth key action. Excellent intonation for beginners.',           599.00,  8, 'Yamaha',    'yamaha_sax.jpg',        0, 0),
(4, 'Bach TR300H2 Student Trumpet',  'bach-tr300h2',                  'Lacquered brass student trumpet with monel valves and first valve slide thumb saddle. Excellent projection and tone.',         329.99, 12, 'Bach',      'bach_trumpet.jpg',      0, 0),
(4, 'Trevor James Flute 10X',        'trevor-james-flute-10x',        'Professional closed-hole student flute with silver-plated head joint, body and footjoint. Rich full tone.',                   219.00, 14, 'Trevor James','tj_flute.jpg',         0, 0),

-- Studio & DJ
(5, 'Focusrite Scarlett 2i2 Gen 4',  'focusrite-scarlett-2i2',        '2-in/2-out USB-C audio interface with Air preamps, 192kHz recording and zero-latency monitoring. Industry standard.',         170.00, 25, 'Focusrite', 'focusrite_2i2.jpg',     0, 1),
(5, 'Sony MDR-7506 Headphones',      'sony-mdr7506',                  'Professional closed-back studio monitoring headphones. Foldable design, 10Hz-20kHz frequency response.',                      99.00, 30, 'Sony',      'sony_mdr7506.jpg',      0, 0),
(5, 'Pioneer DJ DDJ-400',            'pioneer-ddj400',                '2-channel DJ controller for rekordbox. Built-in audio interface, performance pads and full RGB pad lighting.',                 249.00,  8, 'Pioneer DJ', 'pioneer_ddj400.jpg',   0, 0),

-- Sheet Music (Digital)
(6, 'Beginner Piano Chord Pack',     'beginner-piano-chord-pack',     'Over 100 pages of beginner-friendly piano chord charts and exercises. PDF instant download.',                                   9.99,999, 'Melody Masters','sheet_piano.jpg',    1, 0),
(6, 'Guitar Scales Mastery eBook',   'guitar-scales-mastery',         'Complete guide to guitar scales with TAB notation, 200+ exercises and backing tracks. PDF + MP3 download.',                   14.99,999, 'Melody Masters','sheet_guitar.jpg',   1, 0),
(6, 'Classic Rock Drum Patterns',    'classic-rock-drum-patterns',    '50 iconic classic rock drum patterns with notation and MIDI files. Perfect for practice and production.',                      12.99,999, 'Melody Masters','sheet_drums.jpg',    1, 0);

-- Digital Products (file paths)
INSERT INTO digital_products (product_id, file_path, file_name) VALUES
(20, 'downloads/beginner_piano_chord_pack.pdf',   'Beginner_Piano_Chord_Pack.pdf'),
(21, 'downloads/guitar_scales_mastery.zip',       'Guitar_Scales_Mastery.zip'),
(22, 'downloads/classic_rock_drum_patterns.zip',  'Classic_Rock_Drum_Patterns.zip');

-- Sample orders
INSERT INTO orders (user_id, total, shipping, status, shipping_name, shipping_address, shipping_city, shipping_postcode, shipping_phone) VALUES
(3, 559.99, 10.00, 'delivered', 'James Wilson', '3 Music Street', 'Birmingham', 'B1 2AB', '07700900003'),
(3,  24.98,  0.00, 'delivered', 'James Wilson', '3 Music Street', 'Birmingham', 'B1 2AB', '07700900003'),
(4, 709.00, 10.00, 'processing','Sophie Clark', '4 Harmony Ave',  'Manchester', 'M1 3CD', '07700900004');

INSERT INTO order_items (order_id, product_id, quantity, price) VALUES
(1,  1, 1, 549.99),
(2, 20, 1,   9.99),
(2, 21, 1,  14.99),
(3,  6, 1, 699.00);

-- Reviews
INSERT INTO reviews (product_id, user_id, rating, comment) VALUES
(1, 3, 5, 'Absolutely amazing guitar. The tone is incredible and setup out of the box was perfect!'),
(6, 4, 4, 'Great piano with excellent touch response. Bluetooth connectivity is very convenient.'),
(1, 4, 5, 'Best electric guitar for the money. Highly recommend to anyone serious about playing.');
