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
    created: function () {
      var hint = common.consumeAuthHint();
      if (hint) this.$message.warning(hint);
    },
    methods: {
      submit: async function () {
        var identity = String(this.form.account || '').trim();
        if (!identity || !this.form.password) {
          this.$message.warning('请输入账号和密码');
          return;
        }
        if (identity.indexOf('@') !== -1 && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(identity)) {
          this.$message.warning('邮箱格式不对');
          return;
        }
        this.loading = true;
        try {
          var data = await common.api('/auth/login', {
            method: 'POST',
            body: { account: this.form.account, password: this.form.password }
          });
          var user = (data && data.user) || {};
          if (!common.hasAccessibleMenus(user)) {
            common.clearAuth();
            this.$message.warning(common.NO_MENU_ACCESS_HINT);
            return;
          }
          common.applySessionToRoot(this, data);
          this.$message.success('登录成功');
          this.$router.replace(common.firstAllowedRoute(user));
        } catch (e) {
          common.toastErr(this, e);
        } finally {
          this.loading = false;
        }
      }
    }
  };
})(window);
