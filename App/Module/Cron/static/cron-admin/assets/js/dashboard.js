(function (window) {
  'use strict';

  var common = window.CronAdminCommon;

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
        scopeGroups: [],
        scopeLoading: false
      };
    },
    computed: {
      showNoGroupAssigned: function () {
        return !this.scopeLoading && !common.isViewerSuper() && !this.scopeGroups.length;
      }
    },
    created: function () {
      this.loadScopeGroups();
      this.load();
      this.loadTrend();
    },
    methods: {
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
        try {
          this.trend = await common.api('/dashboard/execution-trend?range=' + this.range) || [];
        } catch (e) {
          common.toastErr(this, e);
        }
      }
    }
  };
})(window);
