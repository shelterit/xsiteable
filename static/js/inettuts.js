var iNettuts = {
    jQuery : $,
    settings : {
        columns : '.column',
        widgetSelector: '.widget',
        handleSelector: '.widget-head',
        contentSelector: '.widget-content',
        saveToDB: true,
        widgetDefault : {
            movable: true,
            removable: true,
            collapsible: true,
            editable: true,
            configurable: true,
            colorClasses : ['color-yellow', 'color-red', 'color-blue', 'color-white', 'color-orange', 'color-green'],
            content: "<div align='center'><img src='/static/images/ajax-loader-2.gif' border='0' /></div>"
        },
        widgetIndividual: {},
        widgetsConfig: {}
    },

    init : function () {
        this.attachStylesheet(xs_dir.js + '/inettuts.js.css');
        this.addWidgetControls();
        this.makeSortable();
        this.disableTextSelection(this.settings.columns);
    },

    disableTextSelection : function (targets) {
        $(targets).each(function(){
            if ( 'undefined' != typeof this.onselectstart) {
                this.onselectstart = function(){ return false };
            }
        });
    },

    initWidget : function (opt) {
      if (!opt.content) opt.content=iNettuts.settings.widgetDefault.content;
      return '<li id="'+opt.id+'" class="new widget '+opt.color+'"><div class="widget-head"><h3>'+opt.title+'</h3></div><div class="widget-content">'+opt.content+'</div></li>';
    },

    loadWidget : function(id) {
      uri = xs_dir.api+"/widgets/control/render" ;
      $.post(uri, {"id":id},
      function(data){
        $("#"+id+" "+iNettuts.settings.contentSelector).html(data);
      });
    },

    getWidget : function ( where, name ) {
      $("li").removeClass("new");
      var selectorOld = iNettuts.settings.widgetSelector;
      iNettuts.settings.widgetSelector = '.new';
      uri = xs_dir.api+"/widgets/control/render" ;
      $.get(uri, {"name":name}, function(data){
          $(where).append(data) ;
          id = $(data).find('li:last').attr('id') ;
          iNettuts.settings.widgetSelector = selectorOld;
          iNettuts.addWidgetControls(id);
          iNettuts.makeSortable();
          iNettuts.savePreferences();
      });
    },

    getWidgetSettings : function (id) {
        var $ = this.jQuery,
            settings = this.settings;
            ret = ( id && settings.widgetIndividual[id]) ? $.extend ( {}, settings.widgetDefault, settings.widgetIndividual[id] ) : settings.widgetDefault ;
            // alert ( id + ' : ' + settings.widgetIndividual[id] ) ;
            return ret ;
    },

    addWidgetControls : function ( idx ) {
        var iNettuts = this,
            $ = this.jQuery,
            settings = this.settings,
            widgets = null ;

        if ( idx ) widgets = $('#' + idx ) ;
        else       widgets = $(settings.widgetSelector, $(settings.columns)) ;

        widgets.each(function () {
            var thisWidgetSettings = iNettuts.getWidgetSettings(this.id);
            if (thisWidgetSettings.removable) {
                $('<a href="#" class="remove">CLOSE</a>').mousedown(function (e) {
                    e.stopPropagation();
                }).click(function () {
                    if(confirm('This widget will be removed, ok?')) {
                        $(this).parents(settings.widgetSelector).animate({
                            opacity: 0
                        },function () {
                            $(this).wrap('<div/>').parent().slideUp(function () {
                                $(this).remove();
                                iNettuts.savePreferences();
                            });
                            // alert ( 'gone' ) ;
                        });
                    }
                    return false;
                }).appendTo($(settings.handleSelector, this));
            }

            if (thisWidgetSettings.configurable) {
                $('<a href="#" class="config">CONFIGURE</a>').mousedown(function (e) {
                    e.stopPropagation();
                }).toggle(function () {
                    $(this).css({backgroundPosition: '-175px 0', width: '83px'})
                        .parents(settings.widgetSelector)
                        .find('.config-box')
                        .show('blind',{},'fast')
                        .find('input').focus();
                    return false;
                },function () {
                    $(this).css({backgroundPosition: '', width: ''})
                        .parents(settings.widgetSelector)
                            .find('.config-box').hide('blind',{},'fast');
                    return false;
                }).appendTo($(settings.handleSelector,this));
                $('<div class="config-box" style="display:none;"/>')
                    .append('<ul><li class="item"><label>Some config option</label><input class="i1" value="' + $('h3',this).text() + '"/></li>')
                    .append('</ul>')
                    .insertAfter($(settings.handleSelector,this));
            }

            if (thisWidgetSettings.editable) {
                $('<a href="#" class="edit">EDIT</a>').mousedown(function (e) {
                    e.stopPropagation();
                }).toggle(function () {
                    $(this).css({backgroundPosition: '-66px 0', width: '55px'})
                        .parents(settings.widgetSelector)
                        .find('.edit-box')
                        .show('blind',{},'fast')
                        .find('input').focus();
                    return false;
                },function () {
                    $(this).css({backgroundPosition: '', width: ''})
                        .parents(settings.widgetSelector)
                            .find('.edit-box').hide('blind',{},'fast');
                    return false;
                }).appendTo($(settings.handleSelector,this));
                $('<div class="edit-box" style="display:none;"/>')
                    .append('<ul><li class="item"><label>Change the title?</label><input class="i1" value="' + $('h3',this).text() + '"/></li>')
                    .append('<ul><li class="item"><label>Minimum height?</label><input class="i2" value="' + $('.widget-content',this).css('min-height') + '"/></li>')
                    .append((function(){
                        var colorList = '<li class="item"><label>Available colors:</label><ul class="colors">';
                        $(thisWidgetSettings.colorClasses).each(function () {
                            colorList += '<li class="' + this + '"/>';
                        });
                        return colorList + '</ul>';
                    })())
                    .append('</ul>')
                    .insertAfter($(settings.handleSelector,this));
            }

            if (thisWidgetSettings.collapsible) {
                $('<a href="#" class="collapse">COLLAPSE</a>').mousedown(function (e) {
                    e.stopPropagation();
                }).toggle(function () {
                    $(this).css({backgroundPosition: '-38px 0'})
                        .parents(settings.widgetSelector)
                            .find(settings.contentSelector).hide('blind', {}, 'fast');
                    return false;
                },function () {
                    $(this).css({backgroundPosition: ''})
                        .parents(settings.widgetSelector)
                            .find(settings.contentSelector).show('blind', {}, 'fast');
                    return false;
                }).prependTo($(settings.handleSelector,this));
            }
        });

        $('.edit-box').each(function () {
            $('input.i1',this).keyup(function () {
                $(this).parents(settings.widgetSelector).find('h3').text( $(this).val().length>20 ? $(this).val().substr(0,20)+'...' : $(this).val() );
                iNettuts.savePreferences();
            });
            $('input.i2',this).keyup(function () {
                $(this).parents(settings.widgetSelector).find('.widget-content').css ( { 'min-height': $(this).val() } ) ;
                iNettuts.savePreferences();
            });
            $('ul.colors li',this).click(function () {

                var colorStylePattern = /\bcolor-[\w]{1,}\b/,
                    thisWidgetColorClass = $(this).parents(settings.widgetSelector).attr('class').match(colorStylePattern)
                if (thisWidgetColorClass) {
                    $(this).parents(settings.widgetSelector)
                        .removeClass(thisWidgetColorClass[0])
                        .addClass($(this).attr('class').match(colorStylePattern)[0]);
                }
                iNettuts.savePreferences();
                return false;
            });
        });
    },

    attachStylesheet : function (href) {
        var $ = this.jQuery;
        return $('<link href="' + href + '" rel="stylesheet" type="text/css" />').appendTo('head');
    },

    makeSortable : function () {
        var iNettuts = this,
            $ = this.jQuery,
            settings = this.settings,
            sorted = '',
            sortableItems = '' ; /* (function () {
                var notSortable = '';
                $(settings.widgetSelector,$(settings.columns)).each(function (i) {
                    if (!iNettuts.getWidgetSettings(this.id).movable) {
                        if(!this.id) this.id = 'widget-no-id-' + i;
                        notSortable += '#' + this.id + ',';
                        // alert ( this.id ) ;
                    } else {
                        sorted += '#' + this.id + ',';
                    }

                });
                var nStr = (notSortable=="") ? '> li' : '> li:not(' + notSortable + ')' ;
               // alert ( sorted ) ;
                return $(nStr, settings.columns);
            })(); */
            // alert ( sortableItems.size() ) ;
            // alert ( sortableItems.find ( settings.handleSelector ).size() ) ;
            // var items =
        sortableItems = $(settings.widgetSelector,$(settings.columns)) ;
        sortableItems.find(settings.handleSelector).css({
            cursor: 'move'
        }).mousedown(function (e) {
            sortableItems.css({width:''});
            $(this).parent().css({
                width: $(this).parent().width() + 'px'
            });
        }).mouseup(function () {
            if(!$(this).parent().hasClass('dragging')) {
                $(this).parent().css({width:''});
            } else {
                $(settings.columns).sortable('disable');
            }
        });

        $(settings.columns).sortable('destroy');

        $(settings.columns).sortable({
            items: sortableItems,
            connectWith: $(settings.columns),
            handle: settings.handleSelector,
            placeholder: 'widget-placeholder',
            forcePlaceholderSize: true,
            revert: 300,
            delay: 100,
            opacity: 0.8,
            containment: 'document',
            start: function (e,ui) {
                $(ui.helper).addClass('dragging');
            },
            stop: function (e,ui) {
                $(ui.item).css({width:''}).removeClass('dragging');
                $(settings.columns).sortable('enable');
                iNettuts.savePreferences();
            }
        });
    },

    savePreferences : function () {
        var iNettuts = this,
            $ = this.jQuery,
            settings = this.settings,
            cookieString = '';

        // if(!settings.saveToDB) {return;}
        // alert('move') ;

        /* Assemble the cookie string */
        $(settings.columns).each(function(col){
            // cookieString += '[' + col + ']=';
            $(settings.widgetSelector,this).each(function(i){
                /* ID of widget: */
                cookieString += '[' + $(this).attr('id') + ']=' + col + ',';
                /* Title of widget (replaced used characters) */
                cookieString += $('h3:eq(0)',this).text().replace(/\|/g,'[-PIPE-]').replace(/,/g,'[-COMMA-]') + ',';
                /* Color of widget (color classes) */
                cookieString += $(this).attr('class').match(/\bcolor-[\w]{1,}\b/) + ',';
                /* min-height size */
                cookieString += $('.widget-content',this).css('min-height') + ',';
                /* Collapsed/not collapsed widget? : */
                cookieString += $(settings.contentSelector,this).css('display') === 'none' ? 'collapsed' : 'not-collapsed';
                cookieString += ' ';
            });
        });

        // alert ( cookieString ) ;

        /* AJAX call to store string on database */
        $.post( xs_dir.api + "/widgets/control","uri="+xs_dir.q+"&layout="+cookieString, function( res ){
            $("#callbacks").html(res) ;
        });


    },

    sortWidgets : function () {
        var iNettuts = this,
            $ = this.jQuery,
            settings = this.settings;

        if(!settings.saveToDB) {
            $('body').css({background:'#000'});
            $(settings.columns).css({visibility:'visible'});
            return;
        }

        $.post("iNettuts_rpc.php", "",
            function(data){

              var cookie=data;

              if (cookie=="") {
                  $('body').css({background:'#000'});
                  $(settings.columns).css({visibility:'visible'});
                  iNettuts.addWidgetControls();
                  iNettuts.makeSortable();
                  return;
              }

              /* For each column */
              $(settings.columns).each(function(i){

                  var thisColumn = $(this),
                      widgetData = cookie.split('|')[i].split(';');

                  $(widgetData).each(function(){
                      if(!this.length) {return;}

                      /*
                      var thisWidgetData = this.split(','),
                          clonedWidget = $('#' + thisWidgetData[0]),
                          colorStylePattern = /\bcolor-[\w]{1,}\b/,
                          thisWidgetColorClass = $(clonedWidget).attr('class').match(colorStylePattern);

                      // Add/Replace new colour class:
                      if (thisWidgetColorClass) {
                          $(clonedWidget).removeClass(thisWidgetColorClass[0]).addClass(thisWidgetData[1]);
                      }

                      // Add/replace new title (Bring back reserved characters):
                      $(clonedWidget).find('h3:eq(0)').html(thisWidgetData[2].replace(/\[-PIPE-\]/g,'|').replace(/\[-COMMA-\]/g,','));

                      // Modify collapsed state if needed:
                      if(thisWidgetData[3]==='collapsed') {
                          // Set CSS styles so widget is in COLLAPSED state
                          $(clonedWidget).addClass('collapsed');
                      }


                      $('#' + thisWidgetData[0]).remove();
                      $(thisColumn).append(clonedWidget);
                      */

                      var thisWidgetData = this.split(','),
                          opt={
                            id: thisWidgetData[0],
                            color: thisWidgetData[1],
                            title: thisWidgetData[2].replace(/\[-PIPE-\]/g,'|').replace(/\[-COMMA-\]/g,','),
                            content: settings.widgetDefault.content
                          };
                      $(thisColumn).append(iNettuts.initWidget(opt));
                      if (thisWidgetData[3]==='collapsed') $('#'+thisWidgetData[0]).addClass('collapsed');
                      iNettuts.loadWidget(thisWidgetData[0]);
                  });
              });


              /* All done, remove loading gif and show columns: */
              $('body').css({background:'#000'});
              $(settings.columns).css({visibility:'visible'});

              iNettuts.addWidgetControls();
              iNettuts.makeSortable();

            });
    }
};
