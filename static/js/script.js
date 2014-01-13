$(function(){

        
        // $('#lut').bind ( 'click', function () {
            $.getJSON( xs_dir.home + '/api/data/phonebook', function ( data ) {
                $("#lut").autoSuggest( data.items, {
                    startText:"People, phones, places",
                    selectedItemProp: "name",
                    searchObjProps: "name,location,phone",
                    neverSubmit: true
                }) ;
                // setTimeout ( "$('#lut>.as-input').click()", 300 ) ;
            }) ;
        // }) ;
        


        // find all the input elements with title attributes
        $('input[title!=""]').hint();

        // When using JS enabled tabs
        $('#tabs-disabled').tabs();

        // Dialog
        $('#dialog').dialog({autoOpen: false, width: 600, buttons: {
            "Ok": function() {$(this).dialog("close");},
            "Cancel": function() {$(this).dialog("close");}
        }});

        // Dialog Link
        $('#dialog_link').click(function(){$('#dialog').dialog('open');return false;});

        // Datepicker
        $('#datepicker').datepicker({inline: true});

        // Find and bind some widgets to our widget plugin
        $('li.widget').xs_widgets();

        // If any widgets are around, bind them to us
        xs_widgets.make_sortable() ;

        // If any admin widgets are around, bind them, too!
        xs_widgets.make_sortable_admin() ;
        
});

$(function(){
        $('#adm-ctrl-login').toggle( function () {
            $('#login-box').slideDown('fast') ;
            $(this).text('Hide login <<') ;
        }, function () {
            $('#login-box').slideUp('fast') ;
            $(this).text('Login >>') ;
        }) ;

        $('#xs-ctrl-msg').toggle( function ( ev ) {
            $(this).text('Hide <<') ;
            $('#xs-msg').slideDown('fast') ;
        }, function () {
            $(this).text('Show >>') ;
            $('#xs-msg').slideUp('fast') ;
        }) ;
        
        $('#adm-ctrl-page').toggle( function ( ev ) {
            $(this).text('Hide page admin <<') ;
            $('#admin-page').slideDown('fast') ;
        }, function () {
            $(this).text('Show page admin >>') ;
            $('#admin-page').slideUp('fast') ;
        }) ;

        $('#adm-ctrl-access').toggle( function ( ev ) {
            redraw_access () ;
            $(this).text('Hide access admin <<') ;
            $('#admin-access').slideDown('fast') ;
        }, function () {
            $(this).text('Show access admin >>') ;
            $('#admin-access').slideUp('fast') ;
        }) ;

        $('#adm-ctrl-widgets').toggle( function ( ev ) {
            $(this).text('Hide widget manager <<') ;
            $('#admin-widgets').slideDown('fast') ;
        }, function () {
            $(this).text('Show widget manager >>') ;
            $('#admin-widgets').slideUp('fast') ;
        }) ;
        
        $('.show-hide').css({
            "color": "blue",
            "font-style": "underline",
            "font-size": "0.9em",
            "cursor": "pointer"
        }) ;
        $('.show-hide .label-div').toggle( function ( ev ) {
            $(this).text('Hide <<') ;
            $(this).parent().find('.content-div').slideDown('fast') ;
        }, function () {
            $(this).text('Show >>') ;
            $(this).parent().find('.content-div').slideUp('fast') ;
        }) ;
        
        
    
        
});

