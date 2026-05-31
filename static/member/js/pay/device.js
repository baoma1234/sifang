define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'pay/device/index',
                    add_url: 'pay/device/add',
                    edit_url: 'pay/device/edit',
                    del_url: 'pay/device/del',
                    multi_url: 'pay/device/multi',
                    table: 'pay_device',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'weigh',
                pagination: false,
                columns: [
                    [
                        {
                            checkbox: true, formatter: function (value, row, index) {
                                this.checkbox = !(row.id <= 1);
                            }
                        },
                        {field: 'id', title: __('Id')},
                        {field: 'name', title: __('Name')},
                        {field: 'ratio', title: __('Ratio'), operate: 'BETWEEN'},
                        {
                            field: 'ratiopercent', title: __('Ratio text'), operate: false, formatter: function (value, row, index) {
                                return value + "%";
                            }
                        },
                        {field: 'wechat', title: __('Wechat'), formatter: Controller.api.formatter.checkupload},
                        {field: 'alipay', title: __('Alipay'), formatter: Controller.api.formatter.checkupload},
                        {field: 'createtime', title: __('Createtime'), operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime, visible: false},
                        {field: 'weigh', title: __('Weigh')},
                        {field: 'status', title: __('Status'), searchList: {"hidden": __('Hidden'), "normal": __('Normal')}, formatter: Table.api.formatter.status},
                        {
                            field: 'operate', title: __('Operate'), width: '270', align: 'left', table: table, events: Table.api.events.operate, buttons: [
                                {
                                    name: 'addqrcode',
                                    text: '上传二维码',
                                    title: '只允许上传固定金额收款二维码',
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-upload',
                                    url: 'pay/qrcode/add?device_id={ids}',
                                    extend: 'data-toggle="tooltip"',
                                },
                                {
                                    name: 'viewqrcode',
                                    text: '查看二维码',
                                    title: '查看设备所有的二维码',
                                    classname: 'btn btn-xs btn-warning btn-dialog',
                                    icon: 'fa fa-qrcode',
                                    url: 'pay/qrcode/index?device_id={ids}',
                                    extend: 'data-toggle="tooltip"',
                                }
                            ],
                            formatter: function (value, row, index) {
                                var that = this;
                                if (row.id <= 1) {
                                    that = $.extend({}, this);
                                    that.table.data("operate-del", null);
                                } else {
                                    that.table.data("operate-del", true);
                                }
                                return Table.api.formatter.operate.apply(that, [value, row, index]);
                            }
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            formatter: {
                checkupload: function (value, row) {
                    return value > 0
                        ? '<a href="pay/qrcode/edit/ids/' + value + '?device_id=' + row.id + '&type=' + this.field + '" class="btn btn-xs btn-dialog" title="点击修改二维码" data-toggle="tooltip"><span class="label label-success">' + __('Uploaded') + '</span></a>'
                        : '<a href="pay/qrcode/add?device_id=' + row.id + '&type=' + this.field + '" class="btn btn-xs btn-dialog" title="补全二维码" data-toggle="tooltip"><span class="label label-danger">' + __('Missing') + '</span></a>';
                }
            }
        }
    };
    return Controller;
});