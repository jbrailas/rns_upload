CREATE TABLE `ihs14_rns_upload_files` (
  `id` int NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `catid` int NOT NULL DEFAULT '0',
  `filename` varchar(350) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `filepath` varchar(600) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `published` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;