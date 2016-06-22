<?php

require_once('config.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/test.php');
$PAGE->set_heading('Test');
$PAGE->set_pagelayout('standard');

echo $OUTPUT->header();

$chart = new core\chart_line();
$sales = new core\chart_series('Sales', [1000, 1170, 660, 1030]);
$expenses = new core\chart_series('Expenses', [400, 460, 1120, 540]);
//$expenses->set_type(core\chart_serie::TYPE_LINE);
$chart->add_series($sales);
$chart->add_series($expenses);
$chart->set_labels(['2004', '2005', '2006', '2007']);

$chart2 = new core\chart_bar();
$chart2->add_series($sales);
$chart2->add_series($expenses);
$chart2->set_labels(['2004', '2005', '2006', '2007']);

$chart3 = new core\chart_pie();
$chart3->add_series($sales);
// $chart3->add_serie($expenses);
$chart3->set_labels(['2004', '2005', '2006', '2007']);

// echo $OUTPUT->render_chart($chart);

// $PAGE->requires->js(new moodle_url('http://fred.per.in.moodle.com/charts/chart.js/dist/Chart.js'));
// $PAGE->requires->js(new moodle_url('/chart.js'));

$id = 'chart' . uniqid();
$canvas = html_writer::tag('canvas', '', ['id' => $id]);
$js = "require(['core/chart_builder', 'core/chart_output', 'core/chart_series'], function(Builder, Output, Series) {
    Builder.make(" . json_encode($chart) . ").then(function(ChartType) {
        var op = new Output('#" . $id . "', ChartType);
        setTimeout(function() {
            ChartType.addSeries(new Series('Test', [100, 222, 3333, 444]));
            op.update();
        }, 2000);
    });
});";
echo $canvas;
$PAGE->requires->js_init_code($js, true);

echo $OUTPUT->render($chart2);
echo $OUTPUT->render($chart3);

echo $OUTPUT->footer();
