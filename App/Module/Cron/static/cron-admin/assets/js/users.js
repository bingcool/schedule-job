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
        query: { page: 1, pageSize: 20, account: '', userName: '', status: '' }
      };
    },
    created: function () {
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