$(function(){

        $.extend($.gritter.options, {
            position: 'top-right', // possibilities: bottom-left, bottom-right, top-left, top-right
                fade_in_speed: 400, // how fast notifications fade in (string or int)
                fade_out_speed: 400, // how fast the notices fade out
                time: 4000 // hang on the screen for...
        });

        // Deal with incoming alerts and warnings, pop them into the JQuery Gritter plugin
        var timer = 0 ;

        for ( mtype in xs_alerts ) {
            for ( msg in xs_alerts[mtype] ) {

                timer += 3000 ;
                var sticky = false ;
                var d = xs_alerts[mtype][msg] ;
                if ( mtype == 'error' ) sticky = true ;
                t = d[0] ; m = d[1] ;
                if ( t ) $.gritter.add({title: t, text: m, time: timer, sticky:sticky, class_name:'alert-' + mtype});
            }
        }
        
        $( "#dialog-form-new-page" ).dialog({
            autoOpen: false, height: 380, width: 650, modal: true,
            buttons: {
                "Create page!": function() {
                    var b = $('#new-page-input-slug').val() ;
                    var uri = $('#dialog-form-new-page form').attr('title') ;
                    $('#dialog-form-new-page form').attr('action', uri + b ) ;
                    // alert($('#dialog-form-new-page form').attr('action'));
                    $( this ).dialog( "close" ); 
                    $('#dialog-form-new-page form').submit() ;
                },
                Cancel: function() { $( this ).dialog( "close" ); }
            },
            close: function() { $( this ).dialog( "close" ); }
         });
        $('#new-page-input').on ( 'input', function() {
            $('#new-page-input-slug').val( xs_dir.q + '/' + string_to_slug(this.value) ) ;
        }) ;
        // damn you, IE8!!!!
        $('#new-page-input').on ( 'propertychange', function() {
            $('#new-page-input-slug').val( xs_dir.q + '/' + string_to_slug(this.value) ) ;
        }) ;
        $('#new-page-input-slug').val( xs_dir.q + '/' ) ;        
        
});


(function ($) {

    $.fn.hint = function (blurClass) {
      if (!blurClass) {
        blurClass = 'blur';
      }

      return this.each(function () {
        // get jQuery version of 'this'
        var $input = $(this),

        // capture the rest of the variable to allow for reuse
          title = $input.attr('title'),
          $form = $(this.form),
          $win = $(window);

        function remove() {
          if ($input.val() === title && $input.hasClass(blurClass)) {
            $input.val('').removeClass(blurClass);
          }
        }

        // only apply logic if the element has the attribute
        if (title) {
          // on blur, set value to title attr if text is blank
          $input.blur(function () {
            if (this.value === '') {
              $input.val(title).addClass(blurClass);
            }
          }).focus(remove).blur(); // now change all inputs to title

          // clear the pre-defined text when form is submitted
          $form.submit(remove);
          $win.unload(remove); // handles Firefox's autocomplete
        }
      });
    };

})(jQuery);


function go_menu ( inp ) {
    var menu = '#'+inp+'-menu' ;
    var tab = '#' + inp ;
    $(menu).menu();
    $(tab).on('mouseenter', function () { 
        $(menu).slideDown('fast'); 
        $(document).click(function (e) { 
            $(menu).slideUp('fast'); 
            $(document).off('click');
        });
    }).on('mouseleave', function() {
        $(menu).slideUp('fast'); 
    }) ;
}

function create_new_page () {
    $( "#dialog-form-new-page" ).dialog( "open" );
}

function days_ago ( when ) {
    var start = new Date() ;
    var end = new Date( when ) ;
    var diff = new Date(end - start);
    return ( Math.floor ( diff/1000/60/60/24 ) ) ;
}

function color_selector ( item, tag, color ) {
    // alert(color);
    var classy = 'color-' + color ;
    var cl = $(item).parents('li.widget') ;
    cl.removeClass('color-red color-blue color-green color-yellow color-white color-orange color-trans color-cyan color-pink color-grey color-burgundy');
    cl.addClass(classy);
    $(item).parent().parent().find('input').attr('value',classy);
    $(item).parent().find('li').removeClass('color-select');
    var nn = $(item).attr('class') ;
    $('#'+tag).attr('value', nn );
    $(item).addClass('color-select');
}

function dia_del ( id, type ) {
    var t = '' ;
    if ( type == 0 ) t = 'news';
    else if ( type == 1 ) t = 'forum';
    uri = xs_dir.home + '/'+t+'/'+id+'?_method=DELETE&_redirect='+xs_dir.home+'/'+t ;
    // alert(uri);
    // return ;
   $('#dialog-confirm-' + id).dialog( {
      resizable:false, height:170, modal:true,
      buttons: {
         'Delete item': function() {window.location = uri},
         'Cancel': function() {$( this ).dialog( 'close' );}
      }
   });
}

