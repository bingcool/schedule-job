(function (window) {
  'use strict';

  var common = window.CronAdminCommon;

  window.CronAdminRoles = {
    template: '#tpl-roles',
    data: function () {
      return {
        items: [],
        total: 0,
        loading: false,
        stats: { total: 0, enabled: 0, disabled: 0, super: 0, userCount: 0 },
        query: { page: 1, pageSize: 20, name: '', status: '' },
        pageDlg: false,
        pageSaving: false,
        pageRole: {},
        menuTree: [],
        pageIds: []
      };
    },
    created: function () {
      this.loadStats();
      this.load();
    },
    methods: {
      loadStats: async function () {
        try {
          this.stats = await common.api('/roles/stats') || this.stats;
        } catch (e) {
          common.toastErr(this, e);
        }
      },
      load: async function () {
        this.loading = true;
        try {
          var qs = new URLSearchParams();
          var self = this;
          Object.keys(this.query).forEach(function (k) {
            if (self.query[k] !== '' && self.query[k] !== null) qs.set(k, self.query[k]);
          });
          var d = await common.api('/roles?' + qs.toString());
          this.items = common.extractListRows(d);
          this.total = common.extractListTotal(d, this.items);
        } catch (e) {
          common.toastErr(this, e);
        } finally {
          this.loading = false;
        }
      },
      search: function () {
        this.query.page = 1;
        this.load();
      },
      onStatusChange: async function (row, next) {
        var prev = next === 1 ? 0 : 1;
        try {
          if ((row.isSuperRole || row.isSystemRole) && next === 0) {
            throw new Error('系统角色不能禁用');
          }
          if (next === 0) {
            await common.confirmRoleDisable(this, row.name);
          }
          await common.api('/roles/status', { method: 'PUT', body: { id: Number(row.id), status: Number(next) } });
          row.status = next;
          this.$message.success(next === 1 ? '已启用' : '已禁用');
          this.loadStats();
        } catch (e) {
          row.status = prev;
          this.$forceUpdate();
          if (e !== 'cancel') common.toastErr(this, e);
        }
      },
      openPages: async function (row) {
        if (row.isSuperRole) {
          this.$message.info('超级管理员拥有全部菜单，无需配置');
          return;
        }
        this.pageRole = row;
        this.pageDlg = true;
        this.pageIds = [];
        this.menuTree = [];
        try {
          var menus = await common.api('/menus');
          this.menuTree = (menus && menus.list) || [];
          var d = await common.api('/roles/detail?id=' + encodeURIComponent(row.id));
          this.pageIds = d.pageIds || [];
          if (d.menus && d.menus.length) this.menuTree = d.menus;
          this.$nextTick(function () {
            if (this.$refs.pageMenuTree) {
              this.$refs.pageMenuTree.setCheckedKeys(this.pageIds);
            }
          }.bind(this));
        } catch (e) {
          common.toastErr(this, e);
        }
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
        if (this.$refs.pageMenuTree) this.$refs.pageMenuTree.setCheckedKeys(ids);
      },
      uncheckAllMenu: function () {
        if (this.$refs.pageMenuTree) this.$refs.pageMenuTree.setCheckedKeys([]);
      },
      collectPageIds: function () {
        if (!this.$refs.pageMenuTree) return this.pageIds;
        return this.$refs.pageMenuTree.getCheckedKeys().concat(this.$refs.pageMenuTree.getHalfCheckedKeys());
      },
      savePages: async function () {
        if (!this.pageRole.id) return;
        this.pageSaving = true;
        try {
          await common.api('/roles/pages', {
            method: 'PUT',
            body: { id: Number(this.pageRole.id), pageIds: this.collectPageIds() }
          });
          this.$message.success('菜单权限已保存');
          this.pageDlg = false;
          this.load();
        } catch (e) {
          common.toastErr(this, e);
        } finally {
          this.pageSaving = false;
        }
      },
      remove: async function (row) {
        if (row.isSuperRole || row.isSystemRole || row.code === 'super_admin' || row.code === 'editer_task_group') {
          this.$message.warning('系统角色不能删除');
          return;
        }
        var used = Number(row.userCount || 0);
        if (used > 0) {
          this.$message.warning('角色已被 ' + used + ' 个用户关联使用，无法删除');
          return;
        }
        try {
          await common.confirmRoleDelete(this, row.name);
          await common.api('/roles?id=' + encodeURIComponent(row.id), {
            method: 'DELETE',
            body: { id: Number(row.id) }
          });
          this.$message.success('已删除');
          this.loadStats();
          this.load();
        } catch (e) {
          if (e !== 'cancel') common.toastErr(this, e);
        }
      }
    }
  };
})(window);
