(function (window) {
  'use strict';

  var common = window.CronAdminCommon;

  var CHART_SERIES = [
    { key: 'success', label: '成功', color: '#16a34a' },
    { key: 'failed', label: '失败', color: '#dc2626' },
    { key: 'timeout', label: '超时', color: '#d97706' },
    { key: 'cancelled', label: '取消', color: '#4b5563' }
  ];

  function niceMax(n) {
    if (n <= 0) return 1;
    var exp = Math.pow(10, Math.floor(Math.log10(n)));
    var f = n / exp;
    var nf = f <= 1 ? 1 : (f <= 2 ? 2 : (f <= 5 ? 5 : 10));
    return nf * exp;
  }

  window.CronAdminDashboard = {
    template: '#tpl-dashboard',
    data: function () {
      return {
        overview: {
          tasks: { total: 0, enabled: 0, disabled: 0 },
          executions: { today: 0, success: 0, failed: 0, skipped: 0, timeout: 0, cancelled: 0 },
          nodes: { total: 0, online: 0, offline: 0 }
        },
        trend: [],
        range: '24h',
        viewMode: 'list',
        trendLoading: false,
        chartWidth: 900,
        hoverIndex: -1,
        chartSeries: CHART_SERIES,
        scopeGroups: [],
        scopeLoading: false
      };
    },
    computed: {
      showNoGroupAssigned: function () {
        return !this.scopeLoading && !common.isViewerSuper() && !this.scopeGroups.length;
      },
      todayDate: function () {
        var d = new Date();
        var m = String(d.getMonth() + 1);
        var day = String(d.getDate());
        if (m.length < 2) m = '0' + m;
        if (day.length < 2) day = '0' + day;
        return d.getFullYear() + '-' + m + '-' + day;
      },
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
      this.loadScopeGroups();
      this.load();
      this.loadTrend();
    },
    mounted: function () {
      this.syncChartWidth();
      window.addEventListener('resize', this.syncChartWidth);
    },
    beforeDestroy: function () {
      window.removeEventListener('resize', this.syncChartWidth);
    },
    methods: {
      syncChartWidth: function () {
        var box = this.$refs.dashChartBox;
        if (box && box.clientWidth) {
          this.chartWidth = box.clientWidth;
        }
      },
      onViewModeChange: function () {
        var self = this;
        this.$nextTick(function () {
          self.syncChartWidth();
        });
      },
      loadScopeGroups: async function () {
        this.scopeLoading = true;
        try {
          if (common.isViewerSuper()) {
            var all = await common.api('/node-groups');
            this.scopeGroups = (all && all.list) || [];
            return;
          }
          if (!common.viewerNodeGroupIds().length) {
            this.scopeGroups = [];
            return;
          }
          var groups = await common.api('/node-groups');
          this.scopeGroups = common.filterGroupsForViewer((groups && groups.list) || []);
        } catch (e) {
          this.scopeGroups = [];
          common.toastErr(this, e);
        } finally {
          this.scopeLoading = false;
        }
      },
      load: async function () {
        try {
          this.overview = await common.api('/dashboard/overview');
        } catch (e) {
          common.toastErr(this, e);
        }
      },
      loadTrend: async function () {
        this.trendLoading = true;
        this.hoverIndex = -1;
        try {
          var trendData = await common.api('/dashboard/execution-trend?range=' + this.range);
          this.trend = common.extractListRows(trendData);
          var self = this;
          this.$nextTick(function () {
            self.syncChartWidth();
          });
        } catch (e) {
          this.trend = [];
          common.toastErr(this, e);
        } finally {
          this.trendLoading = false;
        }
      },
      onChartMove: function (evt) {
        var box = this.$refs.dashChartBox;
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
      }
    }
  };
})(window);
