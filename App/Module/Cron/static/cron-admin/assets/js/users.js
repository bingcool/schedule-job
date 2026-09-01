(function (window) {
  'use strict';

  var common = window.CronAdminCommon;

  window.CronAdminUsers = {
    template: '#tpl-users',
    data: function () {
      return {
        items: [],
        total: 0,
        loading: false,
        query: { page: 1, pageSize: 20, account: '', userName: '', status: '' },
        nodeGroups: [],
        roleOptions: [],
        grantDlg: false,
        grantSaving: false,
        grantUser: { id: 0, userName: '', account: '' },
        grantForm: { nodeGroupIds: [] },
        roleDlg: false,
        roleSaving: false,
        roleUser: { id: 0, userName: '', account: '' },
        roleForm: { roleIds: [] }
      };
    },
    computed: {
      grantGroupOptions: function () {
        return common.filterGroupsForViewer(this.nodeGroups);
      }
    },
    created: function () {
      this.loadGroups();
      this.loadRoles();
      this.load();
    },
    methods: {
      load: async function () {
        this.loading = true;
        try {
          var qs = new URLSearchParams();
          var self = this;
          Object.keys(this.query).forEach(function (k) {
            if (self.query[k] !== '' && self.query[k] !== null) qs.set(k, self.query[k]);
          });
          var d = await common.api('/users?' + qs.toString());
          this.items = common.extractListRows(d);
          this.total = common.extractListTotal(d, this.items);
        } catch (e) {
          common.toastErr(this, e);
        } finally {
          this.loading = false;
        }
      },
      loadRoles: async function () {
        try {
          var d = await common.api('/roles/options');
          this.roleOptions = (d && d.list) || [];
        } catch (e) {
          common.toastErr(this, e);
        }
      },
      openRoles: function (row) {
        var ids = row.roleIds || (row.roles || []).map(function (r) { return r.id; });
        this.roleUser = { id: Number(row.id), userName: row.userName || '', account: row.account || '' };
        this.roleForm = { roleIds: (ids || []).map(Number) };
        this.roleDlg = true;
      },
      selectAllRoles: function () {
        this.roleForm.roleIds = this.roleOptions.map(function (r) { return Number(r.id); });
      },
      clearAllRoles: function () {
        this.roleForm.roleIds = [];
      },
      saveRoles: async function () {
        this.roleSaving = true;
        try {
          await common.api('/users/roles', {
            method: 'PUT',
            body: { id: this.roleUser.id, roleIds: this.roleForm.roleIds }
          });
          this.$message.success('角色分配已保存');
          this.roleDlg = false;
          this.load();
        } catch (e) {
          common.toastErr(this, e);
        } finally {
          this.roleSaving = false;
        }
      },
      loadGroups: async function () {
        try {
          var d = await common.api('/node-groups');
          this.nodeGroups = (d && d.list) || [];
        } catch (e) {
          common.toastErr(this, e);
        }
      },
      openGrant: function (row) {
        if (row.isSuper) {
          this.$message.warning('超级管理员固定拥有所有节点，无需单独授权');
          return;
        }
        this.grantUser = { id: Number(row.id), userName: row.userName || '', account: row.account || '' };
        this.grantForm = { nodeGroupIds: (row.nodeGroupIds || []).map(Number) };
        this.grantDlg = true;
      },
      selectAllGroups: function () {
        this.grantForm.nodeGroupIds = this.grantGroupOptions.map(function (g) { return Number(g.id); });
      },
      clearAllGroups: function () {
        this.grantForm.nodeGroupIds = [];
      },
      saveGrant: async function () {
        this.grantSaving = true;
        try {
          await common.api('/users/node-groups', {
            method: 'PUT',
            body: { id: this.grantUser.id, nodeGroupIds: this.grantForm.nodeGroupIds }
          });
          this.$message.success('节点组授权已保存');
          this.grantDlg = false;
          this.load();
        } catch (e) {
          common.toastErr(this, e);
        } finally {
          this.grantSaving = false;
        }
      },
      search: function () {
        this.query.page = 1;
        this.load();
      },
      reset: function () {
        this.query = { page: 1, pageSize: 20, account: '', userName: '', status: '' };
        this.load();
      },
      onStatusChange: async function (row, next) {
        var prev = next === 1 ? 0 : 1;
        var name = row.userName || row.account;
        try {
          if (next === 0) {
            await common.confirmUserDisable(this, name);
          }
          await common.api('/users/status', { method: 'PUT', body: { id: Number(row.id), status: Number(next) } });
          row.status = next;
          this.$message.success(next === 1 ? '已启用' : '已禁用');
        } catch (e) {
          row.status = prev;
          this.$forceUpdate();
          if (e !== 'cancel') common.toastErr(this, e);
        }
      },
      remove: async function (row) {
        try {
          await common.confirmUserDelete(this, row.userName || row.account);
          await common.api('/users?id=' + encodeURIComponent(row.id), {
            method: 'DELETE',
            body: { id: Number(row.id) }
          });
          this.$message.success('已删除');
          this.load();
        } catch (e) {
          if (e !== 'cancel') common.toastErr(this, e);
        }
      }
    }
  };
})(window);
