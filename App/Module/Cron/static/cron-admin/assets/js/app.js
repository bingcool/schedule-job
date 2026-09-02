(function (window) {
  'use strict';

  var common = window.CronAdminCommon;

  var router = new VueRouter({
    routes: [
      { path: '/', redirect: '/dashboard' },
      { path: '/login', component: window.CronAdminLogin, meta: { title: '登录', public: true } },
      { path: '/register', component: window.CronAdminRegister, meta: { title: '注册', public: true } },
      { path: '/dashboard', component: window.CronAdminDashboard, meta: { title: 'Dashboard', subtitle: '任务、今日执行与节点心跳聚合', breadcrumb: 'Dashboard' } },
      { path: '/tasks', component: window.CronAdminTasks, meta: { title: '计划任务', subtitle: '配置写入 cron_task，Worker Polling 后生效', breadcrumb: '计划任务' } },
      { path: '/tasks/create', component: window.CronAdminEditor, meta: { title: '创建计划任务', subtitle: '配置任务调度规则、执行方式以及运行策略', breadcrumb: '计划任务 / 创建任务' } },
      { path: '/tasks/edit/:id', component: window.CronAdminEditor, meta: { title: '编辑计划任务', subtitle: '配置任务调度规则、执行方式以及运行策略', breadcrumb: '计划任务 / 编辑任务' } },
      { path: '/tasks/detail/:id', component: window.CronAdminDetail, meta: { title: '任务详情', subtitle: '', breadcrumb: '计划任务 / 任务详情' } },
      { path: '/executions', component: window.CronAdminExecutions, meta: { title: '执行记录', subtitle: '按任务、流水状态、批次过滤', breadcrumb: '执行记录' } },
      { path: '/executions/log', component: window.CronAdminExecutionLog, meta: { title: '执行日志', subtitle: '单次执行详情、stdout/stderr 与下载', breadcrumb: '执行记录 / 执行日志' } },
      { path: '/nodes', component: window.CronAdminNodes, meta: { title: 'Cron Nodes', subtitle: 'Agent 节点管理与心跳状态', breadcrumb: 'Cron Nodes' } },
      { path: '/runtime', component: window.CronAdminRuntime, meta: { title: 'Runtime', subtitle: 'Cron Worker 运行时聚合概览', breadcrumb: 'Runtime' } },
      { path: '/users', component: window.CronAdminUsers, meta: { title: '用户管理', subtitle: '管理系统登录用户及其角色分配', breadcrumb: '权限管理 / 用户管理' } },
      { path: '/users/create', component: window.CronAdminUserEditor, meta: { title: '新增用户', subtitle: '配置用户基本信息、密码及角色权限', breadcrumb: '权限管理 / 用户管理 / 新增用户' } },
      { path: '/users/edit/:id', component: window.CronAdminUserEditor, meta: { title: '编辑用户', subtitle: '配置用户基本信息、密码及角色权限', breadcrumb: '权限管理 / 用户管理 / 编辑用户' } },
      { path: '/roles', component: window.CronAdminRoles, meta: { title: '角色管理', subtitle: '定义角色并配置菜单页面权限', breadcrumb: '权限管理 / 角色管理' } },
      { path: '/roles/create', component: window.CronAdminRoleEditor, meta: { title: '新增角色', subtitle: '配置角色基本信息', breadcrumb: '权限管理 / 角色管理 / 新增角色' } },
      { path: '/roles/edit/:id', component: window.CronAdminRoleEditor, meta: { title: '编辑角色', subtitle: '配置角色基本信息', breadcrumb: '权限管理 / 角色管理 / 编辑角色' } },
      { path: '/menus', component: window.CronAdminMenus, meta: { title: '菜单管理', subtitle: '管理侧边栏菜单页面节点，对应 staff_menu_pages 表', breadcrumb: '权限管理 / 菜单管理' } }
    ]
  });

  router.beforeEach(function (to, from, next) {
    var isPublic = !!(to.meta && to.meta.public);
    var token = common.getToken();
    if (isPublic) {
      if (token && (to.path === '/login' || to.path === '/register')) {
        var loggedIn = common.getUser();
        return next(loggedIn ? common.firstAllowedRoute(loggedIn) : '/dashboard');
      }
      return next();
    }
    if (!token) {
      return next('/login');
    }
    var user = common.getUser();
    if (user && Array.isArray(user.menus) && !common.canAccessRoute(to.path, user)) {
      var fallback = common.firstAllowedRoute(user);
      if (to.path === fallback || !common.canAccessRoute(fallback, user)) {
        common.clearAuth();
        return next('/login');
      }
      return next(fallback);
    }
    next();
  });

  function redirectLegacyUrls() {
    var path = window.location.pathname;
    var search = window.location.search;
    var hash = window.location.hash;
    if (path.indexOf('/cron-admin/task.html') !== -1) {
      var id = (new URLSearchParams(search)).get('id');
      window.location.replace('/cron-admin' + (id ? '#/tasks/edit/' + id : '#/tasks/create'));
      return true;
    }
    if (path.indexOf('/cron-admin/execution.html') !== -1) {
      var taskId = (new URLSearchParams(search)).get('taskId');
      window.location.replace('/cron-admin#/executions' + (taskId ? '?taskId=' + encodeURIComponent(taskId) : ''));
      return true;
    }
    if (path.indexOf('/cron-admin/log.html') !== -1) {
      var params = new URLSearchParams(search);
      window.location.replace('/cron-admin#/executions/log?taskId=' + encodeURIComponent(params.get('taskId') || '')
        + '&execBatchId=' + encodeURIComponent(params.get('execBatchId') || '')
        + '&logId=' + encodeURIComponent(params.get('logId') || ''));
      return true;
    }
    if (path.indexOf('/cron-admin/index.html') !== -1 && !hash) {
      window.location.replace('/cron-admin#/tasks');
      return true;
    }
    return false;
  }

  if (redirectLegacyUrls()) {
    return;
  }

  new Vue({
    el: '#app',
    router: router,
    data: function () {
      return {
        currentUser: common.getUser(),
        settingsDlg: false,
        passwordSaving: false,
        passwordForm: { oldPassword: '', newPassword: '', newPasswordConfirm: '' },
        profileDlg: false,
        profileSaving: false,
        profileForm: { account: '', userName: '' }
      };
    },
    computed: {
      isAuthPage: function () {
        return !!(this.$route.meta && this.$route.meta.public);
      },
      navMenus: function () {
        return common.sidebarMenus(this.currentUser);
      },
      breadcrumbPath: function () {
        return (this.$route.meta && this.$route.meta.breadcrumb) || '';
      },
      pageTitle: function () {
        return (this.$route.meta && this.$route.meta.title) || '';
      },
      pageSubtitle: function () {
        return (this.$route.meta && this.$route.meta.subtitle) || '';
      }
    },
    created: function () {
      this.refreshUser();
    },
    watch: {
      '$route': function (to, from) {
        var toPublic = !!(to.meta && to.meta.public);
        var fromPublic = !!(from.meta && from.meta.public);
        if (!toPublic && fromPublic && common.getToken()) {
          this.currentUser = common.getUser();
          this.refreshUser();
        }
      }
    },
    methods: {
      refreshUser: async function () {
        if (!common.getToken()) {
          this.currentUser = null;
          return;
        }
        try {
          var user = await common.api('/auth/me');
          common.setUser(user);
          this.currentUser = user;
          if (!this.isAuthPage && user && !common.canAccessRoute(this.$route.path, user)) {
            this.$router.replace(common.firstAllowedRoute(user));
          }
        } catch (e) {
          this.currentUser = common.getUser();
        }
      },
      menuLink: function (uri) {
        return common.normalizeMenuPath(uri);
      },
      logout: function () {
        common.clearAuth();
        this.currentUser = null;
        this.$router.replace('/login');
      },
      onUserCommand: function (command) {
        if (command === 'password') {
          this.resetPasswordForm();
          this.settingsDlg = true;
        }
        if (command === 'profile') {
          this.openProfile();
        }
      },
      openProfile: function () {
        var user = this.currentUser || {};
        this.profileForm = {
          account: user.account || '',
          userName: user.userName || ''
        };
        this.profileDlg = true;
      },
      submitProfile: async function () {
        var form = this.profileForm;
        if (!form.userName) {
          this.$message.warning('请填写用户名称');
          return;
        }
        this.profileSaving = true;
        try {
          var user = await common.api('/auth/profile', {
            method: 'PUT',
            body: { userName: form.userName }
          });
          common.setUser(user);
          this.currentUser = user;
          this.$message.success('资料已保存');
          this.profileDlg = false;
        } catch (e) {
          common.toastErr(this, e);
        } finally {
          this.profileSaving = false;
        }
      },
      resetPasswordForm: function () {
        this.passwordForm = { oldPassword: '', newPassword: '', newPasswordConfirm: '' };
      },
      submitPassword: async function () {
        var form = this.passwordForm;
        if (!form.oldPassword || !form.newPassword || !form.newPasswordConfirm) {
          this.$message.warning('请填写旧密码、新密码和确认新密码');
          return;
        }
        if (form.newPassword !== form.newPasswordConfirm) {
          this.$message.warning('两次输入的新密码不一致');
          return;
        }
        if (form.newPassword.length < 8) {
          this.$message.warning('新密码至少 8 位');
          return;
        }
        if (form.oldPassword === form.newPassword) {
          this.$message.warning('新密码不能与旧密码相同');
          return;
        }
        this.passwordSaving = true;
        try {
          await common.api('/auth/password', {
            method: 'PUT',
            body: {
              oldPassword: form.oldPassword,
              newPassword: form.newPassword,
              newPasswordConfirm: form.newPasswordConfirm
            }
          });
          this.$message.success('密码已修改');
          this.settingsDlg = false;
          this.resetPasswordForm();
        } catch (e) {
          common.toastErr(this, e);
        } finally {
          this.passwordSaving = false;
        }
      }
    }
  });
})(window);
