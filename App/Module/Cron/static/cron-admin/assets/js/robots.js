(function (window) {
  'use strict';

  var common = window.CronAdminCommon;

  var PLATFORM_LABELS = { 1: '企业微信', 2: '钉钉', 3: '飞书' };

  function emptyForm() {
    return { id: 0, name: '', platform: 1, webhookUrl: '', secret: '' };
  }

  window.CronAdminRobots = {
    template: '#tpl-robots',
    data: function () {
      return {
        items: [],
        loading: false,
        dlg: false,
        saving: false,
        testingId: 0,
        form: emptyForm(),
        isSuper: common.isViewerSuper()
      };
    },
    created: function () {
      this.load();
    },
    methods: {
      platformLabel: function (platform) {
        return PLATFORM_LABELS[Number(platform)] || '未知';
      },
      goNodeGroups: function () {
        if (!common.isViewerSuper()) {
          this.$message.warning('仅超级管理员可管理节点分组绑定');
          return;
        }
        this.$router.push({ path: '/nodes', query: { tab: 'groups' } });
      },
      load: async function () {
        this.loading = true;
        try {
          var d = await common.api('/robots');
          this.items = common.extractListRows(d);
        } catch (e) {
          common.toastErr(this, e);
        } finally {
          this.loading = false;
        }
      },
      open: function (row) {
        if (!this.isSuper) {
          this.$message.warning('仅超级管理员可编辑机器人');
          return;
        }
        this.form = row
          ? {
            id: row.id,
            name: row.name,
            platform: Number(row.platform) || 1,
            webhookUrl: '',
            secret: ''
          }
          : emptyForm();
        this.dlg = true;
      },
      save: async function () {
        var name = this.form.name ? String(this.form.name).trim() : '';
        var webhookUrl = this.form.webhookUrl ? String(this.form.webhookUrl).trim() : '';
        if (!name) {
          this.$message.warning('请填写名称');
          return;
        }
        if (!this.form.id && !webhookUrl) {
          this.$message.warning('请填写 Webhook 地址');
          return;
        }
        this.saving = true;
        try {
          var payload = {
            id: this.form.id,
            name: name,
            platform: Number(this.form.platform) || 1,
            webhookUrl: webhookUrl,
            secret: this.form.secret ? String(this.form.secret).trim() : ''
          };
          if (this.form.id) {
            await common.api('/robots', { method: 'PUT', body: payload });
          } else {
            await common.api('/robots', { method: 'POST', body: payload });
          }
          this.dlg = false;
          this.$message.success('已保存');
          this.load();
        } catch (e) {
          common.toastErr(this, e);
        } finally {
          this.saving = false;
        }
      },
      switchStatus: async function (row, next) {
        if (!this.isSuper) {
          this.$message.warning('仅超级管理员可启用或禁用');
          return;
        }
        var action = Number(next) === 1 ? '启用' : '禁用';
        try {
          await common.confirmDialog(this, '确认' + action + '机器人「' + row.name + '」？', Number(next) === 1 ? 'success' : 'warning');
          await common.api('/robots/status', { method: 'PUT', body: { id: row.id, status: Number(next) } });
          this.$message.success('已' + action);
          this.load();
        } catch (e) {
          if (e !== 'cancel') common.toastErr(this, e);
        }
      },
      test: async function (row) {
        if (!this.isSuper) {
          this.$message.warning('仅超级管理员可测试');
          return;
        }
        this.testingId = row.id;
        try {
          var d = await common.api('/robots/test', { method: 'POST', body: { id: row.id } });
          if (d && d.ok) {
            this.$message.success('测试发送成功');
          } else {
            this.$message.error((d && d.error) || '测试失败');
          }
          this.load();
        } catch (e) {
          common.toastErr(this, e);
        } finally {
          this.testingId = 0;
        }
      },
      remove: async function (row) {
        if (!this.isSuper) {
          this.$message.warning('仅超级管理员可删除');
          return;
        }
        try {
          await common.confirmDelete(this, '确认删除机器人「' + row.name + '」？删除前需先解除节点组绑定。');
          await common.api('/robots', { method: 'DELETE', body: { id: row.id } });
          this.$message.success('已删除');
          this.load();
        } catch (e) {
          if (e !== 'cancel') common.toastErr(this, e);
        }
      }
    }
  };
})(window);
