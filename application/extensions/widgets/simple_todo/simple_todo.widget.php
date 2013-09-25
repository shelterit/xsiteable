<?php
/*
 * An example widget that manages a todo list for a user
 *
 * Several things to note, but most widget classes are not instances, but
 * should be treated as if they are controllers; a widget could potentially
 * spit out several versions of itself, but with different id's, so for
 * example a user can have several todo lists, or a project can have several
 * lists for different things.
 *
 * Properties for widgets are loaded in the following order ;
 *
 *  - Controllers (this class) default properties
 *  - Widget view's default properties
 *  - widget instance's overriding properties
 *
 * All widgets have one default view, but can have as many as you like.
 * 
 */
   
class xs_widget_simple_todo extends xs_Action_Widget_Controller {

    // Generic metadata
    public $meta = array(
        'name' => 'Simple todo widget',
        'description' => 'Simple todo widget for all your todoing',
        'version' => '1.0',
        'author' => 'Alexander Johannesen',
        'author_link' => 'http://shelter.nu/',
        'category' => 'tool',
    );

    // Widget settings (default values, might be manually overridden)
    // $settings denote the appearance of the widget, the outer skin, if you like
    public $settings = array (
        'title' => 'Todo!',
        'style' => 'min-height:300px;',
        'class' => array ( 'color-orange', '' ),
    ) ;

    // Widget properties (default values, might be manually overridden)
    // Properties are any data variable the widget might use.
    public $properties = array (
        'state_context' => array (
            'title' => 'State context?',
            'type' => 'drop-down',
            'default' => '',
            'options' => array ( 'none' => 'None', 'class' => 'Widget class', 'instance' => 'Widget instance', 'user' => 'User' )
        ),
        'something' => array ( 'title' => 'Something?', 'default' => 'Fishing' ),

        'other_state' => "State context?|none|[none=None,class=Class,instance=Instance,user=User]"

    ) ;

    private $db = null ;

    /*
    public $properties_options = array (
        'context' => array (
            'user' => 'List is tied to user', 
            'template' => 'List is tied to the current template',
            'template_user' => 'List is tied to current template and user', 
            'page_type' => 'List is tied to the type of page', 
            'page_type_user' => 'List is tied to type of page and user'
        ) 
    ) ;
     */

    // XS_WIDGETS (or ___widgets) is the main point at which widgets
    // specifically should initialize themselves.

    function ___widgets () {
        // Define a couple of constants that deal with todo lists
        // The numbers (500,501) are chosen at random, between 100 and 999
        // and hopefully won't crash with something else. There's a future
        // API that will properly deal with this, one would think.
        define ( 'XS_WIDGET_TODO', 500 ) ;
        define ( 'XS_WIDGET_TODO_ITEM', 501 ) ;

        // $this->_properties->fish = new xs_Properties () ;

        // Setup how the state (or properties) of this widget should be handled
        // in prioritized order. First we look for class properties, then
        // instance properties, and last any user specific properties. For
        // performance, give as few of these as possible. For example, a
        // static widget would have none of them, which is the default setting.

        /* note: the widget_class is specified as file, which is the default,
         * so both it and the widget_instance properties are saved and loaded
         * from a file, but the user specific properties will be stored in the
         * default database. Specify a different database as 'db:whatsit_plugin'
         */
        
        $this->_setup_state ( array (
            'widget_class' => 'file',
            'widget_instance',
            'user' => 'db'
        ) ) ;

    }


    // Let's hook up to an event that only exists if this widget is a) active
    // and b) about to be displayed

    // the short version for that is ___this () which is an alias for ___widget_[name-of-widget]_active ()

    function ___this () {



    }

    // Deal with various bits of input action (remember, we have a resource
    // automatically given us, which is /_api/widgets/[name_of_widget]

    function ___action () {

        /* The $request object has a method call for each property it stores,
         * and you call it to get the property value, but if you wcant a
         * default value in case the property doesn't exist, you pass that
         * one in.  */
        
        // Incoming action
        $action = $this->glob->request->action ( '' ) ;

        // Incoming id
        $id     = $this->glob->request->id ( 'default' ) ;



        // $this->load ( $id ) ;

        // echo "[".$this->glob->request->method()." = '$action' = '$id'] " ;
        // print_r ( $this->glob->request->__getArray() ) ;


        switch ( $action ) {

            case 'new' :
                $new_id = '' ;
                echo $this->item ( $new_id ) ;

                // mysql_query("INSERT INTO tz_todo SET text='".$text."', position = ".$position);

                break ;
                
            case 'edit' :

// mysql_query("   UPDATE tz_todo
//                    SET text='".$text."'
//                    WHERE id=".$id
//                );

                break ;

            case 'delete' :

                // mysql_query("DELETE FROM tz_todo WHERE id=".$id);

                break ;

            case 'rearrange' :
/*
    $updateVals = array();
    foreach($key_value as $k=>$v)
    {
        $strVals[] = 'WHEN '.(int)$v.' THEN '.((int)$k+1).PHP_EOL;
    }
    if(!$strVals) throw new Exception("No data!");
    mysql_query("   UPDATE tz_todo SET position = CASE id
                    ".join($strVals)."
                    ELSE position
                    END");


 */



                break ;

            default :
                break ;
        }

    }

    // Creates a new todo item
    function item ( $id, $text = 'new item' ) {
        return '<li id="todo-'.$id.'" class="todo">
                    <div class="text">'.$text.'</div>
                    <div class="actions"><a href="#" class="edit">Edit</a><a href="#" class="delete">Delete</a></div>
		</li>' ;
    }

    // Test
    function ___on_document_view () {
        // echo "triggered event " ;
    }

    // Default output
    function GET_content () {
        return $this->prepare (
           '<span id="todo-list-">
               <ul class="todoList">' .

                 $this->item ( '1010', 'one' ) .
                 $this->item ( '1015', 'two' ) .
                 $this->item ( '1017', 'three' ) .
                 $this->item ( '1012', 'four' ) .
                 $this->item ( '1013', 'five' ) .

               '</ul> 
                <a class="green-button" href="#">Add a ToDo</a>
            </span>' ) ;
    }


    
}
