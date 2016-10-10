<?php

require_once('config.php');

$context = context_system::instance();

$PAGE->set_url('/bs-test.php');
$PAGE->set_context($context);
require_login($SITE->id);

$PAGE->set_title('Testing page for bs2 and bs4');
echo $OUTPUT->header();
echo $OUTPUT->heading('BS4 Examples');

?>

<h3>Card</h3>
<div class="card">
    <div class="card-block">
        <h3 class="card-title">Card Title</h3>
        <p> Live-edge meggings hammock, meh tote bag freegan raclette hashtag. Hashtag jean shorts godard art party jianbing wolf. Authentic tumeric unicorn, chicharrones fingerstache taxidermy vinyl letterpress 90's small batch kombucha. Health goth next level tbh tofu, stumptown farm-to-table narwhal vegan coloring book beard keffiyeh chillwave sriracha. Kogi art party jean shorts tumeric live-edge.  </p>
    </div>
</div>

<h3>Columns</h3>
<p>Note the columns will not be re-ordered in bootstrapbase - we can't support that because bs2 has no equivalent for pull-md-X.</p>
<div class="container">
    <div class="row">
        <div class="col-md-3 push-md-2">
            Column MD 3 (Push 2)
        </div>
        <div class="col-md-2 pull-md-3">
            Column MD 2 (Pull 3)
        </div>
        <div class="col-md-7">
            Column MD 7
        </div>
    </div>
</div>

<h3>Utilities</h3>
<p>Note the columns will not be re-ordered in bootstrapbase - we can't support that because bs2 has no equivalent for pull-md-X.</p>
<style type="text/css">
.a {
    background-color: red ! important;
}
.b {
    background-color: blue ! important;
    border: 1px solid black;
    font-weight: 900;
    color: white;
    width: 80px;
    height: 20px;
    box-sizing: content-box;
}
</style>
<h4>Padding</h4>
<div class="a">
    <span class="b p-l-1">P-L-1</span>
    <span class="b p-x-1">P-X-1</span>
    <span class="b p-t-2">P-T-2</span>
    <span class="b p-a-3">P-A-3</span>
    <span class="b p-y-1">P-Y-1</span>
</div>
<h4>Margin</h4>
<div class="a">
    <div class="b m-l-1">M-L-1</div>
    <div class="b m-x-1">M-X-1</div>
    <div class="b m-t-2">M-T-2</div>
    <div class="b m-a-3">M-A-3</div>
    <div class="b m-y-1">M-Y-1</div>
    <div class="b m-x-auto">M-X-AUTO</div>
    <div class="clearfix"></div>
</div>

<h3>Img FLUID</h3>

<div class="container" style="width: 50%">
    <img class="img-fluid" src="http://johnpolacek.github.io/scrolldeck.js/decks/responsive/img/responsive_web_design.png"/>
</div>

<h3>Text DANGER</h3>

<p class="text-danger">OH NOES!</p>

<h3>Tags</h3>

<span class="tag tag-default">Default</span>
<span class="tag tag-primary">Primary</span>
<span class="tag tag-success">Success</span>
<span class="tag tag-info">Info</span>
<span class="tag tag-warning">Warning</span>
<span class="tag tag-danger">Danger</span>

<h3>Buttons</h3>
<p><button class="btn btn-secondary">press me!</button></p>

<h3>d-*</h3>
<div class="d-inline">Inline</div>
<span class="d-inline-block">Inline Block</span>
<span class="d-block">Block</span>

<h3>Floats</h3>
<p><span class="pull-xs-left">Left</span><span class="pull-xs-right">Right</span></p>


<?php echo $OUTPUT->heading('BS2 Examples'); ?>

<h3>Well</h3>
<div class="well">
    <h3>Well Title</h3>
    <p> Live-edge meggings hammock, meh tote bag freegan raclette hashtag. Hashtag jean shorts godard art party jianbing wolf. Authentic tumeric unicorn, chicharrones fingerstache taxidermy vinyl letterpress 90's small batch kombucha. Health goth next level tbh tofu, stumptown farm-to-table narwhal vegan coloring book beard keffiyeh chillwave sriracha. Kogi art party jean shorts tumeric live-edge.  </p>
</div>

<h3>Columns</h3>
<p>We can support offset-X</p>
<div class="container">
    <div class="row">
        <div class="span3 offset-2">
            Column Span 3 (Offset 2)
        </div>
        <div class="span2 offset-3">
            Column Span 2 (Offset 3)
        </div>
    </div>
</div>

<h3>Img Responsive</h3>

<div class="container" style="width: 50%">
    <img class="img-responsive" src="http://johnpolacek.github.io/scrolldeck.js/decks/responsive/img/responsive_web_design.png"/>
</div>

<h3>Text ERROR</h3>

<p class="text-error">OH NOES!</p>

<h3>Labels</h3>

<span class="label">Default</span>
<span class="label label-success">Success</span>
<span class="label label-warning">Warning</span>
<span class="label label-important">Important</span>
<span class="label label-info">Info</span>

<h3>Badges</h3>

<span class="badge">Default</span>
<span class="badge badge-success">Success</span>
<span class="badge badge-warning">Warning</span>
<span class="badge badge-important">Important</span>
<span class="badge badge-info">Info</span>

<h3>Floats</h3>
<p><span class="pull-left">Left</span><span class="pull-right">Right</span></p>

<?php

echo $OUTPUT->footer();

