(function (window) {
  'use strict';

  var common = window.CronAdminCommon;

  function emptyForm() {
    return { name: '', code: '', uri: '', icon: '', parentId: 0, sort: 0, status: 1 };
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
        parentOptions: []
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
    methods: {
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
            sort: row.sort || 0,
            status: row.status === 0 ? 0 : 1
          };
        } else if (row && isChild) {
          this.menuForm = Object.assign(emptyForm(), { parentId: row.id });
        } else {
          this.menuForm = emptyForm();
        }
        this.dialogVisible = true;
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
            sort: Number(this.menuForm.sort || 0),
            status: this.menuForm.status
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
