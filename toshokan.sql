
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for assoc_node_file
-- ----------------------------
DROP TABLE IF EXISTS `assoc_node_file`;
CREATE TABLE `assoc_node_file`  (
  `id_node` bigint(15) UNSIGNED NOT NULL,
  `id_file` bigint(15) UNSIGNED NOT NULL,
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_node`, `id_file`) USING BTREE,
  INDEX `status`(`id_node`, `status`) USING BTREE
) ENGINE = Aria CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci PAGE_CHECKSUM = 1 ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for assoc_node_tag
-- ----------------------------
DROP TABLE IF EXISTS `assoc_node_tag`;
CREATE TABLE `assoc_node_tag`  (
  `id_node` bigint(15) UNSIGNED NOT NULL,
  `id_tag` bigint(15) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_node`, `id_tag`) USING BTREE,
  INDEX `id_tag`(`id_tag`) USING BTREE
) ENGINE = Aria CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci PAGE_CHECKSUM = 1 ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for file
-- ----------------------------
DROP TABLE IF EXISTS `file`;
CREATE TABLE `file`  (
  `id` bigint(15) UNSIGNED NOT NULL AUTO_INCREMENT,
  `hash` char(16) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `suffix` char(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `type` enum('audio','video','image','binary','text') CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT 'binary',
  `size` bigint(15) UNSIGNED NOT NULL DEFAULT 0,
  `time_create` datetime(0) NOT NULL DEFAULT current_timestamp,
  `time_update` datetime(0) NOT NULL DEFAULT current_timestamp ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `hash`(`hash`) USING BTREE
) ENGINE = Aria AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci PAGE_CHECKSUM = 1 ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for node
-- ----------------------------
DROP TABLE IF EXISTS `node`;
CREATE TABLE `node`  (
  `id` bigint(15) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_parent` bigint(15) UNSIGNED NOT NULL DEFAULT 0,
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `sort` int(10) NOT NULL DEFAULT 0,
  `is_file` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `name` varchar(240) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` varchar(240) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `id_cover` bigint(15) UNSIGNED NOT NULL,
  `time_create` datetime(0) NOT NULL DEFAULT current_timestamp,
  `time_update` datetime(0) NOT NULL DEFAULT current_timestamp ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`, `id_parent`) USING BTREE,
  INDEX `id_parent`(`id_parent`, `status`, `sort`, `time_update`) USING BTREE
) ENGINE = Aria AUTO_INCREMENT = 4 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '基本文件表' PAGE_CHECKSUM = 1 ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for node_index
-- ----------------------------
DROP TABLE IF EXISTS `node_index`;
CREATE TABLE `node_index`  (
  `id` bigint(15) UNSIGNED NOT NULL,
  `index_base` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `index_tag` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `list_tag` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `list_node` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  FULLTEXT INDEX `name`(`index_tag`)
) ENGINE = Aria CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci PAGE_CHECKSUM = 1 ROW_FORMAT = Page;

-- ----------------------------
-- Table structure for settings
-- ----------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `time_create` datetime(0) NULL DEFAULT current_timestamp,
  `time_update` datetime(0) NULL DEFAULT current_timestamp ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for tag
-- ----------------------------
DROP TABLE IF EXISTS `tag`;
CREATE TABLE `tag`  (
  `id` bigint(15) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_group` bigint(15) UNSIGNED NOT NULL,
  `name` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `alt` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `time_create` datetime(0) NOT NULL DEFAULT current_timestamp,
  `time_update` datetime(0) NOT NULL DEFAULT current_timestamp ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `id_group`(`id_group`, `status`) USING BTREE
) ENGINE = Aria AUTO_INCREMENT = 7 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci PAGE_CHECKSUM = 1 ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for tag_group
-- ----------------------------
DROP TABLE IF EXISTS `tag_group`;
CREATE TABLE `tag_group`  (
  `id` bigint(15) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_node` bigint(15) UNSIGNED NOT NULL DEFAULT 0,
  `name` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `alt` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `sort` bigint(15) NOT NULL DEFAULT 0,
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `time_create` datetime(0) NOT NULL DEFAULT current_timestamp,
  `time_update` datetime(0) NOT NULL DEFAULT current_timestamp ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `id_node`(`id_node`, `status`, `sort`) USING BTREE
) ENGINE = Aria AUTO_INCREMENT = 4 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci PAGE_CHECKSUM = 1 ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for user
-- ----------------------------
DROP TABLE IF EXISTS `user`;
CREATE TABLE `user`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_group` int(10) UNSIGNED NOT NULL,
  `name` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `mail` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  `status` tinyint(1) UNSIGNED NOT NULL,
  `time_create` datetime(0) NOT NULL DEFAULT current_timestamp,
  `time_update` datetime(0) NOT NULL DEFAULT current_timestamp ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = Aria AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci PAGE_CHECKSUM = 1 ROW_FORMAT = Page;

-- ----------------------------
-- Table structure for user_group
-- ----------------------------
DROP TABLE IF EXISTS `user_group`;
CREATE TABLE `user_group`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `admin` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `status` tinyint(1) UNSIGNED NOT NULL,
  `time_create` datetime(0) NOT NULL DEFAULT current_timestamp,
  `time_update` datetime(0) NOT NULL DEFAULT current_timestamp ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = Aria AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci PAGE_CHECKSUM = 1 ROW_FORMAT = Page;

-- ----------------------------
-- Table structure for user_group_auth
-- ----------------------------
DROP TABLE IF EXISTS `user_group_auth`;
CREATE TABLE `user_group_auth`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_group` int(10) UNSIGNED NOT NULL,
  `id_node` bigint(15) UNSIGNED NOT NULL,
  `access` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `modify` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `delete` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = Aria AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Fixed;

-- ----------------------------
-- View structure for tag_view
-- ----------------------------
DROP VIEW IF EXISTS `tag_view`;
CREATE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `tag_view` AS select `tg`.`id` AS `id`,`tg`.`id_group` AS `id_group`,`tg`.`time_create` AS `time_create`,`tg`.`time_update` AS `time_update`,`ti`.`alt` AS `alt`,`ti`.`description` AS `description`,`ti`.`name` AS `name` from (`tag` `tg` left join `tag_info` `ti` on(`tg`.`id` = `ti`.`id`));

SET FOREIGN_KEY_CHECKS = 1;
