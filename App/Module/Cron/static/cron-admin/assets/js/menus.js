(function (window) {
  'use strict';

  var common = window.CronAdminCommon;

  function emptyForm() {
    return { name: '', code: '', uri: '', icon: '', parentId: 0, sort: 0 };
  }

  window.CronAdminMenus = {
    template: '#tpl-menus',
    data: function () {
      return {
        items: [],
        loading: false,
        dialogVisible: false,
        dialogTitle: '新增菜单',
        menuForm: emptyForm(),
        parentOptions: [],
        sortSaving: false
      };
    },
    computed: {
      groupOptions: function () {
        return this.items || [];
      },
      isGroupForm: function () {
        return !this.menuForm.parentId;
      }
    },
    created: function () {
      this.load();
    },
    beforeDestroy: function () {
      this.destroySortable();
    },
    methods: {
      isMenuDisabled: function (row) {
        return row && Number(row.status) === 0;
      },
      destroySortable: function () {
        (this._sortables || []).forEach(function (instance) {
          instance.destroy();
        });
        this._sortables = [];
      },
      initSortable: function () {
        var Sortable = window.Sortable;
        if (!Sortable || this.loading) return;

        this.destroySortable();
        this._sortables = [];

        var groupList = this.$refs.groupList;
        if (groupList) {
          this._sortables.push(Sortable.create(groupList, {
            animation: 150,
            handle: '.menu-drag-handle--group',
            draggable: '.menu-nav-group',
            ghostClass: 'menu-sort-ghost',
            onEnd: this.onGroupSortEnd.bind(this)
          }));
        }

        var itemLists = this.$refs.itemLists || [];
        if (!Array.isArray(itemLists)) itemLists = [itemLists];
        itemLists.forEach(function (el) {
          if (!el) return;
          var groupId = Number(el.getAttribute('data-group-id') || 0);
          this._sortables.push(Sortable.create(el, {
            animation: 150,
            handle: '.menu-drag-handle--item',
            draggable: '.menu-nav-item-row',
            ghostClass: 'menu-sort-ghost',
            onEnd: function (evt) {
              this.onItemSortEnd(groupId, evt);
            }.bind(this)
          }));
        }, this);
      },
      syncRootMenus: function () {
        var root = this.$root;
        if (root && typeof root.refreshUser === 'function') {
          root.refreshUser();
        }
      },
      persistSort: async function (parentId, ids) {
        if (this.sortSaving) return;
        this.sortSaving = true;
        try {
          await common.api('/menus/sort', {
            method: 'PUT',
            body: { parentId: Number(parentId || 0), ids: ids }
          });
          this.$message.success('排序已保存');
          this.syncRootMenus();
        } catch (e) {
          common.toastErr(this, e);
          await this.load();
        } finally {
          this.sortSaving = false;
        }
      },
      onGroupSortEnd: async function (evt) {
        if (evt.oldIndex === evt.newIndex) return;
        var moved = this.items.splice(evt.oldIndex, 1)[0];
        this.items.splice(evt.newIndex, 0, moved);
        var ids = this.items.map(function (group) {
          return Number(group.id);
        });
        await this.persistSort(0, ids);
      },
      onItemSortEnd: async function (groupId, evt) {
        if (evt.oldIndex === evt.newIndex) return;
        var group = (this.items || []).find(function (row) {
          return Number(row.id) === Number(groupId);
        });
        if (!group) {
          await this.load();
          return;
        }
        var children = (group.children || []).slice();
        var moved = children.splice(evt.oldIndex, 1)[0];
        children.splice(evt.newIndex, 0, moved);
        this.$set(group, 'children', children);
        var ids = children.map(function (item) {
          return Number(item.id);
        });
        await this.persistSort(groupId, ids);
      },
      load: async function () {
        this.loading = true;
        try {
          var d = await common.api('/menus');
          this.items = (d && d.list) || [];
          this.parentOptions = JSON.parse(JSON.stringify(this.items));
        } catch (e) {
          common.toastErr(this, e);
        } finally {
          this.loading = false;
          var self = this;
          this.$nextTick(function () {
            self.initSortable();
          });
        }
      },
      openDialog: function (row, isChild) {
        this.dialogTitle = isChild ? '新增菜单' : (row ? (row.parentId ? '编辑菜单' : '编辑分组') : '新增分组');
        if (row && !isChild) {
          this.menuForm = {
            id: row.id,
            name: row.name,
            code: row.code,
            uri: row.uri,
            icon: row.icon,
            parentId: row.parentId || 0,
            sort: row.sort || 0
          };
        } else if (row && isChild) {
          this.menuForm = Object.assign(emptyForm(), { parentId: row.id });
        } else {
          this.menuForm = emptyForm();
        }
        this.dialogVisible = true;
      },
      onStatusChange: async function (row, next) {
        var prev = next === 1 ? 0 : 1;
        try {
          await common.api('/menus/status', {
            method: 'PUT',
            body: { id: Number(row.id), status: Number(next) }
          });
          row.status = next;
          this.$message.success(next === 1 ? '已启用' : '已禁用');
        } catch (e) {
          row.status = prev;
          this.$forceUpdate();
          common.toastErr(this, e);
        }
      },
      save: async function () {
        if (!this.menuForm.name || !this.menuForm.code) {
          this.$message.warning('请填写名称和唯一标识');
          return;
        }
        if (this.menuForm.parentId && !this.menuForm.uri) {
          this.$message.warning('请填写菜单 URI');
          return;
        }
        try {
          var body = {
            name: this.menuForm.name,
            code: this.menuForm.code,
            uri: this.menuForm.uri,
            icon: this.menuForm.icon,
            parentId: Number(this.menuForm.parentId || 0),
            sort: Number(this.menuForm.sort || 0)
          };
          if (this.menuForm.id) {
            body.id = Number(this.menuForm.id);
            await common.api('/menus', { method: 'PUT', body: body });
          } else {
            await common.api('/menus', { method: 'POST', body: body });
          }
          this.$message.success('已保存');
          this.dialogVisible = false;
          this.load();
        } catch (e) {
          common.toastErr(this, e);
        }
      },
      remove: async function (row) {
        try {
          await common.confirmDelete(this, '确认删除菜单「' + row.name + '」？');
          await common.api('/menus?id=' + encodeURIComponent(row.id), {
            method: 'DELETE',
            body: { id: Number(row.id) }
          });
          this.$message.success('已删除');
          this.load();
        } catch (e) {
          if (e !== 'cancel') common.toastErr(this, e);
        }
      }
    }
  };
})(window);