function xs_generic_content ( id ) {
 
    var $_id = id+"-edit" ;
    var content_id = "#"+id+"-content" ;
    var $_content = $(content_id).html() ;
    
    // $( "#xs-generic-content" ).html($_content);
    
    $( "#xs-generic-content" ).dialog({
            resizable: true, width:'90%', height:600,
            modal: true, show: 'fade', hide: 'fade',
            buttons: {
                "Save": function() {
                    // var t = $("#"+$_id+"_ifr").contents().find("body").html();
                    c = tinyMCE.get($_id).getContent() ;
                    // alert ( c ) ;
                    $(content_id).html(c);
                    
                    $.ajax( {
                        type: "POST",
                        url: xs_dir.home + '/_api/resources/content',
                        data: { 'name': id, 'only_content': true, 'content': c },
                        success: function( response ) { 
                            $(content_id).html ( response ) ;
                            // alert(xs_dir.home + '/_api/resources/content');
                        },
                        fail: function ( response ) { $(content_id).html ( 'Hmm. Something went wrong.' ) ; }
                    } );     
                    
                    
                    
                    $( this ).dialog( "close" );
                },
                Cancel: function() {
                    $( this ).dialog( "close" );
                }
            }
        })
        .html("<div id='"+$_id+"'>"+$_content+"</div>" );
        
        wysiwyg ( '#'+$_id, null, 380 ) ;
}

    function wysiwyg ( id, w, h ) {
        
        tinymce.init({
            selector: id,
            width: w,
            height: h,
            //  inline: true,
            plugins: [
                "advlist autolink lists link image charmap print preview anchor",
                "searchreplace visualblocks code fullscreen",
                "insertdatetime media table contextmenu paste"
            ],
            toolbar: "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | xSiteable",
            setup: function(editor) {
                editor.addButton('xSiteable', {
                    text: 'List sub-pages',
                    icon: false,
                    onclick: function() {
                        editor.insertContent('{list:sub-pages}');
                    }
                });
            }            
        }) ;
    }

        function xs_delete_from ( find ) {
            var names = [] ;
            $(find+" input:checked").each(function() { names.push(this.id); });
            $('#dialog-confirm-delete-all').dialog( {
                resizable:false, height:210, modal:true,
                buttons: {
                    'Delete item(s)': function() { postIt ( xs_dir._this, names ) ; },
                    'Cancel': function() {$( this ).dialog( 'close' );}
                }
            });
        }

        function postIt(url, data){
            $('body').append($('<form />', {
                id: 'jQueryPostItForm',
                method: 'POST',
                action: url
            }));
            for(var i in data){
                $('#jQueryPostItForm').append($('<input />', {
                    type: 'hidden', name: 'f:' + i, value: data[i]
                }));
            }
            $('#jQueryPostItForm').append($('<input />', { type: 'hidden', name: '_action', value: '_delete' }));
            $('#jQueryPostItForm').append($('<input />', { type: 'hidden', name: '_type', value: $_type }));
            $('#jQueryPostItForm').append($('<input />', { type: 'hidden', name: '_name', value: $_name }));
            $('#jQueryPostItForm').append($('<input />', { type: 'hidden', name: '_parent', value: $_parent }));
            $('#jQueryPostItForm').submit();
            // alert ( $('#jQueryPostItForm').html() ) ;
        }
        
function ajax_flip ( prop, id ) {
    // var state
    dir = xs_dir.home + '/_api/data/topic/prop' + '?id=' + id + '&state=' + state ;
    
}

function ajax_flip_controlled ( uid, type ) {
    var e = '' ;
    
    if ( type == 1 ) {
        
        
        // <img src="{$dir/images}/icons/24x24/actions/knewstuff.png" height="15" style="margin:0;padding:0;" />
        // <img src="{$dir/images}/icons/24x24/actions/advanced.png" height="15" style="margin:0;padding:0;" />
        
        
        // state = '' + $('#controlled-'+uid+' input').is(':checked') + '' ;
        state = '' ;
        dir = xs_dir.home + '/_api/module/docs/controlled' + '?uid=' + uid + '&state=' + state + '&type=' + type ;
        // if ( state == 'true' ) e = 'checked="checked"' ;
        // $('#controlled-'+uid).html('<input type="checkbox" '+e+' />');
        $.get( dir, function(data) {
            $('#controlledi-'+uid).html(data);
        });
        return ;
    }
    
    state = '' + $('#controlled-'+uid+' input').is(':checked') + '' ;
    dir = xs_dir.home + '/_api/module/docs/controlled' + '?uid=' + uid + '&state=' + state + '&type=' + type ;

    if ( state == 'true' ) e = 'checked="checked"' ;
    $('#controlled-'+uid).html('<input type="checkbox" '+e+' />');
    $.get( dir, function(data) {
      $('#controlled-'+uid).html(data);
    });
}

