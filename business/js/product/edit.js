/**
 * Created by root on 15-8-27.
 */
var attr_list;
var index = 0;
var editing = 0;
var current_type = 0;

$(function() {

    init();

    $('#add-attr').click(function(e) {
        e.preventDefault();
        do_add_attr();
    });

    $('#confirm').live('click', function() {
        do_confirm();
    });


});
//初始化
function init() {
    var temp = '<label class="l-title">产品属性：</label>';
    temp += '<input style="cursor: pointer;" class="text-input" type="button" id="add-attr" value="增加属性"/>';
    temp += '<p class="clear" id="show-attr"></p>';
    $('#product_attr').append(temp);
    var pid = $('#product_type').val();
    if( attributes.length == 0 ) {
        $('#product_attr').slideDown();
        return;
    }
    //空属性库存初始化
    if( attributes.length == 1 && !attributes[0].attributes && attributes[0].inventory_await == 0 ) {
        $('#product_attr').slideDown();
        $('input[name=single_inventory]').removeAttr('disabled');
        return;
    }
    //非空属性库存初始化
    for( var j = 0; j < attributes.length; j++ ) {
        //attributes[j].attributes = eval('(' + attributes[j].attributes + ')');
        temp = '<div class="pull-left" style="width: 80%" id="show-attr-'+ attributes[j].id +'">';
        for( var i = 0 ; i < data_product_attr[pid].length; i++ ) {
            var required = '';
            if( data_product_attr[pid][i].needle == 1 ) {
                required = 'required';
            }
            temp += '<p style="display: inline-block" class="clear">';
            temp += '<label class="l-title">'+data_product_attr[pid][i].name+'：</label><em>'+attributes[j].attributes[data_product_attr[pid][i].id]+'</em>';
            switch( data_product_attr[pid][i].type ) {
                case 'text':
                    temp += '<input type="text" class="text-input w50 product-attribute '+required+'" name="attr[' + attributes[j].id + ']['+data_product_attr[pid][i].id+']" label="'+data_product_attr[pid][i].name+'" style="border: 1px solid rgb(213, 213, 213); color: rgb(0, 0, 0); display: none;" value="'+attributes[j].attributes[data_product_attr[pid][i].id]+'"/>';
                    break;
                case 'select':
                    temp += '<select name="attr[' + attributes[j].id + '][' + data_product_attr[pid][i].id + ']" class="w100 product-attribute" label="' + data_product_attr[pid][i].name + '" style="display: none" >';
                    var options = data_product_attr[pid][i].options.split(',');
                    for( var k = 0; k < options.length; k++ ) {
                        var selected = '';
                        if( attributes[j].attributes[data_product_attr[pid][i].id] == options[k] ) {
                            selected = 'selected="selected"';
                        }
                        temp += '<option value="' + options[k] + '" '+selected+'>' + options[k] + '</option>';
                    }
                    temp += '</select>';
                    break;
            }
            temp += '</p>';
        }
        temp += '<p>' +
        '<label class="l-title">库存：</label><em>'+attributes[j].inventory+'</em>' +
        '<input class="text-input w50 product-attribute inventory" type="text" name="inventory[' + attributes[j].id + ']" placeholder="请输入库存" label="库存" style="border: 1px solid rgb(213, 213, 213); color: rgb(0, 0, 0); display: none;" value="' + attributes[j].inventory + '"/>' +
        '</p>';
        index++;
        temp += '</div>';
        temp += '<div class="pull-right"><button class="btn btn-primary edit-attr" type="button">编辑</button>&nbsp;<button class="btn btn-primary remove-attr" type="button">删除</button>&nbsp;</div>';
        $('#show-attr').append(temp);
        $('#product_attr').css('display', 'block');
    }
    index = attributes[attributes.length - 1].id + 1;
}

function init_add_attr_modal() {
    $('.modal-body').empty();
    var pid = $('#product_type').val();
    var temp = '';
    for(var i = 0; i < data_product_attr[pid].length; i++ ) {
        temp += '<p style="display: inline-block">';
        temp += '<label class="l-title">' + data_product_attr[pid][i].name + '</label>';
        var required = '';
        if( data_product_attr[pid][i].needle == 1 ) {
            required = 'required';
        }
        switch( data_product_attr[pid][i].type ) {
            case 'text':
                temp += '<input type="text" class="text-input w50 product-attribute ' + required + '" name="attr[' + index + '][' + data_product_attr[pid][i].id + ']" label="' + data_product_attr[pid][i].name + '"/>';
                break;
            case 'select':
                temp += '<select name="attr[' + index + '][' + data_product_attr[pid][i].id + ']" class="w100 product-attribute" label="' + data_product_attr[pid][i].name + '">';
                var options = data_product_attr[pid][i].options.split(',');
                for( var j = 0; j < options.length; j++ ) {
                    temp += '<option value="' + options[j] + '">' + options[j] + '</option>';
                }
                temp += '</select>';
                break;
        }
        temp += '</p>';
    }

    $('.modal-body').append(temp);

    $('#modal-product-attr').modal({
        clickClose: false,
        showClose: false,
        width: 800,
        height: 225
    });

    $('.required').focus(function() {
        if( $(this).val() == '必填项' ) {
            $(this).val('');
        }
        $(this).css('border', '1px rgb(213,213,213) solid');
        $(this).css('color', '#000000');
    });
}

