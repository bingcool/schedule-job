(function (window) {
  'use strict';

  var common = window.CronAdminCommon;

  var PLATFORM_LABELS = { 1: '企业微信', 2: '钉钉', 3: '飞书' };

  function emptyForm() {
    return { id: 0, name: '', platform: 1, webhookUrl: '', secret: '' };
  }

  function emptyLogQuery() {
    return {
      page: 1,
      pageSize: 20,
      robotId: '',
      alertType: '',
      status: '',
      startTime: '',
      endTime: '',
      timeRange: []
    };
  }

  window.CronAdminRobots = {
    template: '#tpl-robots',
    data: function () {
      return {
        activeTab: 'robots',
        items: [],
        loading: false,
        dlg: false,
        saving: false,
        testingId: 0,
        form: emptyForm(),
        isSuper: common.isViewerSuper(),
        logItems: [],
        logTotal: 0,
        logLoading: false,
        logLoaded: false,
        logQuery: emptyLogQuery()
      };
    },
    created: function () {
      if (this.$route.query && this.$route.query.tab === 'logs') {
        this.activeTab = 'logs';
      }
      this.load();
      if (this.activeTab === 'logs') {
        this.loadLogs();
      }
    },
    methods: {
      platformLabel: function (platform) {
        return PLATFORM_LABELS[Number(platform)] || '未知';
      },
      alertTypeClass: function (row) {
        var t = Number(row && row.alertType);
        if (t === 4) return 'status-online';
        if (t === 3) return 'status-offline';
        if (t === 2) return 'status-timeout';
        return 'status-failed';
      },
      deliveryStatusClass: function (row) {
        var s = Number(row && row.status);
        if (s === 1) return 'status-success';
        if (s === 3) return 'status-cancelled';
        return 'status-failed';
      },
      goNodeGroups: function () {
        if (!common.isViewerSuper()) {
          this.$message.warning('仅超级管理员可管理节点分组绑定');
          return;
        }
        this.$router.push({ path: '/nodes', query: { tab: 'groups' } });
      },
      onTabClick: function (tab) {
        var name = (tab && tab.name) ? tab.name : this.activeTab;
        if (name === 'logs') {
          this.loadLogs();
        }
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
      syncLogTimeRange: function () {
        var range = this.logQuery.timeRange || [];
        if (range.length === 2) {
          this.logQuery.startTime = range[0];
          this.logQuery.endTime = range[1];
        } else {
          this.logQuery.startTime = '';
          this.logQuery.endTime = '';
        }
      },
      loadLogs: async function () {
        this.syncLogTimeRange();
        this.logLoading = true;
        try {
          var qs = new URLSearchParams();
          var q = this.logQuery;
          qs.set('page', String(q.page || 1));
          qs.set('pageSize', String(q.pageSize || 20));
          if (q.robotId !== '' && q.robotId !== null) qs.set('robotId', q.robotId);
          if (q.alertType !== '' && q.alertType !== null) qs.set('alertType', q.alertType);
          if (q.status !== '' && q.status !== null) qs.set('status', q.status);
          if (q.startTime) qs.set('startTime', q.startTime);
          if (q.endTime) qs.set('endTime', q.endTime);
          var d = await common.api('/robots/alert-logs?' + qs.toString());
          this.logItems = common.extractListRows(d);
          this.logTotal = common.extractListTotal(d, this.logItems);
          this.logLoaded = true;
        } catch (e) {
          common.toastErr(this, e);
        } finally {
          this.logLoading = false;
        }
      },
      searchLogs: function () {
        this.logQuery.page = 1;
        this.loadLogs();
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
