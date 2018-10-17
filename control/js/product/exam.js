/**
 * Created by root on 15-8-27.
 */

$(function() {

    var index = 0;
    var editing = 0;

    init();

    function init() {
        var temp = '<label class="l-title">产品属性：</label>';
        //temp += '<input style="cursor: pointer;" class="text-input" type="button" id="add-attr" value="增加属性"/>';
        temp += '<p class="clear" id="show-attr"></p>';
        $('#product_attr').append(temp);
        var pid = $('#product_type').attr('data-id');
        if( attributes.length == 0 ) {
            $('#product_attr').slideDown();
            return;
        }

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
            temp += '<div class="pull-right">';
            temp += '</div>';
            // <button class="btn btn-primary edit-attr" type="button">编辑</button>&nbsp;<button class="btn btn-primary remove-attr" type="button">删除</button>&nbsp;</div>';
            $('#show-attr').append(temp);
            $('#product_attr').css('display', 'block');
        }
        index = attributes[attributes.length - 1].id + 1;


    }

    $('#product_type').change(function() {
        if ($(this).val() == 0) {
            if ($('#product_attr').is(':visible')) {
                $('#product_attr').slideUp(function() {
                    $('#show-attr').empty();
                    $('#product_attr').empty();
                });
            }
            return;
        } else {
            $('#product_attr').slideUp(function() {
                $('#show-attr').empty();
                $('#product_attr').empty();
                var temp = '<label class="l-title">产品属性：</label>';
                //temp += ' <input style="cursor: pointer;" class="text-input" type="button" id="add-attr" value="增加属性" />';
                temp += '<p class="clear" id="show-attr"></p>';
                $(this).append(temp);
                $(this).slideDown();

                $('#add-attr').click(function(e) {
                    e.preventDefault();
                    $('.modal-body').empty();
                    var pid = $('#product_type').val();
                    temp = '';
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

                });

            });
        }
    });

    $('.remove-attr').click(function() {
        editing = $(this).parent().prev().attr('id').split('show-attr-')[1];
        var data = {sn:$('#product_sn').val(), id:editing, opera: 'delete_attr'};
        var url = 'ajax.php';
        $.post(url, data, function(response){
            console.log(response);
            if( response.error == 1 ) {
                alert(response.message);
            } else {
                $('#show-attr-' + editing).next().remove();
                $('#show-attr-' + editing).remove();
            }
        },'json');
    });

    $('.edit-attr').click(function() {
        $('#modal-edit-product-attr .modal-body').empty();
        $('.edit-attr').parent().prev().children().each(function() {
            var temp = $(this).clone();
            temp.children('.product-attribute').css('display', 'inline-block');
            temp.children('.product-attribute').prev('em').css('display', 'none');
            $('#modal-edit-product-attr .modal-body').append(temp);
        });

        editing = $(this).parent().prev().attr('id').split('show-attr-')[1];

        $('#modal-edit-product-attr').modal();

        $('.required').focus(function() {
            if( $(this).val() == '必填项' ) {
                $(this).val('');
            }
            $(this).css('border', '1px rgb(213,213,213) solid');
            $(this).css('color', '#000000');
        });
    });

    $('#add-attr').click(function(e) {
        e.preventDefault();
        $('.modal-body').empty();
        var pid = $('#product_type').val();
        temp = '';
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
        temp += '<p>' +
        '<label class="l-title">库存</label>' +
        '<input class="text-input w50 product-attribute inventory" type="text" name="inventory[' + index + ']" placeholder="请输入库存" label="库存">' +
        '</p>'

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

    });

    $('#confirm').click(function() {
        var flag = true;
        var attr = '{';
        $('#modal-product-attr .required').each(function(index) {
            if( '' == $(this).val().trim() ) {
                $(this).css('border', '1px #ff0000 solid');
                $(this).css('color', '#ff0000');
                $(this).val('必填项');
                flag = false;
            }else if( '必填项' == $(this).val().trim() ) {
                flag = false;
            }
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
            var url = 'ajax.php';
            var inventory = $('#modal-product-attr .inventory').val();
            var data = {opera: 'add_attr', sn: $('#product_sn').val(), attr: attr, inventory: inventory};
            console.log(data);
            $.post(url, data, function(response){
                if( response.error == 1 ) {
                    alert(response.message);
                } else {
                    var attribute = '<div class="pull-left" style="width: 80%;" id="show-attr-' + response.id  + '">';
                    var temp_array = new Array;
                    $('#modal-product-attr .product-attribute').each(function() {
                        var name = $(this).attr('name');
                        var label = $(this).attr('label');
                        var value = $(this).val();
                        var type = $(this).attr('type');

                        attribute += '<p style="display: inline-block" class="clear">';
                        attribute += '<label class="l-title">' + label + '：</label>';
                        attribute += '<em>' + value + '</em>';
                        attribute += '</p>';

                        temp_array.push($(this).clone());
                    });
                    attribute += '</div>';
                    attribute += '<div class="pull-right">';
                    //attribute += '<button class="btn btn-primary edit-attr" type="button">编辑</button>&nbsp;';
                    //attribute += '<button class="btn btn-primary remove-attr" type="button">删除</button>&nbsp;';
                    attribute += '</div>';
                    $('#show-attr').append(attribute);
                    var length = temp_array.length
                    for( var i = 0 ; i < length; i++ ) {
                        temp_array.shift().css('display', 'none').appendTo($('#show-attr-' + response.id + ' p.clear')[i]);
                    }
                    $('#show-attr .inventory').parent().css('display', 'block');

                    //$('#add-attr').hide();
                    $.modal.close();
                    index++;
                }
            },'json');



        }

    });

    $('#edit-confirm').click(function() {
        var flag = true;
        var attr = '{';
        $('#modal-edit-product-attr .required').each(function (index) {
            if ( '' == $(this).val().trim() ) {
                $(this).css('border', '1px #ff0000 solid');
                $(this).css('color', '#ff0000');
                $(this).val('必填项');
                flag = false;
            } else if( '必填项' == $(this).val().trim() ) {
                flag = false;
            }
            var index_temp = $(this).attr('name');
            index_temp = index_temp.split('[')[2];
            index_temp = index_temp.split(']')[0];
            if( index > 0 ) {
                attr += ',';
            }
            attr += '"' + index_temp + '":"' + $(this).val() + '"';
        });

        var value_array = new Array();
        $('#modal-edit-product-attr .product-attribute').each(function() {
            value_array.push($(this).val());
        });

        if( flag ) {
            attr += '}';
            var inventory = $('#modal-edit-product-attr .inventory').val();
            var url = 'ajax.php';
            var data = {opera: 'edit_attr', sn: $('#product_sn').val(), attr: attr, id: editing, inventory: inventory};

            $.post(url, data, function(response) {
                if( response.error == 1 ) {
                    alert(response.message);
                } else {
                    $('#show-attr-' + editing).empty();
                    $('#show-attr-' + editing).append($('#modal-edit-product-attr .modal-body').html());

                    var length = value_array.length;
                    for( var i = 0; i < length; i++ ) {
                        var value = value_array.shift();
                        $('#show-attr-' + editing + ' em:eq('+ i +')').text(value);
                        $('#show-attr-' + editing + ' .product-attribute:eq('+ i +')').val(value);
                    }

                    $('#show-attr-' + editing + ' em').removeAttr('style');
                    $('#show-attr-' + editing + ' .product-attribute').css('display', 'none');
                    $('#show-attr .inventory').parent().css('display', 'block');
                    $.modal.close();
                }
            }, 'json');
        }
    });

});
