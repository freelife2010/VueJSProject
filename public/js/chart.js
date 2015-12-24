// Start Bootstrap JS
// ----------------------------------- 

var charts = [];
initCharts();

function initCharts() {
    initAPPDailyUsageChart('chartjs-app-daily');
    initAPPCDRChart('chartjs-app-cdr');
    initOverallCDRChart('chartjs-overall-cdr');
}

function initOverallCDRChart(selector) {
    destroyChart(selector);

    getChartData(selector, '/cdr/chart-data', '.loader-overall-cdr');

}

function initAPPCDRChart(selector) {
    destroyChart(selector);
    var appId = getUrlParam('app');
    getChartData(selector, '/app-cdr/chart-data?app='+appId, '.loader-app-cdr');

}

function initAPPDailyUsageChart(selector) {
    destroyChart(selector);
    var appId = getUrlParam('app');
    getChartData(selector, '/app-cdr/chart-daily-usage-data?app='+appId, '.loader-app-daily');

}

function getChartData(chart_selector, url, loader) {
    var $loader = $(loader);
    $.ajax({
        data: {
            from_date: $('#from_date').find('input').val(),
            to_date: $('#to_date').find('input').val()
        },
        url: url,
        beforeSend: function () {
            $loader.removeClass('hide');
        },
        success: function (data) {
            renderChart(data, chart_selector);
        },
        complete: function () {
            $loader.addClass('hide');
        }
    });
}


function renderChart(data, chart_selector) {
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

    var linectx = document.getElementById(chart_selector).getContext("2d");
    charts[chart_selector] = new Chart(linectx).Line(lineData, lineOptions);
}

function destroyChart(selector) {
    if (selector in charts && typeof(charts[selector]) != "undefined")
        charts[selector].destroy();
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

function getUrlParam(name) {
    name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
    var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
        results = regex.exec(location.search);
    return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
}