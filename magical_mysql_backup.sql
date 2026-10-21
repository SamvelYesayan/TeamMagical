-- MySQL dump 10.13  Distrib 8.0.46, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: magical
-- ------------------------------------------------------
-- Server version	8.0.46

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `action` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_type` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject_id` bigint unsigned DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `properties` json DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `activity_logs_user_id_foreign` (`user_id`),
  KEY `activity_logs_subject_type_subject_id_index` (`subject_type`,`subject_id`),
  KEY `activity_logs_action_index` (`action`),
  KEY `activity_logs_created_at_index` (`created_at`),
  CONSTRAINT `activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES (1,1,'auth.login','User',1,'Մուտք համակարգ',NULL,'127.0.0.1','2026-10-17 09:29:12'),(2,1,'auth.logout','User',1,'Ելք համակարգից',NULL,'127.0.0.1','2026-10-17 09:29:52'),(3,1,'auth.login','User',1,'Մուտք համակարգ',NULL,'127.0.0.1','2026-10-17 09:37:41'),(4,1,'deadline.created','Deadline',1,'Ստեղծվեց դեդլայն՝ Mherin cccnel',NULL,'127.0.0.1','2026-10-17 09:38:29'),(5,1,'deadline.assigned','Deadline',1,'«Mherin cccnel» → Sam Yesayan','{\"assigned_to\": 1}','127.0.0.1','2026-10-17 09:38:29'),(6,1,'deadline.started','Deadline',1,'Սկսվեց՝ Mherin cccnel',NULL,'127.0.0.1','2026-10-17 09:38:43'),(7,1,'submission.created','Submission',1,'Հանձնվեց աշխատանք #1՝ Mherin cccnel','{\"deadline_id\": 1}','127.0.0.1','2026-10-17 09:39:22'),(8,1,'submission.review_started','Deadline',1,'Սկսվեց ստուգումը՝ Mherin cccnel',NULL,'127.0.0.1','2026-10-17 09:39:27'),(9,1,'submission.needs_revision','Submission',1,'Թերի է՝ Mherin cccnel','{\"feedback\": \"masdasdas\", \"deadline_id\": 1}','127.0.0.1','2026-10-17 09:39:35'),(10,1,'project.created','Project',1,'Ստեղծվեց նախագիծ՝ Socarm',NULL,'127.0.0.1','2026-10-17 09:39:50'),(11,1,'announcement.created','Announcement',1,'Ստեղծվեց հայտարարություն՝ asdasd',NULL,'127.0.0.1','2026-10-17 09:39:59'),(12,1,'announcement.published','Announcement',1,'Հրապարակվեց՝ asdasd',NULL,'127.0.0.1','2026-10-17 09:39:59'),(13,1,'submission.created','Submission',2,'Հանձնվեց աշխատանք #2՝ Mherin cccnel','{\"deadline_id\": 1}','127.0.0.1','2026-10-17 09:42:00'),(14,1,'submission.approved','Submission',2,'Հաստատվեց աշխատանքը՝ Mherin cccnel','{\"deadline_id\": 1}','127.0.0.1','2026-10-17 09:42:16'),(15,1,'announcement.created','Announcement',2,'Ստեղծվեց հայտարարություն՝ sad',NULL,'127.0.0.1','2026-10-17 09:42:38'),(16,1,'announcement.published','Announcement',2,'Հրապարակվեց՝ sad',NULL,'127.0.0.1','2026-10-17 09:42:38'),(17,1,'announcement.updated','Announcement',2,'Խմբագրվեց հայտարարություն՝ sad',NULL,'127.0.0.1','2026-10-17 09:43:13'),(18,1,'auth.logout','User',1,'Ելք համակարգից',NULL,'127.0.0.1','2026-10-17 13:11:45'),(19,1,'auth.login','User',1,'Մուտք համակարգ',NULL,'127.0.0.1','2026-10-17 13:12:00'),(20,1,'deadline.created','Deadline',2,'Ստեղծվեց դեդլայն՝ kayq sarqel',NULL,'127.0.0.1','2026-10-17 13:13:23'),(21,1,'deadline.assigned','Deadline',2,'«kayq sarqel» → Sam Yesayan','{\"assigned_to\": 1}','127.0.0.1','2026-10-17 13:13:23'),(22,1,'deadline.started','Deadline',2,'Սկսվեց՝ kayq sarqel',NULL,'127.0.0.1','2026-10-17 13:13:48'),(23,1,'submission.created','Submission',3,'Հանձնվեց աշխատանք #1՝ kayq sarqel','{\"deadline_id\": 2}','127.0.0.1','2026-10-17 13:14:12'),(24,1,'submission.review_started','Deadline',2,'Սկսվեց ստուգումը՝ kayq sarqel',NULL,'127.0.0.1','2026-10-17 13:14:30'),(25,1,'submission.needs_revision','Submission',3,'Թերի է՝ kayq sarqel','{\"feedback\": \"sdadasdasdsa\", \"deadline_id\": 2}','127.0.0.1','2026-10-17 13:14:45'),(26,1,'submission.created','Submission',4,'Հանձնվեց աշխատանք #2՝ kayq sarqel','{\"deadline_id\": 2}','127.0.0.1','2026-10-17 13:14:59'),(27,1,'submission.review_started','Deadline',2,'Սկսվեց ստուգումը՝ kayq sarqel',NULL,'127.0.0.1','2026-10-17 13:15:03'),(28,1,'submission.approved','Submission',4,'Հաստատվեց աշխատանքը՝ kayq sarqel','{\"deadline_id\": 2}','127.0.0.1','2026-10-17 13:15:13'),(29,1,'auth.logout','User',1,'Ելք համակարգից',NULL,'127.0.0.1','2026-10-17 13:17:07'),(30,NULL,'auth.login_failed',NULL,NULL,'Անհաջող մուտքի փորձ՝ samyesayan','{\"username\": \"samyesayan\"}','192.168.1.103','2026-10-19 06:24:42'),(31,1,'auth.login_failed',NULL,NULL,'Անհաջող մուտքի փորձ՝ sam_yesayan','{\"username\": \"sam_yesayan\"}','192.168.1.103','2026-10-19 06:29:10'),(32,NULL,'auth.login_failed',NULL,NULL,'Անհաջող մուտքի փորձ՝ sam_yeaayan','{\"username\": \"sam_yeaayan\"}','192.168.1.103','2026-10-19 06:37:29'),(33,NULL,'auth.login_failed',NULL,NULL,'Անհաջող մուտքի փորձ՝ sam_yeaayan','{\"username\": \"sam_yeaayan\"}','192.168.1.103','2026-10-19 06:37:35'),(34,1,'auth.login_failed',NULL,NULL,'Անհաջող մուտքի փորձ՝ sam_yesayan','{\"username\": \"sam_yesayan\"}','127.0.0.1','2026-10-19 06:40:13'),(35,1,'auth.login_failed',NULL,NULL,'Անհաջող մուտքի փորձ՝ sam_yesayan','{\"username\": \"sam_yesayan\"}','127.0.0.1','2026-10-19 06:40:29'),(36,1,'auth.login_failed',NULL,NULL,'Անհաջող մուտքի փորձ՝ sam_yesayan','{\"username\": \"sam_yesayan\"}','127.0.0.1','2026-10-19 06:40:50'),(37,NULL,'auth.login_failed',NULL,NULL,'Անհաջող մուտքի փորձ՝ sam_yeaayan','{\"username\": \"sam_yeaayan\"}','192.168.1.103','2026-10-19 06:43:23'),(38,1,'auth.login','User',1,'Մուտք համակարգ (բջջային՝ mobile-test)',NULL,'127.0.0.1','2026-10-19 06:44:17'),(39,NULL,'auth.login_failed',NULL,NULL,'Անհաջող մուտքի փորձ՝ sam_yeaayan','{\"username\": \"sam_yeaayan\"}','192.168.1.103','2026-10-19 06:47:52'),(40,1,'auth.login','User',1,'Մուտք համակարգ (բջջային՝ mobile-android)',NULL,'192.168.1.103','2026-10-20 05:29:48'),(41,1,'auth.login','User',1,'Մուտք համակարգ (բջջային՝ mobile-android)',NULL,'192.168.1.103','2026-10-20 05:38:47'),(42,1,'auth.login','User',1,'Մուտք համակարգ (բջջային՝ mobile-android)',NULL,'192.168.1.103','2026-10-20 05:43:02'),(43,1,'auth.login','User',1,'Մուտք համակարգ (բջջային՝ mobile-android)',NULL,'192.168.1.103','2026-10-20 05:47:07'),(44,1,'auth.login','User',1,'Մուտք համակարգ (բջջային՝ mobile-android)',NULL,'192.168.1.103','2026-10-20 05:52:03'),(45,1,'auth.login','User',1,'Մուտք համակարգ (բջջային՝ mobile-android)',NULL,'192.168.1.103','2026-10-20 05:54:54'),(46,1,'auth.login','User',1,'Մուտք համակարգ',NULL,'127.0.0.1','2026-10-20 05:55:15'),(47,1,'project.deleted',NULL,NULL,'Ջնջվեց նախագիծ՝ Socarm',NULL,'127.0.0.1','2026-10-20 05:56:11'),(48,1,'announcement.deleted',NULL,NULL,'Ջնջվեց հայտարարություն՝ sad',NULL,'127.0.0.1','2026-10-20 05:56:18'),(49,1,'announcement.deleted',NULL,NULL,'Ջնջվեց հայտարարություն՝ asdasd',NULL,'127.0.0.1','2026-10-20 05:56:21');
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `announcement_attachments`
--

DROP TABLE IF EXISTS `announcement_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `announcement_attachments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `announcement_id` bigint unsigned NOT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size` bigint unsigned NOT NULL,
  `kind` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'file',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `announcement_attachments_announcement_id_foreign` (`announcement_id`),
  CONSTRAINT `announcement_attachments_announcement_id_foreign` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `announcement_attachments`
