(function (window) {
  'use strict';

  var common = window.CronAdminCommon;

  function execStatusRaw(row) {
    return String((row && (row.statusName || row.status)) || '').trim().toLowerCase();
  }

  function normalizeExecStatus(row) {
    var raw = execStatusRaw(row);
    if (raw === 'success' || raw === 'succeeded' || raw === 'ok' || raw === '成功') return 'success';
    if (raw === 'failed' || raw === 'failure' || raw === 'error' || raw === '失败') return 'failed';
    if (raw === 'timeout' || raw === 'timed_out' || raw === '超时') return 'timeout';
    if (raw === 'cancelled' || raw === 'canceled' || raw === '取消') return 'cancelled';
    if (raw === 'cancel_requested' || raw === '8' || raw === '取消中') return 'cancel_requested';
    if (raw === 'running' || raw === 'processing' || raw === '执行中') return 'running';
    if (raw === 'skipped' || raw === 'skip' || raw === '跳过') return 'skipped';
    if (raw === 'register' || raw === 'pending' || raw === '0' || raw === '注册定时任务') return 'register';
    if (raw === 'unregister' || raw === '7' || raw === '解除定时任务') return 'unregister';
    return 'default';
  }

  function registerTaskStatusSuffix(row) {
    var taskStatus = row && row.taskStatus;
    if (taskStatus === 1 || taskStatus === '1') return '（启用）';
    if (taskStatus === 0 || taskStatus === '0') return '（禁用）';
    return '';
  }

  function execStatusText(row) {
    var raw = execStatusRaw(row);
    if (raw === 'register' || raw === 'pending' || raw === '0') return '注册定时任务' + registerTaskStatusSuffix(row);
    if (raw === 'unregister' || raw === '7') return '解除定时任务';
    var key = normalizeExecStatus(row);
    var map = {
      success: '成功',
      failed: '失败',
      timeout: '超时',
      cancelled: '取消',
      cancel_requested: '取消中',
      running: '执行中',
      skipped: '跳过',
      register: '注册定时任务',
      unregister: '解除定时任务',
      default: row && (row.statusName || row.status) ? String(row.statusName || row.status) : '未知'
    };
    return map[key];
  }

  var CHART_SERIES = [
    { key: 'success', label: '成功', color: '#16a34a' },
    { key: 'failed', label: '失败', color: '#dc2626' },
    { key: 'timeout', label: '超时', color: '#d97706' },
    { key: 'cancelled', label: '取消', color: '#4b5563' }
  ];

  function pad2(n) {
    return n < 10 ? '0' + n : String(n);
  }

  function formatDateTime(d) {
    return d.getFullYear() + '-' + pad2(d.getMonth() + 1) + '-' + pad2(d.getDate())
      + ' ' + pad2(d.getHours()) + ':' + pad2(d.getMinutes()) + ':' + pad2(d.getSeconds());
  }

  function startOfDay(d) {
    return new Date(d.getFullYear(), d.getMonth(), d.getDate(), 0, 0, 0);
  }

  function endOfDay(d) {
    return new Date(d.getFullYear(), d.getMonth(), d.getDate(), 23, 59, 59);
  }

  function rangeForPreset(preset) {
    if (!preset || preset === 'all') {
      return [];
    }
    var now = new Date();
    var end = endOfDay(now);
    var start = startOfDay(now);
    if (preset === '3d') start = startOfDay(new Date(now.getFullYear(), now.getMonth(), now.getDate() - 2));
    else if (preset === '7d') start = startOfDay(new Date(now.getFullYear(), now.getMonth(), now.getDate() - 6));
    else if (preset === '15d') start = startOfDay(new Date(now.getFullYear(), now.getMonth(), now.getDate() - 14));
    return [formatDateTime(start), formatDateTime(end)];
  }

  function niceMax(n) {
    if (n <= 0) return 1;
    var exp = Math.pow(10, Math.floor(Math.log10(n)));
    var f = n / exp;
    var nf = f <= 1 ? 1 : (f <= 2 ? 2 : (f <= 5 ? 5 : 10));
    return nf * exp;
  }

  window.CronAdminExecutions = {
    template: '#tpl-executions',
    data: function () {
      return {
        items: [],
        total: 0,
        loading: false,
        chartLoading: false,
        dlg: false,
        execDetail: null,
        cancelling: false,
        timePreset: 'all',
        viewMode: 'list',
        trend: [],
        chartWidth: 900,
        hoverIndex: -1,
        chartSeries: CHART_SERIES,
        query: { taskId: '', taskName: '', page: 1, pageSize: 20, execBatchId: '', status: '', execType: '', triggerType: '', startTime: '', endTime: '', executionTimeRange: [] }
      };
    },
    computed: {
      chartTotals: function () {
        var totals = { success: 0, failed: 0, timeout: 0, cancelled: 0 };
        (this.trend || []).forEach(function (row) {
          totals.success += Number(row.success || 0);
          totals.failed += Number(row.failed || 0);
          totals.timeout += Number(row.timeout || 0);
          totals.cancelled += Number(row.cancelled || 0);
        });
        return totals;
      },
      chartModel: function () {
        var width = Math.max(480, this.chartWidth || 900);
        var height = 340;
        var pad = { l: 44, r: 18, t: 16, b: 40 };
        var buckets = this.trend || [];
        var innerW = width - pad.l - pad.r;
        var innerH = height - pad.t - pad.b;
        var max = 0;
        buckets.forEach(function (row) {
          CHART_SERIES.forEach(function (s) {
            max = Math.max(max, Number(row[s.key] || 0));
          });
        });
        max = niceMax(max);
        var n = buckets.length;
        var xs = buckets.map(function (row, i) {
          return pad.l + (n <= 1 ? innerW / 2 : (i / (n - 1)) * innerW);
        });
        var lines = CHART_SERIES.map(function (s) {
          var pts = buckets.map(function (row, i) {
            var y = pad.t + innerH - (Number(row[s.key] || 0) / max) * innerH;
            return xs[i] + ',' + y;
          });
          return { key: s.key, color: s.color, points: pts.join(' ') };
        });
        var ticks = 4;
        var gridY = [];
        var t;
        for (t = 0; t <= ticks; t++) {
          var val = Math.round((max * (ticks - t)) / ticks);
          gridY.push({
            y: pad.t + (innerH * t) / ticks,
            label: String(val)
          });
        }
        var labelEvery = n <= 8 ? 1 : Math.ceil(n / 8);
        var xLabels = [];
        buckets.forEach(function (row, i) {
          if (i % labelEvery !== 0 && i !== n - 1) return;
          xLabels.push({ i: i, x: xs[i], label: row.time || '' });
        });
        var dots = [];
        var hoverX = null;
        if (this.hoverIndex >= 0 && this.hoverIndex < n) {
          hoverX = xs[this.hoverIndex];
          CHART_SERIES.forEach(function (s) {
            var y = pad.t + innerH - (Number(buckets[this.hoverIndex][s.key] || 0) / max) * innerH;
            dots.push({ key: s.key, x: hoverX, y: y, color: s.color });
          }, this);
        }
        return {
          width: width,
          height: height,
          pad: pad,
          gridY: gridY,
          xLabels: xLabels,
          lines: lines,
          dots: dots,
          hoverX: hoverX,
          xs: xs
        };
      },
      hoverTip: function () {
        if (this.hoverIndex < 0 || !this.trend[this.hoverIndex]) return null;
        var row = this.trend[this.hoverIndex];
        var x = this.chartModel.hoverX || 0;
        return {
          time: row.time || '',
          values: {
            success: Number(row.success || 0),
            failed: Number(row.failed || 0),
            timeout: Number(row.timeout || 0),
            cancelled: Number(row.cancelled || 0)
          },
          left: Math.min(x + 12, (this.chartWidth || 900) - 160),
          top: 24
        };
      }
    },
    created: function () {
      if (this.$route.query.taskId) {
        this.query.taskId = String(this.$route.query.taskId);
      }
      this.syncExecutionTimeRange();
      this.refreshView();
    },
    mounted: function () {
      this.syncChartWidth();
      window.addEventListener('resize', this.syncChartWidth);
    },
    beforeDestroy: function () {
      window.removeEventListener('resize', this.syncChartWidth);
    },
    watch: {
      '$route.query.taskId': function (val) {
        this.query.taskId = val ? String(val) : '';
        this.query.page = 1;
        this.refreshView();
      }
    },
    methods: {
      syncChartWidth: function () {
        var box = this.$refs.execChartBox;
        if (box && box.clientWidth) {
          this.chartWidth = box.clientWidth;
        }
      },
      rangeForPreset: rangeForPreset,
      applyTimePreset: function (preset) {
        var range = rangeForPreset(preset);
        this.query.executionTimeRange = range.length === 2 ? range : [];
        this.syncStartEndFromRange();
      },
      matchTimePreset: function () {
        var start = String(this.query.startTime || '').trim();
        var end = String(this.query.endTime || '').trim();
        var keys = ['all', 'today', '3d', '7d', '15d'];
        var i;
        for (i = 0; i < keys.length; i++) {
          var range = rangeForPreset(keys[i]);
          if (!start && !end && keys[i] === 'all') return 'all';
          if (range.length === 2 && range[0] === start && range[1] === end) return keys[i];
        }
        return '';
      },
      onTimePresetChange: function () {
        this.applyTimePreset(this.timePreset);
        this.query.page = 1;
        this.refreshView();
      },
      onViewModeChange: function () {
        this.refreshView();
        var self = this;
        this.$nextTick(function () {
          self.syncChartWidth();
        });
      },
      refreshView: function () {
        this.syncStartEndFromRange();
        if (!this.validateTimeRange()) return;
        if (this.viewMode === 'chart') {
          this.loadTrend();
          return;
        }
        this.load();
      },
      syncExecutionTimeRange: function () {
        var startTime = String(this.query.startTime || '').trim();
        var endTime = String(this.query.endTime || '').trim();
        if (startTime && endTime) {
          this.query.executionTimeRange = [startTime, endTime];
          return;
        }
        this.query.executionTimeRange = [];
        this.query.startTime = '';
        this.query.endTime = '';
      },
      syncStartEndFromRange: function () {
        var range = this.query.executionTimeRange;
        if (Array.isArray(range) && range.length === 2) {
          this.query.startTime = String(range[0] || '').trim();
          this.query.endTime = String(range[1] || '').trim();
          return;
        }
        this.query.startTime = '';
        this.query.endTime = '';
      },
      normalizeStatus: normalizeExecStatus,
      statusClass: function (row) {
        return 'status-' + this.normalizeStatus(row);
      },
      statusText: execStatusText,
      formatDurationMs: function (ms) {
        return common.formatDurationMs(ms);
      },
      lastMessageLine: function (message) {
        var text = String(message || '').trim();
        if (!text) {
          return '-';
        }
        var lines = text.split(/\n/);
        return lines[lines.length - 1] || '-';
      },
      triggerTypeText: function (row) {
        var triggerType = Number(row && row.triggerType);
        if (triggerType === 1) return '定时';
        if (triggerType === 2) return '手动执行';
        return '未知';
      },
      execTypeText: function (row) {
        var execType = Number(row && row.execType);
        if (execType === 2) return 'HTTP';
        if (execType === 3) return 'Kubernetes';
        if (execType === 1) return 'GLUE模式';
        return '-';
      },
      validateTimeRange: function () {
        var range = this.query.executionTimeRange;
        if (!Array.isArray(range) || range.length === 0) {
          return true;
        }
        if (range.length !== 2) {
          this.$message.warning('执行时间筛选需同时选择开始执行时间和结束执行时间');
          return false;
        }
        return true;
      },
      buildLogsQuery: function (opts) {
        var includePage = !!(opts && opts.includePage);
        var includeStatus = !!(opts && opts.includeStatus);
        var qs = new URLSearchParams();
        if (includePage) {
          qs.set('page', String(this.query.page || 1));
          qs.set('pageSize', String(this.query.pageSize || 20));
        }
        var taskId = String(this.query.taskId || '').trim();
        if (taskId) qs.set('taskId', taskId);
        var taskName = String(this.query.taskName || '').trim();
        if (taskName) qs.set('taskName', taskName);
        if (this.query.execBatchId) qs.set('execBatchId', this.query.execBatchId);
        if (includeStatus && this.query.status) qs.set('status', this.query.status);
        if (this.query.execType !== '' && this.query.execType !== null && this.query.execType !== undefined) {
          qs.set('execType', String(this.query.execType));
        }
        if (this.query.triggerType !== '' && this.query.triggerType !== null && this.query.triggerType !== undefined) {
          qs.set('triggerType', String(this.query.triggerType));
        }
        var startTime = String(this.query.startTime || '').trim();
        if (startTime) qs.set('startTime', startTime);
        var endTime = String(this.query.endTime || '').trim();
        if (endTime) qs.set('endTime', endTime);
        return qs;
      },
      load: async function () {
        this.syncStartEndFromRange();
        if (!this.validateTimeRange()) return;
        this.loading = true;
        try {
          var d = await common.api('/tasks/logs?' + this.buildLogsQuery({ includePage: true, includeStatus: true }).toString());
          this.items = (d && d.list) || [];
          this.total = (d && d.total) || 0;
        } catch (e) {
          common.toastErr(this, e);
        } finally {
          this.loading = false;
        }
      },
      loadTrend: async function () {
        this.syncStartEndFromRange();
        if (!this.validateTimeRange()) return;
        this.chartLoading = true;
        this.hoverIndex = -1;
        try {
          this.trend = await common.api('/tasks/logs/trend?' + this.buildLogsQuery({ includePage: true, includeStatus: false }).toString()) || [];
          var self = this;
          this.$nextTick(function () {
            self.syncChartWidth();
          });
        } catch (e) {
          this.trend = [];
          common.toastErr(this, e);
        } finally {
          this.chartLoading = false;
        }
      },
      search: function () {
        this.syncStartEndFromRange();
        if (!this.validateTimeRange()) return;
        this.timePreset = this.matchTimePreset();
        this.query.page = 1;
        this.refreshView();
      },
      onChartMove: function (evt) {
        var box = this.$refs.execChartBox;
        var xs = this.chartModel.xs || [];
        if (!box || !xs.length) return;
        var rect = box.getBoundingClientRect();
        var x = evt.clientX - rect.left;
        var scale = (this.chartModel.width || rect.width) / rect.width;
        var svgX = x * scale;
        var nearest = 0;
        var best = Math.abs(xs[0] - svgX);
        var i;
        for (i = 1; i < xs.length; i++) {
          var dist = Math.abs(xs[i] - svgX);
          if (dist < best) {
            best = dist;
            nearest = i;
          }
        }
        this.hoverIndex = nearest;
      },
      onChartLeave: function () {
        this.hoverIndex = -1;
      },
      detail: async function (row) {
        try {
          var logId = row && row.id ? ('&logId=' + encodeURIComponent(String(row.id))) : '';
          this.execDetail = await common.api(
            '/tasks/execution?id=' + encodeURIComponent(row.cronId)
              + '&execBatchId=' + encodeURIComponent(row.execBatchId)
              + logId
          );
          this.dlg = true;
        } catch (e) {
          common.toastErr(this, e);
        }
      },
      viewLog: function (row) {
        this.$router.push({
          path: '/executions/log',
          query: { taskId: row.cronId, execBatchId: row.execBatchId, logId: row.id }
        });
      },
      canCancel: function (row) {
        var key = this.normalizeStatus(row);
        return key === 'running' || key === 'cancel_requested';
      },
      cancelExec: async function (row) {
        var id = row && row.id;
        if (!id) {
          this.$message.warning('缺少执行记录 ID，无法取消');
          return;
        }
        try {
          await this.$confirm('确认取消这次执行？将发送 SIGTERM。', '取消执行', { type: 'warning' });
        } catch (e) {
          return;
        }
        this.cancelling = true;
        try {
          var result = await common.api('/executions/cancel', { method: 'POST', body: { id: Number(id) } });
          if (result && result.alreadyFinished) {
            this.$message.info('执行已结束：' + (result.status || ''));
          } else {
            this.$message.success('已请求取消');
          }
          this.dlg = false;
          this.refreshView();
        } catch (e) {
          common.toastErr(this, e);
        } finally {
          this.cancelling = false;
        }
      }
    }
  };

  window.CronAdminExecutionLog = {
    template: '#tpl-execution-log',
    data: function () {
      return { detail: null, loading: false };
    },
    computed: {
      taskId: function () {
        return this.$route.query.taskId || '';
      },
      execBatchId: function () {
        return this.$route.query.execBatchId || '';
      },
      logId: function () {
        return this.$route.query.logId || '';
      },
      executionTaskName: function () {
        if (this.detail && this.detail.taskName) {
          return String(this.detail.taskName).trim() || '-';
        }
        var item = (this.detail && this.detail.taskItem) || {};
        var name = item.cron_name || item.name || item.task_name || '';
        return String(name || '').trim() || '-';
      }
    },
    created: function () {
      this.load();
    },
    methods: {
      statusText: execStatusText,
      formatDurationMs: function (ms) {
        return common.formatDurationMs(ms);
      },
      load: async function () {
        if (!this.logId && (!this.taskId || !this.execBatchId)) {
          this.$message.warning('缺少 logId，或缺少 taskId 与 execBatchId');
          return;
        }
        this.loading = true;
        try {
          var logId = String(this.logId || '').trim();
          var logIdQuery = logId ? ('&logId=' + encodeURIComponent(logId)) : '';
          this.detail = await common.api(
            '/tasks/execution?id=' + encodeURIComponent(this.taskId)
              + '&execBatchId=' + encodeURIComponent(this.execBatchId)
              + logIdQuery
          );
        } catch (e) {
          common.toastErr(this, e);
        } finally {
          this.loading = false;
        }
      },
      refresh: function () {
        this.load();
      },
      back: function () {
        var q = this.taskId ? { taskId: this.taskId } : {};
        this.$router.push({ path: '/executions', query: q });
      },
      download: function () {
        if (!this.detail) return;
        var content = [
          '[任务名称]', this.executionTaskName || '', '',
          '[taskId]', String(this.taskId), '',
          '[execBatchId]', String(this.execBatchId), '',
          '[logId]', String(this.logId || ''), '',
          '[执行流水]', this.detail.message || '', '',
          '[stdout]', this.detail.stdout || '', '',
          '[stderr]', this.detail.stderr || '', '',
          '[taskItem]', JSON.stringify(this.detail.taskItem || {}, null, 2)
        ].join('\n');
        var blob = new Blob([content], { type: 'text/plain;charset=utf-8' });
        var link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'cron-log-' + this.taskId + '-' + this.execBatchId + '.txt';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(link.href);
      }
    }
  };
})(window);
