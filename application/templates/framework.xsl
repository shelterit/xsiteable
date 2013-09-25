<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet 

	xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
        xmlns:exsl="http://exslt.org/common"
	xmlns:nut="http://schema.shelter.nu/nut"
	xmlns:tm="http://schema.shelter.nu/tm"
	xmlns:str="http://xsltsl.org/string"
	xmlns:tmt="http://www.massimocorner.com/libraries/"
	xmlns:php="http://php.net/xsl"
	xmlns:form="http://schema.shelter.nu/nut-form"
	xmlns:f="http://schema.shelter.nu/nut-formy"
	exclude-result-prefixes="xsl nut tm str php exsl form f"
	version="1.0"
>
	<!-- Specify output -->
	<xsl:output method="html" indent="yes" encoding="utf-8" omit-xml-declaration="yes" />

	<!-- Nice and clean output -->
	<!-- <xsl:strip-space elements="*" /> -->
	
	
	<!-- Include files -->
	<xsl:include href="xslt/nut.xsl" />
	<xsl:include href="xslt/nut-conditional.xsl" />
	<xsl:include href="xslt/nut-context.xsl" />
	<xsl:include href="xslt/nut-content.xsl" />
	<xsl:include href="xslt/html.xsl" />
	<xsl:include href="xslt/css.xsl" />
	<xsl:include href="xslt/gui.xsl" />
	<xsl:include href="xslt/widgets.xsl" />
	<xsl:include href="xslt/form.xsl" />
	<xsl:include href="xslt/string.xsl" />
	
	<!-- Parameters -->
	
	<!-- mode.content can be 'normal' (normal rendering), 'inline' for inline editing, and 'find' for XML markers -->
	<xsl:param name="param.mode.content" select="'normal'" />

	<!-- manual template -->
	<xsl:param name="param.template" select="''" />

	<!-- Global variables -->
	
	<!-- Various characters for text control -->
	<xsl:variable name="crlf" select="'&#10;'" />
	<xsl:variable name="fnutt">'</xsl:variable>

	
	<xsl:variable name="debug" select="'false'" />
	
	<xsl:variable name="input" select="/response" />
	<xsl:variable name="objects" select="$input/item" />
	<xsl:variable name="language" select="$input/item[@name='xs_page']/item[@name='language']" />
	<xsl:variable name="languages" select="$input/item[@name='xs_languages']" />
	<xsl:variable name="profile" select="$input/item[@name='xs_application_profiling']" />
	<xsl:variable name="content" select="$input/item[@name='xs_content']" />
	<xsl:variable name="dir" select="$input/item[@name='xs_dir']" />
	<xsl:variable name="page" select="$input/item[@name='xs_page']" />

	<xsl:variable name="pageTemplate"><xsl:choose>
		<xsl:when test="$input/item[@name='xs_page']/item[@name='template']"><xsl:value-of select="concat($input/item[@name='xs_page']/item[@name='template'],'.xml')" /></xsl:when>
		<xsl:otherwise>index.xml</xsl:otherwise>
	</xsl:choose></xsl:variable>

	
	<!-- File used to map xs_* constants to shorter handy names, i.e. 
		xs_paths with item named 'static' is mapped as "$dir/static",
		so in our XML templates you can do <nut:value-of select="$dir/static" /> or
		{$dir/static} in attributes.  -->
	
	<xsl:variable name="global" select="document('./global_values.xml')/*" />
	
	
	
	<!-- On with the show! -->
	
	<xsl:template match="/">

		<!-- First template to kick off the framework ; look in 'xslt/nut.xsl' -->
		
		<xsl:choose>
			<xsl:when test="$param.mode.content = 'find'">
				<response xmlns="" name="xs_content">
					<xsl:apply-templates select="document('./global/framework.xml')/*" mode="contentfind">
						<xsl:with-param name="data" select="$objects" />
					</xsl:apply-templates>
				</response>
			</xsl:when>
			<xsl:when test="$param.mode.content = 'blank'">
				<xsl:apply-templates select="document('./global/framework_blank.xml')/*">
					<xsl:with-param name="data" select="$objects" />
				</xsl:apply-templates>
			</xsl:when>
			<xsl:when test="$param.mode.content = 'css'">
				<xsl:apply-templates select="document('./global/framework_css.xml')/*">
					<xsl:with-param name="data" select="$objects" />
				</xsl:apply-templates>
			</xsl:when>
			<xsl:when test="$param.mode.content = 'widget'">
                            <xsl:variable name="request" select="$input/item[@name='xs_request']" />
                            <span>
                                <!--
                                [<xsl:copy-of select="$objects" />]
                                [<xsl:copy-of select="$request/item[@name='_controller_name']" />]
                                -->
                                <xsl:variable name="fetch" select="php:function('fetch_widget_setup_xml', $request/item[@name='_controller_name'])" />
                                <!-- [<xsl:copy-of select="$fetch" />] -->
				<xsl:apply-templates select="$fetch">
					<xsl:with-param name="data" select="$objects" />
				</xsl:apply-templates>
                            </span>
			</xsl:when>
			<xsl:otherwise>
				<xsl:apply-templates select="document('./global/framework.xml')/*">
					<xsl:with-param name="data" select="$objects" />
				</xsl:apply-templates>
                                <!-- [<xsl:value-of select="$pageTemplate" />] -->
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template match="tmt:*">
		<xsl:value-of select="." />
	</xsl:template>

	<xsl:template match="nut:variable"></xsl:template>

    <xsl:template match="nut:lorem-ipsum" name="lorem-ipsum">Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. <span style='display:none;'>Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</span></xsl:template>

</xsl:stylesheet>