--

LOCK TABLES `announcement_attachments` WRITE;
/*!40000 ALTER TABLE `announcement_attachments` DISABLE KEYS */;
/*!40000 ALTER TABLE `announcement_attachments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `announcements`
--

DROP TABLE IF EXISTS `announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `announcements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_mime` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `author_id` bigint unsigned DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `published_at` datetime DEFAULT NULL,
  `notified_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `announcements_author_id_foreign` (`author_id`),
  KEY `announcements_status_index` (`status`),
  KEY `announcements_published_at_index` (`published_at`),
  CONSTRAINT `announcements_author_id_foreign` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `announcements`
--

LOCK TABLES `announcements` WRITE;
/*!40000 ALTER TABLE `announcements` DISABLE KEYS */;
/*!40000 ALTER TABLE `announcements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `deadline_attachments`
--

DROP TABLE IF EXISTS `deadline_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `deadline_attachments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `deadline_id` bigint unsigned NOT NULL,
  `uploaded_by` bigint unsigned DEFAULT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size` bigint unsigned NOT NULL,
  `kind` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'file',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `deadline_attachments_deadline_id_foreign` (`deadline_id`),
  KEY `deadline_attachments_uploaded_by_foreign` (`uploaded_by`),
  CONSTRAINT `deadline_attachments_deadline_id_foreign` FOREIGN KEY (`deadline_id`) REFERENCES `deadlines` (`id`) ON DELETE CASCADE,
  CONSTRAINT `deadline_attachments_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `deadline_attachments`
--

LOCK TABLES `deadline_attachments` WRITE;
/*!40000 ALTER TABLE `deadline_attachments` DISABLE KEYS */;
INSERT INTO `deadline_attachments` VALUES (1,2,1,'photo_2026-10-17_12-07-27 (2).jpg','deadlines/2/e9f5c080-6144-4273-8355-68bb38203bfd.jpg','image/jpeg',133679,'image','2026-10-17 09:13:23','2026-10-17 09:13:23');
/*!40000 ALTER TABLE `deadline_attachments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `deadline_change_requests`
--

DROP TABLE IF EXISTS `deadline_change_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `deadline_change_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `deadline_id` bigint unsigned NOT NULL,
  `requested_by` bigint unsigned DEFAULT NULL,
  `previous_due_at` datetime NOT NULL,
  `proposed_due_at` datetime NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `status_before` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `decided_by` bigint unsigned DEFAULT NULL,
  `decision_comment` text COLLATE utf8mb4_unicode_ci,
  `decided_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `deadline_change_requests_requested_by_foreign` (`requested_by`),
  KEY `deadline_change_requests_decided_by_foreign` (`decided_by`),
  KEY `deadline_change_requests_deadline_id_status_index` (`deadline_id`,`status`),
  KEY `deadline_change_requests_status_index` (`status`),
  CONSTRAINT `deadline_change_requests_deadline_id_foreign` FOREIGN KEY (`deadline_id`) REFERENCES `deadlines` (`id`) ON DELETE CASCADE,
  CONSTRAINT `deadline_change_requests_decided_by_foreign` FOREIGN KEY (`decided_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `deadline_change_requests_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `deadline_change_requests`
--

LOCK TABLES `deadline_change_requests` WRITE;
/*!40000 ALTER TABLE `deadline_change_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `deadline_change_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `deadline_events`
--

DROP TABLE IF EXISTS `deadline_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `deadline_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `deadline_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `type` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` json DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `deadline_events_user_id_foreign` (`user_id`),
  KEY `deadline_events_deadline_id_created_at_index` (`deadline_id`,`created_at`),
  CONSTRAINT `deadline_events_deadline_id_foreign` FOREIGN KEY (`deadline_id`) REFERENCES `deadlines` (`id`) ON DELETE CASCADE,
  CONSTRAINT `deadline_events_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `deadline_events`
--

LOCK TABLES `deadline_events` WRITE;
/*!40000 ALTER TABLE `deadline_events` DISABLE KEYS */;
INSERT INTO `deadline_events` VALUES (1,1,1,'created','{\"due_at\": \"2026-10-23T09:38:00.000000Z\", \"assigned_to\": \"Sam Yesayan\", \"self_assigned\": true}','2026-10-17 09:38:29'),(2,1,1,'started',NULL,'2026-10-17 09:38:43'),(3,1,1,'submitted','{\"files\": 1, \"number\": 1, \"submission_id\": 1}','2026-10-17 09:39:22'),(4,1,1,'review_started',NULL,'2026-10-17 09:39:27'),(5,1,1,'revision_requested','{\"number\": 1, \"feedback\": \"masdasdas\", \"submission_id\": 1}','2026-10-17 09:39:35'),(6,1,1,'submitted','{\"files\": 0, \"number\": 2, \"submission_id\": 2}','2026-10-17 09:42:00'),(7,1,1,'approved','{\"number\": 2, \"feedback\": \"sadsad\", \"submission_id\": 2}','2026-10-17 09:42:16'),(8,2,1,'created','{\"due_at\": \"2026-10-24T13:12:00.000000Z\", \"assigned_to\": \"Sam Yesayan\", \"self_assigned\": true}','2026-10-17 13:13:19'),(9,2,1,'started',NULL,'2026-10-17 13:13:48'),(10,2,1,'submitted','{\"files\": 1, \"number\": 1, \"submission_id\": 3}','2026-10-17 13:14:12'),(11,2,1,'review_started',NULL,'2026-10-17 13:14:30'),(12,2,1,'revision_requested','{\"number\": 1, \"feedback\": \"sdadasdasdsa\", \"submission_id\": 3}','2026-10-17 13:14:45'),(13,2,1,'submitted','{\"files\": 0, \"number\": 2, \"submission_id\": 4}','2026-10-17 13:14:59'),(14,2,1,'review_started',NULL,'2026-10-17 13:15:03'),(15,2,1,'approved','{\"number\": 2, \"feedback\": \"apres\", \"submission_id\": 4}','2026-10-17 13:15:13');
/*!40000 ALTER TABLE `deadline_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `deadlines`
--

DROP TABLE IF EXISTS `deadlines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `deadlines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `project_id` bigint unsigned DEFAULT NULL,
  `assigned_to` bigint unsigned NOT NULL,
  `created_by` bigint unsigned NOT NULL,
  `starts_at` datetime DEFAULT NULL,
  `due_at` datetime NOT NULL,
  `priority` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'new',
  `confirmed_at` datetime DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `reminder_sent_at` datetime DEFAULT NULL,
  `overdue_notified_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `deadlines_project_id_foreign` (`project_id`),
  KEY `deadlines_status_index` (`status`),
  KEY `deadlines_due_at_index` (`due_at`),
  KEY `deadlines_priority_index` (`priority`),
  KEY `deadlines_assigned_to_status_index` (`assigned_to`,`status`),
  KEY `deadlines_created_by_index` (`created_by`),
  CONSTRAINT `deadlines_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `deadlines_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `deadlines_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `deadlines`
--

LOCK TABLES `deadlines` WRITE;
/*!40000 ALTER TABLE `deadlines` DISABLE KEYS */;
INSERT INTO `deadlines` VALUES (1,'Mherin cccnel','mher',NULL,1,1,'2026-10-17 09:38:00','2026-10-23 09:38:00','urgent','completed','2026-10-17 09:38:29','2026-10-17 09:38:43','2026-10-17 09:42:00','2026-10-17 09:42:16',NULL,NULL,'2026-10-17 05:38:29','2026-10-17 05:42:16',NULL),(2,'kayq sarqel','asdasd',NULL,1,1,'2026-10-17 13:12:00','2026-10-24 13:12:00','urgent','completed','2026-10-17 13:13:19','2026-10-17 13:13:48','2026-10-17 13:14:59','2026-10-17 13:15:13',NULL,NULL,'2026-10-17 09:13:19','2026-10-17 09:15:13',NULL);
/*!40000 ALTER TABLE `deadlines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2026_01_01_000001_create_roles_table',1),(2,'2026_01_01_000002_create_users_table',1),(3,'2026_01_01_000003_create_settings_table',1),(4,'2026_01_01_000004_create_activity_logs_table',1),(5,'2026_01_01_000010_create_projects_table',1),(6,'2026_01_01_000011_create_deadlines_table',1),(7,'2026_01_01_000012_create_deadline_attachments_table',1),(8,'2026_01_01_000013_create_deadline_change_requests_table',1),(9,'2026_01_01_000014_create_deadline_events_table',1),(10,'2026_01_01_000015_create_submissions_table',1),(11,'2026_01_01_000020_create_announcements_tables',1),(12,'2026_01_01_000021_create_notifications_table',1),(13,'2026_01_01_000030_create_personal_access_tokens_table',2);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `type` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci,
  `link` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data` json DEFAULT NULL,
  `read_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_user_id_read_at_index` (`user_id`,`read_at`),
  KEY `notifications_user_id_created_at_index` (`user_id`,`created_at`),
  CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
INSERT INTO `personal_access_tokens` VALUES (1,'App\\Models\\User',1,'mobile-test','164314b6ec2172f4907a12383c8d719f21dbb66ec1fe7c664943644e33aded76','[\"*\"]',NULL,NULL,'2026-10-19 02:44:17','2026-10-19 02:44:17'),(7,'App\\Models\\User',1,'mobile-android','a791ee3f67ff61293c7c03d0f5c8d2f1883d9e5c43ac1acd6249a40ec50920ba','[\"*\"]','2026-10-20 01:55:25',NULL,'2026-10-20 01:54:54','2026-10-20 01:55:25');
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `projects`
--

DROP TABLE IF EXISTS `projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `projects` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `color` varchar(9) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `projects_name_unique` (`name`),
  KEY `projects_created_by_foreign` (`created_by`),
  KEY `projects_status_index` (`status`),
  CONSTRAINT `projects_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `projects`
--

LOCK TABLES `projects` WRITE;
/*!40000 ALTER TABLE `projects` DISABLE KEYS */;
/*!40000 ALTER TABLE `projects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'admin','Ադմինիստրատոր','2026-10-17 05:03:07','2026-10-17 05:03:07'),(2,'employee','Աշխատակից','2026-10-17 05:03:07','2026-10-17 05:03:07');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `submission_files`
--

DROP TABLE IF EXISTS `submission_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `submission_files` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `submission_id` bigint unsigned NOT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size` bigint unsigned NOT NULL,
  `kind` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'file',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `submission_files_submission_id_foreign` (`submission_id`),
  CONSTRAINT `submission_files_submission_id_foreign` FOREIGN KEY (`submission_id`) REFERENCES `submissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `submission_files`
--

LOCK TABLES `submission_files` WRITE;
/*!40000 ALTER TABLE `submission_files` DISABLE KEYS */;
INSERT INTO `submission_files` VALUES (1,1,'photo_2026-10-17_12-07-27 (2).jpg','submissions/1/1/ba6e54c7-b846-4aba-b0de-c7b78752380b.jpg','image/jpeg',133679,'image','2026-10-17 05:39:22','2026-10-17 05:39:22'),(2,3,'photo_2026-10-17_12-07-27.jpg','submissions/2/3/9a2b8d67-3baa-492d-b61b-b96d14c95ada.jpg','image/jpeg',122541,'image','2026-10-17 09:14:12','2026-10-17 09:14:12');
/*!40000 ALTER TABLE `submission_files` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `submissions`
--

DROP TABLE IF EXISTS `submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `submissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `deadline_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `number` int unsigned NOT NULL,
  `text` longtext COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `feedback` text COLLATE utf8mb4_unicode_ci,
  `reviewed_by` bigint unsigned DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `submissions_deadline_id_number_unique` (`deadline_id`,`number`),
  KEY `submissions_user_id_foreign` (`user_id`),
  KEY `submissions_reviewed_by_foreign` (`reviewed_by`),
  KEY `submissions_status_index` (`status`),
  CONSTRAINT `submissions_deadline_id_foreign` FOREIGN KEY (`deadline_id`) REFERENCES `deadlines` (`id`) ON DELETE CASCADE,
  CONSTRAINT `submissions_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `submissions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `submissions`
--

LOCK TABLES `submissions` WRITE;
/*!40000 ALTER TABLE `submissions` DISABLE KEYS */;
INSERT INTO `submissions` VALUES (1,1,1,1,'asdsadasdsadasdas','needs_revision','masdasdas',1,'2026-10-17 09:39:35','2026-10-17 05:39:22','2026-10-17 05:39:35'),(2,1,1,2,'zxcd','approved','sadsad',1,'2026-10-17 09:42:16','2026-10-17 05:42:00','2026-10-17 05:42:16'),(3,2,1,1,'kdldjlfd','needs_revision','sdadasdasdsa',1,'2026-10-17 13:14:45','2026-10-17 09:14:12','2026-10-17 09:14:45'),(4,2,1,2,'qweqw','approved','apres',1,'2026-10-17 13:15:13','2026-10-17 09:14:59','2026-10-17 09:15:13');
/*!40000 ALTER TABLE `submissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  `position` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `must_change_password` tinyint(1) NOT NULL DEFAULT '0',
  `last_login_at` datetime DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_username_unique` (`username`),
  KEY `users_role_id_foreign` (`role_id`),
  KEY `users_status_index` (`status`),
  CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Sam Yesayan','sam_yesayan','$2y$12$oHPeuDu5hgfOzN4mGHP8Z.JFIBRwSypSFGxTWzRsV5gbmnXyakJMS',1,'Ադմինիստրատոր','active',0,'2026-10-20 05:55:15',NULL,'2026-10-17 05:03:08','2026-10-20 01:55:15',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'magical'
--

--
-- Dumping routines for database 'magical'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-20 10:01:54
