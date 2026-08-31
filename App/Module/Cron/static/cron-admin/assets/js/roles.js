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
        query: { page: 1, pageSize: 20, name: '', status: '' }
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
      remove: async function (row) {
        try {
          await common.confirmDelete(this, '确认删除角色「' + row.name + '」？');
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
