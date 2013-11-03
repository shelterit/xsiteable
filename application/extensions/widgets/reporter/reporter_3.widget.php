<?php
   
class xs_widget_reporter_3 extends \xs\Action\WidgetController {

    public $meta = array(
        'name' => 'Reporter widget 3',
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

    function xx___gui_jsx () {
        return ( '
            <script src="{$dir/js}/jquery.jqplot.min.js" ></script>
            <script src="{$dir/js}/jqplot.barRenderer.min.js" ></script>
            <script src="{$dir/js}/jqplot.categoryAxisRenderer.min.js" ></script>
            <script src="{$dir/js}/jqplot.pointLabels.min.js" ></script>
            <script src="{$dir/js}/jqplot.pieRenderer.min.js" ></script>
        ' ) ;
    }

    function GET_content () {
        return $this->GET_content2 () ;
    }

    function GET_content1 () {
        return $this->prepare ( "

    <span id='info3' style='display:none'>nil</span>

 <div id='chart3-controls' style='width:100%;'>
    <select>
       <option name='a'> - Serious House</option>
    </select>
    <select>
       <option name='a'>By days</option>
       <option name='a'>By weeks</option>
       <option name='a'>By months</option>
       <option name='a'>By quarters</option>
       <option name='a'>By years</option>
    </select>
 </div>
    
 <div id='chart3' style='width:100%;height:300px;margin:0;padding:0;'></div>

    <script type='text/javascript'>
        window.onload = function () {

  var s1 = [2, 66, 7, 10, 5];
  var s2 = [7, 5, 3, 4, 3];
  var s3 = [14, 9, 3, 8, 8];
  plot3 = $.jqplot('chart3', [s1, s2, s3, s1, s2, s3], {
    // Tell the plot to stack the bars.
    stackSeries: true,
    captureRightClick: true,
    seriesDefaults:{
      renderer:$.jqplot.BarRenderer,
      rendererOptions: {
          // Put a 30 pixel margin between bars.
          barMargin: 30,
          // Highlight bars when mouse button pressed.
          // Disables default highlighting on mouse over.
          highlightMouseDown: true
      },
      pointLabels: {show: true}
    },
    axes: {
      xaxis: {
          renderer: $.jqplot.CategoryAxisRenderer
      },
      yaxis: {
        // Don't pad out the bottom of the data range.  By default,
        // axes scaled as if data extended 10% above and below the
        // actual range to prevent data points right on grid boundaries.
        // Don't want to do that here.
        padMin: 0
      }
    },
    legend: {
      show: false,
      location: 'e',
      placement: 'outside'
    }
  });

  $('#chart3').parent().parent().css('padding', '0') ;
  $('#chart3').bind('jqplotDataClick',
    function (ev, seriesIndex, pointIndex, data) {
      $('#info3').html('series: '+seriesIndex+', point: '+pointIndex+', data: '+data);
    }
  );

        }
    </script>
" ) ;
    }

    function GET_content2 () {
        $rn = rand(1, 100000) ;
        return $this->prepare ( "

 <div id='chart$rn' style='width:100%;height:300px;margin:0;padding:-5px;'></div>
 <div id='chart3-controls' style='width:100%;margin:0;padding:0;'>
    <select>
       <option name='a'> - Silly House</option>
       <option name='a'> - Serious House</option>
    </select>
    <select>
       <option name='a'>By days</option>
       <option name='a'>By weeks</option>
       <option name='a'>By months</option>
       <option name='a'>By quarters</option>
       <option name='a'>By years</option>
    </select>
 </div>
                
    <script type='text/javascript'>
       
          var plot$rn = jQuery.jqplot ('chart$rn',
            [[
                ['High (220)', 220],
                ['High ESS (140)', 140],
                ['Low (300)', 300],
                ['Low ESS (80)', 80],
                ['Free (15)', 5]
            ]],
            {
              seriesDefaults: {
                renderer: jQuery.jqplot.PieRenderer,
                rendererOptions: {
                  showDataLabels: true,
                  dataLabels: 'label'
                }
              }
            }
          );

    </script>
" ) ;
    }

}
