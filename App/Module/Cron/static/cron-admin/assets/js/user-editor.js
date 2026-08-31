(function (window) {
  'use strict';

  var common = window.CronAdminCommon;

  function emptyForm() {
    return { account: '', userName: '', password: '', passwordConfirm: '', roleIds: [], nodeGroupIds: [] };
  }

  window.CronAdminUserEditor = {
    template: '#tpl-user-editor',
    data: function () {
      return {
        form: emptyForm(),
        roleOptions: [],
        nodeGroups: [],
        saving: false,
        userId: 0
      };
    },
    computed: {
      isEdit: function () {
        return !!this.$route.params.id;
      },
      selectedRoleNames: function () {
        var ids = this.form.roleIds || [];
        return this.roleOptions.filter(function (r) { return ids.indexOf(r.id) >= 0; });
      }
    },
    created: function () {
      this.init();
    },
    methods: {
      init: async function () {
        try {
          var reqs = [common.api('/roles/options'), common.api('/node-groups')];
          if (this.isEdit) reqs.push(common.api('/users/detail?id=' + this.$route.params.id));
          var results = await Promise.all(reqs);
          this.roleOptions = (results[0] && results[0].list) || [];
          this.nodeGroups = (results[1] && results[1].list) || [];
          if (this.isEdit && results[2]) {
            var d = results[2];
            this.userId = d.id;
            this.form = {
              account: d.account || '',
              userName: d.userName || '',
              password: '',
              passwordConfirm: '',
              roleIds: d.roleIds || [],
              nodeGroupIds: d.nodeGroupIds || []
            };
          }
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
            password: this.form.password || '',
            roleIds: this.form.roleIds,
            nodeGroupIds: this.form.nodeGroupIds
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
