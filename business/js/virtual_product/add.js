/**
 * Created by root on 15-10-13.
 */

$(function() {

    var index = 0;
    var editing = 0;

    $('#add-content').click(function() {
        $('#modal-consume-content .modal-body').empty();
        var temp = '';
        temp += '<p style="display: inline-block">';
        temp += '<label class="l-title">内容</label>';
        temp += '<input class="text-input w50" type="text" name="content[' + index + ']" label="内容">';
        temp += '</p>';
        temp += '<p style="display: inline-block">';
        temp += '<label class="l-title">数量/规格</label>';
        temp += '<input class="text-input w50" type="text" name="count[' + index + ']" label="数量/规格">';
        temp += '</p>';
        temp += '<p style="display: inline-block">';
        temp += '<label class="l-title">小计</label>';
        temp += '<input class="text-input w50" type="text" name="total[' + index + ']" label="小计">';
        temp += '</p>';

        $('#modal-consume-content .modal-body').append(temp);
        $('#modal-consume-content').modal();

    });

    $('#confirm').click(function() {
        var flag = true;
        $('#modal-consume-content .modal-body input').each(function(i) {
            if( '' == $(this).val().trim() || $(this).hasClass('required') ) {
                $(this).css('border', '1px #ff0000 solid');
                $(this).css('color', '#ff0000');
                $(this).addClass('required');
                flag = false;
                switch (i) {
                    case 0:
                        $(this).val('请输入内容');
                        break;
                    case 1:
                        $(this).val('请输入数量/规格');
                        break;
                    case 2:
                        $(this).val('请输入小计金额');
                        break;
                }
            }
        });

        if( flag ) {

            var content = '<div class="pull-left" style="width: 80%;" id="show-content-' + index + '">';
            var temp_array = new Array;
            $('#modal-consume-content .modal-body input').each(function() {
                var name = $(this).attr('name');
                var label = $(this).attr('label');
                var value = $(this).val();
                var type = $(this).attr('type');

                content += '<p style="display: inline-block" class="clear">';
                content += '<label class="l-title">' + label + '：</label>';
                content += '<em>' + value + '</em>';
                content += '</p>';

                temp_array.push($(this).clone());
            });
            content += '</div>';
            content += '<div class="pull-right">';
            content += '<button class="btn btn-primary edit-content" type="button">编辑</button>&nbsp;';
            content += '<button class="btn btn-primary remove-content" type="button">删除</button>&nbsp;';
            content += '</div>';
            $('#show-content').append(content);
            var length = temp_array.length
            for( var i = 0 ; i < length; i++ ) {
                temp_array.shift().css('display', 'none').appendTo($('#show-content-' + index + ' p.clear')[i]);
            }

            $.modal.close();
            index++;
        }

        $('#modal-consume-content .modal-body input.required').focus(function() {
            $(this).val('');
            $(this).css('border', '1px rgb(213,213,213) solid');
            $(this).css('color', '#000000');
            $(this).removeClass('required');
        });

        $('.remove-content').click(function() {
            $(this).parent().prev().remove();
            $(this).parent().remove();
        });

        $('.edit-content').click(function() {
            $('#modal-edit-consume-content .modal-body').empty();
            $(this).parent().prev().children().each(function() {
                var temp = $(this).clone();
                temp.children('input').css('display', 'inline-block');
                temp.children('input').prev('em').css('display', 'none');
                $('#modal-edit-consume-content .modal-body').append(temp);
            });

            editing = $(this).parent().prev().attr('id').split('show-content-')[1];

            $('#modal-edit-consume-content').modal();


            $('#modal-edit-consume-content .required').focus(function() {
                $(this).val('');
                $(this).css('border', '1px rgb(213,213,213) solid');
                $(this).css('color', '#000000');
            });

        });

    });

    $('#edit-confirm').click(function() {
        var flag = true;
        $('#modal-edit-consume-content .modal-body input').each(function(i) {
            if( '' == $(this).val().trim() ) {
                $(this).css('border', '1px #ff0000 solid');
                $(this).css('color', '#ff0000');
                $(this).addClass('required');
                flag = false;
                switch (i) {
                    case 0:
                        $(this).val('请输入内容');
                        break;
                    case 1:
                        $(this).val('请输入数量/规格');
                        break;
                    case 2:
                        $(this).val('请输入小计金额');
                        break;
                }
            }

            var value_array = new Array();
            $('#modal-edit-consume-content input').each(function() {
                value_array.push($(this).val());
            });

            if( flag ) {
                $('#show-content-' + editing).empty();
                $('#show-content-' + editing).append($('#modal-edit-consume-content .modal-body').html());

                var length = value_array.length;
                for( var i = 0; i < length; i++ ) {
                    var value = value_array.shift();
                    $('#show-content-' + editing + ' em:eq('+ i +')').text(value);
                    $('#show-content-' + editing + ' input:eq('+ i +')').val(value);
                }

                $('#show-content-' + editing + ' em').removeAttr('style');
                $('#show-content-' + editing + ' input').css('display', 'none');
                $.modal.close();
            }

        });

    });


});