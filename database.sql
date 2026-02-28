-- Copyright (c) 2026 徐州邻兔跃IT工作室
-- 联系邮箱：lintuyue@qq.com
-- 官方网站：lintuyue.com
-- 技术交流QQ群：550133641
-- 
-- 版权所有，禁止未经授权的复制、传播、修改或商业使用。
-- 未经开发者书面授权，任何单位或个人不得以任何形式复制、传播、
-- 展示、修改本软件或制作衍生作品。

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+08:00";

-- =====================================================
-- 第一部分：创建所有表结构（独立事务，确保表一定被创建）
-- =====================================================
START TRANSACTION;

CREATE TABLE IF NOT EXISTS `lty-roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT '角色名称',
  `slug` varchar(50) NOT NULL COMMENT '角色标识',
  `description` varchar(255) DEFAULT NULL COMMENT '角色描述',
  `is_system` tinyint(1) DEFAULT 0 COMMENT '是否系统角色：1是 0否',
  `sort_order` int(11) DEFAULT 0 COMMENT '排序',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态：1启用 0禁用',
  `email_notify_enabled` tinyint(1) DEFAULT 0 COMMENT '邮件通知：1启用 0禁用',
  `email_notify_types` text DEFAULT NULL COMMENT '通知类型JSON数组',
  `email_notify_content` text DEFAULT NULL COMMENT '自定义通知内容',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色表';

CREATE TABLE IF NOT EXISTS `lty-admin_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL COMMENT '角色ID',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态：1启用 0禁用',
  `last_login` timestamp NULL DEFAULT NULL COMMENT '最后登录时间',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `fk_admin_role` FOREIGN KEY (`role_id`) REFERENCES `lty-roles` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lty-permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '权限名称',
  `slug` varchar(100) NOT NULL COMMENT '权限标识',
  `description` varchar(255) DEFAULT NULL COMMENT '权限描述',
  `group` varchar(50) DEFAULT NULL COMMENT '权限分组',
  `sort_order` int(11) DEFAULT 0 COMMENT '排序',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态：1启用 0禁用',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='权限表';

CREATE TABLE IF NOT EXISTS `lty-role_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL COMMENT '角色ID',
  `permission_id` int(11) NOT NULL COMMENT '权限ID',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_permission` (`role_id`, `permission_id`),
  KEY `role_id` (`role_id`),
  KEY `permission_id` (`permission_id`),
  CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `lty-roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_rp_permission` FOREIGN KEY (`permission_id`) REFERENCES `lty-permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色权限关联表';

CREATE TABLE IF NOT EXISTS `lty-admin_menus` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT NULL COMMENT '父菜单ID',
  `name` varchar(100) NOT NULL COMMENT '菜单名称',
  `slug` varchar(100) NOT NULL COMMENT '菜单标识',
  `url` varchar(255) DEFAULT NULL COMMENT '菜单链接',
  `icon` text DEFAULT NULL COMMENT '菜单图标',
  `type` enum('category','menu','link') DEFAULT 'menu' COMMENT '类型：category分类 menu菜单 link链接',
  `sort_order` int(11) DEFAULT 0 COMMENT '排序',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态：1启用 0禁用',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `parent_id` (`parent_id`),
  CONSTRAINT `fk_menu_parent` FOREIGN KEY (`parent_id`) REFERENCES `lty-admin_menus` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='后台菜单表';

CREATE TABLE IF NOT EXISTS `lty-role_menus` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL COMMENT '角色ID',
  `menu_id` int(11) NOT NULL COMMENT '菜单ID',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_menu` (`role_id`, `menu_id`),
  KEY `role_id` (`role_id`),
  KEY `menu_id` (`menu_id`),
  CONSTRAINT `fk_rm_role` FOREIGN KEY (`role_id`) REFERENCES `lty-roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_rm_menu` FOREIGN KEY (`menu_id`) REFERENCES `lty-admin_menus` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色菜单关联表';

INSERT INTO `lty-roles` (`name`, `slug`, `description`, `is_system`, `sort_order`, `status`, `email_notify_enabled`, `email_notify_types`) VALUES
('超级管理员', 'super_admin', '拥有系统所有权限', 1, 1, 1, 1, '["contact","system","order"]'),
('管理员', 'admin', '拥有大部分管理权限', 1, 2, 1, 1, '["contact","system","order"]'),
('编辑', 'editor', '内容编辑权限', 0, 3, 1, 0, NULL),
('访客', 'viewer', '只读权限', 0, 4, 1, 0, NULL)
ON DUPLICATE KEY UPDATE `slug` = `slug`;

INSERT INTO `lty-permissions` (`name`, `slug`, `description`, `group`, `sort_order`, `status`) VALUES
('仪表盘查看', 'dashboard_view', '查看仪表盘', '仪表盘', 1, 1),
('媒体库管理', 'media_manage', '管理媒体库文件', '媒体', 10, 1),
('媒体库查看', 'media_view', '查看媒体库', '媒体', 11, 1),
('首页轮播管理', 'mission_manage', '管理首页轮播图', '官网管理', 20, 1),
('公司简介管理', 'story_manage', '管理公司简介', '官网管理', 21, 1),
('企业文化管理', 'culture_manage', '管理企业文化', '官网管理', 22, 1),
('大事记管理', 'timeline_manage', '管理大事记', '官网管理', 23, 1),
('团队成员管理', 'members_manage', '管理团队成员', '官网管理', 24, 1),
('行为准则管理', 'behavior_manage', '管理行为准则', '官网管理', 25, 1),
('产品管理', 'products_manage', '管理产品', '官网管理', 26, 1),
('业务领域管理', 'business_manage', '管理业务领域', '官网管理', 27, 1),
('联系我们管理', 'contact_manage', '管理联系我们', '官网管理', 28, 1),
('留言管理', 'messages_manage', '管理留言', '互动管理', 30, 1),
('网站设置', 'settings_manage', '管理网站设置', '系统设置', 40, 1),
('SEO优化管理', 'seo_manage', '管理SEO优化设置', '系统设置', 41, 1),
('角色管理', 'roles_manage', '管理角色权限', '开发配置', 50, 1),
('权限管理', 'permissions_manage', '管理系统权限', '开发配置', 51, 1),
('后台用户管理', 'users_manage', '管理后台用户', '开发配置', 52, 1)
ON DUPLICATE KEY UPDATE `name` = `name`;

INSERT INTO `lty-role_permissions` (`role_id`, `permission_id`) 
SELECT 1, id FROM `lty-permissions`
ON DUPLICATE KEY UPDATE `role_id` = `role_id`;

INSERT INTO `lty-role_permissions` (`role_id`, `permission_id`) 
SELECT 2, id FROM `lty-permissions`
ON DUPLICATE KEY UPDATE `role_id` = `role_id`;

INSERT INTO `lty-role_permissions` (`role_id`, `permission_id`) 
SELECT 3, id FROM `lty-permissions` WHERE `slug` IN ('dashboard_view', 'media_view', 'media_manage', 'mission_manage', 'story_manage', 'culture_manage', 'timeline_manage', 'members_manage', 'behavior_manage', 'products_manage', 'business_manage')
ON DUPLICATE KEY UPDATE `role_id` = `role_id`;

INSERT INTO `lty-role_permissions` (`role_id`, `permission_id`) 
SELECT 4, id FROM `lty-permissions` WHERE `slug` IN ('dashboard_view', 'media_view')
ON DUPLICATE KEY UPDATE `role_id` = `role_id`;

INSERT INTO `lty-admin_menus` (`parent_id`, `name`, `slug`, `url`, `icon`, `type`, `sort_order`, `status`) VALUES
(NULL, '仪表盘', 'dashboard', 'index.php', '<svg width=\"18\" height=\"18\" viewBox=\"0 0 1024 1024\" xmlns=\"http://www.w3.org/2000/svg\"><path d=\"M128 192h768v64H128zM128 384h768v64H128zM128 576h768v64H128zM128 768h768v64H128z\" fill=\"currentColor\"/></svg>', 'menu', 1, 1)
ON DUPLICATE KEY UPDATE `slug` = `slug`;

INSERT INTO `lty-admin_menus` (`parent_id`, `name`, `slug`, `url`, `icon`, `type`, `sort_order`, `status`) VALUES
(NULL, '媒体', 'media_category', NULL, '<svg width=\"16\" height=\"16\" viewBox=\"0 0 1024 1024\" xmlns=\"http://www.w3.org/2000/svg\"><path d=\"M128 192h256v192H128zM448 192h256v192H448zM128 448h256v192H128zM448 448h256v192H448z\" fill=\"currentColor\"/></svg>', 'category', 2, 1)
ON DUPLICATE KEY UPDATE `slug` = `slug`;

SET @media_category_id = (SELECT id FROM `lty-admin_menus` WHERE slug = 'media_category');

INSERT INTO `lty-admin_menus` (`parent_id`, `name`, `slug`, `url`, `icon`, `type`, `sort_order`, `status`) VALUES
(@media_category_id, '媒体库', 'media_library', 'media.php', '<svg width=\"18\" height=\"18\" viewBox=\"0 0 1024 1024\" xmlns=\"http://www.w3.org/2000/svg\"><path d=\"M128 192h768v640H128z\" fill=\"currentColor\"/><path d=\"M256 320h192v192H256zM512 320h256v64H512zM512 448h256v64H512zM256 576h512v64H256zM256 704h512v64H256z\" fill=\"#ffffff\"/></svg>', 'menu', 1, 1)
ON DUPLICATE KEY UPDATE `slug` = `slug`;

INSERT INTO `lty-admin_menus` (`parent_id`, `name`, `slug`, `url`, `icon`, `type`, `sort_order`, `status`) VALUES
(NULL, '官网管理', 'site_category', NULL, '<svg width=\"16\" height=\"16\" viewBox=\"0 0 1024 1024\" xmlns=\"http://www.w3.org/2000/svg\"><path d=\"M128 384h256v448H128zM448 384h192v448H448zM704 384h192v448H704zM128 192h768v128H128z\" fill=\"currentColor\"/></svg>', 'category', 3, 1)
ON DUPLICATE KEY UPDATE `slug` = `slug`;

SET @site_category_id = (SELECT id FROM `lty-admin_menus` WHERE slug = 'site_category');

INSERT INTO `lty-admin_menus` (`parent_id`, `name`, `slug`, `url`, `icon`, `type`, `sort_order`, `status`) VALUES
(@site_category_id, '首页', 'home_category', NULL, '<svg width=\"16\" height=\"16\" viewBox=\"0 0 1024 1024\" xmlns=\"http://www.w3.org/2000/svg\"><path d=\"M512 128L128 448v384h256V640h256v192h256V448L512 128z\" fill=\"currentColor\"/></svg>', 'category', 1, 1)
ON DUPLICATE KEY UPDATE `slug` = `slug`;

SET @home_category_id = (SELECT id FROM `lty-admin_menus` WHERE slug = 'home_category');

