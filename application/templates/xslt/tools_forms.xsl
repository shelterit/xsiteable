<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet 
	xmlns="http://www.w3.org/1999/xhtml"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
	xmlns:nut="http://schema.shelter.nu/nut"
	version="1.0"
>

	<!-- Specify output -->

	<xsl:output method="xml" indent="yes" encoding="utf-8" omit-xml-declaration="yes" />

	<xsl:template match="form">
		
		$q = array() ;
		$q['url'] = <xsl:value-of select="@action" /> ;
		
		<xsl:for-each select="input">
			$q['input']['<xsl:value-of select="@name" />'] = <xsl:value-of select="@value" /> ;
		</xsl:for-each>
	</xsl:template>

	
</xsl:stylesheet>
