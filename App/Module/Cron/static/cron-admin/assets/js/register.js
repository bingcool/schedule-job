(function (window) {
  'use strict';

  var common = window.CronAdminCommon;

  window.CronAdminRegister = {
    template: '#tpl-register',
    data: function () {
      return {
        form: { account: '', userName: '', password: '', passwordConfirm: '' },
        loading: false
      };
    },
    methods: {
      submit: async function () {
        if (!this.form.account || !this.form.userName || !this.form.password) {
          this.$message.warning('请填写完整注册信息');
          return;
        }
        if (this.form.password !== this.form.passwordConfirm) {
          this.$message.warning('两次输入的密码不一致');
          return;
        }
        this.loading = true;
        try {
          var data = await common.api('/auth/register', {
            method: 'POST',
            body: this.form
          });
          common.applySessionToRoot(this, data);
          this.$message.success('注册成功');
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
