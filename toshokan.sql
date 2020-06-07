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
  `time_create` datetime(0) NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`id_node`, `id_file`) USING BTREE,
  INDEX `status`(`status`) USING BTREE
) ENGINE = Aria CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci PAGE_CHECKSUM = 1 ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for assoc_node_tag
-- ----------------------------
DROP TABLE IF EXISTS `assoc_node_tag`;
CREATE TABLE `assoc_node_tag`  (
  `id_node` bigint(15) UNSIGNED NOT NULL,
  `id_tag` bigint(15) UNSIGNED NOT NULL,
  `time_create` datetime(0) NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`id_node`, `id_tag`) USING BTREE,
  INDEX `id_tag`(`id_tag`) USING BTREE
) ENGINE = Aria CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci PAGE_CHECKSUM = 1 ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for assoc_user_group_node
-- ----------------------------
DROP TABLE IF EXISTS `assoc_user_group_node`;
CREATE TABLE `assoc_user_group_node`  (
  `id_node` bigint(15) UNSIGNED NOT NULL,
  `id_user_group` bigint(15) UNSIGNED NOT NULL,
  `show` enum('show','hidden') CHARACTER SET ascii COLLATE ascii_bin NULL DEFAULT 'hidden',
  `time_create` datetime(0) NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`id_node`, `id_user_group`) USING BTREE,
  INDEX `id_user_group`(`id_user_group`) USING BTREE
) ENGINE = Aria CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci PAGE_CHECKSUM = 1 ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for file
-- ----------------------------
DROP TABLE IF EXISTS `file`;
CREATE TABLE `file`  (
  `id` bigint(15) UNSIGNED NOT NULL AUTO_INCREMENT,
  `hash` char(16) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `type` enum('audio','video','image','binary','text') CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT 'binary',
  `size` bigint(15) UNSIGNED NOT NULL DEFAULT 0,
  `time_create` datetime(0) NOT NULL DEFAULT current_timestamp,
  `time_update` datetime(0) NOT NULL DEFAULT current_timestamp ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `hash`(`hash`) USING BTREE
) ENGINE = Aria AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci PAGE_CHECKSUM = 1 ROW_FORMAT = Fixed;

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
  `time_create` datetime(0) NOT NULL DEFAULT current_timestamp,
  `time_update` datetime(0) NOT NULL DEFAULT current_timestamp ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`, `id_parent`) USING BTREE
) ENGINE = Aria AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '基本文件表' PAGE_CHECKSUM = 1 ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for node_index
-- ----------------------------
DROP TABLE IF EXISTS `node_index`;
CREATE TABLE `node_index`  (
  `id` bigint(15) UNSIGNED NOT NULL,
  `index` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `type` enum('folder','audio','video','image','binary','text') CHARACTER SET ascii COLLATE ascii_bin NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = Aria CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci PAGE_CHECKSUM = 1 ROW_FORMAT = Page;

-- ----------------------------
-- Table structure for node_info
-- ----------------------------
DROP TABLE IF EXISTS `node_info`;
CREATE TABLE `node_info`  (
  `id` bigint(15) UNSIGNED NOT NULL,
  `name` varchar(240) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` varchar(240) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = Aria CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci PAGE_CHECKSUM = 1 ROW_FORMAT = Page;

-- ----------------------------
-- Table structure for node_tree
-- ----------------------------
DROP TABLE IF EXISTS `node_tree`;
CREATE TABLE `node_tree`  (
  `id` bigint(15) UNSIGNED NOT NULL,
  `tree` text CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = Aria CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci PAGE_CHECKSUM = 1 ROW_FORMAT = Page;

-- ----------------------------
-- Table structure for tag
-- ----------------------------
DROP TABLE IF EXISTS `tag`;
CREATE TABLE `tag`  (
  `id` bigint(15) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_group` bigint(15) UNSIGNED NOT NULL,
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `time_create` datetime(0) NOT NULL DEFAULT current_timestamp,
  `time_update` datetime(0) NOT NULL DEFAULT current_timestamp ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = Aria AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for tag_group
-- ----------------------------
DROP TABLE IF EXISTS `tag_group`;
CREATE TABLE `tag_group`  (
  `id` bigint(15) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_node` bigint(15) UNSIGNED NOT NULL DEFAULT 0,
  `sort` bigint(15) NOT NULL DEFAULT 0,
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `time_create` datetime(0) NOT NULL DEFAULT current_timestamp,
  `time_update` datetime(0) NOT NULL DEFAULT current_timestamp ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = Aria AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci PAGE_CHECKSUM = 1 ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for tag_group_info
-- ----------------------------
DROP TABLE IF EXISTS `tag_group_info`;
CREATE TABLE `tag_group_info`  (
  `id` bigint(15) UNSIGNED NOT NULL,
  `name` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = Aria CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Page;

-- ----------------------------
-- Table structure for tag_info
-- ----------------------------
DROP TABLE IF EXISTS `tag_info`;
CREATE TABLE `tag_info`  (
  `id` bigint(15) UNSIGNED NOT NULL,
  `name` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = Aria CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Page;

-- ----------------------------
-- Table structure for user
-- ----------------------------
DROP TABLE IF EXISTS `user`;
CREATE TABLE `user`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  `status` tinyint(1) UNSIGNED NOT NULL,
  `time_create` datetime(0) NOT NULL DEFAULT current_timestamp,
  `time_update` datetime(0) NOT NULL DEFAULT current_timestamp ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = Aria AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci PAGE_CHECKSUM = 1 ROW_FORMAT = Page;

-- ----------------------------
-- Table structure for user_group
-- ----------------------------
DROP TABLE IF EXISTS `user_group`;
CREATE TABLE `user_group`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `status` tinyint(1) UNSIGNED NOT NULL,
  `time_create` datetime(0) NOT NULL DEFAULT current_timestamp,
  `time_update` datetime(0) NOT NULL DEFAULT current_timestamp ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = Aria AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci PAGE_CHECKSUM = 1 ROW_FORMAT = Page;

SET FOREIGN_KEY_CHECKS = 1;
