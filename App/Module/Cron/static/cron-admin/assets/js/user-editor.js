(function (window) {
  'use strict';

  var common = window.CronAdminCommon;

  function emptyForm() {
    return { account: '', userName: '', password: '', passwordConfirm: '' };
  }

  window.CronAdminUserEditor = {
    template: '#tpl-user-editor',
    data: function () {
      return {
        form: emptyForm(),
        saving: false,
        userId: 0
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
        try {
          if (!this.isEdit) return;
          var d = await common.api('/users/detail?id=' + this.$route.params.id);
          this.userId = d.id;
          this.form = {
            account: d.account || '',
            userName: d.userName || '',
            password: '',
            passwordConfirm: ''
          };
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
        this.saving = true;
        try {
          var body = {
            account: this.form.account,
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
