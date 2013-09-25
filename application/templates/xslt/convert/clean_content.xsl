<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet 
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
	version="1.0"
	xmlns:str="http://xsltsl.org/string"
	exclude-result-prefixes="xsl str"
>

	<!-- Specify output -->

	<xsl:output method="xml" indent="yes" encoding="utf-8" omit-xml-declaration="yes" />

	<xsl:include href="../string.xsl" />
	
	<!-- Various characters for text control -->
	<xsl:variable name="crlf" select="'&#10;'" />
	<xsl:variable name="fnutt">'</xsl:variable>

	
		
	
	<xsl:template match="/">
		<result name="{@name}">
			<xsl:apply-templates />
			<!-- <item id="199378" />
			<item id="380762" />
			<item id="237513" />
			<item id="250940" />
			<item id="1" />
			<item id="2" />
			<item id="3" />
			<item id="4" />
			<item id="5" /> -->
			<!-- <original>
				<xsl:copy-of select="/" />
			</original> -->
		</result>
	</xsl:template>

	<xsl:template match="item">
		<xsl:variable name="t" select="(ancestor::module[1]/@name)|@module" />
		<item id="{@name}" module="{$t}" template="{@template}" />
	</xsl:template>

	<xsl:template match="module">
		<xsl:variable name="test" select="count(item|module/item|module/module/item)" />
		<xsl:if test="$test &gt; 0">
			<xsl:apply-templates />
		</xsl:if>
	</xsl:template>

	
</xsl:stylesheet>
