// Start Bootstrap JS
// ----------------------------------- 


function getCDRData() {
    if (typeof(lineChart) != "undefined")
        lineChart.destroy();

    var $loader = $('.loader-demo');
    var $chart  = $('.chartjs-linechart');
    $.ajax({
        data: {
            from_date: $('#from_date').find('input').val(),
            to_date: $('#to_date').find('input').val()
        },
        url: '/cdr/chart-data',
        beforeSend: function () {
            $loader.removeClass('hide');
            $chart.addClass('hide');
        },
        success: function (data) {
            renderLineChart(data);
        },
        complete: function () {
            $loader.addClass('hide');
            $chart.removeClass('hide');
        }
    });
}

getCDRData();

function renderLineChart(data) {
    var lineData = {
        labels: data.labels,
        datasets: [
            {
                label: 'CDR',
                fillColor: 'rgba(35,183,229,0.2)',
                strokeColor: 'rgba(35,183,229,1)',
                pointColor: 'rgba(35,183,229,1)',
                pointStrokeColor: '#fff',
                pointHighlightFill: '#fff',
                pointHighlightStroke: 'rgba(35,183,229,1)',
                data: data.data
            }
        ]
    };

    var lineOptions = {
        scaleShowGridLines: true,
        scaleGridLineColor: 'rgba(0,0,0,.05)',
        scaleGridLineWidth: 1,
        bezierCurve: true,
        bezierCurveTension: 0.4,
        pointDot: true,
        pointDotRadius: 4,
        pointDotStrokeWidth: 1,
        pointHitDetectionRadius: 20,
        datasetStroke: true,
        datasetStrokeWidth: 2,
        datasetFill: true,
        responsive: true
    };

    var linectx = document.getElementById("chartjs-linechart").getContext("2d");
    lineChart = new Chart(linectx).Line(lineData, lineOptions);
}

function renderBarChart() {
    var barData = {
        labels : ['January','February','March','April','May','June','July'],
        datasets : [
            {
                fillColor : '#23b7e5',
                strokeColor : '#23b7e5',
                highlightFill: '#23b7e5',
                highlightStroke: '#23b7e5',
                data : [rFactor(),rFactor(),rFactor(),rFactor(),rFactor(),rFactor(),rFactor()]
            },
            {
                fillColor : '#5d9cec',
                strokeColor : '#5d9cec',
                highlightFill : '#5d9cec',
                highlightStroke : '#5d9cec',
                data : [rFactor(),rFactor(),rFactor(),rFactor(),rFactor(),rFactor(),rFactor()]
            }
        ]
    };

    var barOptions = {
        scaleBeginAtZero : true,
        scaleShowGridLines : true,
        scaleGridLineColor : 'rgba(0,0,0,.05)',
        scaleGridLineWidth : 1,
        barShowStroke : true,
        barStrokeWidth : 2,
        barValueSpacing : 5,
        barDatasetSpacing : 1,
        responsive: true
    };

    var barctx = document.getElementById("chartjs-barchart").getContext("2d");
    var barChart = new Chart(barctx).Bar(barData, barOptions);
}

