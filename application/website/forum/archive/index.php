<?php

    class xs_action_instance extends xs_Action_Webpage {

        // Local shortcut to the Topic Maps database
        private $db = false ;

        private $news = null ;

        public $page = array (
            'title' => "News"
        ) ;

        function __construct () {
            parent::__construct() ;
            // create a quick short-cut to the Topic Map object
            $this->db = $this->glob->tm ;
        }

        // The news archive section's action starts here
        function ___action () {

            // first, get all news items (for sorting and such)
            $all = $this->db->query ( array (
                'select'    => 'id,type1,label,m_p_date,m_p_who',
                'type'      => __NEWS,
                'sort_by'   => 'm_p_date DESC',
                'return'    => 'topics'
            ) ) ;

            $all_dates = array () ;

            foreach ( $all as $idx => $item )
                if ( isset ( $item['m_p_date'] ) )
                    $all_dates[$item['id']] = $item['m_p_date'] ;





            // Get the view facets of the archive
            $year  = $this->glob->breakdown->id ;
            $month = $this->glob->breakdown->selector ;
            $week  = $this->glob->breakdown->specific ;
            $day   = $this->glob->breakdown->atom ;
            
            
            $view = 'all' ;
            $path = array ( 'news' => 'News', 'news/archive' => 'Archive' ) ;

            $from = date( XS_DATE, mktime ( 0, 0, 0, 1, 1, 2000 ) ) ;

            // observe DATE limit!! When PHP (and your computer system) support yearly
            // numbers over 2030, change this value to something nice and high!!!!!!!!
            $to   = date( XS_DATE, mktime ( 0, 0, 0, 0, 0, 2030 ) ) ;
            
            
            if ( $year != '' ) {

                // yearly view
                $view = 'year' ;

                $from = date( XS_DATE, mktime ( 0, 0, 0, 1, 1, $year ) ) ;
                $to   = date( XS_DATE, mktime ( 0, 0, 0, 13, 0, $year ) ) ;

                // Let's create an array that will be our breadcrumb
                $path['news/archive/'.$year] = $year ;

            }            
            
            if ( $month != '' ) {

                // monthly view
                $view = 'month' ;

                $from = date( XS_DATE, mktime ( 0, 0, 0, $month, 1, $year ) ) ;
                $to   = date( XS_DATE, mktime ( 0, 0, 0, $month + 1, 0, $year ) ) ;

                // Let's create an array that will be our breadcrumb
                $path["news/archive/$year/$month"] = $month ;

            }            
            
            if ( $week != '' ) {

                // weekly view
                $view = 'week' ;

                $from = date( XS_DATE, mktime ( 0, 0, 0, $month, 1, $year ) ) ;
                $to   = date( XS_DATE, mktime ( 0, 0, 0, $month + 1, 0, $year ) ) ;

                // Let's create an array that will be our breadcrumb
                $path["news/archive/$year/$month/$week"] = $week ;

            }            
            
            if ( $day != '' ) {

                // daily view
                $view = 'day' ;

                $from = date( XS_DATE, mktime ( 0, 0, 0, $month, $day, $year ) ) ;
                $to   = date( XS_DATE, mktime ( 23, 59, 59, $month, $day, $year ) ) ;

                // Let's create an array that will be our breadcrumb
                $path["news/archive/$year/$month/$day"] = $day ;

            }            
            
            $this->glob->stack->add ( 'xs_facets', $path  ) ;


            $result = $this->db->query ( array (
                'select'    => 'id,type1,label,m_p_date,m_p_who',
                'type'      => __NEWS,
                'sort_by'   => 'm_p_date DESC',
                'between'   => "m_p_date BETWEEN CAST('$from' as DATETIME) AND CAST('$to' as DATETIME)",
                'return'    => 'topics'
            ) ) ;


            $index = array () ;

            foreach ( $result as $idx => $item ) {
                if ( isset ( $item['m_p_date'] ) ) {
                    $d = new DateTime( $item['m_p_date'] ) ;
                    switch ( $view ) {
                        case 'all':   $index[$d->format('Y')] = $item['label'] ; break ;
                        case 'year':  $index[$d->format('Y')][$d->format('m')][$d->format('W')][$d->format('d')] = $item['label'] ; break ;
                        case 'month': $index[$d->format('Y')][$d->format('m')][$d->format('W')][$d->format('d')][$item['id']] = $item['label'] ; break ;
                        case 'week':  $index[$d->format('Y')][$d->format('m')][$d->format('W')][$d->format('d')][$item['id']] = $item['label'] ; break ;
                        case 'day':   $index[$d->format('Y')][$d->format('m')][$d->format('W')][$d->format('d')][$item['id']] = $item['label'] ; break ;
                    }
                    
                }
            }

            switch ( $view ) {
                case 'week':
                case 'day':
                    $this->glob->stack->add ( 'xs_news', $result ) ;
                    break ;
                default: break ;
            }

            $this->log ( 'READ', '['.$this->glob->request->q.']' ) ;
            $this->glob->stack->add ( 'xs_news_archive', $index ) ;

        }

    }
