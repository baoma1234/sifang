define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'pay/qrcode/index',
                    add_url: 'pay/qrcode/add',
                    edit_url: 'pay/qrcode/edit',
                    del_url: 'pay/qrcode/del',
                    multi_url: 'pay/qrcode/multi',
                    table: 'pay_qrcode',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {
                            checkbox: true, formatter: function (value, row, index) {
                                this.checkbox = !(row.realprice == 0);
                            }
                        },
                        {field: 'id', title: __('ID'), sortable: true, operate: false},
                        {
                            field: 'device_id', title: __('Device'),
                            formatter: Table.api.formatter.normal,
                            searchList: Config.deviceList
                        },
                        {
                            field: 'type',
                            title: __('Type'),
                            formatter: function (value, row, index) {
                                return '<a href="javascript:;" class="searchit label label-' + (value == 'wechat' ? 'success' : 'info') + '" data-toggle="tooltip" title="' + __('Click to search %s', __(value)) + '" data-field="' + this.field + '" data-value="' + value + '">' + __(value) + '</a>';
                            },
                            searchList: {"alipay": __('Alipay'), "wechat": __('Wechat')}
                        },
                        {
                            field: 'image', title: __('Image'), formatter: function (value, row, index) {
                                var image = Fast.api.cdnurl(value);
                                return '<a href="' + image + '" title="" onclick="return false;" class="view-payimg" data-img="' + image + '"><img src="' + image + '" class="img-sm img-center"/></a>';
                            }
                        },
                        {field: 'realprice', sortable: true, title: __('Realprice'), operate: 'BETWEEN', formatter: Table.api.formatter.search},
                        {field: 'url', title: __('Url'), formatter: Table.api.formatter.url},
                        {
                            field: 'createtime',
                            title: __('Createtime'),
                            operate: 'RANGE', sortable: true,
                            addclass: 'datetimerange',
                            formatter: Table.api.formatter.datetime
                        },
                        {
                            field: 'updatetime',
                            title: __('Updatetime'),
                            operate: 'RANGE', sortable: true,
                            addclass: 'datetimerange',
                            formatter: Table.api.formatter.datetime,
                            visible: false
                        },
                        {
                            field: 'status',
                            title: __('Status'),
                            formatter: Table.api.formatter.status,
                            searchList: {"hidden": __('hidden'), "normal": __('normal')}
                        },
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            formatter: function (value, row, index) {
                                var that = this;
                                if (row.realprice == 0) {
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

            // 查看二维码
            $('body').popover({
                selector: 'a.view-payimg',
                html: true,
                trigger: 'hover',
                placement: 'left',
                content: function () {
                    return '<img src="' + $(this).data('img') + '" width="320" />';
                }
            });
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
            $("#plupload-image").data("upload-success", function (data) {
                Fast.api.ajax({
                    url: "pay/qrcode/add",
                    data: {image: data.url}
                }, function (data) {
                    if ($(".alert-uploadtips").size() > 0 && data.realprice > 0) {
                        Toastr.error("当前记录只允许上传无固定金额收款二维码图片");
                        $("#c-image").val('').trigger('change');
                        $("#c-url").val('');
                        return false;
                    } else {
                        $("#c-url").val(data.url);
                        $("#c-realprice").val(data.realprice);
                    }
                });
            });
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"), function () {

                }, function () {
                }, function () {
                    if ($("#plupload-qrcode").size() > 0 && $("input[name^='qrcode']").size() == 0) {
                        Toastr.error("请至少上传一张价格二维码");
                        return false;
                    }
                });
                var index = 0;
                $("#plupload-qrcode").data("upload-success", function (data) {
                    Fast.api.ajax({
                        url: "pay/qrcode/add",
                        data: {image: data.url}
                    }, function (data) {
                        data.index = index;
                        data.fullimage = Fast.api.cdnurl(data.image);
                        var html = Template("qrcodetpl", data);
                        $("#p-qrcode").append(html);
                        index++;
                        if (data.url == "") {
                            Toastr.error("你上传了一张未能正确识别二维码的图片，请手动补全相关信息");
                            return false;
                        }
                    });
                });
                $(document).on('click', '.btn-trash', function () {
                    $(this).closest("li").remove();
                });
            }
        }
    };
    return Controller;
});