function ajax_wait () {
    return '<img src="' + xs_dir.images + '/ajax-loader-2.gif" width="40" alt="please wait" /> ' ;
}

function ajax_get ( uri, id, param, name ) {
    _dir = xs_dir.home + '/' + uri + '?id=' + id + '&name=' + name + '&' + param  ;
    // alert ( _dir ) ;
    $('#'+id).html( ajax_wait () );
    $.get( _dir, function(data) {
      $('#'+id).html(data);
    });
}

function ajax_register ( id ) {
   $('#'+id).click();
}

function menu ( who ) {
    $(who).parent().parent().find('a').removeClass('selected') ;
    $(who).addClass('selected') ;
}


// Generic functions of various kinds
function xs_search () {

    var q1 = $("#query").val() ;
    var q2 = $("#mainquery").val() ;
    if ( q1 == undefined ) q1 = '' ;
    if ( q2 == undefined ) q2 = '' ;

    var value = q1 + q2 ;

    var url = escape ( $("#redirect").val() + value ) ;

    xs_redirect ( url, null ) ;

}

function xs_redirect ( url, id ) {

    if ( id != null )
        $('#'+id).attr('src',url);
    else
        document.location = url ;

}

$('#query').keypress(function(event) {
  if (event.keyCode == '13') {
     xs_search() ;
   }
});
$('#mainquery').keypress(function(event) {
  if (event.keyCode == '13') {
     xs_search() ;
   }
});

function setIframeHeight(id, h) {
    if ( document.getElementById ) {
        var theIframe = document.getElementById(id);
        if (theIframe) {
            dw_Viewport.getWinHeight();
            var t = findPos(theIframe)[1] ;
            theIframe.style.height = Math.round (  ( dw_Viewport.height - t - 40 ) ) + "px";
            // theIframe.style.marginTop = Math.round( (dw_Viewport.height - parseInt(theIframe.style.height) )/2 ) + "px";
        }
    }
}

function findPos ( obj ) {
    var curleft = curtop = 0;
    if (obj.offsetParent) {
        do {
            curleft += obj.offsetLeft;
            curtop += obj.offsetTop;
        } while (obj = obj.offsetParent);
    }
    return [curleft,curtop];
}

function debug ( obj ) {str='';for(prop in obj) {str+="["+prop + "]='"+ obj[prop]+"'\n";}return(str);}

function feedbackAction ( url ) {
    $.get ( url, $("#issues").serialize(), function(data){
        $('#ajax_feedback_after').show ( 'fast' ) ;
        // $('#ddebug').html( "<pre>" + data + "</pre>" ) ;
        setTimeout ( "$('#ajax_feedback_after').hide ( 'fast' );", 5000 ) ;
        setTimeout ( "$('#ajax_feedback_before').show ( 'slow' );", 5000 ) ;
        setTimeout ( "$('#issues').hide ( 'slow' );", 5000 ) ;
    }) ;

}

