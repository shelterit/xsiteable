<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet 
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:nut="http://schema.shelter.nu/nut"
	xmlns:php="http://php.net/xsl"
	version="1.0"
>
	<xsl:template match="nut:control">
		<xsl:param name="data" select="." />
		<xsl:param name="parent" />
		<xsl:param name="position" select="0" />
		<xsl:param name="max" select="0" />
		<button class="small" type="button">
			
			<xsl:if test="@back">
				<xsl:attribute name="onclick"><xsl:if test="@pre"><xsl:value-of select="@pre" />;</xsl:if>$('#step<xsl:value-of select="number(@back)+1" />').hide('slow');$('#step<xsl:value-of select="@back" />').show('slow');</xsl:attribute>
			</xsl:if>
			<xsl:if test="@forward">
				<xsl:attribute name="onclick"><xsl:if test="@pre"><xsl:value-of select="@pre" />;</xsl:if>$('#step<xsl:value-of select="number(@forward)-1" />').hide('slow');$('#step<xsl:value-of select="@forward" />').show('slow');</xsl:attribute>
			</xsl:if>
			<xsl:if test="@style">
				<xsl:attribute name="style"><xsl:value-of select="@style" /></xsl:attribute>
			</xsl:if>
			<span><em>
			<xsl:if test="@back"> &lt;&lt; </xsl:if>
			<xsl:call-template name="nut:content">
				<xsl:with-param name="name" select="@name|@id" />
				<xsl:with-param name="default" select="@default" />
			</xsl:call-template>
			<xsl:if test="@forward"> &gt;&gt; </xsl:if>
		</em></span></button>
	</xsl:template>
	
	<xsl:template match="nut:button">
		<xsl:param name="data" select="." />
		<xsl:param name="parent" />
		<xsl:param name="position" select="0" />
		<xsl:param name="max" select="0" />
		<xsl:variable name="link"><xsl:call-template name="digest-variables">
			<xsl:with-param name="text" select="@href" />
			<xsl:with-param name="data" select="$data" />
			<xsl:with-param name="parent" select="$parent" />
			<xsl:with-param name="position" select="$position" />
			<xsl:with-param name="max" select="$max" />
		</xsl:call-template></xsl:variable>
		<button style="cursor:pointer;">
			<xsl:if test="@style">
				<xsl:attribute name="style"><xsl:value-of select="@style" /></xsl:attribute>
			</xsl:if>
			<xsl:if test="@value">
				<xsl:attribute name="value"><xsl:value-of select="@value" /></xsl:attribute>
			</xsl:if>
			<xsl:if test="@name">
				<xsl:attribute name="name"><xsl:value-of select="@name" /></xsl:attribute>
			</xsl:if>
			<xsl:if test="$link != ''">
				<xsl:choose>
					<xsl:when test="@confirm = 'true'">
						<xsl:attribute name="onclick">confirm('<xsl:value-of select="$link" />');</xsl:attribute>
					</xsl:when>
					<xsl:otherwise>
						<xsl:attribute name="onclick">location.href='<xsl:value-of select="$link" />';return false;</xsl:attribute>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:if>
			<xsl:if test="$link = ''">
				<xsl:attribute name="type">submit</xsl:attribute>
			</xsl:if>
			<span><em>
			<xsl:call-template name="nut:content">
				<xsl:with-param name="name" select=".|@label|@name|@id" />
				<xsl:with-param name="default" select="@default" />
			</xsl:call-template>
		</em></span></button>
	</xsl:template>
	
	<xsl:template match="nut:help">
		<span class="formInfo">
			<a href="/static/js/help/{@name}.htm?width=475" id="help{@name}" class="jTip">?</a>
		</span>
	</xsl:template>

    <xsl:template match="nut:plugins|nut:plugin">
        <xsl:param name="data" />
        <xsl:param name="parent" select="." />
        <xsl:param name="input" />
        <xsl:param name="position" />
        <xsl:param name="max" select="0" />
        <!-- [NUT:PLUGIN @event=<xsl:value-of select="@event" />] -->
        <xsl:variable name="fetch" select="php:function ( 'gui_actions', @event )" />
        <!-- <xsl:copy-of select="$fetch/span/*" /> -->
        <!-- All responses are <span>wrapped in a span</span>, so just output what's in it -->
            <xsl:apply-templates select="$fetch/span/*">
                <xsl:with-param name="data" select="$data" />
                <xsl:with-param name="parent" select="$parent" />
                <xsl:with-param name="input" select="$input" />
                <xsl:with-param name="position" select="$position" />
                <xsl:with-param name="max" select="$max" />
            </xsl:apply-templates>
    </xsl:template>

	<!-- Internationalized labels -->
	
	<xsl:template match="nut:label|nut:date">
		<xsl:param name="data" select="." />
		<xsl:param name="parent" />
		<xsl:param name="position" select="0" />
		<xsl:param name="max" select="0" />
		<xsl:param name="select" select="@select|@name" />
		<xsl:param name="lookup" select="@lookup" />
		<xsl:param name="default" select="@default" />
		<xsl:variable name="value"><xsl:call-template name="digest-variables">
			<xsl:with-param name="text" select="$select" />
			<xsl:with-param name="data" select="$data" />
			<xsl:with-param name="parent" select="$parent" />
			<xsl:with-param name="position" select="$position" />
			<xsl:with-param name="max" select="$max" />
		</xsl:call-template></xsl:variable>		
		<xsl:choose>
			<xsl:when test="$lookup">
				<xsl:variable name="l" select="$objects[@name=$lookup]/item/item[@name='id'][normalize-space(.)=normalize-space($value)]/../item[@name='label']" />
				<xsl:value-of select="php:function ( 'label', $l, $language )" />
			</xsl:when>
			<xsl:otherwise>

                            <xsl:choose>
                                    <xsl:when test="name() = 'nut:date' ">
                                        <xsl:value-of select="php:function ( 'timed', $value )" />
                                    </xsl:when>
                                    <xsl:otherwise>
                                        <xsl:value-of select="php:function ( 'label', $value, $language )" />
                                    </xsl:otherwise>
                            </xsl:choose>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
	
	<!-- Get content from the input XML of a certain name -->
	
	<xsl:template match="nut:content" name="nut:content">
		<xsl:param name="data" select="." />
		<xsl:param name="parent" />
		<xsl:param name="position" select="0" />
		<xsl:param name="template" select="@template" />
		<xsl:param name="select" select="@select" />
                <xsl:param name="allow-edit" select="true()" />
		<xsl:param name="default" select="@default" />
		<xsl:variable name="name"><xsl:call-template name="digest-variables">
			<xsl:with-param name="text" select="normalize-space(@name)" />
			<xsl:with-param name="data" select="$data" />
			<xsl:with-param name="parent" select="$parent" />
			<xsl:with-param name="position" select="$position" />
		</xsl:call-template></xsl:variable>
		<xsl:variable name="nugget" select="$content/item[@name=$name]" />
		<xsl:choose>
			<xsl:when test="$param.mode.content = 'true'">
				<div id="xs_content_{$nugget}" class="xsContent">
					<xsl:copy-of select="$nugget" />
				</div>
			</xsl:when>
			<xsl:otherwise>
				<xsl:choose>
					<xsl:when test="normalize-space($nugget) = ''">
						<xsl:choose>
							<xsl:when test="normalize-space($default) != ''">
                                <xsl:choose>
                                    <xsl:when test="not(@nocontent)">
        								<span id="xs_content_{$name}" class="xs_content_bit"><xsl:value-of select="php:function ( 'label', $default, $language )" /></span>
                                    </xsl:when>
                                    <xsl:otherwise>
                                        <xsl:value-of select="php:function ( 'label', $default, $language )" />
                                    </xsl:otherwise>
                                </xsl:choose>
							</xsl:when>
							<xsl:when test="@lorem-ipsum">
								<xsl:call-template name="lorem-ipsum" />
							</xsl:when>
							<xsl:otherwise>
								<span class="noContent">Content <b><xsl:value-of select="$name" /></b> missing</span>
							</xsl:otherwise>
						</xsl:choose>
					</xsl:when>
					<xsl:otherwise>
						<xsl:apply-templates select="$nugget">
							<xsl:with-param name="template" select="$template" />
							<xsl:with-param name="data" select="$data" />
							<xsl:with-param name="position" select="$position" />
						</xsl:apply-templates>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
	
	<!-- mode=contentfind is running the framework, hunting for content, creating an XML to represent this -->
	<xsl:template match="*|text()" mode="contentfind">
		<xsl:param name="data" select="." />
		<xsl:param name="position" select="0" />
			<xsl:apply-templates mode="contentfind">
				<xsl:with-param name="data" select="$data" />
			</xsl:apply-templates>
	</xsl:template>
	
	<xsl:template match="nut:import" mode="contentfind">
		<xsl:param name="data" select="." />
		<xsl:param name="position" select="0" />
		<module name="{@template}">
			<xsl:apply-templates select="document(concat('../',@template,'.xml'))/*" mode="contentfind">
				<xsl:with-param name="data" select="$data" />
				<xsl:with-param name="position" select="$position" />
			</xsl:apply-templates>
		</module>
	</xsl:template>
	
	<xsl:template match="nut:render" mode="contentfind">
		<xsl:param name="data" select="." />
		<xsl:param name="position" select="0" />
		<xsl:param name="template" select="@template" />
		<xsl:param name="select" select="@select" />
		<xsl:apply-templates select="$objects[@type=$select]" mode="contentfind">
			<xsl:with-param name="template" select="$template" />
			<xsl:with-param name="data" select="$data" />
			<xsl:with-param name="position" select="$position" />
		</xsl:apply-templates>
	</xsl:template>
	
	<xsl:template match="nut:apply-templates" mode="contentfind">
		<xsl:param name="data" select="." />
		<xsl:param name="position" select="0" />
		<xsl:choose>
			<xsl:when test="@select = '$page/template'">
				<module name="{$pageTemplate}">
					<xsl:apply-templates select="document(concat('../pages/',$pageTemplate))/*" mode="contentfind">
						<xsl:with-param name="data" select="$data" />
						<xsl:with-param name="position" select="$position" />
					</xsl:apply-templates>
				</module>
			</xsl:when>
			<xsl:otherwise><xsl:apply-templates mode="contentfind">
				<xsl:with-param name="data" select="$data" />
				<xsl:with-param name="position" select="$position" />
			</xsl:apply-templates></xsl:otherwise>
		</xsl:choose>
	</xsl:template>
	
	
	<xsl:template match="nut:content" mode="contentfind">
		<xsl:variable name="name"><xsl:call-template name="digest-variables">
			<xsl:with-param name="text" select="@name" />
		</xsl:call-template></xsl:variable>
		<xsl:variable name="module"><xsl:call-template name="digest-variables">
			<xsl:with-param name="text" select="@module" />
		</xsl:call-template></xsl:variable>
		<item name="{$name}" module="{$module}" template="{/response/item[@name='xs_page']/item[@name='template']}">
			<xsl:value-of select="$content/item[@name=$name]" />
		</item>
	</xsl:template>
	
</xsl:stylesheet>
