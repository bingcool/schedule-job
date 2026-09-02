-- Schedule Job RBAC schema
-- staff_user.status：1-启用，0-禁用；delete_at：删除时间

CREATE TABLE IF NOT EXISTS `staff_user` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增用户ID',
    `account` varchar(128) NOT NULL DEFAULT '' COMMENT '用户账号(建议邮箱)',
    `password` varchar(128) NOT NULL DEFAULT '' COMMENT '密码哈希',
    `user_name` varchar(128) NOT NULL DEFAULT '' COMMENT '用户名称',
    `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1-启用,0-禁用',
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    `enabled_at` datetime DEFAULT NULL COMMENT '启用时间（新增或再次启用）',
    `disabled_at` datetime DEFAULT NULL COMMENT '启用后，再次禁用的时间',
    `delete_at` datetime DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `account` (`account`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='登录的用户表';

CREATE TABLE IF NOT EXISTS `staff_user_relate_node_group` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
    `user_id` int unsigned NOT NULL COMMENT '用户ID',
    `node_group_id` bigint unsigned NOT NULL COMMENT '节点组ID',
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    `delete_at` datetime DEFAULT NULL COMMENT '删除|禁用时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户拥有的Cron节点组';

CREATE TABLE IF NOT EXISTS `staff_menu_pages` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
    `app_id` bigint unsigned NOT NULL COMMENT '应用Id',
    `name` varchar(128) NOT NULL DEFAULT '' COMMENT '页面节点名称',
    `parent_prefix` varchar(256) NOT NULL DEFAULT '' COMMENT '父页面所有id',
    `parent_id` int DEFAULT '0' COMMENT '父页面Id',
    `uri` varchar(256) NOT NULL DEFAULT '' COMMENT '页面URI',
    `code` varchar(256) NOT NULL DEFAULT '' COMMENT '唯一标志',
    `icon` varchar(256) NOT NULL DEFAULT '' COMMENT '图标',
    `sort` int unsigned DEFAULT '0' COMMENT '排序：越大越靠前',
    `status` tinyint(2) unsigned DEFAULT '1' COMMENT '状态：0-禁用(菜单栏不展示)，1-启用，2-删除',
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    `delete_at` datetime DEFAULT NULL COMMENT '删除|禁用时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_uri_appid` (`uri`,`app_id`),
    UNIQUE KEY `uniq_code` (`code`),
    KEY `idx_pid` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='菜单页面节点表';

CREATE TABLE IF NOT EXISTS `staff_role_page` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增Id',
    `app_id` bigint unsigned NOT NULL COMMENT '应用Id',
    `role_id` bigint unsigned NOT NULL COMMENT '角色Id',
    `page_id` bigint unsigned NOT NULL COMMENT '页面Id',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq-key` (`app_id`,`role_id`,`page_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='角色-页面权限表';

CREATE TABLE IF NOT EXISTS `staff_role_permission` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增Id',
    `app_id` bigint unsigned NOT NULL COMMENT '应用Id',
    `type` tinyint(2) unsigned NOT NULL COMMENT '类型1-api接口，2-关联任务接口',
    `role_id` bigint unsigned NOT NULL COMMENT '角色Id',
    `per_id` bigint unsigned NOT NULL COMMENT '权限Id',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_uk-key` (`app_id`,`type`,`role_id`,`per_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='角色-api权限表';

CREATE TABLE IF NOT EXISTS `staff_roles` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增Id',
    `app_id` bigint NOT NULL DEFAULT '0' COMMENT '应用id',
    `is_super_role` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否是超级管理员角色组',
    `name` varchar(64) NOT NULL DEFAULT '' COMMENT '角色名称',
    `code` varchar(128) NOT NULL DEFAULT '' COMMENT '唯一标识',
    `desc` varchar(256) NOT NULL DEFAULT '' COMMENT '角色描述',
    `status` tinyint(1) DEFAULT '1' COMMENT '状态：0-禁用，1-启用',
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='角色表';

CREATE TABLE IF NOT EXISTS `staff_user_role` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '自增Id',
    `app_id` bigint unsigned NOT NULL COMMENT '应用Id',
    `user_id` bigint unsigned NOT NULL COMMENT '用户Id',
    `role_id` bigint unsigned NOT NULL COMMENT '角色Id',
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq-userid-roleid-appid` (`user_id`,`role_id`,`app_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户与角色关联表';

