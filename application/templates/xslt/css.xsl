<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet 
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
	xmlns:nut="http://schema.shelter.nu/nut"
	version="1.0"
	exclude-result-prefixes="xsl nut"
>
	<xsl:template match="*" mode="css"></xsl:template>

    <xsl:template match="nut:variable" mode="css">
		<xsl:param name="data" select="." />
		<xsl:param name="parent" />
		<xsl:param name="orig" select="." />
		<xsl:param name="position" select="999" />
		<xsl:param name="max" select="0" />
        <xsl:param name="notfound" select="''" />
		<xsl:param name="select" select="@select" />
		<xsl:param name="input" select="@input" />
        <xsl:param name="selected" select="''" />
		<xsl:variable name="sel" select="$select" />
        <xsl:if test="$selected = @name">
            <xsl:variable name="res"><xsl:value-of select="@value" /><xsl:apply-templates>
                        <xsl:with-param name="data" select="$data" />
                        <xsl:with-param name="orig" select="$orig" />
                        <xsl:with-param name="position" select="$position" />
                        <xsl:with-param name="max" select="$max" />
                        <xsl:with-param name="notfound" select="$notfound" />
                        <xsl:with-param name="selected" select="$selected" />
                    </xsl:apply-templates></xsl:variable>
            <xsl:copy-of select="$res" />
        </xsl:if>
    </xsl:template>
<!--
    <xsl:template match="css" mode="css">
        <xsl:variable name="lut" select="*" />
        [<xsl:value-of select="count($lut)" />]
        [<xsl:copy-of select="." />]
    </xsl:template>
-->

</xsl:stylesheet>