var _tmplCache = {}
this.parseTemplate = function(str, data) {
    /// <summary>
    /// Client side template parser that uses &lt;#= #&gt; and &lt;# code #&gt; expressions.
    /// and # # code blocks for template expansion.
    /// NOTE: chokes on single quotes in the document in some situations
    ///       use &amp;rsquo; for literals in text and avoid any single quote
    ///       attribute delimiters.
    /// </summary>    
    /// <param name="str" type="string">The text of the template to expand</param>    
    /// <param name="data" type="var">
    /// Any data that is to be merged. Pass an object and
    /// that object's properties are visible as variables.
    /// </param>    
    /// <returns type="string" />  
    var err = "";
    try {
        var func = _tmplCache[str];
        if (!func) {
            var strFunc =
            "var p=[],print=function(){p.push.apply(p,arguments);};" +
                        "with(obj){p.push('" +
            //                        str
            //                  .replace(/[\r\t\n]/g, " ")
            //                  .split("<#").join("\t")
            //                  .replace(/((^|#>)[^\t]*)'/g, "$1\r")
            //                  .replace(/\t=(.*?)#>/g, "',$1,'")
            //                  .split("\t").join("');")
            //                  .split("#>").join("p.push('")
            //                  .split("\r").join("\\'") + "');}return p.join('');";

            str.replace(/[\r\t\n]/g, " ")
               .replace(/'(?=[^#]*#>)/g, "\t")
               .split("'").join("\\'")
               .split("\t").join("'")
               .replace(/\{=(.+?)\}/g, "',$1,'")
               .split("\{").join("');")
               .split("\}").join("p.push('")
               + "');}return p.join('');";

            //alert(strFunc);
            func = new Function("obj", strFunc);
            _tmplCache[str] = func;
        }
        return func(data);
    } catch (e) { err = e.message; }
    return "< # ERROR: " + err.htmlEncode() + " # >";
}

var fixHelper = function(e, ui) {
        ui.children().each(function() {
                $(this).width($(this).width());
        });
        return ui;
};

function xs_tag_add ( tag, type ) {
    // alert ( xs_dir.home + '/_api/module/tags/factory' ) ;
    $.ajax( {
        type: "POST",
        url: xs_dir.home + '/_api/module/tags/factory',
        data: { 'tag': tag, 'type': type },
        success: function( response ) { 
            $('#messages').html ( response ) ;
            // $('#messages').css ( 'background-color', '#cfc' );
            // t = 
            // $('#messages').css ( 'top', '#cfc' );
            $('#messages').fadeIn('fast');
            setTimeout ( function () { $('#messages').fadeOut('slow'); }, 4000 ) ;
        },
        fail: function ( response ) { $('#messages').html ( 'Hmm. Something went wrong.' ) ; }
    } );     
}

function string_to_slug (str) {
  str = str.replace(/^\s+|\s+$/g, ''); // trim
  str = str.toLowerCase();
  
  // remove accents, swap ñ for n, etc
  var from = "àáäâèéëêìíïîòóöôùúüûñç·/_,:;";
  var to   = "aaaaeeeeiiiioooouuuunc------";
  for (var i=0, l=from.length ; i<l ; i++) {
    str = str.replace(new RegExp(from.charAt(i), 'g'), to.charAt(i));
  }

  str = str.replace(/[^a-z0-9 -]/g, '') // remove invalid chars
    .replace(/\s+/g, '-') // collapse whitespace and replace by -
    .replace(/-+/g, '-'); // collapse dashes

  return str;
}

var slug = function(Text) {
    return Text
        .toLowerCase()
        .replace("/ /g",'-')
        .replace("/[^\w-]+/g",'') ;
};

// self-calling lambda to for jQuery shorthand "$" namespace
(function($){
    // document onReady wrapper
    $().ready(function(){
        // check for the nefarious IE
        if($.browser.msie) {
            // capture the file input fields
            var fileInput = $('input[type="file"]');
            // add presentational <span> tags "underneath" all file input fields for styling
            fileInput.after(
                $(document.createElement('span')).addClass('file-underlay')
            );
            // bind onClick to get the file-path and update the style <div>
            fileInput.click(function(){
                // need to capture $(this) because setTimeout() is on the
                // Window keyword 'this' changes context in it
                var fileContext = $(this);
                // capture the timer as well as set setTimeout()
                // we use setTimeout() because IE pauses timers when a file dialog opens
                // in this manner we give ourselves a "pseudo-onChange" handler
                var ieBugTimeout = setTimeout(function(){
                    // set vars
                    var filePath     = fileContext.val(),
                        fileUnderlay = fileContext.siblings('.file-underlay');
                    // check for IE's lovely security speil
                    if(filePath.match(/fakepath/)) {
                        // update the file-path text using case-insensitive regex
                        filePath = filePath.replace(/C:\\fakepath\\/i, '');
                    }
                    // update the text in the file-underlay <span>
                    fileUnderlay.text(filePath);
                    // clear the timer var
                    clearTimeout(ieBugTimeout);
                }, 10);
            });
        }
    });
})(jQuery);

function unslug(slug) {
    slug = slug.replace(/\.[^/.]+$/, "");
    var words = slug.split(/[\s-_]+/);

    for(var i = 0; i < words.length; i++) {
      var word = words[i];
      words[i] = word.charAt(0).toUpperCase() + word.slice(1);
    }

    return words.join(' ');
}