INSERT INTO `lty-admin_menus` (`parent_id`, `name`, `slug`, `url`, `icon`, `type`, `sort_order`, `status`) VALUES
(@home_category_id, '轮播图管理', 'mission', 'mission.php', '<svg width=\"18\" height=\"18\" viewBox=\"0 0 1024 1024\" xmlns=\"http://www.w3.org/2000/svg\"><path d=\"M128 192h768v640H128z\" fill=\"currentColor\"/><path d=\"M416 512l192-128v256z\" fill=\"#ffffff\"/></svg>', 'menu', 1, 1),
(@home_category_id, '公司简介', 'story', 'story.php', '<svg width=\"18\" height=\"18\" viewBox=\"0 0 1024 1024\" xmlns=\"http://www.w3.org/2000/svg\"><path d=\"M192 128h640v768H192z\" fill=\"currentColor\"/><path d=\"M256 256h512v64H256zM256 384h512v64H256zM256 512h384v64H256z\" fill=\"#ffffff\"/></svg>', 'menu', 2, 1),
(@home_category_id, '企业文化', 'culture', 'culture.php', '<svg width=\"18\" height=\"18\" viewBox=\"0 0 1024 1024\" xmlns=\"http://www.w3.org/2000/svg\"><path d=\"M512 128c-106 0-192 86-192 192 0 64 32 121 80 156v320h224V476c48-35 80-92 80-156 0-106-86-192-192-192z m0 128c35.3 0 64 28.7 64 64s-28.7 64-64 64-64-28.7-64-64 28.7-64 64-64z\" fill=\"currentColor\"/></svg>', 'menu', 3, 1),
(@home_category_id, '大事记', 'timeline', 'timeline.php', '<svg width=\"18\" height=\"18\" viewBox=\"0 0 1024 1024\" xmlns=\"http://www.w3.org/2000/svg\"><path d=\"M128 192h192v640H128zM320 320h192v512H320zM512 256h192v576H512zM704 384h192v448H704z\" fill=\"currentColor\"/></svg>', 'menu', 4, 1),
(@home_category_id, '团队成员', 'members', 'members.php', '<svg width=\"18\" height=\"18\" viewBox=\"0 0 1024 1024\" xmlns=\"http://www.w3.org/2000/svg\"><path d=\"M512 512c-106 0-192-86-192-192s86-192 192-192 192 86 192 192-86 192-192 192z\" fill=\"currentColor\"/><path d=\"M512 576c-141.4 0-256 114.6-256 256v64h512v-64c0-141.4-114.6-256-256-256z\" fill=\"currentColor\"/></svg>', 'menu', 5, 1),
(@home_category_id, '行为准则', 'behavior', 'behavior.php', '<svg width=\"18\" height=\"18\" viewBox=\"0 0 1024 1024\" xmlns=\"http://www.w3.org/2000/svg\"><path d=\"M512 128c-212 0-384 172-384 384s172 384 384 384 384-172 384-384-172-384-384-384z m0 640c-141.4 0-256-114.6-256-256s114.6-256 256-256 256 114.6 256 256-114.6 256-256 256z\" fill=\"currentColor\"/><path d=\"M464 384h96v256h-96zM464 704h96v96h-96z\" fill=\"currentColor\"/></svg>', 'menu', 6, 1),
(@home_category_id, '产品管理', 'products', 'products.php', '<svg width=\"18\" height=\"18\" viewBox=\"0 0 1024 1024\" xmlns=\"http://www.w3.org/2000/svg\"><path d=\"M832 256H192v512h640V256z m-64 448H256V320h512v384z\" fill=\"currentColor\"/><path d=\"M320 384h128v64H320zM320 512h128v64H320zM480 384h256v64H480zM480 512h256v64H480z\" fill=\"currentColor\"/></svg>', 'menu', 7, 1),
(@home_category_id, '业务领域', 'business', 'business.php', '<svg width=\"18\" height=\"18\" viewBox=\"0 0 1024 1024\" xmlns=\"http://www.w3.org/2000/svg\"><path d=\"M512 128c-212 0-384 172-384 384s172 384 384 384 384-172 384-384-172-384-384-384z m0 640c-141.4 0-256-114.6-256-256s114.6-256 256-256 256 114.6 256 256-114.6 256-256 256z\" fill=\"currentColor\"/><path d=\"M512 320v192l128 128\" stroke=\"currentColor\" stroke-width=\"64\" fill=\"none\"/></svg>', 'menu', 8, 1)
ON DUPLICATE KEY UPDATE `slug` = `slug`;

INSERT INTO `lty-admin_menus` (`parent_id`, `name`, `slug`, `url`, `icon`, `type`, `sort_order`, `status`) VALUES
(@site_category_id, '联系我们', 'contact', 'contact.php', '<svg width=\"18\" height=\"18\" viewBox=\"0 0 1024 1024\" xmlns=\"http://www.w3.org/2000/svg\"><path d=\"M128 192h768v576L512 640 128 768V192z\" fill=\"currentColor\"/><path d=\"M192 256h640v64H192zM192 384h640v64H192zM192 512h384v64H192z\" fill=\"#ffffff\"/></svg>', 'menu', 2, 1)
ON DUPLICATE KEY UPDATE `slug` = `slug`;

INSERT INTO `lty-admin_menus` (`parent_id`, `name`, `slug`, `url`, `icon`, `type`, `sort_order`, `status`) VALUES
(NULL, '互动管理', 'interaction_category', NULL, '<svg width=\"16\" height=\"16\" viewBox=\"0 0 1024 1024\" xmlns=\"http://www.w3.org/2000/svg\"><path d=\"M512 128c-212 0-384 172-384 384s172 384 384 384 384-172 384-384-172-384-384-384z m0 640c-141.4 0-256-114.6-256-256s114.6-256 256-256 256 114.6 256 256-114.6 256-256 256z\" fill=\"currentColor\"/><path d=\"M384 448h64v128h-64zM448 384h128v64H448zM576 448h64v128h-64z\" fill=\"currentColor\"/></svg>', 'category', 4, 1)
ON DUPLICATE KEY UPDATE `slug` = `slug`;

SET @interaction_category_id = (SELECT id FROM `lty-admin_menus` WHERE slug = 'interaction_category');

INSERT INTO `lty-admin_menus` (`parent_id`, `name`, `slug`, `url`, `icon`, `type`, `sort_order`, `status`) VALUES
(@interaction_category_id, '留言管理', 'messages', 'messages.php', '<svg width=\"18\" height=\"18\" viewBox=\"0 0 1024 1024\" xmlns=\"http://www.w3.org/2000/svg\"><path d=\"M128 192h768v576L512 640 128 768V192z\" fill=\"currentColor\"/><path d=\"M192 256h640v64H192zM192 384h640v64H192zM192 512h384v64H192z\" fill=\"#ffffff\"/></svg>', 'menu', 1, 1)
ON DUPLICATE KEY UPDATE `slug` = `slug`;

INSERT INTO `lty-admin_menus` (`parent_id`, `name`, `slug`, `url`, `icon`, `type`, `sort_order`, `status`) VALUES
(NULL, '系统设置', 'system_category', NULL, '<svg width=\"16\" height=\"16\" viewBox=\"0 0 1024 1024\" xmlns=\"http://www.w3.org/2000/svg\"><path d=\"M512 192c-17.7 0-32 14.3-32 32v64c-35.3 8.6-68.1 24.2-96.4 45.1l-55.4-32c-15.3-8.8-34.8-3.6-43.6 11.7l-56 96.9c-8.8 15.3-3.6 34.8 11.7 43.6l55.4 32c-2.2 17.7-2.2 35.7 0 53.4l-55.4 32c-15.3 8.8-20.5 28.3-11.7 43.6l56 96.9c8.8 15.3 28.3 20.5 43.6 11.7l55.4-32c28.3 20.9 61.1 36.5 96.4 45.1v64c0 17.7 14.3 32 32 32s32-14.3 32-32v-64c35.3-8.6 68.1-24.2 96.4-45.1l55.4 32c15.3 8.8 34.8 3.6 43.6-11.7l56-96.9c8.8-15.3 3.6-34.8-11.7-43.6l-55.4-32c2.2-17.7 2.2-35.7 0-53.4l55.4-32c15.3-8.8 20.5-28.3 11.7-43.6l-56-96.9c-8.8-15.3-28.3-20.5-43.6-11.7l-55.4 32c-28.3-20.9-61.1-36.5-96.4-45.1v-64c0-17.7-14.3-32-32-32z m0 256c53 0 96 43 96 96s-43 96-96 96-96-43-96-96 43-96 96-96z\" fill=\"currentColor\"/></svg>', 'category', 5, 1)
ON DUPLICATE KEY UPDATE `slug` = `slug`;

SET @system_category_id = (SELECT id FROM `lty-admin_menus` WHERE slug = 'system_category');

INSERT INTO `lty-admin_menus` (`parent_id`, `name`, `slug`, `url`, `icon`, `type`, `sort_order`, `status`) VALUES
(@system_category_id, '网站设置', 'settings', 'settings.php', '<svg width=\"18\" height=\"18\" viewBox=\"0 0 1024 1024\" xmlns=\"http://www.w3.org/2000/svg\"><path d=\"M512 192c-17.7 0-32 14.3-32 32v64c-35.3 8.6-68.1 24.2-96.4 45.1l-55.4-32c-15.3-8.8-34.8-3.6-43.6 11.7l-56 96.9c-8.8 15.3-3.6 34.8 11.7 43.6l55.4 32c-2.2 17.7-2.2 35.7 0 53.4l-55.4 32c-15.3 8.8-20.5 28.3-11.7 43.6l56 96.9c8.8 15.3 28.3 20.5 43.6 11.7l55.4-32c28.3 20.9 61.1 36.5 96.4 45.1v64c0 17.7 14.3 32 32 32s32-14.3 32-32v-64c35.3-8.6 68.1-24.2 96.4-45.1l55.4 32c15.3 8.8 34.8 3.6 43.6-11.7l56-96.9c8.8-15.3 3.6-34.8-11.7-43.6l-55.4-32c2.2-17.7 2.2-35.7 0-53.4l55.4-32c15.3-8.8 20.5-28.3 11.7-43.6l-56-96.9c-8.8-15.3-28.3-20.5-43.6-11.7l-55.4 32c-28.3-20.9-61.1-36.5-96.4-45.1v-64c0-17.7-14.3-32-32-32z m0 256c53 0 96 43 96 96s-43 96-96 96-96-43-96-96 43-96 96-96z\" fill=\"currentColor\"/></svg>', 'menu', 1, 1),
(@system_category_id, 'SEO优化', 'seo', 'seo.php', '<svg width=\"18\" height=\"18\" viewBox=\"0 0 1024 1024\" xmlns=\"http://www.w3.org/2000/svg\"><path d=\"M888 224H232c-6.7 0-10.4 7.7-6.3 12.9L408 464v232c0 4.4 3.6 8 8 8h80c4.4 0 8-3.6 8-8V464l182.3-227.1c4.1-5.2.4-12.9-6.3-12.9z\" fill=\"currentColor\"/><path d=\"M896 128H128c-35.3 0-64 28.7-64 64v640c0 35.3 28.7 64 64 64h768c35.3 0 64-28.7 64-64V192c0-35.3-28.7-64-64-64z m0 704H128V192h768v640z\" fill=\"currentColor\"/><path d=\"M224 288h576v64H224zM224 416h448v64H224zM224 544h320v64H224z\" fill=\"currentColor\"/></svg>', 'menu', 2, 1)
ON DUPLICATE KEY UPDATE `slug` = `slug`;

INSERT INTO `lty-role_menus` (`role_id`, `menu_id`) 
SELECT 1, id FROM `lty-admin_menus`
ON DUPLICATE KEY UPDATE `role_id` = `role_id`;

