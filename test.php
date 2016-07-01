<?php

use core\chart_series;
use core\chart_bar;
use core\chart_line;
use core\chart_axis;

function myrender($chart, $tag = 'div') {
    global $PAGE;
    $id = 'chart' . uniqid();

    $canvas = html_writer::tag($tag, '', ['id' => $id]);

    $js = "require(['core/chart_builder', 'core/chart_output', 'core/chart_series'], function(Builder, Output, Series) {
        Builder.make(" . json_encode($chart) . ").then(function(ChartInst) {
            var op = new Output('#" . $id . "', ChartInst);
            setTimeout(() => {
                ChartInst.addSeries(new Series('Async', [20, 40, 80, 160]));
                op.update();
            }, 4000);
        });
    });";

    $PAGE->requires->js_init_code($js, true);
    return $canvas;
}

require_once('config.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/test.php');
$PAGE->set_heading('Test');
$PAGE->set_pagelayout('standard');

echo $OUTPUT->header();

// Survey.
// http://fred.per.in.moodle.com/graphs/mod/survey/graph.php?id=7&qid=67&group=0&type=multiquestion.png
$qid = 67;
$surveyid = 7;
$group = 0;

$cm = get_coursemodule_from_id('survey', $surveyid);
$question  = $DB->get_record("survey_questions", array("id"=>$qid));
$question->text = get_string($question->text, "survey");
$question->options = get_string($question->options, "survey");

$options = explode(",",$question->options);
$questionorder = explode( ",", $question->multi);

$qqq = $DB->get_records_list("survey_questions", "id", explode(',',$question->multi));

foreach ($questionorder as $i => $val) {
   $names[$i] = get_string($qqq["$val"]->shorttext, "survey");
   $buckets1[$i] = 0;
   $buckets2[$i] = 0;
   $count1[$i] = 0;
   $count2[$i] = 0;
   $indexof[$val] = $i;
   $stdev1[$i] = 0;
   $stdev2[$i] = 0;
   $bubbles[$i] = array_fill(0, count($options) + 1, 0);
}

$aaa = $DB->get_records_select("survey_answers", "((survey = ?) AND (question in ($question->multi)))", array($cm->instance));

if ($aaa) {
   foreach ($aaa as $a) {
       if (!$group or isset($users[$a->userid])) {
           $index = $indexof[$a->question];
           if ($a->answer1) {
               $buckets1[$index] += $a->answer1;
               $count1[$index]++;
               $bubbles[$index][$a->answer1]++;
           }
           if ($a->answer2) {
               $buckets2[$index] += $a->answer2;
               $count2[$index]++;
           }
       }
   }
}

foreach ($questionorder as $i => $val) {
   if ($count1[$i]) {
       // $buckets1[$i] = (float)$buckets1[$i] / (float)$count1[$i];
       $finalbuckets1[$i] = (float)$buckets1[$i] / (float)$count1[$i];
   }
   if ($count2[$i]) {
       // $buckets2[$i] = (float)$buckets2[$i] / (float)$count2[$i];
       $finalbuckets2[$i] = (float)$buckets2[$i] / (float)$count2[$i];
   }
}

if ($aaa) {
   foreach ($aaa as $a) {
       if (!$group or isset($users[$a->userid])) {
           $index = $indexof[$a->question];
           if ($a->answer1) {
               $difference = (float) ($a->answer1 - $finalbuckets1[$index]);
               $stdev1[$index] += ($difference * $difference);
           }
           if ($a->answer2) {
               $difference = (float) ($a->answer2 - $finalbuckets2[$index]);
               $stdev2[$index] += ($difference * $difference);
           }
       }
   }
}

foreach ($questionorder as $i => $val) {
   if ($count1[$i]) {
       $stdev1[$i] = sqrt( (float)$stdev1[$i] / ((float)$count1[$i]));
   }
   if ($count2[$i]) {
       $stdev2[$i] = sqrt( (float)$stdev2[$i] / ((float)$count2[$i]));
   }
   // $buckets1[$i] = $buckets1[$i];
   // $buckets2[$i] = $buckets2[$i];
}

// $maxbuckets1 = max($finalbuckets1);
// $maxbuckets2 = max($finalbuckets2);

