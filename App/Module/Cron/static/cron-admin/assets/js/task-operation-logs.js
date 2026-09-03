(function (window) {
  'use strict';

  var common = window.CronAdminCommon;

  var ACTION_OPTIONS = [
    { value: 1, label: '启用任务' },
    { value: 2, label: '禁用任务' },
    { value: 3, label: '删除任务' },
    { value: 4, label: '执行任务' },
    { value: 5, label: '编辑任务' }
  ];

  window.CronAdminTaskOperationLogs = {
    template: '#tpl-task-operation-logs',
    data: function () {
      return {
        items: [],
        total: 0,
        loading: false,
        operatorOptions: [],
        detailDlg: false,
        detailRow: null,
        query: {
          page: 1,
          pageSize: 20,
          taskName: '',
          actionType: '',
          operatorId: '',
          startTime: '',
          endTime: '',
          operationTimeRange: []
        }
      };
    },
    created: function () {
      this.loadOperators();
      this.load();
    },
    methods: {
      syncOperationTimeRange: function () {
        var startTime = String(this.query.startTime || '').trim();
        var endTime = String(this.query.endTime || '').trim();
        if (startTime && endTime) {
          this.query.operationTimeRange = [startTime, endTime];
          return;
        }
        this.query.operationTimeRange = [];
        this.query.startTime = '';
        this.query.endTime = '';
      },
      syncStartEndFromRange: function () {
        var range = this.query.operationTimeRange || [];
        if (range.length === 2) {
          this.query.startTime = range[0];
          this.query.endTime = range[1];
        } else {
          this.query.startTime = '';
          this.query.endTime = '';
        }
      },
      loadOperators: async function () {
        try {
          var d = await common.api('/tasks/operation-logs/operators');
          this.operatorOptions = (d && d.list) || [];
        } catch (e) {
          common.toastErr(this, e);
        }
      },
      load: async function () {
        this.loading = true;
        try {
          var qs = new URLSearchParams();
          var self = this;
          Object.keys(this.query).forEach(function (k) {
            if (k === 'operationTimeRange') return;
            if (self.query[k] !== '' && self.query[k] !== null) qs.set(k, self.query[k]);
          });
          var d = await common.api('/tasks/operation-logs?' + qs.toString());
          this.items = common.extractListRows(d);
          this.total = common.extractListTotal(d, this.items);
        } catch (e) {
          common.toastErr(this, e);
        } finally {
          this.loading = false;
        }
      },
      search: function () {
        this.syncStartEndFromRange();
        this.query.page = 1;
        this.load();
      },
      actionTypeText: function (row) {
        return (row && (row.actionTypeName || row.action_type_name)) || '-';
      },
      actionTypeClass: function (row) {
        var type = Number((row && (row.actionType || row.action_type)) || 0);
        if (type === 1) return 'status-success';
        if (type === 2) return 'status-disabled';
        if (type === 3) return 'status-failed';
        if (type === 4) return 'status-enabled';
        if (type === 5) return 'status-timeout';
        return 'default';
      },
      showDetail: function (row) {
        this.detailRow = row;
        this.detailDlg = true;
      },
      detailPayload: function () {
        if (!this.detailRow) return '';
        var payload = {
          contentBefore: this.detailRow.contentBefore || this.detailRow.content_before || null,
          contentAfter: this.detailRow.contentAfter || this.detailRow.content_after || null
        };
        return JSON.stringify(payload, null, 2);
      }
    }
  };
})(window);
