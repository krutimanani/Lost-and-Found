-- Rajkot E Milaap Database
-- Database: rajkot_emilaap

SET NAMES utf8mb4;
SET CHARACTER_SET_CLIENT = utf8mb4;
SET TIME_ZONE = '+00:00';

-- ========================================
-- TABLE: activity_log
-- ========================================
CREATE TABLE `activity_log` (
  `log_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `user_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE: admins
-- ========================================
CREATE TABLE `admins` (
  `admin_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('Active','Inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `language` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT 'en',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE: categories
-- ========================================
CREATE TABLE `categories` (
  `category_id` int NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_name_hi` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `description_hi` text COLLATE utf8mb4_unicode_ci,
  `status` enum('Active','Inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `category_name` (`category_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE: users
-- ========================================
CREATE TABLE `users` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `status` enum('Active','Inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `language` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT 'en',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE: police_stations
-- ========================================
CREATE TABLE `police_stations` (
  `station_id` int NOT NULL AUTO_INCREMENT,
  `station_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `station_address` text COLLATE utf8mb4_unicode_ci,
  `contact_no` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`station_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE: police
-- ========================================
CREATE TABLE `police` (
  `police_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `badge_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `station_id` int DEFAULT NULL,
  `police_rank` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('Active','Inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `language` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT 'en',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`police_id`),
  UNIQUE KEY `badge_number` (`badge_number`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_badge` (`badge_number`),
  KEY `idx_email` (`email`),
  KEY `station_id` (`station_id`),
  CONSTRAINT `police_ibfk_1` FOREIGN KEY (`station_id`) REFERENCES `police_stations` (`station_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE: locations
-- ========================================
CREATE TABLE `locations` (
  `location_id` int NOT NULL AUTO_INCREMENT,
  `location_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location_name_hi` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('Active','Inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE: lost_items
-- ========================================
CREATE TABLE `lost_items` (
  `lost_item_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `police_id` int DEFAULT NULL,
  `category_id` int NOT NULL,
  `location_id` int DEFAULT NULL,
  `item_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `lost_date` date NOT NULL,
  `contact_info` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('Pending','Approved','Matched','Resolved','Rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`lost_item_id`),
  KEY `category_id` (`category_id`),
  KEY `location_id` (`location_id`),
  KEY `idx_status` (`status`),
  KEY `idx_date` (`lost_date`),
  KEY `idx_user` (`user_id`),
  KEY `idx_lost_police` (`police_id`),
  CONSTRAINT `lost_items_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `lost_items_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE RESTRICT,
  CONSTRAINT `lost_items_ibfk_3` FOREIGN KEY (`location_id`) REFERENCES `locations` (`location_id`) ON DELETE SET NULL,
  CONSTRAINT `lost_items_police_fk` FOREIGN KEY (`police_id`) REFERENCES `police` (`police_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE: found_items
-- ========================================
CREATE TABLE `found_items` (
  `found_item_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `police_id` int DEFAULT NULL,
  `category_id` int NOT NULL,
  `location_id` int DEFAULT NULL,
  `item_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `found_date` date NOT NULL,
  `contact_info` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('Pending','Approved','Matched','Returned','Rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`found_item_id`),
  KEY `user_id` (`user_id`),
  KEY `category_id` (`category_id`),
  KEY `location_id` (`location_id`),
  KEY `idx_status` (`status`),
  KEY `idx_date` (`found_date`),
  KEY `idx_found_police` (`police_id`),
  CONSTRAINT `found_items_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `found_items_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE RESTRICT,
  CONSTRAINT `found_items_ibfk_3` FOREIGN KEY (`location_id`) REFERENCES `locations` (`location_id`) ON DELETE SET NULL,
  CONSTRAINT `found_items_police_fk` FOREIGN KEY (`police_id`) REFERENCES `police` (`police_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE: item_claims
-- ========================================
CREATE TABLE `item_claims` (
  `claim_id` int NOT NULL AUTO_INCREMENT,
  `found_item_id` int NOT NULL,
  `lost_item_id` int DEFAULT NULL,
  `user_id` int NOT NULL,
  `claim_reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `proof_description` text COLLATE utf8mb4_unicode_ci,
  `status` enum('Pending','Approved','Rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `collected` tinyint(1) DEFAULT '0',
  `collected_at` timestamp NULL DEFAULT NULL,
  `collected_by` int DEFAULT NULL,
  `citizen_confirmed_collection` tinyint(1) DEFAULT '0',
  `citizen_confirmed_at` timestamp NULL DEFAULT NULL,
  `reviewed_by` int DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`claim_id`),
  KEY `lost_item_id` (`lost_item_id`),
  KEY `reviewed_by` (`reviewed_by`),
  KEY `idx_status` (`status`),
  KEY `idx_user` (`user_id`),
  KEY `idx_found` (`found_item_id`),
  KEY `collected_by` (`collected_by`),
  CONSTRAINT `item_claims_ibfk_1` FOREIGN KEY (`found_item_id`) REFERENCES `found_items` (`found_item_id`) ON DELETE CASCADE,
  CONSTRAINT `item_claims_ibfk_2` FOREIGN KEY (`lost_item_id`) REFERENCES `lost_items` (`lost_item_id`) ON DELETE CASCADE,
  CONSTRAINT `item_claims_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `item_claims_ibfk_4` FOREIGN KEY (`reviewed_by`) REFERENCES `police` (`police_id`) ON DELETE SET NULL,
  CONSTRAINT `item_claims_ibfk_5` FOREIGN KEY (`collected_by`) REFERENCES `police` (`police_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE: notifications
-- ========================================
CREATE TABLE `notifications` (
  `notification_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `user_type` enum('Citizen','Police','Admin') COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `notification_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'System',
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`notification_id`),
  KEY `idx_user` (`user_id`,`user_type`),
  KEY `idx_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE: matched_reports
-- ========================================
CREATE TABLE `matched_reports` (
  `match_id` int NOT NULL AUTO_INCREMENT,
  `lost_item_id` int DEFAULT NULL,
  `found_item_id` int NOT NULL,
  `matched_by_police` int NOT NULL,
  `status` enum('Matched','Resolved') COLLATE utf8mb4_unicode_ci DEFAULT 'Matched',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `matched_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`match_id`),
  KEY `matched_by_police` (`matched_by_police`),
  KEY `idx_status` (`status`),
  KEY `idx_lost` (`lost_item_id`),
  KEY `idx_found` (`found_item_id`),
  CONSTRAINT `matched_reports_ibfk_1` FOREIGN KEY (`lost_item_id`) REFERENCES `lost_items` (`lost_item_id`) ON DELETE SET NULL,
  CONSTRAINT `matched_reports_ibfk_2` FOREIGN KEY (`found_item_id`) REFERENCES `found_items` (`found_item_id`) ON DELETE CASCADE,
  CONSTRAINT `matched_reports_ibfk_3` FOREIGN KEY (`matched_by_police`) REFERENCES `police` (`police_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE: feedback
-- ========================================
CREATE TABLE `feedback` (
  `feedback_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `subject` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `rating` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`feedback_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `feedback_chk_1` CHECK ((`rating` between 1 and 5))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE: settings
-- ========================================
CREATE TABLE `settings` (
  `setting_id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `description` text COLLATE utf8mb4_unicode_ci,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- DATA INSERTION
-- ========================================

-- Insert Admin
INSERT INTO `admins` VALUES 
(1,'Super Admin','admin@demo.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Active','hi','2025-09-30 15:39:16');

-- Insert Categories
INSERT INTO `categories` VALUES 
(1,'Documents','दस्तावेज़','ID cards, licenses, certificates, passports','पहचान पत्र, लाइसेंस, प्रमाण पत्र, पासपोर्ट','Active','2025-09-30 15:39:16'),
(2,'Mobile Phones','मोबाइल फोन','Smartphones, feature phones, tablets','स्मार्टफोन, फीचर फोन, टैबलेट','Active','2025-09-30 15:39:16'),
(4,'Vehicles','वाहन','Cars, bikes, scooters, bicycles','कारें, बाइक, स्कूटर, साइकिलें','Active','2025-09-30 15:39:16'),
(5,'Jewellery','आभूषण','Rings, necklaces, bracelets, earrings','अंगूठियां, हार, कंगन, बालियां','Active','2025-09-30 15:39:16'),
(6,'Bags & Luggage','बैग और सामान','Backpacks, handbags, suitcases','बैकपैक, हैंडबैग, सूटकेस','Active','2025-09-30 15:39:16'),
(7,'Electronics','इलेक्ट्रॉनिक्स','Laptops, cameras, headphones, chargers','लैपटॉप, कैमरा, हेडफोन, चार्जर','Active','2025-09-30 15:39:16'),
(8,'Keys','चाबियां','House keys, car keys, key chains','घर की चाबियां, कार की चाबियां, की चेन','Active','2025-09-30 15:39:16'),
(9,'Pets','पालतू जानवर','Dogs, cats, other pets','कुत्ते, बिल्लियां, अन्य पालतू जानवर','Active','2025-09-30 15:39:16'),
(10,'Watches','घड़ियां','Wrist watches, smart watches','कलाई घड़ियां, स्मार्ट घड़ियां','Inactive','2025-09-30 15:39:16'),
(11,'Clothing','कपड़े','Jackets, shoes, accessories','जैकेट, जूते, सहायक उपकरण','Active','2025-09-30 15:39:16'),
(12,'Books','किताबें','Textbooks, notebooks, diaries','पाठ्यपुस्तकें, नोटबुक, डायरियां','Active','2025-09-30 15:39:16'),
(13,'Other','अन्य','Other miscellaneous items','अन्य विविध वस्तुएं','Active','2025-09-30 15:39:16'),
(14,'Wallet',NULL,'Wallets, purses, money pouches',NULL,'Active','2025-11-29 11:12:26');

-- Insert Users
INSERT INTO `users` VALUES 
(1,'Demo Citizen','citizen@demo.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','9876543210','Rajkot, Gujarat','Active','hi','2025-09-30 15:39:16');

-- Insert Police Stations
INSERT INTO `police_stations` VALUES 
(1,'A Division Police Station','Near Jubilee Garden, Rajkot','0281-2447520','2025-09-30 15:39:16'),
(2,'B Division Police Station','Kalawad Road, Rajkot','0281-2470101','2025-09-30 15:39:16'),
(3,'C Division Police Station','University Road, Rajkot','0281-2586321','2025-09-30 15:39:16'),
(4,'D Division Police Station','Mavdi, Rajkot','0281-2563145','2025-09-30 15:39:16'),
(5,'Kotecha Police Station','Kotecha Chowk, Rajkot','0281-2445620','2025-09-30 15:39:16');

-- Insert Police Officers
INSERT INTO `police` VALUES 
(1,'Demo Police Officer','PO-001','police@demo.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','9876543211',1,'Sub-Inspector','Active','hi','2025-09-30 15:39:16'),
(2,'Test Police','TEST01','test@police.com','$2y$10$lmiVhLhH10athFc8hyWwnehj.l6Tmk8N1jqHuWQZo6Ulq/Mfg55PS','8796789058',1,'Constable','Active','en','2025-11-29 11:08:15');

-- Insert Locations
INSERT INTO `locations` VALUES 
(1,'Race Course','रेस कोर्स','Active','2025-09-30 15:39:16'),
(2,'Kalawad Road','कालावाड़ रोड','Active','2025-09-30 15:39:16'),
(3,'University Road','यूनिवर्सिटी रोड','Active','2025-09-30 15:39:16'),
(4,'Mavdi','मावड़ी','Active','2025-09-30 15:39:16'),
(5,'Kotecha Chowk','कोटेचा चौक','Active','2025-09-30 15:39:16'),
(6,'Yagnik Road','यज्ञिक रोड','Active','2025-09-30 15:39:16'),
(7,'150 Feet Ring Road','150 फीट रिंग रोड','Active','2025-09-30 15:39:16'),
(8,'Raiya Road','रैया रोड','Active','2025-09-30 15:39:16'),
(9,'Gondal Road','गोंडल रोड','Active','2025-09-30 15:39:16'),
(10,'Aji Dam','आजी डैम','Active','2025-09-30 15:39:16'),
(11,'Crystal Mall','क्रिस्टल मॉल','Active','2025-09-30 15:39:16'),
(12,'Rajkot Railway Station','राजकोट रेलवे स्टेशन','Active','2025-09-30 15:39:16'),
(13,'Rajkot Airport','राजकोट हवाई अड्डा','Active','2025-09-30 15:39:16'),
(14,'Other','अन्य','Active','2025-09-30 15:39:16');

-- Insert Lost Items
INSERT INTO `lost_items` VALUES 
(1,1,NULL,12,10,'chain','Silver chain with pendant lost near Aji Dam. Original design with custom engraving. Important sentimental value.','2025-09-30','9876543210','68dbfce5dcf0d6.03035207.png','Approved','2025-09-30 15:53:09');

-- Insert Found Items
INSERT INTO `found_items` VALUES 
(1,1,NULL,10,7,'Watch','Silver wrist watch with leather strap. Found at 150 Feet Ring Road. In good working condition with some minor scratches on the crystal.','2025-09-30','9876543210','68dbfd076e8737.62521314.png','Approved','2025-09-30 15:53:43'),
(2,NULL,1,2,1,'Iphone','Blue color old iPhone model. Found by police during routine patrol. Device is locked and requires verification of ownership. Police custody reference number: PC-2025-002','2025-11-29','Police Custody - Ref: PC-2025-002','692ad88fac2ed6.47401569.jpg','Approved','2025-11-29 11:27:11');

-- Insert Item Claims with detailed descriptions
-- Insert Item Claims with detailed descriptions
INSERT INTO `item_claims` VALUES 
(1,1,NULL,1,'this item belongs to me','I am the original owner. The watch has my initials engraved on the back and a unique scratch pattern I can describe in detail. I lost it while jogging at 150 Feet Ring Road on September 30th.','Approved',1,'2025-09-30 16:34:38',1,1,'2025-09-30 16:34:47',1,'2025-09-30 16:25:10','Claim verified by police officer. Citizen confirmed ownership through unique identifying marks. Item collection completed successfully. Both parties satisfied with resolution.','2025-09-30 16:20:18'),
(2,2,NULL,1,'this is mine','The iPhone belongs to me. I can provide IMEI number, original purchase receipt, and describe the custom lock screen setup with specific family photos.','Approved',1,NULL,NULL,0,NULL,1,'2025-11-29 15:29:26','Police verified identity and ownership proof documents. Claim approved pending item collection from police custody at A Division Police Station.','2025-11-29 15:28:51');

-- Insert Settings
INSERT INTO `settings` VALUES 
(1,'site_name','Rajkot E Milaap','Website name','2025-09-30 15:39:16'),
(2,'site_email','contact@rajkotemilaap.com','Contact email','2025-09-30 15:39:16'),
(3,'items_per_page','20','Pagination limit','2025-09-30 15:39:16'),
(4,'max_image_size','5242880','Max image size in bytes (5MB)','2025-09-30 15:39:16'),
(5,'allowed_extensions','jpg,jpeg,png,gif','Allowed image extensions','2025-09-30 15:39:16');