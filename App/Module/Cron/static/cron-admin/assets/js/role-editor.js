(function (window) {
  'use strict';

  var common = window.CronAdminCommon;

  var API_CATALOG = [
    { id: 1, name: '任务列表', method: 'GET', path: '/api/v1/tasks', group: '任务管理' },
    { id: 2, name: '创建任务', method: 'POST', path: '/api/v1/tasks', group: '任务管理' },
    { id: 3, name: '更新任务', method: 'PUT', path: '/api/v1/tasks', group: '任务管理' },
    { id: 4, name: '删除任务', method: 'DELETE', path: '/api/v1/tasks', group: '任务管理' },
    { id: 5, name: '任务启停', method: 'PUT', path: '/api/v1/tasks/status', group: '任务管理' },
    { id: 6, name: '节点列表', method: 'GET', path: '/api/v1/nodes', group: '节点管理' },
    { id: 7, name: '执行记录', method: 'GET', path: '/api/v1/tasks/logs', group: '执行记录' },
    { id: 8, name: '用户管理', method: 'GET', path: '/api/v1/users', group: '权限管理' },
    { id: 9, name: '角色管理', method: 'GET', path: '/api/v1/roles', group: '权限管理' },
    { id: 10, name: '菜单管理', method: 'GET', path: '/api/v1/menus', group: '权限管理' }
  ];

  var TASK_CATALOG = [
    { id: 1, name: '立即执行', code: 'cron:task:run_once', desc: '手动触发任务执行' },
    { id: 2, name: '启用/禁用', code: 'cron:task:switch', desc: '切换任务启用状态' },
    { id: 3, name: '查看日志', code: 'cron:task:logs', desc: '查看任务执行日志' },
    { id: 4, name: '编辑 GLUE', code: 'cron:task:glue_edit', desc: '编辑 GLUE 脚本内容' }
  ];

  window.CronAdminRoleEditor = {
    template: '#tpl-role-editor',
    data: function () {
      return {
        activeTab: 'menu',
        saving: false,
        form: { name: '', code: '', desc: '', status: 1, isSuperRole: false, pageIds: [], apiPerIds: [], taskPerIds: [] },
        menuTree: [],
        apiPermissions: API_CATALOG,
        taskPermissions: TASK_CATALOG,
        apiSel: [],
        taskSel: []
      };
    },
    computed: {
      isEdit: function () {
        return !!this.$route.params.id;
      }
    },
    created: function () {
      this.init();
    },
    methods: {
      init: async function () {
        try {
          var menus = await common.api('/menus');
          this.menuTree = (menus && menus.list) || [];
          if (!this.isEdit) return;
          var d = await common.api('/roles/detail?id=' + this.$route.params.id);
          this.form = {
            name: d.name || '',
            code: d.code || '',
            desc: d.desc || '',
            status: d.status === 0 ? 0 : 1,
            isSuperRole: !!d.isSuperRole,
            pageIds: d.pageIds || [],
            apiPerIds: d.apiPerIds || [],
            taskPerIds: d.taskPerIds || []
          };
          if (d.menus && d.menus.length) this.menuTree = d.menus;
          if (d.apiPermissions) this.apiPermissions = d.apiPermissions;
          if (d.taskPermissions) this.taskPermissions = d.taskPermissions;
          this.$nextTick(function () {
            if (this.$refs.menuTree) this.$refs.menuTree.setCheckedKeys(this.form.pageIds);
          }.bind(this));
        } catch (e) {
          common.toastErr(this, e);
        }
      },
      methodType: function (m) {
        return { GET: '', POST: 'success', PUT: 'warning', DELETE: 'danger' }[m] || 'info';
      },
      checkAllMenu: function () {
        var ids = [];
        function walk(nodes) {
          (nodes || []).forEach(function (n) {
            ids.push(n.id);
            if (n.children) walk(n.children);
          });
        }
        walk(this.menuTree);
        this.$refs.menuTree.setCheckedKeys(ids);
      },
      uncheckAllMenu: function () {
        this.$refs.menuTree.setCheckedKeys([]);
      },
      collectPageIds: function () {
        if (!this.$refs.menuTree) return this.form.pageIds;
        return this.$refs.menuTree.getCheckedKeys().concat(this.$refs.menuTree.getHalfCheckedKeys());
      },
      onApiSelect: function (rows) {
        this.form.apiPerIds = (rows || []).map(function (r) { return r.id; });
      },
      onTaskSelect: function (rows) {
        this.form.taskPerIds = (rows || []).map(function (r) { return r.id; });
      },
      save: async function () {
        if (!this.form.name || !this.form.code) {
          this.$message.warning('请填写角色名称和唯一标识');
          return;
        }
        this.saving = true;
        try {
          var body = {
            name: this.form.name,
            code: this.form.code,
            desc: this.form.desc,
            status: this.form.status,
            isSuperRole: this.form.isSuperRole ? 1 : 0,
            pageIds: this.collectPageIds(),
            apiPerIds: this.form.apiPerIds,
            taskPerIds: this.form.taskPerIds
          };
          if (this.isEdit) {
            body.id = Number(this.$route.params.id);
            await common.api('/roles', { method: 'PUT', body: body });
          } else {
            await common.api('/roles', { method: 'POST', body: body });
          }
          this.$message.success('已保存');
          this.$router.push('/roles');
        } catch (e) {
          common.toastErr(this, e);
        } finally {
          this.saving = false;
        }
      }
    }
  };
})(window);
