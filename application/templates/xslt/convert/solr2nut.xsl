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
		<xsl:apply-templates select="itemList/item/result" />
	</xsl:template>

	<xsl:template match="result">
		<xsl:variable name="me" select="." />
		<itemList count="{@numFound}" scoreMax="{@maxScore}" type="LOON-RESPONSE-NUT-ITEM">
			<xsl:for-each select="doc">
				<item score="{*[@name='score']}" active="{*[@name='ACTIVE']}" fulltext="{*[@name='FULLTEXT']}" heat="{*[@name='HEAT']}">
					<xsl:apply-templates select="*" />
				</item>
			</xsl:for-each>
		</itemList>
	</xsl:template>
	
	<xsl:template match="em|text()"><xsl:value-of select="." /><xsl:apply-templates /></xsl:template>
	
	<xsl:template match="*[@name='score']|*[@name='ACTIVE']|*[@name='FULLTEXT']|*[@name='HEAT']"></xsl:template>
	
	<xsl:template match="str|bool|int|array|float|int">
		<xsl:variable name="name"><xsl:call-template name="str:to-lower"><xsl:with-param name="text" select="@name" /></xsl:call-template></xsl:variable>
		<xsl:if test="$name='description'">
			<xsl:variable name="id" select="../*[@name='ID']" />
			<xsl:variable name="find" select="/itemList/item/lst[@name='highlighting']/lst[@name=$id]/arr[@name='DESCRIPTION']/str" />
			<!-- <xsl:if test="$find"> -->
				<xsl:variable name="new" select="concat($name,'_highlight')" />
				<xsl:element name="{$new}">
					<xsl:copy-of select="$find/*|$find/text()" />
				</xsl:element>
			<!-- </xsl:if> -->
		</xsl:if>
		<xsl:if test="$name='keywords'">
			<xsl:variable name="id" select="../*[@name='ID']" />
			<xsl:variable name="find" select="/itemList/item/lst[@name='highlighting']/lst[@name=$id]/arr[@name='KEYWORDS']/str" />
			<!-- <xsl:if test="$find"> -->
				<xsl:variable name="new" select="concat($name,'_highlight')" />
				<xsl:element name="{$new}">
					<xsl:copy-of select="$find/*|$find/text()" />
				</xsl:element>
			<!-- </xsl:if> -->
		</xsl:if>
		<xsl:element name="{$name}">
			<xsl:value-of select="." />
		</xsl:element>
	</xsl:template>

	<xsl:template match="arr">
		<xsl:variable name="name"><xsl:call-template name="str:to-lower"><xsl:with-param name="text" select="@name" /></xsl:call-template></xsl:variable>
		<xsl:variable name="selection" select="./*" />
		<xsl:element name="{$name}_list">
			<xsl:for-each select="$selection">
				<xsl:element name="{$name}">
					<xsl:value-of select="." />
				</xsl:element>
			</xsl:for-each>
		</xsl:element>
	</xsl:template>

	
</xsl:stylesheet>
