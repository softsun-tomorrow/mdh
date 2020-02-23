<canvas id="myChart" width="400" height="100%"></canvas>
<div></div>
{{--<div>{{ $assign }}</div>--}}

<script>
    $(function () {
        var store_id = "{{ $store_id }}";
        $.get('/api/backend/getOrder?store_id='+store_id,function(ret){
//            console.log(ret);

            getEchart(ret.data);

        });

        function getEchart(result){
            var ctx = document.getElementById("myChart").getContext('2d');

            console.log(result);
            var data = result.value;
            var labels = result.data;
            console.log(data);
            console.log(labels);
            var myChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: '# 订单销量',
                        data: data,
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.2)',
                            'rgba(54, 162, 235, 0.2)',
                            'rgba(255, 206, 86, 0.2)',
                            'rgba(75, 192, 192, 0.2)',
                            'rgba(153, 102, 255, 0.2)',
                            'rgba(255, 159, 64, 0.2)'
                        ],
                        borderColor: [
                            'rgba(255,99,132,1)',
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 206, 86, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(153, 102, 255, 1)',
                            'rgba(255, 159, 64, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        yAxes: [{
                            ticks: {
                                beginAtZero:true
                            },
                        }],

                    },
                    yAxis: {
                        minInterval: 1,
                    }
                },


            });
        }


    });

</script>