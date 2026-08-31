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
      switchStatus: async function (row) {
        var next = row.status ? 0 : 1;
        var action = next ? '启用' : '禁用';
        try {
          await common.confirmDialog(this, '确认' + action + '用户「' + (row.userName || row.account) + '」？', next ? 'success' : 'warning');
          await common.api('/users/status', { method: 'PUT', body: { id: Number(row.id), status: next } });
          this.$message.success('已' + action);
          this.load();
        } catch (e) {
          if (e !== 'cancel') common.toastErr(this, e);
        }
      }
    }
  };
})(window);
