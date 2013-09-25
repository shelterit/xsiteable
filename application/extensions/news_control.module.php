<?php

    class xs_module_news_control extends xs_Action {
        
        public $meta = array (
            'name' => 'News control module',
            'description' => 'News control',
            'version' => '1.0',
            'author' => 'Alexander Johannesen',
            'author_link' => 'http://shelter.nu/',
            'editable_options' => true,
        ) ;

        function ___modules () {

            if ( ! defined ( '__NEWS' )  )
                define ( '__NEWS', 0 ) ;

            if ( ! defined ( '__COMMENT' ) )
                define ( '__COMMENT', 0 ) ;

            $this->glob->data->register_query (

               // use the default xs (xSiteable) datasource
               'xs',

               // identifier for our query
               'news-top-20',

               // the query in question (passing in an array sends the query to
               // the Topic Maps engine (that builds its own SQL) rather than
               // a generic SQL

               array (
                'select'      => 'id,type1,label,m_p_date,m_p_who,m_u_date,m_u_who',
                'type'        => __NEWS,
                'sort_by'     => 'm_c_date DESC',
                'limit'       => 20,
                'lookup_name' => 'm_p_who,m_u_who',
                'count'       => array ( 'what' => 'sub_topics', 'type' => __COMMENT )
                // 'return'      => 'topics'
               ),

               // the timespan of caching the result
               '+1 hour'
            ) ;
        }

    }
