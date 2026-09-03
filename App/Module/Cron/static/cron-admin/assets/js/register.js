(function (window) {
  'use strict';

  var common = window.CronAdminCommon;

  function validateAccount(account) {
    account = account ? String(account).trim() : '';
    if (!account) {
      return '请填写账号';
    }
    if (account.indexOf('@') !== -1) {
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(account)) {
        return '请输入有效的邮箱地址';
      }
      return '';
    }
    if (!/^[A-Za-z0-9]+$/.test(account)) {
      return '账号仅支持大小写字母和数字，或使用有效邮箱';
    }
    return '';
  }

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
        if (!this.form.userName || !this.form.password) {
          this.$message.warning('请填写完整注册信息');
          return;
        }
        var accountErr = validateAccount(this.form.account);
        if (accountErr) {
          this.$message.warning(accountErr);
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
