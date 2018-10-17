/**
 * Created by root on 15-9-2.
 */
$(function(){
    $('.order-detail').on('click', function() {

        var sn = $(this).attr('data-value');
        var data = {sn:sn, opera:'order_detail'};
        var url = 'ajax.php';
        $.post(url, data, function(response){
            if( response.error == 1 ) {
                alert(response.message);
                return;
            }
            var order = response.order;
            var order_detail = response.order_detail;
            $('#modal-order-detail .order-info').empty();
            var temp = '<h5 class="pull-left">订单信息</h5><div class="clear"></div>';

            temp += '<p style="display: inline-block" class="clear">' +
                '<label class="l-title">订单编号：</label>' +
                '<em style="width: 132px;">'+order.order_sn+'</em>' +
                '</p>';
            temp += '<p style="display: inline-block" class="clear">' +
                '<label class="l-title">下单时间：</label>' +
                '<em>'+order.add_time_str+'</em>' +
                '</p>';
            temp += '<p style="display: inline-block" class="clear">' +
                '<label class="l-title">支付时间：</label>' +
                '<em>'+order.pay_time_str+'</em>' +
                '</p>';
            temp += '<p style="display: inline-block" class="clear">' +
                '<label class="l-title">发货时间：</label>' +
                '<em>'+order.delivery_time_str+'</em>' +
                '</p>';
            temp += '<p style="display: inline-block" class="clear">' +
                '<label class="l-title">确认收货时间：</label>' +
                '<em>'+order.receive_time_str+'</em>' +
                '</p>';
            temp += '<p style="display: inline-block" class="clear">' +
                '<label class="l-title">订单状态：</label>' +
                '<em>'+order.status_str+'</em>' +
                '</p>';
            temp += '<table class="table">' +
                '<thead>' +
                '<tr>' +
                '<th>产品</th>' +
                '<th>属性</th>' +
                '<th>单价</th>' +
                '<th>数量</th>' +
                '<th>积分</th>' +
                '<th>返利</th>' +
                '</tr>' +
                '</thead>' +
                '<tbody>';

            for(var i = 0; i < order_detail.length; i++ ) {
                temp += '<tr>';
                temp += '<td class="w180">' +
                    '<div class="pull-left">' +
                    '<img src="..'+order_detail[i].img+'" width="50" height="50"/>' +
                    '</div>' +
                    '<div class="pull-left"><em>' + order_detail[i].product_name + '</em></div>' +
                    '</td>';

                temp += '<td class="w180">';
                //var attributes = '(' + order_detail[i].product_attributes + ')';
                //attributes = eval(attributes);
                //for( var key in attributes ) {
                //    temp += '<em>' + key + ':' + attributes[key] + '</em>&nbsp;&nbsp;';
                //}
                temp += '<em>'+ order_detail[i].product_attributes + '</em>';
                temp += '</td>';

                temp += '<td>' + order_detail[i].product_price + '</td>';
                temp += '<td>' + order_detail[i].count + '</td>';
                temp += '<td>购买积分：' + order_detail[i].integral +
                    '<br />' +
                    '赠送积分：' + order_detail[i].integral_given +
                    '</td>';
                temp += '<td>' + order_detail[i].reward + '</td>';
                temp += '</tr>';
            }

            temp += '</tbody></table>';
            temp += '<div class="pull-right">结算：' + order.amount + '（含运费：' + order.delivery_fee + '）</div>';
            temp += '<div class="clear"></div>';

            $('#modal-order-detail .order-info').append(temp);


            $('#modal-order-detail .express-info').empty();
            temp = '<h5 class="pull-left">物流信息</h5><div class="clear"></div>';
            temp += '<p style="" class="clear">' +
            '<label class="l-title">收货地址：</label>' +
            '<em>'+order.province_name +'&nbsp;' +
            order.city_name +'&nbsp;' +
            order.district_name +'&nbsp;' +
            order.address + '<br />' +
            order.consignee + '(' + order.mobile + ')' + '<br />' +
            order.zipcode +
            '</em>' +
            '</p>';

            temp += '<p style="display: inline-block" class="clear">' +
            '<label class="l-title">配送方式：</label>' +
            '<em>'+order.delivery_name+'</em>' +
            '</p>';

            temp += '<p style="display: inline-block" class="clear">' +
            '<label class="l-title">物流公司：</label>' +
            '<em>'+order.express_name+'</em>' +
            '</p>';

            temp += '<p style="display: inline-block" class="clear">' +
            '<label class="l-title">快递单号：</label>' +
            '<em>'+order.express_sn+'</em>' +
            '</p>';

            temp += '<p style="" class="clear">' +
            '<label class="l-title">备注：</label>' +
            '<em>'+order.remark+'</em>' +
            '</p>';

            $('#modal-order-detail .express-info').append(temp);

            $('#modal-order-detail').modal({
                clickClose: false,
                showClose: false,
                width: 800,
                height: 400
            });
        },'json');



    });
});