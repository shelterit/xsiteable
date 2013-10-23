<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet 
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
	xmlns:nut="http://schema.shelter.nu/nut"
	xmlns:php="http://php.net/xsl"
	exclude-result-prefixes="xsl nut php"
	version="1.0"
>

    <xsl:template match="nut:create-object">
        <xsl:choose>
            <xsl:when test="@type = 'dir'">
                <script type="text/javascript"><xsl:value-of select="@name" /> = { <xsl:for-each select="$dir/item">
                    <xsl:value-of select="@name" />: '<xsl:value-of select="." />'<xsl:if test="position()!=last()">, </xsl:if>
                </xsl:for-each> } ; </script>
            </xsl:when>
            <xsl:when test="@type = 'alerts'">
                <script type="text/javascript"><xsl:value-of select="@name" /> = { <xsl:for-each select="$page/item[@name='alerts']/item">
                        <xsl:value-of select="@name" />: [ <xsl:for-each select="item">
                           [<xsl:for-each select="item">["<xsl:value-of select="." />"]<xsl:if test="position()!=last()">, </xsl:if></xsl:for-each>]<xsl:if test="position()!=last()">, </xsl:if>
                        </xsl:for-each>]<xsl:if test="position()!=last()">, </xsl:if>
                    </xsl:for-each> } ;
                </script>
            </xsl:when>
        </xsl:choose>
    </xsl:template>
	
    <xsl:template match="nut:widget">
        <xsl:param name="data" />
        <xsl:param name="facets" />
        <xsl:param name="path" />
        <xsl:param name="parentselected" select="'selected'" />
        <xsl:param name="level" select="2" />
        <xsl:param name="url" select="''" />
        <xsl:param name="parent" select="." />
        <xsl:param name="input" />
        <xsl:param name="position" />
        <xsl:param name="max" select="0" />
        <xsl:variable name="id-in"><xsl:call-template name="nut:value-of">
                <xsl:with-param name="select" select="@id" />
                <xsl:with-param name="data" select="$data" />
                <xsl:with-param name="position" select="$position" />
                <xsl:with-param name="max" select="$max" />
        </xsl:call-template></xsl:variable>
        <xsl:variable name="title"><xsl:call-template name="nut:value-of">
                <xsl:with-param name="select" select="@title" />
                <xsl:with-param name="data" select="$data" />
                <xsl:with-param name="position" select="$position" />
                <xsl:with-param name="max" select="$max" />
        </xsl:call-template></xsl:variable>
        <xsl:variable name="name"><xsl:call-template name="nut:value-of">
                <xsl:with-param name="select" select="@name" />
                <xsl:with-param name="data" select="$data" />
                <xsl:with-param name="position" select="$position" />
                <xsl:with-param name="max" select="$max" />
        </xsl:call-template></xsl:variable>
        <xsl:variable name="id"><xsl:choose><xsl:when test="not($id-in) or normalize-space($id-in) = ''">widget-<xsl:call-template name="nut:value-of">
                <xsl:with-param name="select" select="@name" />
                <xsl:with-param name="data" select="$data" />
                <xsl:with-param name="position" select="$position" />
                <xsl:with-param name="max" select="$max" />
        </xsl:call-template>-<xsl:value-of select="php:function('rand','100000','9999999')" /></xsl:when><xsl:otherwise><xsl:call-template name="nut:value-of">
                <xsl:with-param name="select" select="$id-in" />
                <xsl:with-param name="data" select="$data" />
                <xsl:with-param name="position" select="$position" />
                <xsl:with-param name="max" select="$max" />
        </xsl:call-template></xsl:otherwise></xsl:choose></xsl:variable>
        <xsl:variable name="class" select="concat(php:function('widget_setting',$name,'color',$id),' ',@class)" />
        <xsl:variable name="ext"><xsl:if test="options/move">widget-move</xsl:if></xsl:variable>
        <xsl:variable name="classes">widget-head<xsl:if test="options/move"> draggable</xsl:if></xsl:variable>
        <xsl:variable name="params">label:<xsl:value-of select="$title" />|
            topic-id:<xsl:call-template name="nut:value-of">
                <xsl:with-param name="select" select="@topic-id" />
                <xsl:with-param name="data" select="$data" />
                <xsl:with-param name="position" select="$position" />
                <xsl:with-param name="max" select="$max" />
        </xsl:call-template>|
            topic-type:<xsl:call-template name="nut:value-of">
                <xsl:with-param name="select" select="@topic-type" />
                <xsl:with-param name="data" select="$data" />
                <xsl:with-param name="position" select="$position" />
                <xsl:with-param name="max" select="$max" />
        </xsl:call-template>|
            assoc-type:<xsl:call-template name="nut:value-of">
                <xsl:with-param name="select" select="@assoc-type" />
                <xsl:with-param name="data" select="$data" />
                <xsl:with-param name="position" select="$position" />
                <xsl:with-param name="max" select="$max" />
        </xsl:call-template>|
            assoc-member-topic:<xsl:call-template name="nut:value-of">
                <xsl:with-param name="select" select="@assoc-member-topic" />
                <xsl:with-param name="data" select="$data" />
                <xsl:with-param name="position" select="$position" />
                <xsl:with-param name="max" select="$max" />
        </xsl:call-template>|
        </xsl:variable>
        <xsl:variable name="content">
            <xsl:apply-templates>
                <xsl:with-param name="data" select="$data" />
                <xsl:with-param name="parent" select="$parent" />
                <xsl:with-param name="input" select="$input" />
                <xsl:with-param name="position" select="$position" />
                <xsl:with-param name="max" select="$max" />
            </xsl:apply-templates>
        </xsl:variable>

            <!-- classes are 'widget', the name of the widget controller, and additional classes given (like color) -->
            <!-- id = unique id given by the widgets controller -->

            <li class="widget {$ext} {$class}" id="{$id}">
                <div class="{$classes}" id="wh-{$id}">
                    <xsl:if test="collapse">
                        <a href="#" class="collapse">COLLAPSE</a>
                    </xsl:if>
                    <h3><xsl:choose><xsl:when test="$title != ''"><xsl:value-of select="$title" /></xsl:when>
                    <xsl:otherwise><xsl:apply-templates select="php:function('widget', $name, 'GET_title', null, null, $id)">
                       <xsl:with-param name="data" select="$data" />
                       <xsl:with-param name="parent" select="$parent" />
                       <xsl:with-param name="input" select="$input" />
                       <xsl:with-param name="position" select="$position" />
                       <xsl:with-param name="max" select="$max" />
                    </xsl:apply-templates></xsl:otherwise></xsl:choose></h3>
                    <xsl:if test="options/close">
                        <a href="#" class="remove">CLOSE</a>
                    </xsl:if>
                    <xsl:if test="options/config">
                        <a href="#" class="config">OPTIONS</a>
                    </xsl:if>
                    <xsl:if test="options/edit">
                        <a href="#" class="edit">GUI</a>
                    </xsl:if>
                    <xsl:if test="options/technical">
                        <a href="#" class="technical">TECH</a>
                    </xsl:if>
                </div>
                
                <form class="xs-widgets-form" action="{$dir/item[@name='api']}/widgets/control?functional=widget" method="post">
                    <input type="hidden" name="instance_id" value="{$id}" />
                    <input type="hidden" name="controller_name" value="{$name}" />
                    <div class="widget-options">
                        <xsl:if test="options/technical">
                            <div class="widget-technical">
                                <ul>
                                    <xsl:for-each select="technical/*">
                                        <xsl:variable name="item_name" select="@name" />
                                        <xsl:variable name="item_value" select="." />
                                        <li class="item">
                                            <label><xsl:value-of select="$item_name" /></label>
                                            <xsl:choose>
                                                <xsl:when test="$item_name = 'topic_id'">
                                                    <span style="padding:2px 5px;background-color:#ddd;color:#555;">
                                                        <a href="{$dir/item[@name='home']}/_tm/topic/{$item_value}"><xsl:value-of select="$item_value" /></a>
                                                    </span>
                                                </xsl:when>
                                                <xsl:when test="$item_name = 'render_uri'">
                                                    <span style="padding:2px 5px;background-color:#ddd;color:#555;">
                                                        <a href="{$item_value}">render link</a>
                                                    </span>
                                                </xsl:when>
                                                <xsl:otherwise>
                                                    <span style="padding:2px 5px;background-color:#ddd;color:#555;"><xsl:value-of select="$item_value" /></span>
                                                </xsl:otherwise>
                                            </xsl:choose>
                                        </li>
                                    </xsl:for-each>
                                    <!-- <li><input type="submit" value="Save" class="xs-widgets-form-submit" /></li> -->
                                </ul>
                            </div>
                        </xsl:if>
                        <xsl:if test="options/edit">
                            <div class="widget-settings">
                                <ul>
                                    <xsl:apply-templates select="settings">
                                        <xsl:with-param name="id" select="$id" />
                                        <xsl:with-param name="name" select="$name" />
                                    </xsl:apply-templates>
                                    <li><input type="submit" value="Save" class="xs-widgets-form-submit" /></li>
                                </ul>
                            </div>
                        </xsl:if>
                        <xsl:if test="options/config">
                            <div class="widget-properties">
                                <ul>
                                    <xsl:for-each select="properties/*">
                                        <xsl:variable name="item_name" select="@name" />
                                        <xsl:variable name="item_value" select="." />
                                        <li class="item">
                                            <label><xsl:value-of select="$item_name" /></label>
                                            <input name="f:p__{$item_name}" class="i1 xs-widget-property" value="{$item_value}"/>
                                        </li>
                                    </xsl:for-each>
                                    <li><input type="submit" value="Save" class="xs-widgets-form-submit" /></li>
                                </ul>
                            </div>
                        </xsl:if>
                    </div>
                </form>
                <xsl:variable name="test" select="php:function('widget_setting',$name,'style', $id)" />
                <!-- [<xsl:copy-of select="$test" />] -->
                <div id="wc-{$id}" class="widget-content" style="{$test}">
                    <xsl:choose>
                        <xsl:when test="$content"><xsl:copy-of select="$content" /></xsl:when>
                        <xsl:otherwise><xsl:apply-templates select="php:function('widget', $name, 'GET_content', $params, $id )">
                            <xsl:with-param name="data" select="$data" />
                            <xsl:with-param name="parent" select="$parent" />
                            <xsl:with-param name="input" select="$input" />
                            <xsl:with-param name="position" select="$position" />
                            <xsl:with-param name="max" select="$max" />
                        </xsl:apply-templates></xsl:otherwise>
                    </xsl:choose>
                </div>
            </li>
            
    </xsl:template>

    <xsl:template match="settings/*">
        <xsl:param name="id" />
        <xsl:param name="name" />
        <xsl:variable name="item_name" select="@name" />
        <xsl:variable name="item_value" select="." />
        <li class="item">
            <label><xsl:value-of select="$item_name" /></label>
            <input name="f:s__{$item_name}" class="i1 xs-widget-setting" value="{$item_value}"/>
        </li>
    </xsl:template>

    <xsl:template match="settings/item[@name='title']">
        <xsl:param name="id" />
        <xsl:param name="name" />
        <xsl:variable name="item_name" select="@name" />
        <xsl:variable name="item_value" select="." />
        <li class="item">
            <label>Title</label>
            <input class="xs-widget-setting" name="f:s__{$item_name}" style="width:60%;" value="{$item_value}"/>
        </li>
    </xsl:template>

    <xsl:template match="settings/item[@name='color']">
        <xsl:param name="id" />
        <xsl:param name="name" />
        <xsl:variable name="item_name" select="@name" />
        <xsl:variable name="item_value" select="." />
        <xsl:variable name="tag">f:s__<xsl:value-of select="$item_name" /></xsl:variable>
        <li class="item">
            <label>Color style</label>
            <ul class="colors">

                <xsl:variable name="cb"><xsl:if test="$item_value = 'color-blue'">color-select</xsl:if></xsl:variable>
                <li class="color-blue {$cb}"     onclick="color_selector(this,'{$tag}', 'blue')" />

                <xsl:variable name="cc"><xsl:if test="$item_value = 'color-cyan'">color-select</xsl:if></xsl:variable>
                <li class="color-cyan {$cc}"     onclick="color_selector(this,'{$tag}', 'cyan')" />

                <xsl:variable name="cbu"><xsl:if test="$item_value = 'color-burgundy'">color-select</xsl:if></xsl:variable>
                <li class="color-burgundy {$cbu}" onclick="color_selector(this,'{$tag}', 'burgundy')" />

                <xsl:variable name="cr"><xsl:if test="$item_value = 'color-red'">color-select</xsl:if></xsl:variable>
                <li class="color-red {$cr}"      onclick="color_selector(this,'{$tag}', 'red')" />

                <xsl:variable name="cp"><xsl:if test="$item_value = 'color-pink'">color-select</xsl:if></xsl:variable>
                <li class="color-pink {$cp}"      onclick="color_selector(this,'{$tag}', 'pink')" />

                <xsl:variable name="co"><xsl:if test="$item_value = 'color-orange'">color-select</xsl:if></xsl:variable>
                <li class="color-orange {$co}"   onclick="color_selector(this,'{$tag}', 'orange')" />

                <xsl:variable name="cg"><xsl:if test="$item_value = 'color-green'">color-select</xsl:if></xsl:variable>
                <li class="color-green {$cg}"    onclick="color_selector(this,'{$tag}', 'green')" />

                <xsl:variable name="cy"><xsl:if test="$item_value = 'color-yellow'">color-select</xsl:if></xsl:variable>
                <li class="color-yellow {$cy}"   onclick="color_selector(this,'{$tag}', 'yellow')" />
                
                <xsl:variable name="cw"><xsl:if test="$item_value = 'color-white'">color-select</xsl:if></xsl:variable>
                <li class="color-white {$cw}"    onclick="color_selector(this,'{$tag}', 'white')" />
                
                <xsl:variable name="cgr"><xsl:if test="$item_value = 'color-grey'">color-select</xsl:if></xsl:variable>
                <li class="color-grey {$cgr}"    onclick="color_selector(this,'{$tag}', 'grey')" />
                
                <xsl:variable name="ct"><xsl:if test="$item_value = 'color-trans'">color-select</xsl:if></xsl:variable>
                <li class="color-trans {$ct}"    onclick="color_selector(this,'{$tag}', 'trans')" />
               
            </ul>
            <input class="xs-widget-setting" type="hidden" id="{$tag}" name="{$tag}" value="{$item_value}" />
        </li>
    </xsl:template>


    <!--
    <xsl:template match="settings/color">
    </xsl:template>

    <xsl:template match="settings/style">
    </xsl:template>
    -->

</xsl:stylesheet>