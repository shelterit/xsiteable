<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet 
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
	xmlns:nut="http://schema.shelter.nu/nut"
	version="1.0"
>
			
	<xsl:template match="itemList">
		<pre>{not-found{<xsl:value-of select="@type" />}}</pre>
	</xsl:template>
	
	<xsl:template match="itemList[@type='LOON-RESPONSE-THESAURUS']">

		<xsl:for-each select="item[@type='LOON-RESPONSE-THESAURUS-TERM']">
			<div class="thesaurus-item">
				<p><a href="/app/eresources/search/{translate(@label,' ','+')}"><xsl:value-of select="@label" /></a></p>
				<xsl:for-each select="itemList/item">
					<p style="margin:0;padding:0;padding-left:10px;margin-left:10px;color:#b77;border-left:solid 1px blue;"><a href="/app/eresources/search/{translate(@label,' ','+')}"><xsl:value-of select="@label" /></a></p>
				</xsl:for-each>
			</div>
		</xsl:for-each>
		
		<xsl:if test="count(item[@type='LOON-RESPONSE-THESAURUS-TERM-ALSO']) != 0">
			<p style="margin:0;padding:0;padding-left:1em;color:#777;">
				<xsl:for-each select="item[@type='LOON-RESPONSE-THESAURUS-TERM-ALSO']">
					<li><a href="/app/eresources/search/{translate(@label,' ','+')}"><xsl:value-of select="@label" /></a></li>
				</xsl:for-each>
			</p>
		</xsl:if>
		
	</xsl:template>

	<xsl:template name="nut:list-items">
		<xsl:param name="selection" select="@selection" />
		<xsl:param name="template" select="@template" />
		<xsl:variable name="high" select="item/lst[@name='highlighting']" />
		<xsl:for-each select="$selection">
			<xsl:variable name="this" select="." />
			<xsl:choose>
				<xsl:when test="$template = 'item'">
					<div class="solr-item" style="margin:0;margin-top:10px;">
						<xsl:apply-templates select="*" />
					</div>
				</xsl:when>
				<xsl:otherwise>
					<xsl:variable name="id" select="int[@name='ID']" />
					<xsl:variable name="type" select="int[@name='TYPE']" />
					<div class="solr-item" style="margin:0;margin-top:10px;">
						<h4 style="border-bottom:solid 1px #987;color:red;"><a href="/app/eresources/item/{*[@name='ID']}"><xsl:value-of select="*[@name='TITLE']" /></a></h4>
						<xsl:apply-templates select="*[@name='PUBLISHER']" />
						<xsl:choose>
							<xsl:when test="$high/lst[@name=$id]"><xsl:apply-templates select="$high/lst[@name=$id]/arr[@name='DESCRIPTION']" /></xsl:when>
							<xsl:otherwise><xsl:apply-templates select="*[@name='DESCRIPTION']" /></xsl:otherwise>
						</xsl:choose>
						<xsl:apply-templates select="*[@name='KEYWORDS']" />
					</div>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:for-each>
	</xsl:template>	
	
	<xsl:template match="itemList[@type='LOON-RESPONSE-SOLR']">
		<xsl:param name="template" select="@template" />
		<xsl:choose>
			<xsl:when test="$template = 'item'">
				<xsl:variable name="selection" select="item/result/doc" />
				<xsl:call-template name="nut:list-items">
					<xsl:with-param name="selection" select="$selection" />
					<xsl:with-param name="template" select="$template" />
				</xsl:call-template>
			</xsl:when>
			<xsl:when test="$template = 'guide'">
				<xsl:variable name="selection" select="item/result/doc/str[@name='TYPE'][normalize-string(.)='guide']/.." />
				<xsl:call-template name="nut:list-items">
					<xsl:with-param name="selection" select="$selection" />
				</xsl:call-template>
			</xsl:when>
			<xsl:otherwise>
				<xsl:variable name="selection" select="item/result/doc" />
				<xsl:call-template name="nut:list-items">
					<xsl:with-param name="selection" select="$selection" />
				</xsl:call-template>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
	
	<xsl:template match="bool|int|str|arr">
		<p>
			<span style="width:40px;background-color:#bef;"><b><xsl:value-of select="@name" /></b> </span> 
			<div style="margin-left:20px;"><xsl:apply-templates /></div>
		</p>
	</xsl:template>
	
	<xsl:template match="text()">
		<xsl:value-of select="." /><xsl:apply-templates />
	</xsl:template>
	
	
</xsl:stylesheet>
