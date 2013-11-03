<?php
   
class xs_widget_reporter_2 extends \xs\Action\WidgetController {

    public $meta = array(
        'name' => 'Reporter widget 2',
        'description' => 'Reporter widget',
        'version' => '1.0',
        'author' => 'Alexander Johannesen',
        'author_link' => 'http://shelter.nu/',
        'category' => 'report',
    );

    public $properties = array (
        'title' => 'Report',
        'style' => 'min-height:300px;',
        'class' => 'color-red',
    ) ;

    function xx___gui_js () {
        return ( '
    <script src="{$dir/js}/rgraph/RGraph.common.core.js"></script>
    <script src="{$dir/js}/rgraph/RGraph.common.context.js"></script>
    <script src="{$dir/js}/rgraph/RGraph.common.annotate.js"></script>
    <script src="{$dir/js}/rgraph/RGraph.common.tooltips.js"></script>
    <script src="{$dir/js}/rgraph/RGraph.common.zoom.js"></script>
    <script src="{$dir/js}/rgraph/RGraph.common.resizing.js"></script>
    <script src="{$dir/js}/rgraph/RGraph.bar.js"></script>
    ' ) ;
    }

    function GET_content () {
        return $this->prepare ( "

<canvas id='bar5' style='width:100%;' height='250'>[No canvas support]</canvas>
    <script type='text/javascript'>
        window.onload = function () {
var bar5 = new RGraph.Bar('bar5', [[30,20,19,21], [23,25, 27, 30], [30,25,29, 32], [27,28,35,33], [26,18,29,30], [31,20,25,27], [39,28,28,35], [27,29,28,29], [26,23,26,27], [30,20,19,21], [30,20,19,21], [30,20,19,21]]);
            bar5.Set('chart.units.pre', '');
            // bar5.Set('chart.title', 'Sales in the last 8 months (tooltips)');
            // bar5.Set('chart.title.vpos', 0.5);
            bar5.Set('chart.colors', ['gray', 'red', 'yellow', 'green']);
            // bar5.Set('chart.gutter.left', 40);
            // bar5.Set('chart.gutter.right', 5);
            // bar5.Set('chart.gutter.top', 40);
            // bar5.Set('chart.gutter.bottom', 90);
            // bar5.Set('chart.shadow', true);
            // bar5.Set('chart.shadow.color', '#aaa');
            bar5.Set('chart.background.barcolor1', 'white');
            bar5.Set('chart.background.barcolor2', 'white');
            // bar5.Set('chart.background.grid.hsize', 5);
            // bar5.Set('chart.background.grid.vsize', 5);
            bar5.Set('chart.grouping', 'stacked');
            // bar5.Set('chart.labels', ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September','October','November','December']);
            // bar5.Set('chart.labels.above', true);
            bar5.Set('chart.key', ['Respite', 'Low', 'High', 'Demetia']);
            bar5.Set('chart.key.background', 'rgba(255,255,255,0.7)');
            // bar5.Set('chart.key.position', 'gutter');
            // bar5.Set('chart.key.position.gutter.boxed', false);
            bar5.Set('chart.key.position.y', bar5.Get('chart.gutter.top') - 15);
            bar5.Set('chart.key.border', false);
            // bar5.Set('chart.background.grid.width', 0.3); // Decimals are permitted
            // bar5.Set('chart.text.angle', 90);
            bar5.Set('chart.strokestyle', 'rgba(0,0,0,0)');
            bar5.Set('chart.tooltips.event', 'onmousemove');

            if (!RGraph.isIE8()) {
                bar5.Set('chart.tooltips', [
                                      'Richard', 'Barbara', 'Johnny', 'Frederick',
                                      'Richard', 'Barbara', 'Johnny', 'Frederick',
                                      'Richard', 'Barbara', 'Johnny', 'Frederick',
                                      'Richard', 'Barbara', 'Johnny', 'Frederick',
                                      'Richard', 'Barbara', 'Johnny', 'Frederick',
                                      'Richard', 'Barbara', 'Johnny', 'Frederick',
                                      'Richard', 'Barbara', 'Johnny', 'Frederick',
                                      'Richard', 'Barbara', 'Johnny', 'Frederick',
                                      'Richard', 'Barbara', 'Johnny', 'Frederick',
                                      'Richard', 'Barbara', 'Johnny', 'Frederick',
                                      'Richard', 'Barbara', 'Johnny', 'Frederick',
                                      'Richard', 'Barbara', 'Johnny', 'Frederick'
                                     ]);
            }

            bar5.Draw();
        }
    </script>
" ) ;
    }

}