INSERT INTO `lty-role_menus` (`role_id`, `menu_id`) 
SELECT 2, id FROM `lty-admin_menus` WHERE `slug` NOT IN ('roles', 'admin_users')
ON DUPLICATE KEY UPDATE `role_id` = `role_id`;

INSERT INTO `lty-role_menus` (`role_id`, `menu_id`) 
SELECT 3, id FROM `lty-admin_menus` WHERE `slug` IN ('dashboard', 'media_library', 'mission', 'story', 'culture', 'timeline', 'members', 'behavior', 'products', 'business')
ON DUPLICATE KEY UPDATE `role_id` = `role_id`;

INSERT INTO `lty-role_menus` (`role_id`, `menu_id`) 
SELECT 4, id FROM `lty-admin_menus` WHERE `slug` IN ('dashboard', 'media_library')
ON DUPLICATE KEY UPDATE `role_id` = `role_id`;

-- 修复菜单排序和层级关系
-- 一级菜单排序（parent_id = NULL）
UPDATE `lty-admin_menus` SET sort_order = 1, parent_id = NULL WHERE slug = 'dashboard';
UPDATE `lty-admin_menus` SET sort_order = 2, parent_id = NULL WHERE slug = 'media_category';
UPDATE `lty-admin_menus` SET sort_order = 3, parent_id = NULL WHERE slug = 'site_category';
UPDATE `lty-admin_menus` SET sort_order = 4, parent_id = NULL WHERE slug = 'interaction_category';
UPDATE `lty-admin_menus` SET sort_order = 5, parent_id = NULL WHERE slug = 'system_category';

-- 媒体子菜单
UPDATE `lty-admin_menus` SET parent_id = (SELECT id FROM (SELECT id FROM `lty-admin_menus` WHERE slug = 'media_category') AS tmp), sort_order = 1 WHERE slug = 'media_library';

-- 官网管理子菜单
UPDATE `lty-admin_menus` SET parent_id = (SELECT id FROM (SELECT id FROM `lty-admin_menus` WHERE slug = 'site_category') AS tmp), sort_order = 1 WHERE slug = 'home_category';
UPDATE `lty-admin_menus` SET parent_id = (SELECT id FROM (SELECT id FROM `lty-admin_menus` WHERE slug = 'site_category') AS tmp), sort_order = 2 WHERE slug = 'contact';

-- 首页子菜单
UPDATE `lty-admin_menus` SET parent_id = (SELECT id FROM (SELECT id FROM `lty-admin_menus` WHERE slug = 'home_category') AS tmp), sort_order = 1 WHERE slug = 'mission';
UPDATE `lty-admin_menus` SET parent_id = (SELECT id FROM (SELECT id FROM `lty-admin_menus` WHERE slug = 'home_category') AS tmp), sort_order = 2 WHERE slug = 'story';
UPDATE `lty-admin_menus` SET parent_id = (SELECT id FROM (SELECT id FROM `lty-admin_menus` WHERE slug = 'home_category') AS tmp), sort_order = 3 WHERE slug = 'culture';
UPDATE `lty-admin_menus` SET parent_id = (SELECT id FROM (SELECT id FROM `lty-admin_menus` WHERE slug = 'home_category') AS tmp), sort_order = 4 WHERE slug = 'timeline';
UPDATE `lty-admin_menus` SET parent_id = (SELECT id FROM (SELECT id FROM `lty-admin_menus` WHERE slug = 'home_category') AS tmp), sort_order = 5 WHERE slug = 'members';
UPDATE `lty-admin_menus` SET parent_id = (SELECT id FROM (SELECT id FROM `lty-admin_menus` WHERE slug = 'home_category') AS tmp), sort_order = 6 WHERE slug = 'behavior';
UPDATE `lty-admin_menus` SET parent_id = (SELECT id FROM (SELECT id FROM `lty-admin_menus` WHERE slug = 'home_category') AS tmp), sort_order = 7 WHERE slug = 'products';
UPDATE `lty-admin_menus` SET parent_id = (SELECT id FROM (SELECT id FROM `lty-admin_menus` WHERE slug = 'home_category') AS tmp), sort_order = 8 WHERE slug = 'business';

-- 互动管理子菜单
UPDATE `lty-admin_menus` SET parent_id = (SELECT id FROM (SELECT id FROM `lty-admin_menus` WHERE slug = 'interaction_category') AS tmp), sort_order = 1 WHERE slug = 'messages';

-- 系统设置子菜单
UPDATE `lty-admin_menus` SET parent_id = (SELECT id FROM (SELECT id FROM `lty-admin_menus` WHERE slug = 'system_category') AS tmp), sort_order = 1 WHERE slug = 'settings';
UPDATE `lty-admin_menus` SET parent_id = (SELECT id FROM (SELECT id FROM `lty-admin_menus` WHERE slug = 'system_category') AS tmp), sort_order = 2 WHERE slug = 'seo';

-- 开发配置菜单
INSERT INTO `lty-admin_menus` (`parent_id`, `name`, `slug`, `url`, `icon`, `type`, `sort_order`, `status`) VALUES
(NULL, '开发配置', 'dev_category', NULL, '<svg width="16" height="16" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg"><path d="M888 224H232c-6.7 0-10.4 7.7-6.3 12.9L408 464v232c0 4.4 3.6 8 8 8h80c4.4 0 8-3.6 8-8V464l182.3-227.1c4.1-5.2.4-12.9-6.3-12.9z" fill="currentColor"/><path d="M320 128h384v64H320zM256 256h512v64H256zM192 384h640v64H192z" fill="currentColor"/></svg>', 'category', 5, 1)
ON DUPLICATE KEY UPDATE `slug` = `slug`;

SET @dev_category_id = (SELECT id FROM `lty-admin_menus` WHERE slug = 'dev_category');

INSERT INTO `lty-admin_menus` (`parent_id`, `name`, `slug`, `url`, `icon`, `type`, `sort_order`, `status`) VALUES
(@dev_category_id, '团队角色', 'roles', 'roles.php', '<svg width="18" height="18" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg"><path d="M512 512c-106 0-192-86-192-192s86-192 192-192 192 86 192 192-86 192-192 192z" fill="currentColor"/><path d="M512 576c-141.4 0-256 114.6-256 256v64h512v-64c0-141.4-114.6-256-256-256z" fill="currentColor"/><path d="M704 320c0 106-86 192-192 192s-192-86-192-192 86-192 192-192 192 86 192 192z" fill="currentColor" opacity="0.3"/></svg>', 'menu', 1, 1),
(@dev_category_id, '后台用户', 'admin_users', 'admin_users.php', '<svg width="18" height="18" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg"><path d="M512 320c88.4 0 160-71.6 160-160S600.4 0 512 0 352 71.6 352 160s71.6 160 160 160z m0 64c-106.7 0-320 53.3-320 160v128h640V544c0-106.7-213.3-160-320-160z" fill="currentColor"/></svg>', 'menu', 2, 1)
ON DUPLICATE KEY UPDATE `slug` = `slug`;

-- 更新开发配置菜单的排序（开发配置=5，系统设置=6）
UPDATE `lty-admin_menus` SET sort_order = 5, parent_id = NULL WHERE slug = 'dev_category';
UPDATE `lty-admin_menus` SET sort_order = 6, parent_id = NULL WHERE slug = 'system_category';

-- 开发配置子菜单
UPDATE `lty-admin_menus` SET parent_id = (SELECT id FROM (SELECT id FROM `lty-admin_menus` WHERE slug = 'dev_category') AS tmp), sort_order = 1 WHERE slug = 'roles';
UPDATE `lty-admin_menus` SET parent_id = (SELECT id FROM (SELECT id FROM `lty-admin_menus` WHERE slug = 'dev_category') AS tmp), sort_order = 2 WHERE slug = 'admin_users';

-- 为超级管理员添加开发配置菜单权限
INSERT INTO `lty-role_menus` (`role_id`, `menu_id`) 
SELECT 1, id FROM `lty-admin_menus` WHERE `slug` IN ('dev_category', 'roles', 'admin_users')
ON DUPLICATE KEY UPDATE `role_id` = `role_id`;