-- 初始化插入菜单栏
INSERT INTO bingcool.staff_menu_pages (id, app_id, name, parent_prefix, parent_id, uri, code, icon, sort, status, created_at, updated_at, delete_at) VALUES (1, 1, 'Dashboard', '12', 12, '/dashboard', 'cron:dashboard', 'el-icon-data-line', 100, 1, '2026-08-31 15:31:24', '2026-08-31 16:30:54', null);
INSERT INTO bingcool.staff_menu_pages (id, app_id, name, parent_prefix, parent_id, uri, code, icon, sort, status, created_at, updated_at, delete_at) VALUES (2, 1, '计划任务', '12', 12, '/tasks', 'cron:tasks', 'el-icon-s-order', 90, 1, '2026-08-31 15:31:24', '2026-08-31 16:30:54', null);
INSERT INTO bingcool.staff_menu_pages (id, app_id, name, parent_prefix, parent_id, uri, code, icon, sort, status, created_at, updated_at, delete_at) VALUES (3, 1, '创建任务', '2', 2, '/tasks/create', 'cron:tasks:create', 'el-icon-plus', 0, 2, '2026-08-31 15:31:24', '2026-08-31 16:30:54', '2026-09-01 00:30:54');
INSERT INTO bingcool.staff_menu_pages (id, app_id, name, parent_prefix, parent_id, uri, code, icon, sort, status, created_at, updated_at, delete_at) VALUES (4, 1, '执行记录', '12', 12, '/executions', 'cron:executions', 'el-icon-time', 80, 1, '2026-08-31 15:31:24', '2026-08-31 16:30:54', null);
INSERT INTO bingcool.staff_menu_pages (id, app_id, name, parent_prefix, parent_id, uri, code, icon, sort, status, created_at, updated_at, delete_at) VALUES (5, 1, '执行日志', '4', 4, '/executions/log', 'cron:executions:log', 'el-icon-notebook-2', 0, 2, '2026-08-31 15:31:24', '2026-08-31 16:30:54', '2026-09-01 00:30:54');
INSERT INTO bingcool.staff_menu_pages (id, app_id, name, parent_prefix, parent_id, uri, code, icon, sort, status, created_at, updated_at, delete_at) VALUES (6, 1, 'Cron Nodes', '12', 12, '/nodes', 'cron:nodes', 'el-icon-monitor', 70, 1, '2026-08-31 15:31:24', '2026-08-31 16:30:54', null);
INSERT INTO bingcool.staff_menu_pages (id, app_id, name, parent_prefix, parent_id, uri, code, icon, sort, status, created_at, updated_at, delete_at) VALUES (7, 1, 'Runtime', '12', 12, '/runtime', 'cron:runtime', 'el-icon-odometer', 60, 1, '2026-08-31 15:31:24', '2026-09-02 14:42:38', null);
INSERT INTO bingcool.staff_menu_pages (id, app_id, name, parent_prefix, parent_id, uri, code, icon, sort, status, created_at, updated_at, delete_at) VALUES (8, 1, '权限管理', '', 0, '/auth', 'auth', 'el-icon-lock', 50, 1, '2026-08-31 15:31:24', '2026-09-02 14:51:04', null);
INSERT INTO bingcool.staff_menu_pages (id, app_id, name, parent_prefix, parent_id, uri, code, icon, sort, status, created_at, updated_at, delete_at) VALUES (9, 1, '用户管理', '8', 8, '/users', 'auth:users', 'el-icon-user', 0, 1, '2026-08-31 15:31:24', '2026-08-31 15:31:24', null);
INSERT INTO bingcool.staff_menu_pages (id, app_id, name, parent_prefix, parent_id, uri, code, icon, sort, status, created_at, updated_at, delete_at) VALUES (10, 1, '角色管理', '8', 8, '/roles', 'auth:roles', 'el-icon-s-custom', 0, 1, '2026-08-31 15:31:24', '2026-08-31 15:31:24', null);
INSERT INTO bingcool.staff_menu_pages (id, app_id, name, parent_prefix, parent_id, uri, code, icon, sort, status, created_at, updated_at, delete_at) VALUES (11, 1, '菜单管理', '8', 8, '/menus', 'auth:menus', 'el-icon-menu', 0, 1, '2026-08-31 15:31:24', '2026-08-31 15:31:24', null);
INSERT INTO bingcool.staff_menu_pages (id, app_id, name, parent_prefix, parent_id, uri, code, icon, sort, status, created_at, updated_at, delete_at) VALUES (12, 1, 'Cron 管理', '', 0, '/cron', 'cron', '', 100, 1, '2026-08-31 16:30:54', '2026-08-31 16:30:54', null);


-- 初始化插入超级管理员角色
INSERT INTO bingcool.staff_roles (id, app_id, is_super_role, name, code, `desc`, status, created_at, updated_at) VALUES (1, 1, 1, '超级管理员', 'super_admin', '拥有系统全部权限', 1, '2026-08-31 15:31:24', '2026-08-31 15:31:24');

