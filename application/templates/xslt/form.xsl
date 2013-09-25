<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet 
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
	xmlns:nut="http://schema.shelter.nu/nut"
	xmlns:form="http://schema.shelter.nu/nut-form"
	xmlns:f="http://schema.shelter.nu/nut-formy"
	xmlns:tmt="http://www.massimocorner.com/libraries/"
	xmlns:php="http://php.net/xsl"
	exclude-result-prefixes="xsl nut php form f"
	version="1.0"
>
	
    <xsl:template match="f:f">
        <xsl:param name="data" select="." />
        <xsl:param name="parent" select="." />
        <xsl:param name="position" select="0" />
        <xsl:param name="max" select="0" />
        <xsl:variable name="class">
            <xsl:call-template name="digest-variables">
                <xsl:with-param name="text" select="@class" />
                <xsl:with-param name="data" select="$data" />
                <xsl:with-param name="parent" select="$parent" />
                <xsl:with-param name="position" select="$position" />
                <xsl:with-param name="max" select="$max" />
            </xsl:call-template>
        </xsl:variable>
        <xsl:variable name="id">
            <xsl:call-template name="digest-variables">
                <xsl:with-param name="text" select="@id" />
                <xsl:with-param name="data" select="$data" />
                <xsl:with-param name="parent" select="$parent" />
                <xsl:with-param name="position" select="$position" />
                <xsl:with-param name="max" select="$max" />
            </xsl:call-template>
        </xsl:variable>
        <xsl:variable name="value">
            <xsl:apply-templates>
                <xsl:with-param name="data" select="$data" />
                <xsl:with-param name="parent" select="$parent" />
                <xsl:with-param name="position" select="$position" />
                <xsl:with-param name="max" select="$max" />
            </xsl:apply-templates>
        </xsl:variable>
        <!-- [<xsl:copy-of select="$value" />]
        [<xsl:copy-of select="." />] -->
        <xsl:variable name="title">
            <xsl:call-template name="digest-variables">
                <xsl:with-param name="text" select="@title" />
                <xsl:with-param name="data" select="$data" />
                <xsl:with-param name="parent" select="$parent" />
                <xsl:with-param name="position" select="$position" />
                <xsl:with-param name="max" select="$max" />
            </xsl:call-template>
        </xsl:variable>
        <xsl:variable name="r">
            <xsl:call-template name="digest-variables">
                <xsl:with-param name="text" select="concat('{$request/',@name,'}')" />
                <xsl:with-param name="data" select="$data" />
                <xsl:with-param name="parent" select="$parent" />
                <xsl:with-param name="position" select="$position" />
                <xsl:with-param name="max" select="$max" />
            </xsl:call-template>
        </xsl:variable>
        <xsl:variable name="req">
            <xsl:choose>
                <xsl:when test="substring-after($r,'{')"></xsl:when>
                <xsl:otherwise>
                    <xsl:value-of select="$r" />
                </xsl:otherwise>
            </xsl:choose>
        </xsl:variable>
        <xsl:variable name="val">
            <xsl:choose>
                <xsl:when test="normalize-space($req)">
                    <xsl:value-of select="$req" />
                </xsl:when>
                <xsl:otherwise>
                    <xsl:copy-of select="$value" />
                </xsl:otherwise>
            </xsl:choose>
        </xsl:variable>
        <xsl:variable name="type">
            <xsl:choose>
                <xsl:when test="@type='textarea'">area</xsl:when>
                <xsl:otherwise>field</xsl:otherwise>
            </xsl:choose>
        </xsl:variable>
        <div class="page-form-item">
            <label for="{@name}"><b>
                <xsl:value-of select="$title" />
                <span style="color:#ccc;">
                    <xsl:text>. </xsl:text>
                </span>
                <xsl:if test="@required='true'">
                    <em>*</em>
                </xsl:if>
            </b></label>
            <xsl:choose>
                <xsl:when test="$type='field'">
                    <br /><input id="{$id}" name="{@name}" style="{@style}" value="{$val}" class="{$class}" />
                </xsl:when>
                <xsl:when test="$type='area'">
                    <br /><textarea id="{$id}" name="{@name}" style="{@style}" class="{$class}">
                        <xsl:copy-of select="$val" />
                    </textarea>
                </xsl:when>
            </xsl:choose>
        </div>
    </xsl:template>
	
    <xsl:template match="f:r">
        <xsl:param name="data" select="." />
        <xsl:param name="parent" select="." />
        <xsl:param name="position" select="0" />
        <xsl:param name="max" select="0" />
        <xsl:variable name="value">
            <xsl:call-template name="digest-variables">
                <xsl:with-param name="text" select="@value" />
                <xsl:with-param name="data" select="$data" />
                <xsl:with-param name="parent" select="$parent" />
                <xsl:with-param name="position" select="$position" />
                <xsl:with-param name="max" select="$max" />
            </xsl:call-template>
        </xsl:variable>
        <xsl:variable name="checking">
            <xsl:call-template name="digest-variables">
                <xsl:with-param name="text" select="@checking" />
                <xsl:with-param name="data" select="$data" />
                <xsl:with-param name="parent" select="$parent" />
                <xsl:with-param name="position" select="$position" />
                <xsl:with-param name="max" select="$max" />
            </xsl:call-template>
        </xsl:variable>
        <input type="radio">
            <xsl:if test="@style">
                <xsl:attribute name="style">
                    <xsl:value-of select="@style" />
                </xsl:attribute>
            </xsl:if>
            <xsl:if test="@value">
                <xsl:attribute name="value">
                    <xsl:value-of select="$value" />
                </xsl:attribute>
            </xsl:if>
            <xsl:if test="@name">
                <xsl:attribute name="name">
                    <xsl:value-of select="@name" />
                </xsl:attribute>
            </xsl:if>
            <xsl:if test="@onclick">
                <xsl:attribute name="onclick">
                    <xsl:value-of select="@onclick" />
                </xsl:attribute>
            </xsl:if>
            <xsl:if test="@checking">
                <xsl:if test="$checking = $value">
                    <xsl:attribute name="checked">checked</xsl:attribute>
                </xsl:if>
            </xsl:if>
        </input>
    </xsl:template>
	
	
	
    <xsl:template match="form:drop-down" name="form:drop-down">
        <xsl:param name="data" select="." />
        <xsl:param name="position" select="999" />
        <xsl:param name="select" select="@select" />
        <xsl:param name="select-from" select="@select-from" />

        <xsl:variable name="this_name" select="@name" />
        <xsl:variable name="this_value" select="$objects[@name='xs_request']/item[@name=$this_name]" />
        <xsl:variable name="test" select="$data/item[@name='id']" />

        <select name="{@name}" id="{@id}" class="{@class}" style="{@style}">
            <xsl:for-each select="$objects[@name=$select-from]/*">
                <xsl:sort select="item[@name='label']" />
                <option value="{item[@name='value']}">
                    <xsl:if test="item[@name='value'] = $this_value">
                        <xsl:attribute name="selected">selected</xsl:attribute>
                    </xsl:if>
                    <xsl:value-of select="php:function ( 'label', item[@name='label'], $language )" />
                </option>
            </xsl:for-each>
        </select>
        
    </xsl:template>
	
    <xsl:template match="form:dynamic|felt">
        <xsl:param name="data" select="." />
        <xsl:param name="orig" select="$data" />
        <xsl:param name="parent" />
        <xsl:param name="position" select="999" />
        <xsl:param name="max" />
        <xsl:param name="n" select="concat(@name,'')" />
        <xsl:param name="s" select="concat(@schema,'')" />
        <xsl:param name="i" select="concat(@input,'')" />
        <xsl:param name="d" select="concat(@db,'')" />
        <xsl:param name="di" select="concat(@dir,'')" />
        <xsl:param name="r" select="concat(@rel,'')" />
        <xsl:variable name="db-dyn">
            <xsl:call-template name="digest-variables">
                <xsl:with-param name="text" select="$d" />
                <xsl:with-param name="data" select="$data" />
                <xsl:with-param name="orig" select="$orig" />
                <xsl:with-param name="parent" select="$parent" />
                <xsl:with-param name="position" select="$position" />
                <xsl:with-param name="max" select="$max" />
            </xsl:call-template>
        </xsl:variable>
        <xsl:variable name="name-dyn">
            <xsl:call-template name="digest-variables">
                <xsl:with-param name="text" select="$n" />
                <xsl:with-param name="data" select="$data" />
                <xsl:with-param name="orig" select="$orig" />
                <xsl:with-param name="parent" select="$parent" />
                <xsl:with-param name="position" select="$position" />
                <xsl:with-param name="max" select="$max" />
            </xsl:call-template>
        </xsl:variable>
        <xsl:variable name="dir-dyn">
            <xsl:call-template name="digest-variables">
                <xsl:with-param name="text" select="$di" />
                <xsl:with-param name="data" select="$data" />
                <xsl:with-param name="orig" select="$orig" />
                <xsl:with-param name="parent" select="$parent" />
                <xsl:with-param name="position" select="$position" />
                <xsl:with-param name="max" select="$max" />
            </xsl:call-template>
        </xsl:variable>
        <xsl:variable name="rel-dyn">
            <xsl:call-template name="digest-variables">
                <xsl:with-param name="text" select="$r" />
                <xsl:with-param name="data" select="$data" />
                <xsl:with-param name="orig" select="$orig" />
                <xsl:with-param name="parent" select="$parent" />
                <xsl:with-param name="position" select="$position" />
                <xsl:with-param name="max" select="$max" />
            </xsl:call-template>
        </xsl:variable>
        <xsl:variable name="input-dyn">
            <xsl:call-template name="digest-variables">
                <xsl:with-param name="text" select="$i" />
                <xsl:with-param name="data" select="$data" />
                <xsl:with-param name="orig" select="$orig" />
                <xsl:with-param name="parent" select="$parent" />
                <xsl:with-param name="position" select="$position" />
                <xsl:with-param name="max" select="$max" />
            </xsl:call-template>
        </xsl:variable>
        <xsl:variable name="lut" select="concat($db-dyn,'.',substring-after($name-dyn,':'))" />
        <xsl:variable name="schema-dyn">
            <xsl:choose>
                <xsl:when test="substring-before($s, '_') = 'xs'">
                    <xsl:call-template name="create-list">
                        <xsl:with-param name="xs" select="$s" />
                    </xsl:call-template>
                </xsl:when>
                <xsl:when test="$objects[@name='xs_status']/item[@name=$lut]">
                    <xsl:call-template name="create-list-custom">
                        <xsl:with-param name="data" select="$objects[@name='xs_status']/item[@name=$lut]" />
                    </xsl:call-template>
                </xsl:when>
                <xsl:otherwise>
                    <xsl:call-template name="digest-variables">
                        <xsl:with-param name="text" select="$s" />
                        <xsl:with-param name="data" select="$data" />
                        <xsl:with-param name="orig" select="$orig" />
                        <xsl:with-param name="parent" select="$parent" />
                        <xsl:with-param name="position" select="$position" />
                        <xsl:with-param name="max" select="$max" />
                    </xsl:call-template>
                </xsl:otherwise>
            </xsl:choose>
        </xsl:variable>
        <xsl:variable name="id">
            <xsl:choose>
                <xsl:when test="substring-after($name-dyn,'field!')">
                    <xsl:value-of select="substring-after($name-dyn,'field!')" />
                </xsl:when>
                <xsl:otherwise>
                    <xsl:value-of select="$name-dyn" />
                </xsl:otherwise>
            </xsl:choose>
        </xsl:variable>
        <xsl:variable name="list" select="substring-before(substring-after($schema-dyn,'['),']')" />
        <xsl:choose>
            <xsl:when test="$list != ''">
                <xsl:choose>
                    <xsl:when test="@radio = 'true'">
                        <xsl:call-template name="options">
                            <xsl:with-param name="list" select="$list" />
                            <xsl:with-param name="radio" select="@radio" />
                            <xsl:with-param name="current" select="$input-dyn" />
                            <xsl:with-param name="name" select="$name-dyn" />
                        </xsl:call-template>
                    </xsl:when>
                    <xsl:otherwise>
                        <select name="{$name-dyn}">
                            <xsl:call-template name="options">
                                <xsl:with-param name="list" select="$list" />
                                <xsl:with-param name="current" select="$input-dyn" />
                            </xsl:call-template>
                        </select>
                    </xsl:otherwise>
                </xsl:choose>
            </xsl:when>
            <xsl:when test="$schema-dyn = 'yes'">
                <input type="radio" name="{$name-dyn}" value="1">
                    <xsl:if test="normalize-space($input-dyn) = '1'">
                        <xsl:attribute name="checked">checked</xsl:attribute>
                    </xsl:if>
                </input>
                <xsl:value-of select="php:function ( 'label', 'en:Yes|no:Ja', $language )" />
                <xsl:text> </xsl:text>
                <input type="radio" name="{$name-dyn}" value="0">
                    <xsl:if test="normalize-space($input-dyn) != '1'">
                        <xsl:attribute name="checked">checked</xsl:attribute>
                    </xsl:if>
                </input>
                <xsl:value-of select="php:function ( 'label', 'en:No|no:Nei', $language )" />
                <xsl:text> </xsl:text>
            </xsl:when>
            <xsl:when test="$schema-dyn = 'true'">
                <input type="radio" name="{$name-dyn}" value="1">
                    <xsl:if test="normalize-space($input-dyn) = '1'">
                        <xsl:attribute name="checked">checked</xsl:attribute>
                    </xsl:if>
                </input>
                <xsl:value-of select="php:function ( 'label', 'en:True|no:Ja', $language )" />
                <xsl:text> </xsl:text>
                <input type="radio" name="{$name-dyn}" value="0">
                    <xsl:if test="normalize-space($input-dyn) != '1'">
                        <xsl:attribute name="checked">checked</xsl:attribute>
                    </xsl:if>
                </input>
                <xsl:value-of select="php:function ( 'label', 'en:False|no:Nei', $language )" />
                <xsl:text> </xsl:text>
            </xsl:when>
            <xsl:when test="$schema-dyn = 'text' or $schema-dyn = ''">
                <input type="text" name="{$name-dyn}" value="{$input-dyn}" style="width:400px;"/>
            </xsl:when>
            <xsl:when test="$schema-dyn = 'text.short'">
                <input type="text" name="{$name-dyn}" value="{$input-dyn}" style="width:200px;"/>
            </xsl:when>
            <xsl:when test="$schema-dyn = 'text.long'">
                <textarea name="{$name-dyn}" style="width:210px;height:80px;">
                    <xsl:copy-of select="$input-dyn" />
                </textarea>
            </xsl:when>
            <xsl:when test="$schema-dyn = 'int'">
                <input type="text" name="{$name-dyn}" value="{$input-dyn}" style="width:50px;"/>
            </xsl:when>
            <xsl:when test="$schema-dyn = 'time'">
                <input type="text" size="10" value="{$input-dyn}" name="{$name-dyn}" id="{$id}" />
                <script type="text/javascript">
		$('#<xsl:value-of select="$id" />').datepicker({ beforeShow: customRange, showOn: "both", dateFormat:"yymmdd", buttonImage:pre+"/images/calendar.gif", buttonImageOnly: true } ) ;
                </script>
            </xsl:when>
            <xsl:otherwise>
				name: '
                <xsl:value-of select="$name-dyn" />'
                <br />
				schema:'
                <xsl:value-of select="$schema-dyn" />'
                <br />
				db: '
                <xsl:value-of select="$db-dyn" />'
                <br />
				rel: '
                <xsl:value-of select="$rel-dyn" />'
                <br />
				dir: '
                <xsl:value-of select="$dir-dyn" />'
                <br />
            </xsl:otherwise>
        </xsl:choose>
        <xsl:variable name="qq" select="substring-before($rel-dyn,'.')" />
        <xsl:variable name="idr" select="substring-after($name-dyn,':')" />
        <xsl:if test="$rel-dyn != ''">
			
            <span id="lut{$idr}" style="width:400px;height:370px;position:absolute;background-color:#efe;border:solid 2px #999;z-index:1000;display:none;">
                <iframe id="lutwin{$idr}" src="{$dir-dyn}/admin/db/{$qq}?output=blank&amp;lut=true&amp;name={$name-dyn}&amp;shut={$idr}"
					style="height:340px;width:100%;margin:0;padding:0;border:none;">
                </iframe>
                <input type="hidden" style="width:100%" value="{$dir-dyn}/admin/db/{$qq}?output=blank&amp;lut=true&amp;name={$name-dyn}&amp;shut={$idr}" />
                <input type="button" value="Cancel" onclick="$('#lut{$idr}').hide('fast');" />
            </span>
            <input type="button" value="&gt;&gt;" onclick="$('#lut{$idr}').show('fast');" />
        </xsl:if>
    </xsl:template>
	
    <xsl:template name="options">
        <xsl:param name="list" select="'no input'" />
        <xsl:param name="radio" select="'false'" />
        <xsl:param name="name" select="''" />
        <xsl:param name="current" select="''" />
        <xsl:variable name="pick" select="substring-before($list,',')" />
        <xsl:variable name="pick-before">
            <xsl:choose>
                <xsl:when test="not(normalize-space($pick))">
                    <xsl:value-of select="$list" />
                </xsl:when>
                <xsl:otherwise>
                    <xsl:value-of select="$pick" />
                </xsl:otherwise>
            </xsl:choose>
        </xsl:variable>
        <xsl:variable name="pick-after" select="substring-after($list,',')" />
        <xsl:variable name="has-label" select="substring-after($pick-before,'=')" />
        <xsl:variable name="l">
            <xsl:choose>
                <xsl:when test="normalize-space($has-label)">
                    <xsl:value-of select="php:function ( 'label', substring-before($pick-before,'='), $language )" />
                </xsl:when>
                <xsl:otherwise>
                    <xsl:value-of select="$pick-before" />
                </xsl:otherwise>
            </xsl:choose>
        </xsl:variable>
        <xsl:variable name="v">
            <xsl:choose>
                <xsl:when test="normalize-space($has-label)">
                    <xsl:value-of select="substring-after($pick-before,'=')" />
                </xsl:when>
                <xsl:otherwise>
                    <xsl:value-of select="$pick-before" />
                </xsl:otherwise>
            </xsl:choose>
        </xsl:variable>

                <xsl:choose>
                    <xsl:when test="$radio = 'true'">
                        <input name="{$name}" type="radio" value="{$v}">
                            <xsl:if test="normalize-space($current) = normalize-space($v)">
                                <xsl:attribute name="checked">checked</xsl:attribute>
                            </xsl:if>
                        </input>
                        <xsl:value-of select="php:function ( 'label', $l, $language )" /> <br />
                    </xsl:when>
                    <xsl:otherwise>
                        <option value="{$v}">
                            <xsl:if test="normalize-space($current) = normalize-space($v)">
                                <xsl:attribute name="selected">selected</xsl:attribute>
                            </xsl:if>
                            <xsl:value-of select="php:function ( 'label', $l, $language )" />
                        </option>
                    </xsl:otherwise>
                </xsl:choose>

        <xsl:if test="normalize-space($pick-after)">
            <xsl:call-template name="options">
                <xsl:with-param name="list" select="$pick-after" />
                <xsl:with-param name="current" select="$current" />
                <xsl:with-param name="radio" select="$radio" />
                <xsl:with-param name="name" select="$name" />
            </xsl:call-template>
        </xsl:if>
    </xsl:template>
	
    <xsl:template name="create-list">
        <xsl:param name="xs" />
		[
        <xsl:for-each select="$objects[@name=$xs]/*">
            <xsl:value-of select="item[@name='label']" />=
            <xsl:value-of select="item[@name='id']" />
            <xsl:if test="position()!=last()">,</xsl:if>
        </xsl:for-each>]
    </xsl:template>
	
    <xsl:template name="create-list-custom">
        <xsl:param name="data" />
		[
        <xsl:for-each select="$data/*">
            <xsl:value-of select="." />=
            <xsl:value-of select="@name" />
            <xsl:if test="position()!=last()">,</xsl:if>
        </xsl:for-each>]
    </xsl:template>
	
    <xsl:template match="form:section">
        <xsl:param name="data" select="." />
        <xsl:param name="position" select="999" />
        <xsl:param name="select" select="@select" />
        <xsl:variable name="id" select="@id" />
        <tr>
            <xsl:if test="normalize-space($id) != ''">
                <xsl:attribute name="id">section_
                    <xsl:value-of select="$id" />
                </xsl:attribute>
            </xsl:if>
            <td valign="top" colspan="3" style="padding-top:15px;color:#234;border-bottom:dotted 1px #234;font-size:1.4em;margin-bottom:6px;">
                <b>
                    <xsl:value-of select="@title" />
                </b>
            </td>
        </tr>
        <xsl:apply-templates>
            <xsl:with-param name="this" select="." />
            <xsl:with-param name="data" select="$data" />
            <xsl:with-param name="position" select="$position" />
        </xsl:apply-templates>
    </xsl:template>
	
    <xsl:template match="form:tmt-field">
        <xsl:param name="this" select="." />
        <xsl:param name="data" select="." />
        <xsl:param name="position" select="999" />
        <xsl:param name="select" select="@select" />
        <xsl:variable name="id">
            <xsl:choose>
                <xsl:when test="@id">
                    <xsl:value-of select="@id" />
                </xsl:when>
                <xsl:otherwise>
                    <xsl:value-of select="$this/@id" />
                </xsl:otherwise>
            </xsl:choose>
        </xsl:variable>
        <xsl:variable name="sz">
            <xsl:choose>
                <xsl:when test="@size">
                    <xsl:value-of select="@size" />
                </xsl:when>
                <xsl:otherwise>100</xsl:otherwise>
            </xsl:choose>
        </xsl:variable>
        <xsl:variable name="value">
            <xsl:call-template name="nut:value-of">
                <xsl:with-param name="select" select="concat('$item/',$id)" />
                <xsl:with-param name="data" select="$data" />
                <xsl:with-param name="position" select="$position" />
            </xsl:call-template>
        </xsl:variable>
        <label for="field!{$id}">
            <xsl:if test="@label">
                <span class="labelText">
                    <xsl:value-of select="@label" />
                </span>
            </xsl:if>
            <xsl:variable name="element-name">
                <xsl:choose>
                    <xsl:when test="@text-area='true'">textarea</xsl:when>
                    <xsl:otherwise>input</xsl:otherwise>
                </xsl:choose>
            </xsl:variable>
            <xsl:element name="{$element-name}">
                <xsl:attribute name="name">field!
                    <xsl:value-of select="$id" />
                </xsl:attribute>
                <xsl:attribute name="id">field!
                    <xsl:value-of select="$id" />
                </xsl:attribute>
                <xsl:if test="@required">
                    <xsl:attribute name="class">required</xsl:attribute>
                    <xsl:attribute name="tmt:required">true</xsl:attribute>
                    <xsl:attribute name="tmt:errorclass">invalid</xsl:attribute>
                </xsl:if>
                <xsl:if test="@text-area">
                    <xsl:attribute name="style">width:590px;</xsl:attribute>
                </xsl:if>
                <xsl:if test="@error">
                    <xsl:attribute name="tmt:message">
                        <xsl:value-of select="@error" />
                    </xsl:attribute>
                </xsl:if>
                <xsl:if test="@pattern">
                    <xsl:attribute name="tmt:pattern">
                        <xsl:value-of select="@pattern" />
                    </xsl:attribute>
                </xsl:if>
                <xsl:choose>
                    <xsl:when test="@text-area='true'">
                        <xsl:value-of select="$value" />
                    </xsl:when>
                    <xsl:otherwise>
                        <xsl:attribute name="value">
                            <xsl:value-of select="$value" />
                        </xsl:attribute>
                    </xsl:otherwise>
                </xsl:choose>
            </xsl:element>
        </label>
		
    </xsl:template>
	
    <xsl:template match="form:tmt-list">
        <xsl:param name="this" select="." />
        <xsl:param name="data" select="." />
        <xsl:param name="context" select="@context" />
        <xsl:param name="position" select="999" />
        <xsl:param name="select" select="@select" />
        <xsl:variable name="id">
            <xsl:choose>
                <xsl:when test="@id">
                    <xsl:value-of select="@id" />
                </xsl:when>
                <xsl:otherwise>
                    <xsl:value-of select="$this/@id" />
                </xsl:otherwise>
            </xsl:choose>
        </xsl:variable>
        <xsl:variable name="sz">
            <xsl:choose>
                <xsl:when test="@size">
                    <xsl:value-of select="@size" />
                </xsl:when>
                <xsl:otherwise>100</xsl:otherwise>
            </xsl:choose>
        </xsl:variable>
        <xsl:variable name="value">
            <xsl:call-template name="nut:value-of">
                <xsl:with-param name="select" select="concat('$item/',$id)" />
                <xsl:with-param name="data" select="$data" />
                <xsl:with-param name="position" select="$position" />
            </xsl:call-template>
        </xsl:variable>
        <label for="{$id}">
            <xsl:if test="@label">
                <span class="labelText">
                    <xsl:value-of select="@label" />
                </span>
            </xsl:if>
            <select>
                <xsl:attribute name="name">field!
                    <xsl:value-of select="$id" />
                </xsl:attribute>
                <xsl:attribute name="id">field!
                    <xsl:value-of select="$id" />
                </xsl:attribute>
                <xsl:if test="@required">
                    <xsl:attribute name="class">required</xsl:attribute>
                    <xsl:attribute name="tmt:required">true</xsl:attribute>
                    <xsl:attribute name="tmt:errorclass">invalid</xsl:attribute>
                </xsl:if>
                <xsl:if test="@error">
                    <xsl:attribute name="tmt:message">
                        <xsl:value-of select="@error" />
                    </xsl:attribute>
                </xsl:if>
                <xsl:if test="@pattern">
                    <xsl:attribute name="tmt:pattern">
                        <xsl:value-of select="@pattern" />
                    </xsl:attribute>
                </xsl:if>
                <xsl:for-each select="$objects[@name=$context]/itemList">
                    <option value="{item[@name='abbrev']|item[@name='code']|item[@name='type']|item[@name='category']|item[@name='name']}">
                        <xsl:if test="item[@name='abbrev'] = $value or item[@name='code'] = $value or item[@name='type'] = $value or item[@name='category'] = $value or item[@name='name'] = $value">
                            <xsl:attribute name="selected">selected</xsl:attribute>
                        </xsl:if>
                        <xsl:value-of select="item[@name='name']|item[@name='type']|item[@name='category']" />
                    </option>
                </xsl:for-each>
            </select>
        </label>
    </xsl:template>
	
    <xsl:template match="form:item">
        <xsl:param name="data" select="." />
        <xsl:param name="position" select="999" />
        <xsl:param name="select" select="@select" />
        <xsl:variable name="id" select="@id" />
        <tr id="item_{@id}">
            <td valign="top" style="width:20%">
                <b>
                    <xsl:value-of select="@title" />
                </b>
            </td>
            <td valign="top" style="width:10px"> </td>
            <td valign="top" style="width:80%">
                <xsl:apply-templates>
                    <xsl:with-param name="this" select="." />
                    <xsl:with-param name="data" select="$data" />
                    <xsl:with-param name="position" select="$position" />
                </xsl:apply-templates>
            </td>
        </tr>
    </xsl:template>
	
    <xsl:template match="form:true-false">
        <xsl:param name="this" select="." />
        <xsl:param name="data" select="." />
        <xsl:param name="position" select="999" />
        <xsl:param name="select" select="@select" />
        <xsl:variable name="id">
            <xsl:choose>
                <xsl:when test="@id">
                    <xsl:value-of select="@id" />
                </xsl:when>
                <xsl:otherwise>
                    <xsl:value-of select="$this/@id" />
                </xsl:otherwise>
            </xsl:choose>
        </xsl:variable>
        <xsl:variable name="test">
            <xsl:call-template name="nut:value-of">
                <xsl:with-param name="select" select="concat('$item/',$id)" />
                <xsl:with-param name="data" select="$data" />
                <xsl:with-param name="position" select="$position" />
            </xsl:call-template>
        </xsl:variable>
        <xsl:variable name="testing">
            <xsl:if test="$test='true'">true</xsl:if>
        </xsl:variable>
        <xsl:choose>
            <xsl:when test="$testing='true'">
                <input name="field!{$id}" type="radio" value="true" checked="checked" />True
                <input name="field!{$id}" type="radio" value="false" />False
            </xsl:when>
            <xsl:otherwise>
                <input name="field!{$id}" type="radio" value="true" />True
                <input name="field!{$id}" type="radio" value="false" checked="checked" />False
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>
	
    <xsl:template match="form:check">
        <xsl:param name="this" select="." />
        <xsl:param name="data" select="." />
        <xsl:param name="position" select="999" />
        <xsl:param name="select" select="@select" />
        <xsl:variable name="id">
            <xsl:choose>
                <xsl:when test="@id">
                    <xsl:value-of select="@id" />
                </xsl:when>
                <xsl:otherwise>
                    <xsl:value-of select="$this/@id" />
                </xsl:otherwise>
            </xsl:choose>
        </xsl:variable>
        <xsl:variable name="test">
            <xsl:call-template name="nut:value-of">
                <xsl:with-param name="select" select="concat('$item/',$id)" />
                <xsl:with-param name="data" select="$data" />
                <xsl:with-param name="position" select="$position" />
            </xsl:call-template>
        </xsl:variable>
        <xsl:variable name="testing">
            <xsl:if test="$test != ''">true</xsl:if>
        </xsl:variable>
        <xsl:choose>
            <xsl:when test="$testing='true'">
                <input type="checkbox" name="field!{$id}" value="yes" checked="checked" />
            </xsl:when>
            <xsl:otherwise>
                <input type="checkbox" name="field!{$id}" value="yes" />
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>
	
    <xsl:template match="form:field">
        <xsl:param name="this" select="." />
        <xsl:param name="data" select="." />
        <xsl:param name="position" select="999" />
        <xsl:param name="select" select="@select" />
        <xsl:variable name="id">
            <xsl:choose>
                <xsl:when test="@id">
                    <xsl:value-of select="@id" />
                </xsl:when>
                <xsl:otherwise>
                    <xsl:value-of select="$this/@id" />
                </xsl:otherwise>
            </xsl:choose>
        </xsl:variable>
        <xsl:variable name="sz">
            <xsl:choose>
                <xsl:when test="@size">
                    <xsl:value-of select="@size" />
                </xsl:when>
                <xsl:otherwise>100</xsl:otherwise>
            </xsl:choose>
        </xsl:variable>
        <xsl:variable name="value">
            <xsl:call-template name="nut:value-of">
                <xsl:with-param name="select" select="concat('$item/',$id)" />
                <xsl:with-param name="data" select="$data" />
                <xsl:with-param name="position" select="$position" />
            </xsl:call-template>
        </xsl:variable>
        <xsl:variable name="on">
            <xsl:call-template name="toggle">
                <xsl:with-param name="list" select="toggle" />
            </xsl:call-template>
        </xsl:variable>
        <xsl:choose>
            <xsl:when test="@text-area='true'">
                <xsl:choose>
                    <xsl:when test="@content-object">
                        <xsl:variable name="lut" select="@content-object" />
                        <xsl:variable name="cnt" select="$objects[@type=$lut]" />
                        <xsl:variable name="cnt_e" select="$objects[@type=concat($lut,'-ENCODED')]" />
                        <object type="application/x-xstandard" id="editor1" width="100%" height="600">
                            <param name="Value">
                                <xsl:attribute name="value">
                                    <xsl:copy-of select="$cnt_e/item/*|$cnt_e/item/text()" />
                                </xsl:attribute>
                            </param>
                            <textarea name="alternate1" id="alternate1" cols="60" rows="15">
                                <xsl:copy-of select="$cnt/item/*|$cnt/item/text()" />
                            </textarea>
                        </object>
                        <textarea name="xhtml1" id="xhtml1" cols="60" rows="15" style="display:none;"></textarea>
						<!-- <input type="hidden" name="xhtml1" id="xhtml1" value="" /> -->
                    </xsl:when>
                    <xsl:otherwise>
                        <textarea name="field!{$id}" id="field!{$id}" rows="{$sz}" cols="80" onkeyup="{$on}">
                            <xsl:copy-of select="$value" />
                        </textarea>
                    </xsl:otherwise>
                </xsl:choose>
            </xsl:when>
            <xsl:otherwise>
                <input type="text" name="field!{$id}" id="field!{$id}" size="{$sz}" value="{$value}" onkeyup="{$on}" />
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>
	
    <xsl:template match="form:button">
        <xsl:param name="this" select="." />
        <xsl:param name="data" select="." />
        <xsl:param name="position" select="999" />
        <input type="button" value="{@label}" />
    </xsl:template>
	
    <xsl:template match="form:checkbox">
        <xsl:param name="this" select="." />
        <xsl:param name="data" select="." />
        <xsl:param name="position" select="999" />
		
        <xsl:variable name="test">
            <xsl:call-template name="nut:value-of">
                <xsl:with-param name="select" select="@value" />
                <xsl:with-param name="data" select="$data" />
                <xsl:with-param name="position" select="$position" />
            </xsl:call-template>
        </xsl:variable>
		
        <input type="checkbox" name="{@name}" value="{@label}">
            <xsl:if test="normalize-space($test) != ''">
                <xsl:attribute name="checked">checked</xsl:attribute>
            </xsl:if>
        </input>
    </xsl:template>
	
    <xsl:template match="form:radio">
        <xsl:param name="this" select="." />
        <xsl:param name="data" select="." />
        <xsl:param name="position" select="999" />
        <xsl:param name="select" select="@select" />
        <xsl:variable name="id">
            <xsl:choose>
                <xsl:when test="@id">
                    <xsl:value-of select="@id" />
                </xsl:when>
                <xsl:otherwise>
                    <xsl:value-of select="$this/@id" />
                </xsl:otherwise>
            </xsl:choose>
        </xsl:variable>
        <xsl:variable name="sz">
            <xsl:choose>
                <xsl:when test="@size">
                    <xsl:value-of select="@size" />
                </xsl:when>
                <xsl:otherwise>50</xsl:otherwise>
            </xsl:choose>
        </xsl:variable>
        <xsl:variable name="value">
            <xsl:call-template name="nut:value-of">
                <xsl:with-param name="select" select="concat('$item/',$id)" />
                <xsl:with-param name="data" select="$data" />
                <xsl:with-param name="position" select="$position" />
            </xsl:call-template>
        </xsl:variable>
        <xsl:variable name="list">
            <xsl:call-template name="depend">
                <xsl:with-param name="status" select="'true'" />
                <xsl:with-param name="list" select="on" />
            </xsl:call-template>
            <xsl:call-template name="depend">
                <xsl:with-param name="status" select="'false'" />
                <xsl:with-param name="list" select="off" />
            </xsl:call-template>
        </xsl:variable>
        <input name="field!{$id}" type="radio" value="{@value}" onfocus="{$list}">
            <xsl:if test="$value=@value">
                <xsl:attribute name="checked">checked</xsl:attribute>
            </xsl:if>
        </input>
        <xsl:value-of select="@label" />
        <xsl:if test="$value=@value">
            <script type="text/javascript">
				
				registerEvents("
                <xsl:value-of select="$id" />","
                <xsl:copy-of select="$list" />");
				
			//
            </script>
        </xsl:if>
    </xsl:template>
	
    <xsl:template name="depend">
        <xsl:param name="status" select="'true'" />
        <xsl:param name="list" />
        <xsl:variable name="after" select="substring-after($list,',')" />
        <xsl:variable name="front" select="substring-before($list,',')" />
        <xsl:variable name="item">
            <xsl:choose>
                <xsl:when test="$front">
                    <xsl:value-of select="$front" />
                </xsl:when>
                <xsl:otherwise>
                    <xsl:value-of select="$list" />
                </xsl:otherwise>
            </xsl:choose>
        </xsl:variable>
        <xsl:if test="normalize-space($item) != ''">typeDependencies(
            <xsl:value-of select="$status" />,'item_
            <xsl:value-of select="$item" />');
            <xsl:call-template name="depend">
                <xsl:with-param name="status" select="$status" />
                <xsl:with-param name="list" select="$after" />
            </xsl:call-template>
        </xsl:if>
    </xsl:template>
	
    <xsl:template name="toggle">
        <xsl:param name="list" />
        <xsl:variable name="after" select="substring-after($list,',')" />
        <xsl:variable name="front" select="substring-before($list,',')" />
        <xsl:variable name="item">
            <xsl:choose>
                <xsl:when test="$front">
                    <xsl:value-of select="$front" />
                </xsl:when>
                <xsl:otherwise>
                    <xsl:value-of select="$list" />
                </xsl:otherwise>
            </xsl:choose>
        </xsl:variable>
        <xsl:if test="normalize-space($item) != ''">toggleActivity(this.id,'
            <xsl:value-of select="$item" />');
            <xsl:call-template name="toggle">
                <xsl:with-param name="list" select="$after" />
            </xsl:call-template>
        </xsl:if>
    </xsl:template>
	
	
    <xsl:template match="form:auto-update">
        <script type="text/javascript">
			
			runEvents() ;
			
		//
        </script>
    </xsl:template>
	
</xsl:stylesheet>















