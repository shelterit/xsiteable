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
		<doc><xsl:apply-templates /></doc>
	</xsl:template>

	<xsl:template match="item">
		<field name="ACTIVE"><xsl:value-of select="@active" /></field>
		<field name="FULLTEXT"><xsl:value-of select="@fulltext" /></field>
		<field name="HEAT"><xsl:value-of select="@heat" /></field>
		<xsl:for-each select="*">
			<xsl:variable name="name"><xsl:call-template name="str:to-upper"><xsl:with-param name="text" select="name()" /></xsl:call-template></xsl:variable>
			<xsl:variable name="check" select="substring-before($name,'_LIST')" />
			<xsl:choose>
				<xsl:when test="normalize-space($check) != ''">
					<xsl:for-each select="*">
						<xsl:variable name="nameish"><xsl:call-template name="str:to-upper"><xsl:with-param name="text" select="name()" /></xsl:call-template></xsl:variable>
						<field name="{$nameish}"><xsl:value-of select="." /></field>
					</xsl:for-each>
				</xsl:when>
				<xsl:otherwise>
					<field name="{$name}"><xsl:value-of select="." /></field>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:for-each>
	</xsl:template>

	
</xsl:stylesheet>
