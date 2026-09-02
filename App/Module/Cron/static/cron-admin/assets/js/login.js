(function (window) {
  'use strict';

  var common = window.CronAdminCommon;

  window.CronAdminLogin = {
    template: '#tpl-login',
    data: function () {
      return {
        form: { account: '', password: '', remember: true },
        loading: false
      };
    },
    methods: {
      submit: async function () {
        if (!this.form.account || !this.form.password) {
          this.$message.warning('请输入账号和密码');
          return;
        }
        this.loading = true;
        try {
          var data = await common.api('/auth/login', {
            method: 'POST',
            body: { account: this.form.account, password: this.form.password }
          });
          common.applySessionToRoot(this, data);
          this.$message.success('登录成功');
          this.$router.replace(common.firstAllowedRoute(data.user || common.getUser()));
        } catch (e) {
          common.toastErr(this, e);
        } finally {
          this.loading = false;
        }
      }
    }
  };
})(window);
