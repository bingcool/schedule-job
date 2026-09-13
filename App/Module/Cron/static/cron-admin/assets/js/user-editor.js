(function (window) {
  'use strict';

  var common = window.CronAdminCommon;

  function emptyForm() {
    return { account: '', email: '', userName: '', password: '', passwordConfirm: '' };
  }

  function isEmailFormat(value) {
    var text = String(value || '').trim();
    if (!text) return false;
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(text);
  }

  window.CronAdminUserEditor = {
    template: '#tpl-user-editor',
    data: function () {
      return {
        form: emptyForm(),
        loadedEmail: '',
        saving: false,
        userId: 0
      };
    },
    computed: {
      isEdit: function () {
        return !!this.$route.params.id;
      },
      accountIsEmail: function () {
        return isEmailFormat(this.form.account);
      },
      isBuiltInAdmin: function () {
        return this.isEdit && String(this.form.account || '').trim().toLowerCase() === 'admin';
      }
    },
    watch: {
      'form.account': function (val) {
        this.syncEmailWithAccount(val);
      }
    },
    created: function () {
      this.init();
    },
    methods: {
      syncEmailWithAccount: function (account) {
        if (isEmailFormat(account)) {
          this.form.email = String(account || '').trim();
          return;
        }
        this.form.email = this.loadedEmail;
      },
      init: async function () {
        try {
          if (!this.isEdit) return;
          var d = await common.api('/users/detail?id=' + this.$route.params.id);
          this.userId = d.id;
          this.loadedEmail = String(d.email || '').trim();
          this.form = {
            account: d.account || '',
            email: this.loadedEmail,
            userName: d.userName || '',
            password: '',
            passwordConfirm: ''
          };
          this.syncEmailWithAccount(this.form.account);
        } catch (e) {
          common.toastErr(this, e);
        }
      },
      save: async function () {
        if (!this.form.account || !this.form.userName) {
          this.$message.warning('请填写账号和用户名称');
          return;
        }
        if (!this.isEdit && !this.form.password) {
          this.$message.warning('请填写密码');
          return;
        }
        if (this.form.password && this.form.password !== this.form.passwordConfirm) {
          this.$message.warning('两次输入的密码不一致');
          return;
        }
        if (this.form.email && !isEmailFormat(this.form.email)) {
          this.$message.warning('邮箱格式不对');
          return;
        }
        this.saving = true;
        try {
          var body = {
            account: this.form.account,
            email: this.form.email || '',
            userName: this.form.userName,
            password: this.form.password || ''
          };
          if (this.isEdit) {
            body.id = Number(this.$route.params.id);
            await common.api('/users', { method: 'PUT', body: body });
          } else {
            await common.api('/users', { method: 'POST', body: body });
          }
          this.$message.success('已保存');
          this.$router.push('/users');
        } catch (e) {
          common.toastErr(this, e);
        } finally {
          this.saving = false;
        }
      }
    }
  };
})(window);
