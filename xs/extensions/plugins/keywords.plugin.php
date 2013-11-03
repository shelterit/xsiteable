<?php

/*
 * This plugin offers a simple API to working with keywords and text clouds
 * 
 */

    class xs_plugin_keywords extends \xs\Action\Generic {

        public $meta = array (
            'name' => 'keywords plugin',
            'version' => '1.0',
            'author' => 'Alexander Johannesen',
            'author_link' => 'http://shelter.nu/',
            'editable_options' => true,
        ) ;

        function ___settings () {

            // register an API (resource) for this plugin / service
            $this->_register_resource ( XS_PLUGIN, '_api/services/keywords/cloud' ) ;
        }

        function POST ( $param = array () ) {

            $list = isset ( $param['list'] ) ? $param['list'] : null ;
            $max = isset ( $param['max'] ) ? $param['max'] : null ;
            $base = isset ( $param['base'] ) ? $param['base'] : null ;

            xs_Core::$glob->log->add ( "keywords.plugin: POST start" ) ;

            if ( ! is_array ( $list ) || count ( $list ) < 1 ) {
                return "No input list of words." ;
            } else {
                $ret = $this->createTagCloud ( $list, $max, $base ) ;
                xs_Core::$glob->log->add ( "keywords.plugin: POST end" ) ;
                return $ret ;
            }
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


                foreach($tags as $item => $count ) {
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
                foreach($output as $tag) {

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
