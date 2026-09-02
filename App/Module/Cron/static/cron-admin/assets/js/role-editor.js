(function (window) {
  'use strict';

  var common = window.CronAdminCommon;

  window.CronAdminRoleEditor = {
    template: '#tpl-role-editor',
    data: function () {
      return {
        saving: false,
        isSuperRole: false,
        form: { name: '', code: '', desc: '', status: 1 }
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
        if (!this.isEdit) return;
        try {
          var d = await common.api('/roles/detail?id=' + this.$route.params.id);
          this.isSuperRole = !!d.isSuperRole;
          this.form = {
            name: d.name || '',
            code: d.code || '',
            desc: d.desc || '',
            status: this.isSuperRole ? 1 : (d.status === 0 ? 0 : 1)
          };
        } catch (e) {
          common.toastErr(this, e);
        }
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
            status: this.isSuperRole ? 1 : this.form.status
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
