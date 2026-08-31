(function (window) {
  'use strict';

  var API = '/api/v1';
  var TOKEN_KEY = 'schedule_job_token';
  var USER_KEY = 'schedule_job_user';
  var AUTH_PUBLIC_PATHS = ['/login', '/register'];

  function getToken() {
    return localStorage.getItem(TOKEN_KEY) || '';
  }

  function setToken(token) {
    if (token) localStorage.setItem(TOKEN_KEY, token);
    else localStorage.removeItem(TOKEN_KEY);
  }

  function getUser() {
    try {
      return JSON.parse(localStorage.getItem(USER_KEY) || 'null');
    } catch (e) {
      return null;
    }
  }

  function setUser(user) {
    if (user) localStorage.setItem(USER_KEY, JSON.stringify(user));
    else localStorage.removeItem(USER_KEY);
  }

  function clearAuth() {
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem(USER_KEY);
  }

  function isAuthPublicPath(path) {
    return AUTH_PUBLIC_PATHS.indexOf(path) !== -1;
  }

  function saveSession(data) {
    if (!data) return;
    if (data.token) setToken(data.token);
    if (data.user) setUser(data.user);
  }

  async function api(path, options) {
    options = options || {};
    var headers = Object.assign({ Accept: 'application/json' }, options.headers || {});
    var token = getToken();
    if (token) headers.Authorization = 'Bearer ' + token;
    if (options.body && typeof options.body !== 'string') {
      headers['Content-Type'] = 'application/json';
      options.body = JSON.stringify(options.body);
    }
    var res = await fetch(API + path, Object.assign({}, options, { headers: headers }));
    var json = {};
    try {
      json = await res.json();
    } catch (e) {
      json = {};
    }
    if (res.status === 401 && path.indexOf('/auth/login') === -1 && path.indexOf('/auth/register') === -1) {
      clearAuth();
      if (window.location.hash.indexOf('#/login') === -1) {
        window.location.hash = '#/login';
      }
    }
    if (!res.ok || (json.code !== undefined && json.code !== 0)) {
      throw new Error(json.msg || json.message || ('HTTP ' + res.status));
    }
    return json.data;
  }

  function toastErr(vm, err) {
    vm.$message.error((err && err.message) || String(err));
  }

  function formatTaskNames(names) {
    var list = Array.isArray(names) ? names : [names];
    return list.map(function (name) {
      return '「' + String(name == null ? '' : name) + '」';
    }).join('、');
  }

  var CONFIRM_TONES = {
    success: { type: 'success', cls: 'cron-confirm-success' },
    warning: { type: 'warning', cls: 'cron-confirm-warning' },
    danger: { type: 'error', cls: 'cron-confirm-danger' },
    info: { type: 'info', cls: 'cron-confirm-info' }
  };

  function confirmDialog(vm, message, tone, title) {
    var preset = CONFIRM_TONES[tone] || CONFIRM_TONES.info;
    var options = {
      type: preset.type,
      customClass: 'cron-confirm-dialog ' + preset.cls,
      confirmButtonClass: 'cron-confirm-ok'
    };
    if (title) {
      return vm.$confirm(message, title, options);
    }
    return vm.$confirm(message, options);
  }

  function confirmTaskStatusChange(vm, status, names) {
    var enable = Number(status) === 1;
    var action = enable ? '启用' : '禁用';
    return confirmDialog(vm, '确认' + action + '任务' + formatTaskNames(names) + '？', enable ? 'success' : 'warning');
  }

  function confirmDelete(vm, message) {
    return confirmDialog(vm, message, 'danger');
  }

  function confirmRunOnce(vm, names) {
    var label = formatTaskNames(names);
    return confirmDialog(
      vm,
      '确认手动执行任务' + label + '？任务将立即入队执行。',
      'info',
      '手动执行'
    ).then(function () {
      return confirmDialog(
        vm,
        '再次确认要立即执行' + label + '？',
        'warning',
        '再次确认'
      );
    });
  }

  function toastTaskStatus(vm, status, names) {
    var label = Array.isArray(names) ? names.join('、') : String(names || '');
    var msg = Number(status) === 1 ? '已启用[' + label + ']' : '已禁用[' + label + ']';
    if (Number(status) === 1) {
      vm.$message.success(msg);
    } else {
      vm.$message.warning(msg);
    }
  }

  function formatDurationMs(ms) {
    if (ms === null || ms === undefined || ms === '') return '-';
    var n = Number(ms);
    if (!isFinite(n)) return '-';
    if (n === 0) return '0';
    if (n > 100) return n + 'ms(约' + (n / 1000).toFixed(1) + 's)';
    return n + 'ms';
  }

  function formatNextRunAt(row) {
    if (!row) return '-';
    var ts = row.nextRunAt;
    if (ts !== null && ts !== undefined && ts !== '' && Number(ts) > 0) {
      var d = new Date(Number(ts) * 1000);
      if (!isNaN(d.getTime())) {
        function p(n) { return String(n).padStart(2, '0'); }
        return d.getFullYear() + '-' + p(d.getMonth() + 1) + '-' + p(d.getDate())
          + ' ' + p(d.getHours()) + ':' + p(d.getMinutes()) + ':' + p(d.getSeconds());
      }
    }
    return row.nextRunAtAt || '-';
  }

  function parseJsonOrNull(text, label) {
    if (!text || !String(text).trim()) return null;
    try {
      return JSON.parse(text);
    } catch (e) {
      throw new Error(label + ' JSON 无效');
    }
  }

  function detectExprType(expression) {
    var expr = String(expression || '').trim();
    if (/^\d+$/.test(expr)) return 'interval';
    return 'cron';
  }

  function asArray(value) {
    if (Array.isArray(value)) return value;
    if (!value || typeof value !== 'object') return null;
    var keys = Object.keys(value);
    if (!keys.length) return null;
    var sequential = keys.every(function (k, i) { return String(i) === k; });
    if (!sequential) return null;
    return keys.map(function (k) { return value[k]; });
  }

  function extractListRows(payload) {
    if (!payload) return [];
    var direct = asArray(payload);
    if (direct) return direct;
    var rows = asArray(payload.list) || asArray(payload.items);
    if (rows) return rows;
    var nested = payload.data;
    if (nested && typeof nested === 'object' && !Array.isArray(nested)) {
      rows = asArray(nested.list) || asArray(nested.items) || asArray(nested);
      if (rows) return rows;
    } else {
      rows = asArray(nested);
      if (rows) return rows;
    }
    return [];
  }

  function extractListTotal(payload, rows) {
    if (payload && payload.total != null && payload.total !== '') return Number(payload.total) || 0;
    if (payload && payload.data && payload.data.total != null && payload.data.total !== '') {
      return Number(payload.data.total) || 0;
    }
    return Array.isArray(rows) ? rows.length : 0;
  }

  function isGroupIdSelected(groupId) {
    return groupId !== '' && groupId !== null && groupId !== undefined;
  }

  function filterNodesByGroupId(nodes, groupId) {
    if (!isGroupIdSelected(groupId)) return [];
    var gid = Number(groupId);
    if (Number.isNaN(gid)) return [];
    return (nodes || []).filter(function (n) {
      var nid = n.groupId || 0;
      return gid === -1 ? nid === 0 : nid === gid;
    });
  }

  function buildGroupOptions(groups, nodes) {
    var list = (groups || []).slice();
    var hasUngrouped = (nodes || []).some(function (n) { return !n.groupId; });
    if (hasUngrouped) {
      list = [{ id: -1, groupName: '未分组' }].concat(list);
    }
    return list;
  }

  window.CronAdminCommon = {
    API: API,
    api: api,
    getToken: getToken,
    setToken: setToken,
    getUser: getUser,
    setUser: setUser,
    clearAuth: clearAuth,
    saveSession: saveSession,
    isAuthPublicPath: isAuthPublicPath,
    toastErr: toastErr,
    confirmDialog: confirmDialog,
    confirmTaskStatusChange: confirmTaskStatusChange,
    confirmDelete: confirmDelete,
    confirmRunOnce: confirmRunOnce,
    toastTaskStatus: toastTaskStatus,
    formatDurationMs: formatDurationMs,
    formatNextRunAt: formatNextRunAt,
    parseJsonOrNull: parseJsonOrNull,
    detectExprType: detectExprType,
    isGroupIdSelected: isGroupIdSelected,
    extractListRows: extractListRows,
    extractListTotal: extractListTotal,
    filterNodesByGroupId: filterNodesByGroupId,
    buildGroupOptions: buildGroupOptions
  };
})(window);
