<?php

class xs_action_instance extends \xs\Action\Generic {

    public $metadata = array(
        'name' => "en:Admin",
        'template' => NONE,
        'content-type' => 'text/html'
    );

    private $status = '501' ;
    private $header = null ;
    private $body = null ;

    function setup ( $param, $default = null ) {
        return isset ( $_REQUEST["param:$param"] ) ? unserialize ( $_REQUEST["param:$param"] ) : $default ;
    }

    function ___action () {

        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: text/html');

        $test = array (
            'policy' => 225,
            'distribution' => 164,
            'author' => 161,
            'controlled' => 144,
            'executive' => 143,
            'procedure' => 141,
            'officer' => 109,
            'page' => 108,
            'health' => 107,
            'resident' => 103,
            'signing' => 100,
        ) ;


        // echo "[".serialize ( $test )."] " ;

        // $list = @unserialize ( $_REQUEST['param:list'] ) ;
        
        $list = $this->setup ( 'list', null ) ;
        $max  = $this->setup ('max', '10' ) ;
        $base = $this->setup ('base', '/' ) ;

        if ( !is_array ( $list ) || count ( $list ) < 1 ) {
            echo "No input list of words." ;
        } else
            echo $this->createTagCloud ( $list, $max, $base ) ;
        
        die() ;
    }

    function createTagCloud($tags = null, $max = -1, $base = '/' ) {

        //I pass through an array of tags
        $i=0; $html = '' ;

        if ( $tags == null ) return ;

        // print_r ( $tags ) ;

        if ( is_array ( $tags ) && count ( $tags ) > 0 ) {
            // Truncate array 
            if ($max != -1) {
                $tags = array_slice($tags, 0, $max);
            }

            //get greatest occurance
            $z = max($tags);

            //sort tags into alphabetical order
            ksort($tags);
	

            foreach($tags as $item => $count )
            {
                $output[$i]['tag'] = $item;
                $output[$i]['num'] = $count;
                $i++ ;
                if ( $max != -1 && $i > $max )
                    break ;
            }


            // print_r ( $output ) ;
            // print_r ( $z ) ;

            $total_tuts = $z ;

            if ( $total_tuts < 1 ) $total_tuts = 1 ;

            //ugh, XHTML in PHP?  Slap my hands - this isn't best practice, but I was obviously feeling lazy
            $html = ' <ul class="tagcloud">';

            //iterate through each item in the $output array (created above)
            foreach($output as $tag)
            {
                //get the number-of-tag-occurances as a percentage of the overall number
                $ratio = ( 100 / $total_tuts) * $tag['num'];

                //round the number to the nearest 10
                $ratio =  round($ratio,-1);

                /*append that classname onto the list-item, so if the result was 20%, it comes out as cloud-20*/
                $html.= '<li class="cloud-'.$ratio.'"><a href="'.$base.'/'.$tag['tag'].'">'.$tag['tag'].'</a></li> ';
            }

            //close the UL
            $html.= '</ul> ';
        }

        return $html;
    }
}