//检查待发库存，如果都为0，允许修改，
function do_product_type_change() {
    if ($('#product_type').val() == 0) {
        if ($('#product_attr').is(':visible')) {
            $('#product_attr').slideUp(function() {
                $('#show-attr').empty();
                $('#product_attr').empty();
            });
        }
        return;
    } else {
        if( current_type == $('#product_type').val() ) {
            //空属性库存初始化
            if( attributes.length == 1 && !attributes[0].attributes && attributes[0].inventory_await == 0 ) {
                $('#product_attr').slideUp(function() {
                    $('#show-attr').empty();
                    $('#show-attr').slideUp();
                    $('#product_attr').empty();
                    var temp = '<label class="l-title">产品属性：</label>';
                    temp += ' <input style="cursor: pointer;" class="text-input" type="button" id="add-attr" value="增加属性" />';
                    temp += '<p class="clear" id="show-attr"></p>';
                    $('#product_attr').append(temp);
                    $('#product_attr').slideDown();
                    $('input[name=single_inventory]').removeAttr('disabled');
                });
                return;
            }
            //非空属性库存初始化
            init_inventory();
        } else {
            $('#product_attr').slideUp(function() {
                $('#show-attr').empty();
                $('#show-attr').slideUp();
                $('#product_attr').empty();
                var temp = '<label class="l-title">产品属性：</label>';
                temp += ' <input style="cursor: pointer;" class="text-input" type="button" id="add-attr" value="增加属性" />';
                temp += '<p class="clear" id="show-attr"></p>';
                $('#product_attr').append(temp);
                $('#product_attr').slideDown();
                $('input[name=single_inventory]').removeAttr('disabled');
            });

        }

    }
}

//更换类型时获取分类的属性
function do_get_attributes() {
    var type_id = $('#product_type').val();
    if( 0 >= type_id ) {
        return;
    }
    var url = 'ajax.php';
    var params = {'opera':'get_attr', 'id':type_id};
    $.post(url, params, do_get_attributes_response, 'json');
}

//删除库存，删除前必须确认是否可以删除：1、空属性，有待发；2、空属性，无待发；3、非空属性，有待发；4、非空属性，无待发
function do_remove_attr() {

}

//添加属性，空库存且有待发，不能添加
function do_add_attr() {
    var sn = $('#product_sn').val();
    var type = $('#product_type').val();
    var url = 'product.php';
    var param = {'opera':'check_inventory', 'sn': sn, 'type': type};
    $.post(url, param, do_add_attr_response, 'json');
}

//编辑属性，无限制
function do_edit_attr() {

}

//无更换类型，空库存且有待发，不能添加；有更换类型，检查待发，删除原来所有
function do_confirm() {
    var flag = true;
    var attr = '{';
    $('#modal-product-attr .required').each(function(index) {
        if ('' == $(this).val().trim()) {
            $(this).css('border', '1px #ff0000 solid');
            $(this).css('color', '#ff0000');
            $(this).val('必填项');
            flag = false;
        } else if ('必填项' == $(this).val().trim()) {
            flag = false;
        }
    });
    $('#modal-product-attr .product-attributes').each(function(index) {
        var index_temp = $(this).attr('name');
        index_temp = index_temp.split('[')[2];
        index_temp = index_temp.split(']')[0];
        if( index > 0 ) {
            attr += ',';
        }
        attr += '"' + index_temp + '":"' + $(this).val() + '"';
    });

    if( flag ) {
        attr += '}';
        var sn = $('#product_sn').val();
        var type = $('#product_type').val();
        var inventory = $('#modal-product-attr .inventory').val();
        var url = 'product.php';
        var params = {'opera': 'add_attr', 'sn': sn, 'type': type, 'attr': attr, 'inventory': inventory};
        $.post(url, params, do_confirm_response, 'json');
    }
}

//编辑属性，无限制
function do_edit_confirm() {

}

function do_add_attr_response(result) {
    if( result.error == 0 ) {
        init_add_attr_modal();
    } else {
        if( result.msg != '' ) {
            alert(result.msg)
        }
    }
}

function do_confirm_response() {

}

function do_edit_confirm_response() {

}

function do_remove_attr_response() {

}

function do_get_attributes_response(result) {
    if( result.error == 0 ) {
        console.log(attr_list);
        attr_list = result.data;
    } else {
        alert(result.message);
        return;
    }
}
