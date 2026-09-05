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
INSERT INTO staff_menu_pages (id, app_id, name, parent_prefix, parent_id, uri, code, icon, sort, status) VALUES (1, 1, 'Dashboard', '12', 12, '/dashboard', 'cron:dashboard', 'el-icon-data-line', 100, 1);
INSERT INTO staff_menu_pages (id, app_id, name, parent_prefix, parent_id, uri, code, icon, sort, status) VALUES (2, 1, '计划任务', '12', 12, '/tasks', 'cron:tasks', 'el-icon-s-order', 90, 1);
INSERT INTO staff_menu_pages (id, app_id, name, parent_prefix, parent_id, uri, code, icon, sort, status) VALUES (4, 1, '执行记录', '12', 12, '/executions', 'cron:executions', 'el-icon-time', 80, 1);
INSERT INTO staff_menu_pages (id, app_id, name, parent_prefix, parent_id, uri, code, icon, sort, status) VALUES (6, 1, 'Cron Nodes', '12', 12, '/nodes', 'cron:nodes', 'el-icon-monitor', 70, 1);
INSERT INTO staff_menu_pages (id, app_id, name, parent_prefix, parent_id, uri, code, icon, sort, status) VALUES (8, 1, '权限管理', '', 0, '/auth', 'auth', 'el-icon-lock', 50, 1);
INSERT INTO staff_menu_pages (id, app_id, name, parent_prefix, parent_id, uri, code, icon, sort, status) VALUES (9, 1, '用户管理', '8', 8, '/users', 'auth:users', 'el-icon-user', 0, 1);
INSERT INTO staff_menu_pages (id, app_id, name, parent_prefix, parent_id, uri, code, icon, sort, status) VALUES (10, 1, '角色管理', '8', 8, '/roles', 'auth:roles', 'el-icon-s-custom', 0, 1);
INSERT INTO staff_menu_pages (id, app_id, name, parent_prefix, parent_id, uri, code, icon, sort, status) VALUES (11, 1, '菜单管理', '8', 8, '/menus', 'auth:menus', 'el-icon-menu', 0, 1);
INSERT INTO staff_menu_pages (id, app_id, name, parent_prefix, parent_id, uri, code, icon, sort, status) VALUES (12, 1, 'Cron 管理', '', 0, '/cron', 'cron', '', 100, 1);


-- 系统默认角色：初始化插入超级管理员角色和编辑任务角色组
INSERT INTO staff_roles (id, app_id, is_super_role, name, code, `desc`, status) VALUES (1, 1, 1, '超级管理员', 'super_admin', '拥有系统全部权限', 1);
INSERT INTO staff_roles (id, app_id, is_super_role, name, code, `desc`, status) VALUES (2, 1, 0, '编辑任务角色组', 'editer_task_group', '登录的用户拥有该角色时，可以编辑不属于他创建的计划任务', 1);

-- 初始化超级管理员用户（账号 admin，默认密码 123456789，部署后请立即修改）
INSERT INTO staff_user (id, account, password, user_name, status) VALUES (1, 'admin', '$2y$12$E5H2YsF9n1VnwoUsxq9Md.TzOCVLe6wUAksNmvYoNsN.N00l54fLS', '超级管理员', 1);

-- 初始化超级管理员分配超管角色
INSERT INTO staff_user_role (app_id, user_id, role_id) VALUES (1, 1, 1);
