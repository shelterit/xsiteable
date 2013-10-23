<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet 
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
	xmlns:nut="http://schema.shelter.nu/nut"
	xmlns:tm="http://schema.shelter.nu/tm"
	xmlns:form="http://schema.shelter.nu/nut-form"
	xmlns:f="http://schema.shelter.nu/nut-formy"
	xmlns:tmt="http://www.massimocorner.com/libraries/"
	xmlns:php="http://php.net/xsl"
	exclude-result-prefixes="xsl nut"
	version="1.0"
>
	
    <xsl:template name="tree-parse">
        <xsl:param name="data" />
        <xsl:param name="facets" />
        <xsl:param name="path" />
        <xsl:param name="parentselected" select="'selected'" />
        <xsl:param name="level" select="2" />
        <xsl:param name="url" select="''" />

        <div class="tree-level">

            <xsl:for-each select="$data">
                <!-- (<xsl:value-of select="count(*)" />) -->
                <xsl:variable name="this-url" select="concat($url,'/',@name)" />
                <xsl:variable name="name" select="@name" />
                <xsl:variable name="selected">
                    <xsl:if test="$facets[position()=$level]/@name = concat($path,'/',$name)">selected</xsl:if>
                </xsl:variable>
                <!-- [<xsl:value-of select="$facets[position()=$level]/@name" />]-[<xsl:value-of select="concat($path,'/',$name)" />] -->
                <xsl:if test="$parentselected = 'selected'">
                    <div class="tree-item {$selected}" style="white-space:nowrap;">
                        <xsl:if test="$selected = 'selected'"><img src="{$dir/item[@name='home']}/static/images/icons/folder_open.gif" class="tree-icon" /></xsl:if>
                        <xsl:if test="$selected != 'selected'"><img src="{$dir/item[@name='home']}/static/images/icons/folder.gif" class="tree-icon" /></xsl:if>
                        <a href="{$dir/item[@name='home']}/{$path}{$this-url}">
                            <xsl:value-of select="php:function ( 'urldecode_wrap', @name )" />
                        </a>
                    </div>
                </xsl:if>
                <xsl:if test="count(*) &gt; 0">
                   <xsl:if test="$selected = 'selected'">
                        <xsl:call-template name="tree-parse">
                            <xsl:with-param name="data" select="*" />
                            <xsl:with-param name="facets" select="$facets" />
                            <xsl:with-param name="url" select="$this-url" />
                            <xsl:with-param name="level" select="$level + 1" />
                            <xsl:with-param name="path" select="$path" />
                            <xsl:with-param name="parentselected" select="$selected" />
                        </xsl:call-template>
                   </xsl:if>
               </xsl:if>
            </xsl:for-each>

        </div>
    </xsl:template>

    <xsl:template match="nut:tree">
        <xsl:variable name="select" select="@select" />
        <xsl:variable name="path" select="@path" />
        <div id="tree">
        <xsl:call-template name="tree-parse">
            <xsl:with-param name="data" select="$objects[@name=$select]/*" />
            <xsl:with-param name="path" select="$path" />
            <xsl:with-param name="facets" select="$objects[@name='xs_facets']/*" />
        </xsl:call-template>
        </div>
    </xsl:template>

    <xsl:template match="nut:news-list-index">
        <xsl:param name="data" />

                <ul class="newslist" style="width:100%;">
                <xsl:for-each select="$data/*">
                    <li>
                    <!-- YEAR -->
                    <h1><a href="{$dir/item[@name='home']}/news/archive/{@name}"><xsl:value-of select="@name" /></a></h1>
                    <hr />
                    <ul>
                        <xsl:for-each select="*">
                            <li>
                                <!-- MONTH -->
                                <h2><a href="{$dir/item[@name='home']}/news/archive/{../@name}/{@name}">
                                    <xsl:choose>    <!--  Sorry for this kludge ... ran out of time -->
                                        <xsl:when test="@name= '1' or @name='01'">Jan</xsl:when>
                                        <xsl:when test="@name= '2' or @name='02'">Feb</xsl:when>
                                        <xsl:when test="@name= '3' or @name='03'">Mar</xsl:when>
                                        <xsl:when test="@name= '4' or @name='04'">Apr</xsl:when>
                                        <xsl:when test="@name= '5' or @name='05'">May</xsl:when>
                                        <xsl:when test="@name= '6' or @name='06'">Jun</xsl:when>
                                        <xsl:when test="@name= '7' or @name='07'">Jul</xsl:when>
                                        <xsl:when test="@name= '8' or @name='08'">Aug</xsl:when>
                                        <xsl:when test="@name= '9' or @name='09'">Sep</xsl:when>
                                        <xsl:when test="@name='10'">Oct</xsl:when>
                                        <xsl:when test="@name='11'">Nov</xsl:when>
                                        <xsl:when test="@name='12'">Dec</xsl:when>
                                    </xsl:choose>
                                </a></h2>
                                <ul>
                                    <xsl:for-each select="*">
                                        <li>
                                            <!-- WEEK -->
                                            <!-- {id}/{selector}/{specific} -->

                                            <table style="width:40%;"><tr><td class="center" style="width:20%;">Week</td><td> </td></tr><tr>
                                                <td class="center" style="width:20%;"><a href="{$dir/item[@name='home']}/news/archive/{../../@name}/{../@name}/{@name}"><xsl:value-of select="@name" /></a></td>
                                                <td style="border-left:solid 1px #999;">
                                                    <xsl:for-each select="*">
                                                        <div style=""><a href="{$dir/item[@name='home']}/news/archive/{../../../@name}/{../../@name}/{../@name}/{@name}" class="newsitem-day">
                                                            <xsl:value-of select="@name" />
                                                        </a></div>
                                                        <xsl:if test="*">
                                                    <div class="clear"> </div>
                                                            <div style="">
                                                            <ul style="display:block;padding:2px;">
                                                            <xsl:for-each select="*">
                                                                <li style="float:none;border-left:solid 4px #bcb;margin-bottom:4px;margin-left:10px;padding-left:4px;"><a href="{$dir/item[@name='home']}/news/{@name}"><xsl:value-of select="." /></a></li>
                                                            </xsl:for-each>
                                                            </ul>
                                                            </div>
                                                        </xsl:if>
                                                    </xsl:for-each>
                                                    <div class="clear"> </div>
                                                </td>
                                             </tr></table>
                                        </li>
                                    </xsl:for-each>
                                </ul>
                            </li>
                        </xsl:for-each>
                    </ul>
                    </li>
                </xsl:for-each>
                </ul>
    </xsl:template>
    
    <xsl:template match="tm:assoc">
        
        <xsl:param name="select" select="@select" />
        <xsl:param name="data" select="." />
        <xsl:param name="orig" select="$data" />
        <xsl:param name="parent" select="." />
        <xsl:param name="action" select="'false'" />
        <xsl:param name="position" select="0" />
        <xsl:param name="max" select="0" />
        <xsl:param name="current" select="." />

        <xsl:variable name="id" select="concat('xs_assoc_',@id)" />
        <xsl:variable name="title" select="@title" />
        <xsl:variable name="context" select="@context" />
        <xsl:variable name="type"><xsl:choose><xsl:when test="@type"><xsl:value-of select="@type" /></xsl:when><xsl:otherwise>user|user_group</xsl:otherwise></xsl:choose></xsl:variable>
        <xsl:variable name="createnew"><xsl:choose><xsl:when test="@create-new = 'true'">true</xsl:when><xsl:otherwise>false</xsl:otherwise></xsl:choose></xsl:variable>
        <xsl:variable name="linkto"><xsl:choose><xsl:when test="@link-to"><xsl:value-of select="@link-to" /></xsl:when><xsl:otherwise>false</xsl:otherwise></xsl:choose></xsl:variable>
        <!-- [<xsl:value-of select="$type" />] -->
        <xsl:variable name="pop"><xsl:choose><xsl:when test="@context"><xsl:value-of select="@context" /></xsl:when><xsl:otherwise><xsl:value-of select="$context" /></xsl:otherwise></xsl:choose></xsl:variable>
        <!-- [<xsl:value-of select="$pop" />] -->
        <xsl:variable name="inp"><xsl:call-template name="digest-variables">
                <xsl:with-param name="text" select="$pop" />
                <xsl:with-param name="data" select="$data" />
                <xsl:with-param name="orig" select="$orig" />
                <xsl:with-param name="position" select="$position" />
                <xsl:with-param name="max" select="$max" />
        </xsl:call-template></xsl:variable>
        <!-- [<xsl:value-of select="$inp" />] -->
        <xsl:variable name="finder"><xsl:call-template name="digest-variables">
                <xsl:with-param name="text" select="@find-member" />
                <xsl:with-param name="data" select="$data" />
                <xsl:with-param name="orig" select="$orig" />
                <xsl:with-param name="position" select="$position" />
                <xsl:with-param name="max" select="$max" />
        </xsl:call-template></xsl:variable>
        <xsl:variable name="assoc" select="$input/item[@name = $inp]" />
        <xsl:variable name="members" select="$assoc/item[@name = 'members']" />
        <!-- <xsl:variable name="docid" select="$data[@name = 'xs_document']/item[@name='id']" /> -->
        <!-- [<xsl:copy-of select="$assoc" />] -->
        <h6><xsl:value-of select="$title" /></h6> 
        <div id="{$id}">
            <form id="{$id}-form" action="{$dir/item[@name='home']}/api/data/tm/assoc">
                <div style="padding:3px 0;font-size:0.8em;">
                    <input class="as-input" id="{$id}-input" />
                </div>
                <input type="hidden" name="assoc_id" value="{$assoc/item[@name='id']}" />
                <input type="hidden" name="assoc_type" value="{$assoc/item[@name='type']}" />
                <input type="hidden" name="assoc_member_id" value="{$finder}" />
            </form>                                            
            <button id="{$id}-save-button" style="display:none;">Save!</button>
        </div>
        
        <script type="text/javascript">
            var <xsl:value-of select="$id" /> = [] ;
            <xsl:for-each select="$members/*">
                <xsl:value-of select="$id" />.push ( { value: "<xsl:value-of select="@name" />", name: "<xsl:value-of select="item[@name='label']" />" } ) ;
            </xsl:for-each>
            $("#<xsl:value-of select="$id" />-input").autoSuggest ( xs_dir.home + '/api/data/lookup/<xsl:value-of select="$type" />', { 
                startText:"Find by typing", selectedItemProp: "name", selectedValuesProp: "value",
                searchObjProps: "name", neverSubmit: true, createNew: <xsl:value-of select="$createnew" />, createType: "<xsl:value-of select="$type" />", linkTo: "<xsl:value-of select="$linkto" />", preFill: <xsl:value-of select="$id" />, queryParam: 'search',
                onChange: function () { $('#<xsl:value-of select="$id" />-save-button').click(); }
            } ) ;
            $(document).ready ( function() {
                $('#<xsl:value-of select="$id" />-save-button').click ( function() {
                old_button = $('#<xsl:value-of select="$id" />-save-button' ).html() ;
                $('#<xsl:value-of select="$id" />-save-button' ).html ( ajax_wait () ) ;
                    $.ajax( {
                        type: "GET",
                        url: $('#<xsl:value-of select="$id" />-form' ).attr( 'action' ),
                        data: $('#<xsl:value-of select="$id" />-form' ).serialize(),
                        success: function( response ) { 
                            $('#results' ).html ( response ) ; 
                            $('#<xsl:value-of select="$id" />-save-button' ).html ( old_button ) ;
                        },
                        always: function ( response ) { }
                    } );                                                    
                } );
            } );
        </script>        
    </xsl:template>
    
    <xsl:template match="tm:list-members">
        
        <xsl:param name="select" select="@select" />
        <xsl:param name="data" select="." />
        <xsl:param name="orig" select="$data" />
        <xsl:param name="parent" select="." />
        <xsl:param name="action" select="'false'" />
        <xsl:param name="position" select="0" />
        <xsl:param name="max" select="0" />
        <xsl:param name="current" select="." />

        <xsl:variable name="title" select="@title" />
        <xsl:variable name="context" select="@context" />
        <xsl:variable name="id-link" select="@id-link" />
        <xsl:variable name="id-ref" select="@id" />
        <xsl:variable name="pop"><xsl:choose><xsl:when test="@context"><xsl:value-of select="@context" /></xsl:when><xsl:otherwise><xsl:value-of select="$context" /></xsl:otherwise></xsl:choose></xsl:variable>
        <xsl:variable name="inp"><xsl:call-template name="digest-variables">
                <xsl:with-param name="text" select="$pop" />
                <xsl:with-param name="data" select="$data" />
                <xsl:with-param name="orig" select="$orig" />
                <xsl:with-param name="position" select="$position" />
                <xsl:with-param name="max" select="$max" />
        </xsl:call-template></xsl:variable>
        <xsl:variable name="d" select="$data[@name = $inp]" />
        <xsl:variable name="m" select="$d/item[@name='members']" />
        <xsl:if test="count($m/*) &gt; 0">
            <h3><xsl:value-of select="$title" /></h3>
            <ul class="generic-list">
            <xsl:for-each select="$m/*">
                <xsl:variable name="id"><xsl:choose><xsl:when test="$id-ref"><xsl:value-of select="item[@name=$id-ref]" /></xsl:when><xsl:otherwise><xsl:value-of select="@name" /></xsl:otherwise></xsl:choose></xsl:variable>
                <li><a href="{$dir/item[@name='home']}/{$id-link}/{$id}"><xsl:value-of select="item[@name='label']" /></a></li>
            </xsl:for-each>
            </ul>
        </xsl:if>
        
    </xsl:template>
    
    
    
        <xsl:template name="levely">
            <xsl:param name="data" select="*" />
            <xsl:param name="level" select="0" />
            <xsl:for-each select="$data">
                <li>
                    <xsl:choose>
                        <xsl:when test="@popup">
                            <a href="{@popup}" target="_blank"><xsl:value-of select="@label" /></a>
                        </xsl:when>
                        <xsl:otherwise>
                            <a href="{$dir/item[@name='home']}/{@path}"><xsl:value-of select="@label" /></a>
                        </xsl:otherwise>
                    </xsl:choose>
                    <xsl:if test="count(*) != 0">
                        <ul>
                            <xsl:call-template name="levely">
                                <xsl:with-param name="level" select="$level + 1" />
                            </xsl:call-template>
                        </ul>
                    </xsl:if>
                </li>    
            </xsl:for-each>                    
        </xsl:template>
	
        <xsl:template match="nut:create-menu" name="create-menu">
            <xsl:param name="for" select="@for" />
            <xsl:param name="this" select="@this" />
            <xsl:param name="data" select="." />
            <xsl:param name="orig" select="$data" />
            <xsl:param name="parent" select="." />
            <xsl:param name="action" select="'false'" />
            <xsl:param name="position" select="0" />
            <xsl:param name="max" select="0" />
            <xsl:param name="current" select="." />
            <xsl:variable name="inp"><xsl:call-template name="digest-variables">
                    <xsl:with-param name="text" select="$for" />
                    <xsl:with-param name="data" select="$data" />
                    <xsl:with-param name="orig" select="$orig" />
                    <xsl:with-param name="position" select="$position" />
                    <xsl:with-param name="max" select="$max" />
            </xsl:call-template></xsl:variable>

            <button id="{$inp}-button" class="ui-button ui-widget ui-state-default ui-corner-all" style="padding:1px 2px;margin:2px 3px;width:24px;height:18px;z-index:999999;" role="button" aria-disabled="false" title="Menu">
                <a style="margin:0;padding:0;" href="#"><span class="ui-button-icon-primary ui-icon ui-icon-triangle-1-s"></span></a>
            </button>
            
            <ul id="{$inp}" class="xs-context-menu" style="border:solid 2px #999;margin-top:20px;display:none;position:absolute;">
                <xsl:choose>
                    <xsl:when test="$this = 'true'">
                        <xsl:call-template name="levely">
                            <xsl:with-param name="data" select="$data" />
                        </xsl:call-template>
                    </xsl:when>
                    <xsl:otherwise>
                        <xsl:call-template name="levely">
                            <xsl:with-param name="data" select="$data/*" />
                        </xsl:call-template>
                    </xsl:otherwise>
                </xsl:choose>
            </ul>
            
            <script type="text/javascript">
                $('#<xsl:value-of select="$inp" />').menu ();
                $('#<xsl:value-of select="$inp" />-button').hover ( function () { 
                    // $(".xs-context-menu").slideUp('fast'); 
                    $('#<xsl:value-of select="$inp" />').slideDown('fast'); 
                    $(document).click(function (e) { 
                        $(".xs-context-menu").slideUp('fast'); 
                        $(document).off('click');
                    });
                });
            </script>
        </xsl:template>
        
        <xsl:template match="nut:context-menu">
            <xsl:param name="name" select="@name" />
            <xsl:param name="this" select="@this" />
            <xsl:param name="data" select="*" />
            <xsl:param name="orig" select="$data" />
            <xsl:param name="parent" select="." />
            <xsl:param name="action" select="'false'" />
            <xsl:param name="position" select="0" />
            <xsl:param name="max" select="0" />
            <xsl:param name="current" select="." />
            <xsl:variable name="inp"><xsl:call-template name="digest-variables">
                <xsl:with-param name="text" select="$name" />
                <xsl:with-param name="data" select="$data" />
                <xsl:with-param name="orig" select="$orig" />
                <xsl:with-param name="position" select="$position" />
                <xsl:with-param name="max" select="$max" />
            </xsl:call-template></xsl:variable>
            <button id="ctxmenu-{$inp}" class="ui-button ui-widget ui-state-default ui-corner-all" style="padding:1px 2px;margin:2px 3px;" role="button" aria-disabled="false" title="Menu">
                <a style="margin:0;padding:0;" href="#"><span class="ui-button-icon-primary ui-icon ui-icon-triangle-1-s"></span></a>
            </button>
            <xsl:call-template name="create-menu">
                <xsl:with-param name="for" select="concat('#ctxmenu-',$inp)" />
                <xsl:with-param name="this" select="$this" />
                <xsl:with-param name="data" select="$data" />
                <xsl:with-param name="orig" select="$orig" />
                <xsl:with-param name="position" select="$position" />
                <xsl:with-param name="max" select="$max" />
            </xsl:call-template>
        </xsl:template>
	
	
</xsl:stylesheet>