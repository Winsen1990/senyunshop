$(function () {
    $('#chart').highcharts({
        title: {
            text: '一天流量趋势',
            x: -20 //center
        },
        subtitle: {
            text: '',
            x: -20
        },
        xAxis: {
            categories: ['00:00', '2:00', '4:00', '6:00', '8:00', '10:00',
                '12:00', '14:00', '16:00', '18:00', '20:00', '22:00']
        },
        yAxis: {
            title: {
                text: '次数'
            },
            plotLines: [{
                value: 0,
                width: 2,
                color: '#808080'
            }]
        },
        tooltip: {
            valueSuffix: '次'
        },
        legend: {
            layout: 'vertical',
            align: 'right',
            verticalAlign: 'middle',
            borderWidth: 0
        },
        series: [{
            name: '访问次数(pv)',
            data: pv_data
        }, {
            name: '独立访客(uv)',
            data: uv_data
        }]
    });

    $('#date').click(function() {
        var date = {
            elem: '#date',
            format: 'YYYY-MM-DD',
            istime: false,
            istoday: true,
            choose: function(datas){
                var url = 'statistics.php';
                if( datas) {
                    url += '?date=' + datas;
                    window.location.href = url;
                }
            }
        };
        laydate(date);
    });

});