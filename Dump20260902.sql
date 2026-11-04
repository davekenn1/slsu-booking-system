CREATE DATABASE  IF NOT EXISTS `slsu_booking_system` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `slsu_booking_system`;
-- MySQL dump 10.13  Distrib 8.0.41, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: slsu_booking_system
-- ------------------------------------------------------
-- Server version	8.0.40

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `bookings`
--

DROP TABLE IF EXISTS `bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bookings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `facility_id` int NOT NULL,
  `booking_date` date NOT NULL,
  `time_slot` varchar(50) NOT NULL,
  `slot_interval` int DEFAULT '60',
  `total_price` decimal(10,2) DEFAULT '0.00',
  `purpose` varchar(50) NOT NULL,
  `organization_name` varchar(100) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `status` enum('pending','approved','rejected','cancelled') DEFAULT 'pending',
  `row_hash` varchar(64) DEFAULT NULL,
  `officer_remarks` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `facility_id` (`facility_id`),
  CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`facility_id`) REFERENCES `facilities` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bookings`
--

LOCK TABLES `bookings` WRITE;
/*!40000 ALTER TABLE `bookings` DISABLE KEYS */;
INSERT INTO `bookings` VALUES (3,3,1,'2026-04-12','Half Day (PM)',60,0.00,'Student Event','SSC','09704557263','rejected','e2ff214a953bd586d89ca93ce11f4f250e47aa63e473318d8dffa2d871acd7c7','Date is late','2026-04-11 02:50:49'),(4,3,2,'2026-04-24','Full Day',60,0.00,'Student Event','SSC','09704557263','rejected','fbd39d9b2699268b89e16ec22ff006c7bdda09a7c0c1b9f6d2929814bf8b261e','date is before','2026-04-11 02:51:16'),(5,3,1,'2026-04-24','Half Day (AM)',60,500.00,'Student Event','SSC','09704557263','cancelled','769bced8e069eb1c41dec20dfd2bebbda4b804eb92f70a6ad3913eb671f501d6',NULL,'2026-04-23 00:51:46'),(6,3,3,'2026-04-24','10:00 AM - 12:00 PM',60,200.00,'Student Event','SSC','09704557263','approved','693072dc3392c8ce9fe74493fdac9fe7b62a05c2f326b1366880fd04215125cc','','2026-04-23 01:25:48'),(7,3,4,'2026-05-15','Half Day (PM)',60,1000.00,'Student Event','SSC','09704557263','approved','77151ed1fef3d5cf0387408934284ca3531564ac19e660c70d3d25ce2789caba','','2026-04-25 00:21:39'),(8,3,3,'2026-04-29','8:00 AM - 10:00 AM',60,200.00,'Outside Organization','SSC','09704557263','approved','cd9c5cea97921de54a2641a6d75a7b838b651db2b70a655098683fdccef88615','','2026-04-28 00:27:55'),(9,3,1,'2026-05-01','3:00 PM - 5:00 PM',60,500.00,'Student Event','SSC','09704557263','approved','a85da2fba439b9d78a6da88f01fc4af1aec30267296cfcb52d3028d0e0146a37','','2026-04-28 09:51:15'),(10,3,1,'2026-04-30','Half Day (PM)',60,500.00,'Student Event','SSC','09704557263','approved','70b144bfb4a4ec7e3a76688f2aa9df00a7d116c93ccfef0ae552ba96e2c63e89','','2026-04-28 09:52:21'),(12,3,2,'2026-05-02','Full Day',60,300.00,'Outside Organization','SSC','09704557263','approved','f73bd122d934d662c3dcd9575bd43e8df8ee4607066adbc90c1c89e515007074','','2026-04-29 04:52:34'),(13,3,3,'2026-05-08','Full Day',60,200.00,'Outside Organization','SSC','09704557263','cancelled','514634966e624289467d0b679821380d4e2c7a383bedf26f64ba6357ef23a487',NULL,'2026-04-29 09:45:11'),(14,3,3,'2026-05-20','Half Day (AM)',60,200.00,'Student Event','SSC','09704557263','cancelled','ab471bb633c68adc4f14bfd718c36b11f103dcd6182f092d7d8c7285420aa735',NULL,'2026-05-19 10:27:45'),(15,3,3,'2026-05-23','Half Day (AM)',60,200.00,'University Event','SSC','09704557263','cancelled','f63970f40e363a4fe11359391c73268ee64f92506adae36a78deb8ff3e48c13d',NULL,'2026-05-19 10:31:01'),(16,3,3,'2026-05-23','Full Day',60,200.00,'Outside Organization','SSC','09704557263','cancelled','aa4ae6a69dbb2ede46015bd38a1a90883d0b9eacda2a7b4d736c557e0a62420a',NULL,'2026-05-19 10:39:14'),(17,3,4,'2026-05-25','Full Day',60,1000.00,'University Event','SSC','09704557263','cancelled','c4925bf75661597bd8cddeabcdf0402e4f119f2f4113713fff40f5fb274e760e',NULL,'2026-05-19 10:40:24'),(18,3,1,'2026-05-24','Half Day (AM)',60,500.00,'University Event','USSC','09704557263','approved','78c1260c89f2d7a7800bb4a2f69422853d5e1a674a28cea7d893560453b1b785','','2026-05-20 05:24:55'),(19,3,1,'2026-05-25','Half Day (PM)',60,500.00,'Student Event','USSC','09704557263','cancelled',NULL,NULL,'2026-05-22 07:26:48'),(20,3,1,'2026-05-28','Full Day',60,500.00,'Student Event','USSC','09704557263','approved',NULL,'','2026-05-22 07:29:17'),(21,3,2,'2026-05-23','Full Day',60,300.00,'Outside Organization','USSC','09704557263','approved',NULL,'','2026-05-22 08:05:28'),(22,3,7,'2026-06-04','Full Day',60,1000.00,'Outside Organization','USSC','09704557263','pending',NULL,NULL,'2026-05-30 00:49:20'),(23,3,1,'2026-09-03','8:00 AM - 12:00 PM',240,500.00,'For meeting ','USSC','09704557263','pending',NULL,NULL,'2026-09-01 03:10:56');
/*!40000 ALTER TABLE `bookings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `facilities`
--

DROP TABLE IF EXISTS `facilities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `facilities` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `image` varchar(255) DEFAULT 'default.jpg',
  `capacity` int DEFAULT '0',
  `price` decimal(10,2) DEFAULT '0.00',
  `status` enum('available','maintenance','closed') DEFAULT 'available',
  `row_hash` varchar(64) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `open_time` time DEFAULT '08:00:00',
  `close_time` time DEFAULT '20:00:00',
  `slot_interval` int DEFAULT '60',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `facilities`
--

LOCK TABLES `facilities` WRITE;
/*!40000 ALTER TABLE `facilities` DISABLE KEYS */;
INSERT INTO `facilities` VALUES (1,'Basketball Court','State-of-the-art basketball court, opened June 2022. Perfect for basketball, volleyball, and large events.','default.jpg',500,500.00,'available','8ae2d6875c3bc1f997b407272551bd891d66ea629f3516d8c7522a9a01b436c0','2026-04-07 23:28:57','08:00:00','20:00:00',60),(2,'USSC Plenary Hall (Mini Theater)','Air-conditioned hall with stage, sound system, and projector. Ideal for seminars, orientations, and performances.','default.jpg',200,300.00,'available','d742a6847cd3b0f92d22f5c54e556f1278c8f1030bad3f7f27238feac64ce8e2','2026-04-07 23:28:57','08:00:00','20:00:00',60),(3,'Indoor Sports Court','Covered court for badminton, table tennis, and other indoor sports.','default.jpg',150,200.00,'available','3aa3eb7127e3ac0c9ee93bfee9f3596a94f43e3c71b59ee9dc4939e554c8fc49','2026-04-07 23:28:57','08:00:00','20:00:00',60),(4,'Outdoor Sports Field','Multi-purpose field with oval track. Good for soccer, baseball, track and field, and large outdoor events.','default.jpg',1000,1000.00,'available','ece06477bf082fefd2f7da9e7f9a7ed77e0b961918472c3fffdb2ea47e878d8f','2026-04-07 23:28:57','08:00:00','20:00:00',60),(5,'Pickleball Court','Brand new pickleball court at SLSU Main Campus','default.jpg',20,300.00,'available','9f61aafa16b2f5d8aa7924b06d1c2e00ee1927693ccc8e1bd4e27e7341aa7b82','2026-04-08 02:32:51','08:00:00','20:00:00',60),(6,'AVR','For meetings and all ','default.jpg',200,300.00,'available','81f8e6349b1050fbdc04d65657a289c8943c29b477823fde3a605f0f0e456bb2','2026-05-21 01:57:36','08:00:00','20:00:00',60),(7,'Grandstand','For Certain Activities/Programs','default.jpg',500,1000.00,'available','4ec3cbcc00929767124ea93ad3e9a58ca2b2e319f4ad933bc2bc238a457c4abf','2026-05-21 01:59:56','08:00:00','20:00:00',60);
/*!40000 ALTER TABLE `facilities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hash_table`
--

DROP TABLE IF EXISTS `hash_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hash_table` (
  `id` int NOT NULL AUTO_INCREMENT,
  `table_name` varchar(50) NOT NULL,
  `record_id` int NOT NULL,
  `row_hash` varchar(64) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_table_record` (`table_name`,`record_id`)
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hash_table`
--

LOCK TABLES `hash_table` WRITE;
/*!40000 ALTER TABLE `hash_table` DISABLE KEYS */;
INSERT INTO `hash_table` VALUES (5,'facilities',1,'8ae2d6875c3bc1f997b407272551bd891d66ea629f3516d8c7522a9a01b436c0','2026-05-21 02:22:28','2026-05-22 07:59:51'),(6,'facilities',2,'d742a6847cd3b0f92d22f5c54e556f1278c8f1030bad3f7f27238feac64ce8e2','2026-05-21 02:22:28','2026-05-21 02:22:28'),(7,'facilities',3,'3aa3eb7127e3ac0c9ee93bfee9f3596a94f43e3c71b59ee9dc4939e554c8fc49','2026-05-21 02:22:28','2026-05-21 02:22:28'),(8,'facilities',4,'ece06477bf082fefd2f7da9e7f9a7ed77e0b961918472c3fffdb2ea47e878d8f','2026-05-21 02:22:28','2026-05-21 02:22:28'),(9,'facilities',5,'9f61aafa16b2f5d8aa7924b06d1c2e00ee1927693ccc8e1bd4e27e7341aa7b82','2026-05-21 02:22:28','2026-05-21 02:22:28'),(10,'facilities',6,'81f8e6349b1050fbdc04d65657a289c8943c29b477823fde3a605f0f0e456bb2','2026-05-21 02:22:28','2026-05-21 02:22:28'),(11,'facilities',7,'4ec3cbcc00929767124ea93ad3e9a58ca2b2e319f4ad933bc2bc238a457c4abf','2026-05-21 02:22:28','2026-08-26 01:26:30'),(12,'facilities',14,'3e499642ec4260af21dab33ee0a3b786a74926f7b5353452384d3a2ed39b94f6','2026-05-21 02:22:28','2026-05-21 02:22:28'),(20,'bookings',3,'e2ff214a953bd586d89ca93ce11f4f250e47aa63e473318d8dffa2d871acd7c7','2026-05-21 02:31:25','2026-05-21 02:31:25'),(21,'bookings',4,'fbd39d9b2699268b89e16ec22ff006c7bdda09a7c0c1b9f6d2929814bf8b261e','2026-05-21 02:31:25','2026-05-21 02:31:25'),(22,'bookings',5,'769bced8e069eb1c41dec20dfd2bebbda4b804eb92f70a6ad3913eb671f501d6','2026-05-21 02:31:25','2026-05-21 02:31:25'),(23,'bookings',6,'693072dc3392c8ce9fe74493fdac9fe7b62a05c2f326b1366880fd04215125cc','2026-05-21 02:31:25','2026-05-21 02:31:25'),(24,'bookings',7,'77151ed1fef3d5cf0387408934284ca3531564ac19e660c70d3d25ce2789caba','2026-05-21 02:31:25','2026-05-21 02:31:25'),(25,'bookings',8,'cd9c5cea97921de54a2641a6d75a7b838b651db2b70a655098683fdccef88615','2026-05-21 02:31:25','2026-05-21 02:31:25'),(26,'bookings',9,'a85da2fba439b9d78a6da88f01fc4af1aec30267296cfcb52d3028d0e0146a37','2026-05-21 02:31:25','2026-05-21 02:31:25'),(27,'bookings',10,'70b144bfb4a4ec7e3a76688f2aa9df00a7d116c93ccfef0ae552ba96e2c63e89','2026-05-21 02:31:25','2026-05-21 02:31:25'),(29,'bookings',12,'f73bd122d934d662c3dcd9575bd43e8df8ee4607066adbc90c1c89e515007074','2026-05-21 02:31:25','2026-05-21 02:31:25'),(30,'bookings',13,'514634966e624289467d0b679821380d4e2c7a383bedf26f64ba6357ef23a487','2026-05-21 02:31:25','2026-05-21 02:31:25'),(31,'bookings',14,'ab471bb633c68adc4f14bfd718c36b11f103dcd6182f092d7d8c7285420aa735','2026-05-21 02:31:25','2026-05-21 02:31:25'),(32,'bookings',15,'f63970f40e363a4fe11359391c73268ee64f92506adae36a78deb8ff3e48c13d','2026-05-21 02:31:25','2026-05-21 02:31:25'),(33,'bookings',16,'aa4ae6a69dbb2ede46015bd38a1a90883d0b9eacda2a7b4d736c557e0a62420a','2026-05-21 02:31:25','2026-05-21 02:31:25'),(34,'bookings',17,'c4925bf75661597bd8cddeabcdf0402e4f119f2f4113713fff40f5fb274e760e','2026-05-21 02:31:25','2026-05-21 02:31:25'),(35,'bookings',18,'78c1260c89f2d7a7800bb4a2f69422853d5e1a674a28cea7d893560453b1b785','2026-05-21 02:31:25','2026-05-21 02:31:25'),(51,'users',1,'388686ce049a67474ce800e9077727dc22c0c45ea238c2191760e6a8db7c1849','2026-05-21 02:31:33','2026-05-21 02:31:33'),(52,'users',2,'90b9df277c4b1e2b0145a70d0737876bfb9a009d1988f0780f82f7fb825eaf8a','2026-05-21 02:31:33','2026-05-21 02:31:33'),(53,'users',3,'5a798a525ffaf41835b78be30f8412afceaba31905e015f101f370bf7b378952','2026-05-21 02:31:33','2026-05-21 02:31:33'),(54,'users',4,'727a9c4eef66c20bd4395e1aca68d379fec8047b4fc14e511729048247a8f041','2026-05-21 02:31:33','2026-05-21 02:31:33'),(55,'users',5,'10ac1ca73de694f065f349b5534c5d61723d09175997b819513efdc8e7362118','2026-05-21 02:31:33','2026-05-21 02:31:33');
/*!40000 ALTER TABLE `hash_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,3,'❌ Your booking has been REJECTED. Reason: date is before',1,'2026-04-23 00:54:08'),(2,3,'❌ Your booking has been REJECTED. Reason: Date is late',1,'2026-04-23 00:54:14'),(3,3,'Your booking has been CANCELLED.',1,'2026-04-25 02:29:37'),(4,3,'✅ Your booking has been APPROVED!',1,'2026-04-26 00:41:23'),(5,3,'✅ Your booking has been APPROVED!',1,'2026-04-28 00:28:11'),(6,3,'✅ Your booking has been APPROVED!',1,'2026-04-28 00:28:13'),(7,3,'✅ Your booking for **Kingfisher Multi-Purpose Court (MPC)** has been APPROVED!',1,'2026-04-29 04:49:08'),(8,3,'✅ Your booking for **Kingfisher Multi-Purpose Court (MPC)** has been APPROVED!',1,'2026-04-29 04:49:11'),(9,3,'✅ Your booking for **USSC Plenary Hall (Mini Theater)** has been APPROVED!',1,'2026-04-29 04:53:02'),(10,3,'❌ Your booking for **Indoor Sports Court** has been CANCELLED.',1,'2026-05-19 10:35:07'),(11,3,'❌ Your booking for **Indoor Sports Court** has been CANCELLED.',1,'2026-05-19 10:39:22'),(12,3,'❌ Your booking for **Outdoor Sports Field** has been CANCELLED.',1,'2026-05-19 10:40:27'),(13,3,'✅ Your booking for **Kingfisher Multi-Purpose Court (MPC)** has been APPROVED!',1,'2026-05-20 05:25:21'),(14,3,'❌ Your booking for **Basketball Court** has been CANCELLED.',1,'2026-05-22 07:28:04'),(15,3,'❌ Your booking for **Indoor Sports Court** has been CANCELLED.',1,'2026-05-22 07:28:36'),(16,3,'✅ Your booking for **Basketball Court** has been APPROVED!',1,'2026-05-22 07:32:12'),(17,3,'✅ Your booking for **USSC Plenary Hall (Mini Theater)** has been APPROVED!',1,'2026-05-22 08:06:03');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reviews` (
  `id` int NOT NULL AUTO_INCREMENT,
  `booking_id` int NOT NULL,
  `facility_id` int NOT NULL,
  `user_id` int NOT NULL,
  `rating` int NOT NULL,
  `comment` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `booking_id` (`booking_id`),
  KEY `facility_id` (`facility_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`),
  CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`facility_id`) REFERENCES `facilities` (`id`),
  CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `reviews_chk_1` CHECK (((`rating` >= 1) and (`rating` <= 5)))
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reviews`
--

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
INSERT INTO `reviews` VALUES (1,8,3,3,5,'it is okay overall','2026-04-28 00:29:05'),(2,18,1,3,5,'Wow','2026-05-22 07:29:29'),(3,20,1,3,5,'','2026-05-22 07:32:42'),(4,21,2,3,5,'','2026-05-22 08:06:30');
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `role` enum('user','officer','admin') DEFAULT 'user',
  `row_hash` varchar(64) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','0192023a7bbd73250516f069df18b500','System Administrator','admin@slsu.edu.ph',NULL,'admin','388686ce049a67474ce800e9077727dc22c0c45ea238c2191760e6a8db7c1849','active','2026-04-07 23:29:47'),(2,'officer1','f611cd1f8f91638960d2d20b29f5c9ba','Juan Dela Cruz','officer@slsu.edu.ph',NULL,'officer','90b9df277c4b1e2b0145a70d0737876bfb9a009d1988f0780f82f7fb825eaf8a','active','2026-04-07 23:29:50'),(3,'student1','ad6a280417a0f533d8b670c61667e1a0','Maria Santos','student@slsu.edu.ph',NULL,'user','5a798a525ffaf41835b78be30f8412afceaba31905e015f101f370bf7b378952','active','2026-04-07 23:29:53'),(4,'Devkenn','81dc9bdb52d04dc20036dbd8313ed055','Dave Kenneth Otero','akotosidave1@gmail.com','09704557263','user','727a9c4eef66c20bd4395e1aca68d379fec8047b4fc14e511729048247a8f041','active','2026-04-15 03:59:36'),(5,'Davekenn','81dc9bdb52d04dc20036dbd8313ed055','Dave Kenneth Otero','davekenneth.otero@gmail.com','09704557263','user','10ac1ca73de694f065f349b5534c5d61723d09175997b819513efdc8e7362118','active','2026-04-15 10:27:38');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-02 11:47:42
