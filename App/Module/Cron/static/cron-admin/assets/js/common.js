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

  function applySessionToRoot(vm, data) {
    saveSession(data);
    var root = vm && vm.$root;
    if (root) {
      root.currentUser = getUser();
    }
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

  function confirmUserDisable(vm, name) {
    var label = '「' + String(name == null ? '' : name) + '」';
    return confirmDialog(
      vm,
      '确认禁用用户' + label + '？禁用后该账号将无法登录。',
      'warning',
      '禁用用户'
    ).then(function () {
      return confirmDialog(
        vm,
        '再次确认要禁用用户' + label + '？',
        'danger',
        '再次确认'
      );
    });
  }

  function confirmUserDelete(vm, name) {
    var label = '「' + String(name == null ? '' : name) + '」';
    return confirmDialog(
      vm,
      '确认删除用户' + label + '？删除后不可恢复。',
      'warning',
      '删除用户'
    ).then(function () {
      return confirmDialog(
        vm,
        '再次确认要删除用户' + label + '？',
        'danger',
        '再次确认'
      );
    });
  }

  function confirmRoleDisable(vm, name) {
    var label = '「' + String(name == null ? '' : name) + '」';
    return confirmDialog(
      vm,
      '确认禁用角色' + label + '？禁用后该角色权限将不再生效。',
      'warning',
      '禁用角色'
    ).then(function () {
      return confirmDialog(
        vm,
        '再次确认要禁用角色' + label + '？',
        'danger',
        '再次确认'
      );
    });
  }

  function confirmRoleDelete(vm, name) {
    var label = '「' + String(name == null ? '' : name) + '」';
    return confirmDialog(
      vm,
      '确认删除角色' + label + '？删除后不可恢复。',
      'warning',
      '删除角色'
    ).then(function () {
      return confirmDialog(
        vm,
        '再次确认要删除角色' + label + '？',
        'danger',
        '再次确认'
      );
    });
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

  function isViewerSuper() {
    var user = getUser() || {};
    return !!user.isSuper;
  }

  function isViewerEditorTaskGroup() {
    var user = getUser() || {};
    if (user.isEditorTaskGroup) return true;
    return (user.roles || []).some(function (r) {
      return r && r.code === 'editer_task_group';
    });
  }

  function viewerUserId() {
    return Number((getUser() || {}).id || 0);
  }

  function canManageTask(row) {
    if (isViewerSuper()) return true;
    if (isViewerEditorTaskGroup()) return true;
    var userId = viewerUserId();
    var createdBy = Number((row && (row.createdBy || row.created_by)) || 0);
    return userId > 0 && createdBy > 0 && userId === createdBy;
  }

  function toastNoTaskPermission(vm) {
    vm.$message.warning('无权限操作');
  }

  function viewerNodeGroupIds() {
    var user = getUser() || {};
    return Array.isArray(user.nodeGroupIds) ? user.nodeGroupIds.map(Number) : [];
  }

  function filterGroupsForViewer(groups) {
    if (isViewerSuper()) return groups || [];
    var allowed = viewerNodeGroupIds();
    return (groups || []).filter(function (g) {
      return allowed.indexOf(Number(g.id)) >= 0;
    });
  }

  function buildGroupOptions(groups, nodes) {
    var list = filterGroupsForViewer(groups);
    var hasUngrouped = (nodes || []).some(function (n) { return !n.groupId; });
    if (hasUngrouped && isViewerSuper()) {
      list = [{ id: -1, groupName: '未分组' }].concat(list);
    }
    return list;
  }

  function normalizeMenuPath(uri) {
    var path = String(uri || '').split('?')[0].split('#')[0];
    if (!path) return '/';
    if (path.charAt(0) !== '/') path = '/' + path;
    return path.replace(/\/+$/, '') || '/';
  }

  function collectMenuPaths(menus) {
    var paths = [];
    (menus || []).forEach(function (group) {
      (group.children || []).forEach(function (item) {
        if (item && item.uri) paths.push(normalizeMenuPath(item.uri));
      });
    });
    return paths;
  }

  function canAccessRoute(path, user) {
    if (!user) return false;
    if (user.isSuper) return true;
    var target = normalizeMenuPath(path);
    var allowed = collectMenuPaths(user.menus || []);
    return allowed.some(function (uri) {
      return target === uri || target.indexOf(uri + '/') === 0;
    });
  }

  function firstAllowedRoute(user) {
    var menus = (user && user.menus) || [];
    for (var i = 0; i < menus.length; i++) {
      var children = menus[i].children || [];
      for (var j = 0; j < children.length; j++) {
        if (children[j] && children[j].uri) {
          return normalizeMenuPath(children[j].uri);
        }
      }
    }
    return '/dashboard';
  }

  function sidebarMenus(user) {
    var menus = (user && user.menus) || [];
    return menus.filter(function (group) {
      return (group.children || []).length > 0;
    });
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
    applySessionToRoot: applySessionToRoot,
    isAuthPublicPath: isAuthPublicPath,
    isViewerSuper: isViewerSuper,
    isViewerEditorTaskGroup: isViewerEditorTaskGroup,
    viewerUserId: viewerUserId,
    canManageTask: canManageTask,
    toastNoTaskPermission: toastNoTaskPermission,
    viewerNodeGroupIds: viewerNodeGroupIds,
    filterGroupsForViewer: filterGroupsForViewer,
    toastErr: toastErr,
    confirmDialog: confirmDialog,
    confirmTaskStatusChange: confirmTaskStatusChange,
    confirmDelete: confirmDelete,
    confirmUserDisable: confirmUserDisable,
    confirmUserDelete: confirmUserDelete,
    confirmRoleDisable: confirmRoleDisable,
    confirmRoleDelete: confirmRoleDelete,
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
    buildGroupOptions: buildGroupOptions,
    normalizeMenuPath: normalizeMenuPath,
    collectMenuPaths: collectMenuPaths,
    canAccessRoute: canAccessRoute,
    firstAllowedRoute: firstAllowedRoute,
    sidebarMenus: sidebarMenus
  };
})(window);