$data = [];
foreach ($bubbles as $questionid => $bubble) {
    foreach ($bubble as $answerid => $weight) {
        if (!$weight) {
            continue;
        }
        $data[] = [
            'x' => $questionid,
            'y' => $answerid,
            'r' => $weight / $count1[$questionid]
        ];
    }
}

$average = new core\chart_series('Average', array_map(function($value, $index) {
    return [
        'x' => $index,
        'y' => $value
    ];
}, $finalbuckets1, array_keys($finalbuckets1)));
$average->set_type(core\chart_series::TYPE_LINE);
$answers = new core\chart_series('Answers', $data);
$chart = new core\chart_bubble();
$chart->set_labels($names);
$chart->add_series($average);
$chart->add_series($answers);
$yaxis = $chart->get_yaxis(0, true);
$yaxis->set_labels(array_merge([null], $options, [null]));
$yaxis->set_min(0);
$yaxis->set_max(count($options) + 1);
$yaxis->set_stepsize(1);
echo $OUTPUT->render($chart);

// $graph = new graph($SURVEY_GWIDTH,$SURVEY_GHEIGHT);
// $graph->parameter['title'] = "$question->text";

// $graph->x_data               = $names;
// $graph->y_data['answers1']   = $buckets1;
// $graph->y_format['answers1'] = array('colour' => 'ltblue', 'line' => 'line',  'point' => 'square',
//                                     'shadow_offset' => 4, 'legend' => $stractual);
// $graph->y_data['answers2']   = $buckets2;
// $graph->y_format['answers2'] = array('colour' => 'ltorange', 'line' => 'line', 'point' => 'square',
//                                         'shadow_offset' => 4, 'legend' => $strpreferred);
// $graph->y_data['stdev1']   = $stdev1;
// $graph->y_format['stdev1'] = array('colour' => 'ltltblue', 'bar' => 'fill',
//                                     'shadow_offset' => '4', 'legend' => 'none', 'bar_size' => 0.3);
// $graph->y_data['stdev2']   = $stdev2;
// $graph->y_format['stdev2'] = array('colour' => 'ltltorange', 'bar' => 'fill',
//                                     'shadow_offset' => '4', 'legend' => 'none', 'bar_size' => 0.2);
// $graph->offset_relation['stdev1'] = 'answers1';
// $graph->offset_relation['stdev2'] = 'answers2';

// $graph->parameter['bar_size']    = 0.15;

// $graph->parameter['legend']        = 'outside-top';
// $graph->parameter['legend_border'] = 'black';
// $graph->parameter['legend_offset'] = 4;

// $graph->y_tick_labels = $options;

// if (($maxbuckets1 > 0.0) && ($maxbuckets2 > 0.0)) {
//       $graph->y_order = array('stdev1', 'answers1', 'stdev2', 'answers2');
// } else if ($maxbuckets1 > 0.0) {
//    $graph->y_order = array('stdev1', 'answers1');
// } else {
//    $graph->y_order = array('stdev2', 'answers2');
// }

// $graph->parameter['y_max_left']= count($options) - 1;
// $graph->parameter['y_axis_gridlines']= count($options);
// $graph->parameter['y_resolution_left']= 1;
// $graph->parameter['y_decimal_left']= 1;
// $graph->parameter['x_axis_angle']  = 20;

// $graph->draw();

// $labels = ['2004', '2005', '2006', '2007'];
// $sales = new chart_series('Sales', [1000, 1170, 660, 1030]);
// $expenses = new chart_series('Expenses', [400, 460, 1120, 540]);

// $chart = new chart_bar();
// $chart->set_labels($labels);
// $chart->add_series($sales);
// $chart->add_series($expenses);

// echo $OUTPUT->heading('Core rendering');
// // echo myrender($chart);
// echo $OUTPUT->render($chart);

// echo $OUTPUT->heading('Rendering from div');
// echo myrender($chart, 'div');
// // echo $OUTPUT->render($chart);

// echo $OUTPUT->heading('Rendering from canvas');
// echo myrender($chart, 'canvas');
// // echo $OUTPUT->render($chart);

echo $OUTPUT->footer();