CREATE TABLE IF NOT EXISTS `lty-team_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `position` varchar(100) NOT NULL,
  `bio` text,
  `avatar` varchar(255),
  `email` varchar(100),
  `phone` varchar(20),
  `employee_id` varchar(50) DEFAULT NULL COMMENT '工号',
  `sort_order` int(11) DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lty-site_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lty-contact_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '类型名称',
  `description` text COMMENT '类型描述',
  `sort_order` int(11) DEFAULT 0 COMMENT '排序',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态：1启用 0禁用',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='联系类型表';

CREATE TABLE IF NOT EXISTS `lty-contact_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type_id` int(11) DEFAULT NULL COMMENT '联系类型ID',
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20),
  `subject` varchar(200),
  `message` text NOT NULL,
  `ip_address` varchar(45),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `type_id` (`type_id`),
  CONSTRAINT `fk_contact_type` FOREIGN KEY (`type_id`) REFERENCES `lty-contact_types` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `lty-contact_types` (`name`, `description`, `sort_order`, `status`) VALUES
('商务合作', '商务合作、业务洽谈相关咨询', 1, 1),
('技术顾问', '技术咨询、解决方案咨询', 2, 1),
('应聘招聘', '求职应聘、招聘相关咨询', 3, 1),
('产品咨询', '产品功能、使用相关咨询', 4, 1),
('售后服务', '售后支持、问题反馈', 5, 1),
('其他', '其他类型咨询', 6, 1)
ON DUPLICATE KEY UPDATE `name` = `name`;

CREATE TABLE IF NOT EXISTS `lty-company_timeline` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `year` varchar(10) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text,
  `sort_order` int(11) DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_timeline` (`year`, `title`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lty-company_culture` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `description` text,
  `image` varchar(255),
  `sort_order` int(11) DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_title` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lty-company_behavior` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `description` text,
  `icon_svg` text DEFAULT NULL COMMENT 'SVG图标代码',
  `sort_order` int(11) DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_title` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lty-mission_slides` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `subtitle` varchar(200),
  `image` varchar(255) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_title` (`title`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lty-business_areas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL COMMENT '业务标题',
  `description` text COMMENT '业务描述',
  `icon_svg` text DEFAULT NULL COMMENT 'SVG图标代码',
  `sort_order` int(11) DEFAULT 0 COMMENT '排序',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态：1启用 0禁用',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_title` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='业务领域表';

CREATE TABLE IF NOT EXISTS `lty-product_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '分类名称',
  `slug` varchar(50) NOT NULL COMMENT '分类标识',
  `description` text COMMENT '分类描述',
  `sort_order` int(11) DEFAULT 0 COMMENT '排序',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态：1启用 0禁用',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lty-products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `description` text,
  `category_id` int(11) DEFAULT NULL COMMENT '分类ID',
  `image` varchar(255),
  `images` text DEFAULT NULL COMMENT '图片列表，JSON格式',
  `link` varchar(255),
  `is_original` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  `status_text` varchar(50) DEFAULT NULL COMMENT '状态文本，如：预售中、运营中',
  `release_date` date DEFAULT NULL COMMENT '发布日期',
  `features` text DEFAULT NULL COMMENT '功能特性，JSON格式',
  `tech_stack` text DEFAULT NULL COMMENT '技术栈，JSON格式',
  `demo_url` varchar(255) DEFAULT NULL COMMENT '演示链接',
  `download_url` varchar(255) DEFAULT NULL COMMENT '下载链接',
  `price` decimal(10,2) DEFAULT NULL COMMENT '价格',
  `views` int(11) DEFAULT 0 COMMENT '浏览量',
  `full_description` text DEFAULT NULL COMMENT '完整描述',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `lty-product_categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lty-product_updates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `version` varchar(20) NOT NULL,
  `content` text NOT NULL,
  `release_date` date NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `lty-site_settings` (`setting_key`, `setting_value`) VALUES
('site_name', 'Synchro'),
('site_url', ''),
('contact_email', 'contact@lintuyue.com'),
('contact_phone', '400-123-4567'),
('contact_address', '北京市朝阳区XX路XX号'),
('about_content', '我们是一支充满激情和创造力的团队，致力于为客户提供最优质的服务。'),
('company_name', 'XX科技有限公司'),
('copyright', '2025 徐州邻兔跃IT团队 版权所有'),
('logo_type', 'default'),
('logo_image', ''),
('logo_text', ''),
('culture_title', '邻兔范'),
('culture_subtitle', '邻兔范是我们共同认可的行为准则'),
('behavior_title', '行为准则'),
('behavior_subtitle', '高道德标准和诚信文化发挥着关键作用'),
('timeline_title', '大事记'),
('timeline_subtitle', '记录我们的成长历程'),
('contact_title', '联系我们'),
('contact_subtitle', '期待与您的合作'),
('members_title', '团队成员'),
('members_subtitle', '认识我们的专业团队'),
('verify_title', '团队成员防伪查询'),
('verify_subtitle', '请输入团队成员信息进行查询验证'),
('story_title', '我们的故事'),
('story_subtitle', '记录我们的成长历程'),
('story_content', '我们是一支充满激情和创造力的团队，致力于为客户提供最优质的服务。'),
('story_content2', '自成立以来，我们始终坚持以技术创新为核心，以客户需求为导向，不断推出高质量的产品和服务。我们的团队由来自不同领域的专业人才组成，拥有丰富的行业经验和深厚的技术积累。'),
('story_content3', '我们相信，只有不断创新和进步，才能在激烈的市场竞争中立于不败之地。因此，我们始终保持着对新技术、新理念的关注和学习，努力为客户提供最前沿的解决方案。'),
('story_stats_years', '10+'),
('story_stats_cases', '500+'),
('story_stats_team', '100+'),
('story_stats_satisfaction', '98%'),
('products_title', '我们的产品'),
('products_subtitle', '以下展示的是工作室自主研发的程序项目'),
('business_title', '业务领域'),
('business_subtitle', '我们专注的核心业务方向'),
('wechat_url', ''),
('weibo_url', ''),
('linkedin_url', ''),
('github_url', ''),
('qq_url', ''),
('qq_number', ''),
('wechat_icon', ''),
('weibo_icon', ''),
('linkedin_icon', ''),
('github_icon', ''),
('sales_email', ''),
('support_email', ''),
('hr_email', ''),
('business_email', ''),
('media_email', ''),
('working_hours', '周一至周五 9:00-18:00'),
('contact_intro', ''),
('stat_response', '24h'),
('stat_satisfaction', '98%'),
('stat_cases', '500+'),
('enable_ip_binding', '1'),
('session_timeout', '30'),
('csrf_token_expiry', '120'),
('email_smtp_host', ''),
('email_smtp_port', '465'),
('email_smtp_username', ''),
('email_smtp_password', ''),
('email_smtp_encryption', 'ssl'),
('email_from_name', ''),
('email_subject_prefix', ''),
('email_footer_content1', ''),
('email_footer_content2', ''),
('email_enabled', '0'),
('custom_css', ''),
('custom_js', ''),
('custom_head_html', ''),
('custom_footer_html', ''),
('analytics_html', '')
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;

INSERT INTO `lty-business_areas` (`title`, `description`, `icon_svg`, `sort_order`, `status`) VALUES
('网站开发', '提供从策划、设计到开发、运维的全流程网站建设服务，打造优质的线上体验', '<svg viewBox=\"0 0 1024 1024\"><path d=\"M928 160H96c-17.7 0-32 14.3-32 32v640c0 17.7 14.3 32 32 32h832c17.7 0 32-14.3 32-32V192c0-17.7-14.3-32-32-32zM338 304c35.3 0 64 28.7 64 64s-28.7 64-64 64-64-28.7-64-64 28.7-64 64-64zM184 448h672v288H184V448z\"/></svg>', 1, 1),
('移动应用', '开发iOS和Android原生应用及跨平台应用，满足不同场景的移动端需求', '<svg viewBox=\"0 0 1024 1024\"><path d=\"M744 64H280c-35.3 0-64 28.7-64 64v768c0 35.3 28.7 64 64 64h464c35.3 0 64-28.7 64-64V128c0-35.3-28.7-64-64-64zM512 880c-22.1 0-40-17.9-40-40s17.9-40 40-40 40 17.9 40 40-17.9 40-40 40zM744 768H280V192h464v576z\"/></svg>', 2, 1),
('云服务', '提供云计算、云存储、云安全等云服务解决方案，助力企业数字化转型', '<svg viewBox=\"0 0 1024 1024\"><path d=\"M811.4 418.7C765.6 297.9 648.9 212 512.2 212S258.8 297.8 213 418.6C127.3 441.1 64 519.1 64 612c0 110.5 89.5 200 199.9 200h496.2C870.5 812 960 722.5 960 612c0-92.7-63.1-170.7-148.6-193.3z\"/></svg>', 3, 1),
('技术咨询', '为企业提供专业的技术咨询服务，帮助客户解决复杂的技术难题', '<svg viewBox=\"0 0 1024 1024\"><path d=\"M512 64C264.6 64 64 264.6 64 512s200.6 448 448 448 448-200.6 448-448S759.4 64 512 64zm0 820c-205.4 0-372-166.6-372-372s166.6-372 372-372 372 166.6 372 372-166.6 372-372 372z\"/><path d=\"M464 336a48 48 0 1 0 96 0 48 48 0 1 0-96 0zm72 112h-48c-4.4 0-8 3.6-8 8v272c0 4.4 3.6 8 8 8h48c4.4 0 8-3.6 8-8V456c0-4.4-3.6-8-8-8z\"/></svg>', 4, 1)
ON DUPLICATE KEY UPDATE `title` = `title`;

INSERT INTO `lty-company_timeline` (`year`, `title`, `description`, `sort_order`, `status`) VALUES
('2023', '公司成立', '我们怀着梦想和激情，开启了创业之旅', 1, 1),
('2024', '团队扩张', '吸纳优秀人才，团队规模扩大到50人', 2, 1),
('2025', '产品发布', '推出首款产品，获得市场认可', 3, 1)
ON DUPLICATE KEY UPDATE `year` = `year`;

INSERT INTO `lty-company_culture` (`title`, `description`, `image`, `sort_order`, `status`) VALUES
('始终创业', '保持创业心态，始终开创而不守成，创新而非依赖资源', '/assets/images/轮播/d57accb905a36ca53dc18ad6da60572c.jpg', 1, 1),
('敏捷有效', '最简化流程，避免简单事情复杂化', '/assets/images/轮播/2690c563cc35c47188099d1b97ba94ee.jpg', 2, 1),
('对外敏锐谦逊', '避免自满或优越感', '/assets/images/轮播/36d8f50fb980f2aad8b8deccd1ccf8ac.jpg', 3, 1)
ON DUPLICATE KEY UPDATE `title` = `title`;

INSERT INTO `lty-company_behavior` (`title`, `description`, `icon_svg`, `sort_order`, `status`) VALUES
('诚信正直', '诚实守信，言行一致，建立信任关系', '<svg viewBox=\"0 0 1024 1024\"><path d=\"M512 64C264.6 64 64 264.6 64 512s200.6 448 448 448 448-200.6 448-448S759.4 64 512 64zm0 820c-205.4 0-372-166.6-372-372s166.6-372 372-372 372 166.6 372 372-166.6 372-372 372z\"/><path d=\"M512 140c-205.4 0-372 166.6-372 372s166.6 372 372 372 372-166.6 372-372-166.6-372-372-372zm193.4 225.7l-210.6 292c-12.7 17.7-39 17.7-51.7 0L318.5 484.9c-3.8-5.3 0-12.7 6.5-12.7h46.9c10.3 0 19.9 5 25.9 13.3l71.2 98.8 157.2-218c6-8.4 15.7-13.3 25.9-13.3H699c6.5 0 10.3 7.4 6.4 12.7z\"/></svg>', 1, 1),
('追求卓越', '不断追求卓越，超越客户期望', '<svg viewBox=\"0 0 1024 1024\"><path d=\"M512 64C264.6 64 64 264.6 64 512s200.6 448 448 448 448-200.6 448-448S759.4 64 512 64zm0 820c-205.4 0-372-166.6-372-372s166.6-372 372-372 372 166.6 372 372-166.6 372-372 372z\"/><path d=\"M512 140c-205.4 0-372 166.6-372 372s166.6 372 372 372 372-166.6 372-372-166.6-372-372-372zm247.5 334.1l-101.9 99.3 24.1 140.3c4.1 23.8-21 42-42.3 30.8L512 672.5l-127.4 67c-21.3 11.2-46.4-7-42.3-30.8l24.1-140.3-101.9-99.3c-17.3-16.9-7.7-46.3 16.2-50.1l140.9-20.5 63-127.7c10.7-21.6 41.5-21.6 52.2 0l63 127.7 140.9 20.5c23.9 3.8 33.5 33.2 16.2 50.1z\"/></svg>', 2, 1),
('团队协作', '团结协作，共同成长，共创辉煌', '<svg viewBox=\"0 0 1024 1024\"><path d=\"M512 64C264.6 64 64 264.6 64 512s200.6 448 448 448 448-200.6 448-448S759.4 64 512 64zm0 820c-205.4 0-372-166.6-372-372s166.6-372 372-372 372 166.6 372 372-166.6 372-372 372z\"/><path d=\"M512 240c-118.8 0-215 96.2-215 215s96.2 215 215 215 215-96.2 215-215-96.2-215-215-215zm0 355c-77.3 0-140-62.7-140-140s62.7-140 140-140 140 62.7 140 140-62.7 140-140 140z\"/><path d=\"M512 390c-35.8 0-65 29.2-65 65s29.2 65 65 65 65-29.2 65-65-29.2-65-65-65z\"/><path d=\"M820.4 712.5c-43.4-76.7-126.2-128.5-221.4-128.5h-173c-95.2 0-178 51.8-221.4 128.5C242.2 748.5 320 832 420 832h184c100 0 177.8-83.5 215.4-119.5z\"/></svg>', 3, 1),
('客户至上', '以客户为中心，满足客户需求', '<svg viewBox=\"0 0 1024 1024\"><path d=\"M512 64C264.6 64 64 264.6 64 512s200.6 448 448 448 448-200.6 448-448S759.4 64 512 64zm0 820c-205.4 0-372-166.6-372-372s166.6-372 372-372 372 166.6 372 372-166.6 372-372 372z\"/><path d=\"M512 240c-118.8 0-215 96.2-215 215s96.2 215 215 215 215-96.2 215-215-96.2-215-215-215zm0 355c-77.3 0-140-62.7-140-140s62.7-140 140-140 140 62.7 140 140-62.7 140-140 140z\"/><path d=\"M512 390c-35.8 0-65 29.2-65 65s29.2 65 65 65 65-29.2 65-65-29.2-65-65-65z\"/></svg>', 4, 1),
('创新进取', '勇于创新，不断进取，引领行业发展', '<svg viewBox=\"0 0 1024 1024\"><path d=\"M512 64C264.6 64 64 264.6 64 512s200.6 448 448 448 448-200.6 448-448S759.4 64 512 64zm0 820c-205.4 0-372-166.6-372-372s166.6-372 372-372 372 166.6 372 372-166.6 372-372 372z\"/><path d=\"M695.8 328.2c-6.3-6.3-16.4-6.3-22.7 0L512 489.3 350.9 328.2c-6.3-6.3-16.4-6.3-22.7 0-6.3 6.3-6.3 16.4 0 22.7L489.3 512 328.2 673.1c-6.3 6.3-6.3 16.4 0 22.7 3.1 3.1 7.2 4.7 11.3 4.7s8.2-1.6 11.3-4.7L512 534.7l161.1 161.1c3.1 3.1 7.2 4.7 11.3 4.7s8.2-1.6 11.3-4.7c6.3-6.3 6.3-16.4 0-22.7L534.7 512l161.1-161.1c6.3-6.3 6.3-16.4 0-22.7z\"/></svg>', 5, 1),
('社会责任', '积极履行社会责任，推动可持续发展', '<svg viewBox=\"0 0 1024 1024\"><path d=\"M512 64C264.6 64 64 264.6 64 512s200.6 448 448 448 448-200.6 448-448S759.4 64 512 64zm0 820c-205.4 0-372-166.6-372-372s166.6-372 372-372 372 166.6 372 372-166.6 372-372 372z\"/><path d=\"M512 140c-205.4 0-372 166.6-372 372s166.6 372 372 372 372-166.6 372-372-166.6-372-372-372zm0 668c-163.3 0-296-132.7-296-296S348.7 216 512 216s296 132.7 296 296-132.7 296-296 296z\"/><path d=\"M512 280c-127.9 0-232 104.1-232 232s104.1 232 232 232 232-104.1 232-232-104.1-232-232-232zm0 388c-86.2 0-156-69.8-156-156s69.8-156 156-156 156 69.8 156 156-69.8 156-156 156z\"/><path d=\"M512 360c-83.8 0-152 68.2-152 152s68.2 152 152 152 152-68.2 152-152-68.2-152-152-152zm0 228c-41.9 0-76-34.1-76-76s34.1-76 76-76 76 34.1 76 76-34.1 76-76 76z\"/></svg>', 6, 1)
ON DUPLICATE KEY UPDATE `title` = `title`;

INSERT INTO `lty-mission_slides` (`title`, `subtitle`, `image`, `sort_order`, `status`) VALUES
('使命激发创造 丰富生活', '企业文化', '/assets/images/轮播/d57accb905a36ca53dc18ad6da60572c.jpg', 1, 1),
('创新驱动发展', '技术引领未来', '/assets/images/轮播/2690c563cc35c47188099d1b97ba94ee.jpg', 2, 1),
('成就客户价值', '共创美好明天', '/assets/images/轮播/36d8f50fb980f2aad8b8deccd1ccf8ac.jpg', 3, 1)
ON DUPLICATE KEY UPDATE `title` = `title`;

INSERT INTO `lty-product_categories` (`name`, `slug`, `description`, `sort_order`, `status`) VALUES
('在运营产品', 'operating', '正在运营的产品和项目', 1, 1),
('网站项目', 'website', '各类网站开发项目', 2, 1),
('程序项目', 'app', '各类应用程序项目', 3, 1),
('小程序项目', 'miniprogram', '微信小程序开发项目', 4, 1)
ON DUPLICATE KEY UPDATE `slug` = `slug`;

CREATE TABLE IF NOT EXISTS `lty-media_library` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL COMMENT '文件名',
  `original_name` varchar(255) NOT NULL COMMENT '原始文件名',
  `file_path` varchar(500) NOT NULL COMMENT '文件路径',
  `file_url` varchar(500) NOT NULL COMMENT '文件URL',
  `file_type` varchar(50) NOT NULL COMMENT '文件类型：image, video, document, other',
  `mime_type` varchar(100) NOT NULL COMMENT 'MIME类型',
  `file_size` bigint(20) NOT NULL COMMENT '文件大小（字节）',
  `width` int(11) DEFAULT NULL COMMENT '图片宽度',
  `height` int(11) DEFAULT NULL COMMENT '图片高度',
  `alt_text` varchar(255) DEFAULT NULL COMMENT '替代文本',
  `title` varchar(255) DEFAULT NULL COMMENT '标题',
  `caption` text DEFAULT NULL COMMENT '说明文字',
  `description` text DEFAULT NULL COMMENT '描述',
  `uploaded_by` int(11) DEFAULT NULL COMMENT '上传者ID',
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp() COMMENT '上传时间',
  `modified_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '修改时间',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态：1正常 0已删除',
  PRIMARY KEY (`id`),
  KEY `file_type` (`file_type`),
  KEY `uploaded_by` (`uploaded_by`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='媒体库表';

CREATE TABLE IF NOT EXISTS `lty-social_links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `platform` varchar(50) NOT NULL COMMENT '平台名称',
  `url` varchar(500) NOT NULL COMMENT '链接URL',
  `icon_type` enum('default','custom') DEFAULT 'default' COMMENT '图标类型：default默认图标，custom自定义图标',
  `icon_url` varchar(500) DEFAULT NULL COMMENT '自定义图标URL',
  `svg_code` text DEFAULT NULL COMMENT 'SVG代码',
  `sort_order` int(11) DEFAULT 0 COMMENT '排序',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态：1启用 0禁用',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_platform` (`platform`),
  KEY `status` (`status`),
  KEY `sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='社交媒体链接表';

INSERT INTO `lty-social_links` (`platform`, `url`, `icon_type`, `sort_order`, `status`) VALUES
('微信公众号', 'https://weixin.qq.com/', 'default', 1, 1),
('微博', 'https://weibo.com/', 'default', 2, 1),
('LinkedIn', 'https://linkedin.com/', 'default', 3, 1),
('GitHub', 'https://github.com/', 'default', 4, 1)
ON DUPLICATE KEY UPDATE `platform` = `platform`;

CREATE TABLE IF NOT EXISTS `lty-footer_links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL COMMENT '链接标题',
  `url` varchar(500) NOT NULL COMMENT '链接URL',
  `target` enum('_self','_blank') DEFAULT '_self' COMMENT '打开方式',
  `sort_order` int(11) DEFAULT 0 COMMENT '排序',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态：1启用 0禁用',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_title` (`title`),
  KEY `status` (`status`),
  KEY `sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='底部快速链接表';

INSERT INTO `lty-footer_links` (`title`, `url`, `target`, `sort_order`, `status`) VALUES
('首页', '/', '_self', 1, 1),
('关于我们', '/about', '_self', 2, 1),
('团队成员', '/team', '_self', 3, 1),
('联系我们', '/contact', '_self', 4, 1)
ON DUPLICATE KEY UPDATE `title` = `title`;

CREATE TABLE IF NOT EXISTS `lty-page_views` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_url` varchar(500) NOT NULL COMMENT '页面URL',
  `page_title` varchar(255) DEFAULT NULL COMMENT '页面标题',
  `view_date` date NOT NULL COMMENT '浏览日期',
  `view_count` int(11) DEFAULT 1 COMMENT '浏览次数',
  `unique_count` int(11) DEFAULT 1 COMMENT '独立访客数',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_page_date` (`page_url`(191), `view_date`),
  KEY `view_date` (`view_date`),
  KEY `page_url` (`page_url`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='页面浏览统计表';

CREATE TABLE IF NOT EXISTS `lty-external_link_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `link_url` varchar(500) NOT NULL COMMENT '外链URL',
  `source_page` varchar(500) DEFAULT NULL COMMENT '来源页面',
  `ip_address` varchar(45) DEFAULT NULL COMMENT '访问者IP',
  `user_agent` varchar(500) DEFAULT NULL COMMENT '用户代理',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `link_url` (`link_url`(191)),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='外链跳转日志表';

CREATE TABLE IF NOT EXISTS `lty-email_verification_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL COMMENT '邮箱地址',
  `code` varchar(6) NOT NULL COMMENT '验证码',
  `type` varchar(50) DEFAULT 'contact' COMMENT '验证类型：contact留言验证',
  `ip_address` varchar(45) DEFAULT NULL COMMENT '请求IP',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NOT NULL COMMENT '过期时间',
  `used` tinyint(1) DEFAULT 0 COMMENT '是否已使用',
  PRIMARY KEY (`id`),
  KEY `email` (`email`),
  KEY `code` (`code`),
  KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='邮箱验证码表';

COMMIT;

-- =====================================================
-- 第二部分：插入初始数据（独立事务）
-- =====================================================
START TRANSACTION;

-- =====================================================
-- 动态字段更新区域 - 自动添加缺失的字段
-- =====================================================

SET @dbname = DATABASE();

-- =====================================================
-- 字段重命名/迁移 - 修复旧版本字段名不一致问题
-- =====================================================

-- lty-media_library 表：filepath → file_path
SET @tablename = 'lty-media_library';
SET @oldcol = 'filepath';
SET @newcol = 'file_path';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @oldcol) > 0, CONCAT('ALTER TABLE `', @tablename, '` CHANGE COLUMN `', @oldcol, '` `', @newcol, '` varchar(500) NOT NULL COMMENT ''文件路径'''), 'SELECT 1')); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- lty-media_library 表：filesize → file_size
SET @oldcol = 'filesize';
SET @newcol = 'file_size';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @oldcol) > 0, CONCAT('ALTER TABLE `', @tablename, '` CHANGE COLUMN `', @oldcol, '` `', @newcol, '` bigint(20) NOT NULL COMMENT ''文件大小（字节）'''), 'SELECT 1')); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- lty-company_culture 表：icon → image
SET @tablename = 'lty-company_culture';
SET @oldcol = 'icon';
SET @newcol = 'image';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @oldcol) > 0, CONCAT('ALTER TABLE `', @tablename, '` CHANGE COLUMN `', @oldcol, '` `', @newcol, '` varchar(255) DEFAULT NULL'), 'SELECT 1')); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- lty-company_behavior 表：icon → icon_svg
SET @tablename = 'lty-company_behavior';
SET @oldcol = 'icon';
SET @newcol = 'icon_svg';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @oldcol) > 0, CONCAT('ALTER TABLE `', @tablename, '` CHANGE COLUMN `', @oldcol, '` `', @newcol, '` text DEFAULT NULL COMMENT ''SVG图标代码'''), 'SELECT 1')); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- lty-business_areas 表：icon → icon_svg
SET @tablename = 'lty-business_areas';
SET @oldcol = 'icon';
SET @newcol = 'icon_svg';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @oldcol) > 0, CONCAT('ALTER TABLE `', @tablename, '` CHANGE COLUMN `', @oldcol, '` `', @newcol, '` text DEFAULT NULL COMMENT ''SVG图标代码'''), 'SELECT 1')); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- lty-social_links 表：删除旧的 icon 字段（已改用 icon_type/icon_url/svg_code）
SET @tablename = 'lty-social_links';
SET @oldcol = 'icon';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @oldcol) > 0, CONCAT('ALTER TABLE `', @tablename, '` DROP COLUMN `', @oldcol, '`'), 'SELECT 1')); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- lty-media_library 表：删除旧的 folder 字段
SET @tablename = 'lty-media_library';
SET @oldcol = 'folder';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @oldcol) > 0, CONCAT('ALTER TABLE `', @tablename, '` DROP COLUMN `', @oldcol, '`'), 'SELECT 1')); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- lty-mission_slides 表：删除旧的 description/button_text/button_url 字段
SET @tablename = 'lty-mission_slides';
SET @oldcol = 'description';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @oldcol) > 0, CONCAT('ALTER TABLE `', @tablename, '` DROP COLUMN `', @oldcol, '`'), 'SELECT 1')); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @oldcol = 'button_text';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @oldcol) > 0, CONCAT('ALTER TABLE `', @tablename, '` DROP COLUMN `', @oldcol, '`'), 'SELECT 1')); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @oldcol = 'button_url';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @oldcol) > 0, CONCAT('ALTER TABLE `', @tablename, '` DROP COLUMN `', @oldcol, '`'), 'SELECT 1')); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =====================================================
-- lty-roles 表字段
-- =====================================================
SET @tablename = 'lty-roles';

SET @columnname = 'name'; SET @columndef = 'varchar(50) NOT NULL COMMENT ''角色名称''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `id`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'slug'; SET @columndef = 'varchar(50) NOT NULL COMMENT ''角色标识''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `name`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'description'; SET @columndef = 'varchar(255) DEFAULT NULL COMMENT ''角色描述''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `slug`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'is_system'; SET @columndef = 'tinyint(1) DEFAULT 0 COMMENT ''是否系统角色：1是 0否''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `description`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'sort_order'; SET @columndef = 'int(11) DEFAULT 0 COMMENT ''排序''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `is_system`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'status'; SET @columndef = 'tinyint(1) DEFAULT 1 COMMENT ''状态：1启用 0禁用''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `sort_order`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'email_notify_enabled'; SET @columndef = 'tinyint(1) DEFAULT 0 COMMENT ''邮件通知：1启用 0禁用''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `status`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'email_notify_types'; SET @columndef = 'text DEFAULT NULL COMMENT ''通知类型JSON数组''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `email_notify_enabled`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'email_notify_content'; SET @columndef = 'text DEFAULT NULL COMMENT ''自定义通知内容''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `email_notify_types`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'created_at'; SET @columndef = 'timestamp NOT NULL DEFAULT current_timestamp()';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `email_notify_content`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'updated_at'; SET @columndef = 'timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `created_at`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =====================================================
-- lty-admin_users 表字段
-- =====================================================
SET @tablename = 'lty-admin_users';

SET @columnname = 'username'; SET @columndef = 'varchar(50) NOT NULL';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `id`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'password'; SET @columndef = 'varchar(255) NOT NULL';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `username`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'email'; SET @columndef = 'varchar(100) NOT NULL';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `password`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'name'; SET @columndef = 'varchar(100) DEFAULT NULL';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `email`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'avatar'; SET @columndef = 'varchar(255) DEFAULT NULL';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `name`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'role_id'; SET @columndef = 'int(11) DEFAULT NULL COMMENT ''角色ID''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `avatar`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'status'; SET @columndef = 'tinyint(1) DEFAULT 1 COMMENT ''状态：1启用 0禁用''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `role_id`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'last_login'; SET @columndef = 'timestamp NULL DEFAULT NULL COMMENT ''最后登录时间''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `status`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'created_at'; SET @columndef = 'timestamp NOT NULL DEFAULT current_timestamp()';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `last_login`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'updated_at'; SET @columndef = 'timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `created_at`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =====================================================
-- lty-permissions 表字段
-- =====================================================
SET @tablename = 'lty-permissions';

SET @columnname = 'name'; SET @columndef = 'varchar(100) NOT NULL COMMENT ''权限名称''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `id`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'slug'; SET @columndef = 'varchar(100) NOT NULL COMMENT ''权限标识''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `name`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'description'; SET @columndef = 'varchar(255) DEFAULT NULL COMMENT ''权限描述''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `slug`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'group'; SET @columndef = 'varchar(50) DEFAULT NULL COMMENT ''权限分组''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `description`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'sort_order'; SET @columndef = 'int(11) DEFAULT 0 COMMENT ''排序''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `group`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'status'; SET @columndef = 'tinyint(1) DEFAULT 1 COMMENT ''状态：1启用 0禁用''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `sort_order`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'created_at'; SET @columndef = 'timestamp NOT NULL DEFAULT current_timestamp()';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `status`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =====================================================
-- lty-role_permissions 表字段
-- =====================================================
SET @tablename = 'lty-role_permissions';

SET @columnname = 'role_id'; SET @columndef = 'int(11) NOT NULL COMMENT ''角色ID''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `id`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'permission_id'; SET @columndef = 'int(11) NOT NULL COMMENT ''权限ID''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `role_id`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'created_at'; SET @columndef = 'timestamp NOT NULL DEFAULT current_timestamp()';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `permission_id`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =====================================================
-- lty-admin_menus 表字段
-- =====================================================
SET @tablename = 'lty-admin_menus';

SET @columnname = 'parent_id'; SET @columndef = 'int(11) DEFAULT NULL COMMENT ''父菜单ID''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `id`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'name'; SET @columndef = 'varchar(100) NOT NULL COMMENT ''菜单名称''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `parent_id`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'slug'; SET @columndef = 'varchar(100) NOT NULL COMMENT ''菜单标识''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `name`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'url'; SET @columndef = 'varchar(255) DEFAULT NULL COMMENT ''菜单链接''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `slug`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'icon'; SET @columndef = 'text DEFAULT NULL COMMENT ''菜单图标''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `url`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'type'; SET @columndef = 'enum(''category'',''menu'',''link'') DEFAULT ''menu'' COMMENT ''类型：category分类 menu菜单 link链接''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `icon`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'sort_order'; SET @columndef = 'int(11) DEFAULT 0 COMMENT ''排序''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `type`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'status'; SET @columndef = 'tinyint(1) DEFAULT 1 COMMENT ''状态：1启用 0禁用''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `sort_order`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'created_at'; SET @columndef = 'timestamp NOT NULL DEFAULT current_timestamp()';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `status`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'updated_at'; SET @columndef = 'timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `created_at`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =====================================================
-- lty-role_menus 表字段
-- =====================================================
SET @tablename = 'lty-role_menus';

SET @columnname = 'role_id'; SET @columndef = 'int(11) NOT NULL COMMENT ''角色ID''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `id`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'menu_id'; SET @columndef = 'int(11) NOT NULL COMMENT ''菜单ID''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `role_id`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'created_at'; SET @columndef = 'timestamp NOT NULL DEFAULT current_timestamp()';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `menu_id`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =====================================================
-- lty-team_members 表字段
-- =====================================================
SET @tablename = 'lty-team_members';

SET @columnname = 'name'; SET @columndef = 'varchar(100) NOT NULL';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `id`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'position'; SET @columndef = 'varchar(100) NOT NULL';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `name`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'bio'; SET @columndef = 'text';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `position`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'avatar'; SET @columndef = 'varchar(255)';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `bio`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'email'; SET @columndef = 'varchar(100)';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `avatar`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'phone'; SET @columndef = 'varchar(20)';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `email`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'employee_id'; SET @columndef = 'varchar(50) DEFAULT NULL COMMENT ''工号''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `phone`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'sort_order'; SET @columndef = 'int(11) DEFAULT 0';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `employee_id`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'status'; SET @columndef = 'tinyint(1) DEFAULT 1';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `sort_order`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'created_at'; SET @columndef = 'timestamp NOT NULL DEFAULT current_timestamp()';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `status`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'updated_at'; SET @columndef = 'timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `created_at`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =====================================================
-- lty-site_settings 表字段
-- =====================================================
SET @tablename = 'lty-site_settings';

SET @columnname = 'setting_key'; SET @columndef = 'varchar(50) NOT NULL';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `id`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'setting_value'; SET @columndef = 'text';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `setting_key`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'updated_at'; SET @columndef = 'timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `setting_value`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =====================================================
-- lty-contact_types 表字段
-- =====================================================
SET @tablename = 'lty-contact_types';

SET @columnname = 'name'; SET @columndef = 'varchar(100) NOT NULL COMMENT ''类型名称''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `id`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'description'; SET @columndef = 'text COMMENT ''类型描述''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `name`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'sort_order'; SET @columndef = 'int(11) DEFAULT 0 COMMENT ''排序''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `description`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'status'; SET @columndef = 'tinyint(1) DEFAULT 1 COMMENT ''状态：1启用 0禁用''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `sort_order`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'created_at'; SET @columndef = 'timestamp NOT NULL DEFAULT current_timestamp()';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `status`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'updated_at'; SET @columndef = 'timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `created_at`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =====================================================
-- lty-contact_messages 表字段
-- =====================================================
SET @tablename = 'lty-contact_messages';

SET @columnname = 'type_id'; SET @columndef = 'int(11) DEFAULT NULL COMMENT ''联系类型ID''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `id`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'name'; SET @columndef = 'varchar(100) NOT NULL';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `type_id`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'email'; SET @columndef = 'varchar(100) NOT NULL';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `name`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'phone'; SET @columndef = 'varchar(20)';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `email`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'subject'; SET @columndef = 'varchar(200)';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `phone`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'message'; SET @columndef = 'text NOT NULL';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `subject`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'ip_address'; SET @columndef = 'varchar(45)';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `message`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'created_at'; SET @columndef = 'timestamp NOT NULL DEFAULT current_timestamp()';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `ip_address`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'status'; SET @columndef = 'tinyint(1) DEFAULT 0';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `created_at`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'replied'; SET @columndef = 'tinyint(1) DEFAULT 0 COMMENT ''回复状态：1已回复 0未回复''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `status`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =====================================================
-- lty-company_timeline 表字段
-- =====================================================
SET @tablename = 'lty-company_timeline';

SET @columnname = 'year'; SET @columndef = 'varchar(10) NOT NULL';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `id`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'title'; SET @columndef = 'varchar(200) NOT NULL';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `year`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'description'; SET @columndef = 'text';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `title`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'sort_order'; SET @columndef = 'int(11) DEFAULT 0';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `description`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'status'; SET @columndef = 'tinyint(1) DEFAULT 1';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `sort_order`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'created_at'; SET @columndef = 'timestamp NOT NULL DEFAULT current_timestamp()';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `status`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'updated_at'; SET @columndef = 'timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `created_at`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =====================================================
-- lty-company_culture 表字段
-- =====================================================
SET @tablename = 'lty-company_culture';

SET @columnname = 'title'; SET @columndef = 'varchar(100) NOT NULL';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `id`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'description'; SET @columndef = 'text';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `title`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'image'; SET @columndef = 'varchar(255)';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `description`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'sort_order'; SET @columndef = 'int(11) DEFAULT 0';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `image`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'status'; SET @columndef = 'tinyint(1) DEFAULT 1';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `sort_order`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'created_at'; SET @columndef = 'timestamp NOT NULL DEFAULT current_timestamp()';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `status`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'updated_at'; SET @columndef = 'timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `created_at`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =====================================================
-- lty-company_behavior 表字段
-- =====================================================
SET @tablename = 'lty-company_behavior';

SET @columnname = 'title'; SET @columndef = 'varchar(100) NOT NULL';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `id`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'description'; SET @columndef = 'text';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `title`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'icon_svg'; SET @columndef = 'text DEFAULT NULL COMMENT ''SVG图标代码''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `description`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'sort_order'; SET @columndef = 'int(11) DEFAULT 0';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `icon_svg`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'status'; SET @columndef = 'tinyint(1) DEFAULT 1';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `sort_order`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'created_at'; SET @columndef = 'timestamp NOT NULL DEFAULT current_timestamp()';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `status`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'updated_at'; SET @columndef = 'timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `created_at`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =====================================================
-- lty-mission_slides 表字段
-- =====================================================
SET @tablename = 'lty-mission_slides';

SET @columnname = 'title'; SET @columndef = 'varchar(200) NOT NULL';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `id`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'subtitle'; SET @columndef = 'varchar(200)';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `title`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'image'; SET @columndef = 'varchar(255) NOT NULL';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `subtitle`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'sort_order'; SET @columndef = 'int(11) DEFAULT 0';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `image`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'status'; SET @columndef = 'tinyint(1) DEFAULT 1';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `sort_order`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'created_at'; SET @columndef = 'timestamp NOT NULL DEFAULT current_timestamp()';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `status`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'updated_at'; SET @columndef = 'timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `created_at`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =====================================================
-- lty-business_areas 表字段
-- =====================================================
SET @tablename = 'lty-business_areas';

SET @columnname = 'title'; SET @columndef = 'varchar(100) NOT NULL COMMENT ''业务标题''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `id`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'description'; SET @columndef = 'text COMMENT ''业务描述''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `title`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'icon_svg'; SET @columndef = 'text DEFAULT NULL COMMENT ''SVG图标代码''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `description`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'sort_order'; SET @columndef = 'int(11) DEFAULT 0 COMMENT ''排序''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `icon_svg`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'status'; SET @columndef = 'tinyint(1) DEFAULT 1 COMMENT ''状态：1启用 0禁用''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `sort_order`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'created_at'; SET @columndef = 'timestamp NOT NULL DEFAULT current_timestamp()';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `status`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'updated_at'; SET @columndef = 'timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `created_at`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =====================================================
-- lty-product_categories 表字段
-- =====================================================
SET @tablename = 'lty-product_categories';

SET @columnname = 'name'; SET @columndef = 'varchar(100) NOT NULL COMMENT ''分类名称''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `id`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'slug'; SET @columndef = 'varchar(50) NOT NULL COMMENT ''分类标识''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `name`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'description'; SET @columndef = 'text COMMENT ''分类描述''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `slug`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'sort_order'; SET @columndef = 'int(11) DEFAULT 0 COMMENT ''排序''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `description`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'status'; SET @columndef = 'tinyint(1) DEFAULT 1 COMMENT ''状态：1启用 0禁用''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `sort_order`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'created_at'; SET @columndef = 'timestamp NOT NULL DEFAULT current_timestamp()';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `status`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'updated_at'; SET @columndef = 'timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `created_at`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =====================================================
-- lty-products 表字段
-- =====================================================
SET @tablename = 'lty-products';

SET @columnname = 'title'; SET @columndef = 'varchar(200) NOT NULL';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `id`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'description'; SET @columndef = 'text';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `title`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'category_id'; SET @columndef = 'int(11) DEFAULT NULL COMMENT ''分类ID''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `description`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'image'; SET @columndef = 'varchar(255)';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `category_id`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'images'; SET @columndef = 'text DEFAULT NULL COMMENT ''图片列表，JSON格式''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `image`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'link'; SET @columndef = 'varchar(255)';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `images`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'is_original'; SET @columndef = 'tinyint(1) DEFAULT 1';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `link`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'sort_order'; SET @columndef = 'int(11) DEFAULT 0';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `is_original`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'status'; SET @columndef = 'tinyint(1) DEFAULT 1';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `sort_order`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'status_text'; SET @columndef = 'varchar(50) DEFAULT NULL COMMENT ''状态文本，如：预售中、运营中''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `status`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'release_date'; SET @columndef = 'date DEFAULT NULL COMMENT ''发布日期''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `status_text`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'features'; SET @columndef = 'text DEFAULT NULL COMMENT ''功能特性，JSON格式''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `release_date`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'tech_stack'; SET @columndef = 'text DEFAULT NULL COMMENT ''技术栈，JSON格式''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `features`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'demo_url'; SET @columndef = 'varchar(255) DEFAULT NULL COMMENT ''演示链接''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `tech_stack`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'download_url'; SET @columndef = 'varchar(255) DEFAULT NULL COMMENT ''下载链接''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `demo_url`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'price'; SET @columndef = 'decimal(10,2) DEFAULT NULL COMMENT ''价格''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `download_url`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'views'; SET @columndef = 'int(11) DEFAULT 0 COMMENT ''浏览量''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `price`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'full_description'; SET @columndef = 'text DEFAULT NULL COMMENT ''完整描述''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `views`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'created_at'; SET @columndef = 'timestamp NOT NULL DEFAULT current_timestamp()';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `full_description`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'updated_at'; SET @columndef = 'timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `created_at`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =====================================================
-- lty-product_updates 表字段
-- =====================================================
SET @tablename = 'lty-product_updates';

SET @columnname = 'product_id'; SET @columndef = 'int(11) NOT NULL';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `id`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'version'; SET @columndef = 'varchar(20) NOT NULL';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `product_id`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'content'; SET @columndef = 'text NOT NULL';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `version`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'release_date'; SET @columndef = 'date NOT NULL';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `content`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'sort_order'; SET @columndef = 'int(11) DEFAULT 0';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `release_date`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'status'; SET @columndef = 'tinyint(1) DEFAULT 1';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `sort_order`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'created_at'; SET @columndef = 'timestamp NOT NULL DEFAULT current_timestamp()';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `status`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'updated_at'; SET @columndef = 'timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `created_at`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =====================================================
-- lty-media_library 表字段
-- =====================================================
SET @tablename = 'lty-media_library';

SET @columnname = 'filename'; SET @columndef = 'varchar(255) NOT NULL COMMENT ''文件名''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `id`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'original_name'; SET @columndef = 'varchar(255) NOT NULL COMMENT ''原始文件名''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `filename`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'file_path'; SET @columndef = 'varchar(500) NOT NULL COMMENT ''文件路径''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `original_name`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'file_url'; SET @columndef = 'varchar(500) NOT NULL COMMENT ''文件URL''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `file_path`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'file_type'; SET @columndef = 'varchar(50) NOT NULL COMMENT ''文件类型：image, video, document, other''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `file_url`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'mime_type'; SET @columndef = 'varchar(100) NOT NULL COMMENT ''MIME类型''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `file_type`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'file_size'; SET @columndef = 'bigint(20) NOT NULL COMMENT ''文件大小（字节）''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `mime_type`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'width'; SET @columndef = 'int(11) DEFAULT NULL COMMENT ''图片宽度''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `file_size`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'height'; SET @columndef = 'int(11) DEFAULT NULL COMMENT ''图片高度''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `width`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'alt_text'; SET @columndef = 'varchar(255) DEFAULT NULL COMMENT ''替代文本''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `height`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'title'; SET @columndef = 'varchar(255) DEFAULT NULL COMMENT ''标题''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `alt_text`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'caption'; SET @columndef = 'text DEFAULT NULL COMMENT ''说明文字''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `title`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'description'; SET @columndef = 'text DEFAULT NULL COMMENT ''描述''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `caption`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'uploaded_by'; SET @columndef = 'int(11) DEFAULT NULL COMMENT ''上传者ID''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `description`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'upload_date'; SET @columndef = 'timestamp NOT NULL DEFAULT current_timestamp() COMMENT ''上传时间''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `uploaded_by`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'modified_date'; SET @columndef = 'timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT ''修改时间''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `upload_date`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'status'; SET @columndef = 'tinyint(1) DEFAULT 1 COMMENT ''状态：1正常 0已删除''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `modified_date`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =====================================================
-- lty-social_links 表字段
-- =====================================================
SET @tablename = 'lty-social_links';

SET @columnname = 'platform'; SET @columndef = 'varchar(50) NOT NULL COMMENT ''平台名称''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `id`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'url'; SET @columndef = 'varchar(500) NOT NULL COMMENT ''链接URL''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `platform`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'icon_type'; SET @columndef = 'enum(''default'',''custom'') DEFAULT ''default'' COMMENT ''图标类型：default默认图标，custom自定义图标''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `url`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'icon_url'; SET @columndef = 'varchar(500) DEFAULT NULL COMMENT ''自定义图标URL''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `icon_type`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'svg_code'; SET @columndef = 'text DEFAULT NULL COMMENT ''SVG代码''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `icon_url`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'sort_order'; SET @columndef = 'int(11) DEFAULT 0 COMMENT ''排序''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `svg_code`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'status'; SET @columndef = 'tinyint(1) DEFAULT 1 COMMENT ''状态：1启用 0禁用''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `sort_order`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'created_at'; SET @columndef = 'timestamp NOT NULL DEFAULT current_timestamp()';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `status`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'updated_at'; SET @columndef = 'timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `created_at`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =====================================================
-- lty-footer_links 表字段
-- =====================================================
SET @tablename = 'lty-footer_links';

SET @columnname = 'title'; SET @columndef = 'varchar(100) NOT NULL COMMENT ''链接标题''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `id`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'url'; SET @columndef = 'varchar(500) NOT NULL COMMENT ''链接URL''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `title`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'target'; SET @columndef = 'enum(''_self'',''_blank'') DEFAULT ''_self'' COMMENT ''打开方式''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `url`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'sort_order'; SET @columndef = 'int(11) DEFAULT 0 COMMENT ''排序''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `target`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'status'; SET @columndef = 'tinyint(1) DEFAULT 1 COMMENT ''状态：1启用 0禁用''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `sort_order`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'created_at'; SET @columndef = 'timestamp NOT NULL DEFAULT current_timestamp()';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `status`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'updated_at'; SET @columndef = 'timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `created_at`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =====================================================
-- lty-page_views 表字段
-- =====================================================
SET @tablename = 'lty-page_views';

SET @columnname = 'page_url'; SET @columndef = 'varchar(500) NOT NULL COMMENT ''页面URL''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `id`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'page_title'; SET @columndef = 'varchar(255) DEFAULT NULL COMMENT ''页面标题''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `page_url`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'view_date'; SET @columndef = 'date NOT NULL COMMENT ''浏览日期''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `page_title`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'view_count'; SET @columndef = 'int(11) DEFAULT 1 COMMENT ''浏览次数''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `view_date`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'unique_count'; SET @columndef = 'int(11) DEFAULT 1 COMMENT ''独立访客数''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `view_count`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'created_at'; SET @columndef = 'timestamp NOT NULL DEFAULT current_timestamp()';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `unique_count`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'updated_at'; SET @columndef = 'timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `created_at`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =====================================================
-- lty-external_link_logs 表字段
-- =====================================================
SET @tablename = 'lty-external_link_logs';

SET @columnname = 'link_url'; SET @columndef = 'varchar(500) NOT NULL COMMENT ''外链URL''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `id`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'source_page'; SET @columndef = 'varchar(500) DEFAULT NULL COMMENT ''来源页面''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `link_url`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'ip_address'; SET @columndef = 'varchar(45) DEFAULT NULL COMMENT ''访问者IP''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `source_page`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'user_agent'; SET @columndef = 'varchar(500) DEFAULT NULL COMMENT ''用户代理''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `ip_address`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'created_at'; SET @columndef = 'timestamp NOT NULL DEFAULT current_timestamp()';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `user_agent`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =====================================================
-- lty-email_verification_codes 表字段
-- =====================================================
SET @tablename = 'lty-email_verification_codes';

SET @columnname = 'email'; SET @columndef = 'varchar(100) NOT NULL COMMENT ''邮箱地址''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `id`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'code'; SET @columndef = 'varchar(6) NOT NULL COMMENT ''验证码''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `email`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'type'; SET @columndef = 'varchar(50) DEFAULT ''contact'' COMMENT ''验证类型：contact留言验证''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `code`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'ip_address'; SET @columndef = 'varchar(45) DEFAULT NULL COMMENT ''请求IP''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `type`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'created_at'; SET @columndef = 'timestamp NOT NULL DEFAULT current_timestamp()';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `ip_address`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'expires_at'; SET @columndef = 'timestamp NOT NULL COMMENT ''过期时间''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `created_at`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'used'; SET @columndef = 'tinyint(1) DEFAULT 0 COMMENT ''是否已使用''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `expires_at`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =====================================================
-- 操作日志表
-- =====================================================
CREATE TABLE IF NOT EXISTS `lty-operation_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL COMMENT '操作用户ID',
  `username` varchar(50) DEFAULT NULL COMMENT '操作用户名',
  `action` varchar(50) NOT NULL COMMENT '操作类型：create创建 update更新 delete删除 login登录 logout登出',
  `module` varchar(50) DEFAULT NULL COMMENT '操作模块',
  `description` varchar(500) DEFAULT NULL COMMENT '操作描述',
  `target_type` varchar(50) DEFAULT NULL COMMENT '目标类型',
  `target_id` int(11) DEFAULT NULL COMMENT '目标ID',
  `details` text DEFAULT NULL COMMENT '操作详情JSON',
  `ip_address` varchar(45) DEFAULT NULL COMMENT '操作IP',
  `user_agent` varchar(500) DEFAULT NULL COMMENT '用户代理',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`),
  KEY `module` (`module`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='操作日志表';

-- 添加操作日志管理权限
INSERT INTO `lty-permissions` (`name`, `slug`, `description`, `group`, `sort_order`, `status`) VALUES
('操作日志查看', 'logs_view', '查看操作日志', '开发配置', 53, 1)
ON DUPLICATE KEY UPDATE `name` = `name`;

-- 为超级管理员添加操作日志权限
INSERT INTO `lty-role_permissions` (`role_id`, `permission_id`) 
SELECT 1, id FROM `lty-permissions` WHERE `slug` = 'logs_view'
ON DUPLICATE KEY UPDATE `role_id` = `role_id`;

INSERT INTO `lty-role_permissions` (`role_id`, `permission_id`) 
SELECT 2, id FROM `lty-permissions` WHERE `slug` = 'logs_view'
ON DUPLICATE KEY UPDATE `role_id` = `role_id`;

-- 添加操作日志菜单
SET @dev_category_id = (SELECT id FROM `lty-admin_menus` WHERE slug = 'dev_category');

INSERT INTO `lty-admin_menus` (`parent_id`, `name`, `slug`, `url`, `icon`, `type`, `sort_order`, `status`) VALUES
(@dev_category_id, '操作日志', 'operation_logs', 'logs.php', '<svg width="18" height="18" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg"><path d="M832 128H192c-35.3 0-64 28.7-64 64v640c0 35.3 28.7 64 64 64h640c35.3 0 64-28.7 64-64V192c0-35.3-28.7-64-64-64z m0 704H192V192h640v640z" fill="currentColor"/><path d="M256 320h512v64H256zM256 448h512v64H256zM256 576h384v64H256zM256 704h256v64H256z" fill="currentColor"/></svg>', 'menu', 3, 1)
ON DUPLICATE KEY UPDATE `slug` = `slug`;

-- 更新操作日志菜单的父级
UPDATE `lty-admin_menus` SET parent_id = (SELECT id FROM (SELECT id FROM `lty-admin_menus` WHERE slug = 'dev_category') AS tmp), sort_order = 3 WHERE slug = 'operation_logs';

-- =====================================================
-- 安全日志表
-- =====================================================
CREATE TABLE IF NOT EXISTS `lty-security_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_type` varchar(50) NOT NULL COMMENT '事件类型：login_failed登录失败 unauthorized_access未授权访问 permission_denied权限拒绝 csrf_invalid CSRF验证失败 session_expired 会话过期 ip_blocked IP封禁 brute_force 暴力破解',
  `severity` enum('low','medium','high','critical') DEFAULT 'medium' COMMENT '严重程度：low低 medium中 high高 critical严重',
  `user_id` int(11) DEFAULT NULL COMMENT '用户ID',
  `username` varchar(50) DEFAULT NULL COMMENT '尝试登录的用户名',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP地址',
  `user_agent` varchar(500) DEFAULT NULL COMMENT '用户代理',
  `request_url` varchar(500) DEFAULT NULL COMMENT '请求URL',
  `request_method` varchar(10) DEFAULT NULL COMMENT '请求方法',
  `description` varchar(500) DEFAULT NULL COMMENT '事件描述',
  `details` text DEFAULT NULL COMMENT '详细信息JSON',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `event_type` (`event_type`),
  KEY `severity` (`severity`),
  KEY `user_id` (`user_id`),
  KEY `ip_address` (`ip_address`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='安全日志表';

-- 添加安全日志管理权限
INSERT INTO `lty-permissions` (`name`, `slug`, `description`, `group`, `sort_order`, `status`) VALUES
('安全日志查看', 'security_logs_view', '查看安全日志', '系统设置', 42, 1)
ON DUPLICATE KEY UPDATE `name` = `name`;

-- 为超级管理员添加安全日志权限
INSERT INTO `lty-role_permissions` (`role_id`, `permission_id`) 
SELECT 1, id FROM `lty-permissions` WHERE `slug` = 'security_logs_view'
ON DUPLICATE KEY UPDATE `role_id` = `role_id`;

INSERT INTO `lty-role_permissions` (`role_id`, `permission_id`) 
SELECT 2, id FROM `lty-permissions` WHERE `slug` = 'security_logs_view'
ON DUPLICATE KEY UPDATE `role_id` = `role_id`;

-- 添加安全日志菜单
SET @system_category_id = (SELECT id FROM `lty-admin_menus` WHERE slug = 'system_category');

INSERT INTO `lty-admin_menus` (`parent_id`, `name`, `slug`, `url`, `icon`, `type`, `sort_order`, `status`) VALUES
(@system_category_id, '安全日志', 'security_logs', 'security_logs.php', '<svg width="18" height="18" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg"><path d="M512 64C264.6 64 64 264.6 64 512s200.6 448 448 448 448-200.6 448-448S759.4 64 512 64zm0 820c-205.4 0-372-166.6-372-372s166.6-372 372-372 372 166.6 372 372-166.6 372-372 372z" fill="currentColor"/><path d="M464 336a48 48 0 1 0 96 0 48 48 0 1 0-96 0zm72 112h-48c-4.4 0-8 3.6-8 8v272c0 4.4 3.6 8 8 8h48c4.4 0 8-3.6 8-8V456c0-4.4-3.6-8-8-8z" fill="currentColor"/></svg>', 'menu', 3, 1)
ON DUPLICATE KEY UPDATE `slug` = `slug`;

-- 更新安全日志菜单的父级
UPDATE `lty-admin_menus` SET parent_id = (SELECT id FROM (SELECT id FROM `lty-admin_menus` WHERE slug = 'system_category') AS tmp), sort_order = 3 WHERE slug = 'security_logs';

-- 添加安全日志默认设置
INSERT INTO `lty-site_settings` (`setting_key`, `setting_value`) VALUES
('enable_security_log', '1'),
('security_alert_email', '1')
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;

-- 为超级管理员添加操作日志菜单权限
INSERT INTO `lty-role_menus` (`role_id`, `menu_id`) 
SELECT 1, id FROM `lty-admin_menus` WHERE `slug` = 'operation_logs'
ON DUPLICATE KEY UPDATE `role_id` = `role_id`;

INSERT INTO `lty-role_menus` (`role_id`, `menu_id`) 
SELECT 2, id FROM `lty-admin_menus` WHERE `slug` = 'operation_logs'
ON DUPLICATE KEY UPDATE `role_id` = `role_id`;

-- =====================================================
-- lty-operation_logs 表字段
-- =====================================================
SET @tablename = 'lty-operation_logs';

SET @columnname = 'user_id'; SET @columndef = 'int(11) DEFAULT NULL COMMENT ''操作用户ID''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `id`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'username'; SET @columndef = 'varchar(50) DEFAULT NULL COMMENT ''操作用户名''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `user_id`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'action'; SET @columndef = 'varchar(50) NOT NULL COMMENT ''操作类型''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `username`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'module'; SET @columndef = 'varchar(50) DEFAULT NULL COMMENT ''操作模块''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `action`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'description'; SET @columndef = 'varchar(500) DEFAULT NULL COMMENT ''操作描述''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `module`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'target_type'; SET @columndef = 'varchar(50) DEFAULT NULL COMMENT ''目标类型''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `description`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'target_id'; SET @columndef = 'int(11) DEFAULT NULL COMMENT ''目标ID''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `target_type`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'details'; SET @columndef = 'text DEFAULT NULL COMMENT ''操作详情JSON''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `target_id`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'ip_address'; SET @columndef = 'varchar(45) DEFAULT NULL COMMENT ''操作IP''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `details`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'user_agent'; SET @columndef = 'varchar(500) DEFAULT NULL COMMENT ''用户代理''';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `ip_address`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columnname = 'created_at'; SET @columndef = 'timestamp NOT NULL DEFAULT current_timestamp()';
SET @preparedStatement = (SELECT IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0, 'SELECT 1', CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ', @columndef, ' AFTER `user_agent`'))); PREPARE stmt FROM @preparedStatement; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 添加关于程序查看权限
INSERT INTO `lty-permissions` (`name`, `slug`, `description`, `group`, `sort_order`, `status`) VALUES
('关于程序查看', 'about_view', '查看关于程序页面', '其他', 60, 1)
ON DUPLICATE KEY UPDATE `name` = `name`;

-- 为超级管理员添加关于程序权限
INSERT INTO `lty-role_permissions` (`role_id`, `permission_id`) 
SELECT 1, id FROM `lty-permissions` WHERE `slug` = 'about_view'
ON DUPLICATE KEY UPDATE `role_id` = `role_id`;

-- 为管理员添加关于程序权限
INSERT INTO `lty-role_permissions` (`role_id`, `permission_id`) 
SELECT 2, id FROM `lty-permissions` WHERE `slug` = 'about_view'
ON DUPLICATE KEY UPDATE `role_id` = `role_id`;

-- 为编辑添加关于程序权限
INSERT INTO `lty-role_permissions` (`role_id`, `permission_id`) 
SELECT 3, id FROM `lty-permissions` WHERE `slug` = 'about_view'
ON DUPLICATE KEY UPDATE `role_id` = `role_id`;

-- 为访客添加关于程序权限
INSERT INTO `lty-role_permissions` (`role_id`, `permission_id`) 
SELECT 4, id FROM `lty-permissions` WHERE `slug` = 'about_view'
ON DUPLICATE KEY UPDATE `role_id` = `role_id`;

COMMIT;
