<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet 
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
	xmlns:nut="http://schema.shelter.nu/nut"
	xmlns:tmt="http://www.massimocorner.com/libraries/"
	version="1.0"
	exclude-result-prefixes="xsl nut tmt"
>
    
    <xsl:template match="*">
    	<xsl:element name="{local-name(.)}">
    		<xsl:apply-templates/>
    	</xsl:element>
    </xsl:template>

    <xsl:template match="@*">
    	<xsl:copy/>
    </xsl:template>    
	
	<xsl:template match="text()">
		<xsl:param name="data" />
		<xsl:param name="orig" />
		<xsl:param name="input" />
		<xsl:param name="parent" />
		<xsl:param name="var" />
		<xsl:param name="pos" select="0" />
		<xsl:call-template name="digest-variables">
			<xsl:with-param name="text" select="." />
			<xsl:with-param name="data" select="$data" />
			<xsl:with-param name="orig" select="$orig" />
			<xsl:with-param name="parent" select="$parent" />
			<xsl:with-param name="input" select="$input" />
			<xsl:with-param name="var" select="$var" />
			<xsl:with-param name="pos" select="$pos" />
		</xsl:call-template>
	</xsl:template>	
	
	<xsl:template match="blockquote"><xsl:copy-of select="text()|child::*" /></xsl:template>
	<xsl:template match="js">
		<xsl:param name="data" />
		<xsl:param name="orig" />
		<xsl:param name="input" />
		<xsl:param name="parent" />
		<xsl:param name="var" />
		<xsl:param name="pos" select="0" />
            <script type="text/javascript">
                <xsl:apply-templates select="text()|child::*">
                    <xsl:with-param name="data" select="$data" />
                    <xsl:with-param name="orig" select="$orig" />
                    <xsl:with-param name="parent" select="$parent" />
                    <xsl:with-param name="input" select="$input" />
                    <xsl:with-param name="var" select="$var" />
                    <xsl:with-param name="pos" select="$pos" />
                </xsl:apply-templates>
            </script>
        </xsl:template>
	<xsl:template match="style"><style><xsl:copy-of select="text()|child::*" /></style></xsl:template>
		
	<xsl:template match="html|body|iframe|head|title|link|a|table|thead|tbody|tr|td|dt|dl|dd|th|div|span|p|img|ol|ul|li|em|b|i|u|strong|h1|h2|h3|h4|h5|h6|script|noscript|form|input|button|fieldset|legend|textarea|select|option|label|br|hr|object|param|embed|sup|map|area|meta|abbr|article|aside|audio|canvas|datalist|details|dialog|eventsource|figure|footer|header|hgroup|mark|menu|meter|nav|output|progress|section|time|video">
		<xsl:param name="data" />
		<xsl:param name="orig" select="$data" />
		<xsl:param name="parent" />
		<xsl:param name="input" />
		<xsl:param name="var" />
		<xsl:param name="position" select="0" />
		<xsl:param name="max" select="0" />
		<xsl:variable name="current" select="." />
		<xsl:variable name="na"><xsl:choose><xsl:when test="name()='js'">script</xsl:when><xsl:otherwise><xsl:value-of select="name(.)" /></xsl:otherwise></xsl:choose></xsl:variable>
	   <xsl:element name="{$na}">
		  <xsl:for-each select="@*">
		  	<xsl:choose>
		  		<xsl:when test="substring(name(.),1,1) = '_'">
		  			<xsl:variable name="t"><xsl:call-template name="digest-variables">
						<xsl:with-param name="text" select="." />
						<xsl:with-param name="data" select="$data" />
						<xsl:with-param name="orig" select="$orig" />
						<xsl:with-param name="parent" select="$parent" />
						<xsl:with-param name="input" select="$input" />
						<xsl:with-param name="var" select="$var" />
						<xsl:with-param name="position" select="$position" />
						<xsl:with-param name="max" select="$max" />
					</xsl:call-template></xsl:variable>
						<xsl:attribute name="{substring(name(.),1)}"><xsl:value-of select="$t" /></xsl:attribute>
					<xsl:if test="normalize-space($t) != ''">
						<xsl:attribute name="{substring(name(.),2)}"><xsl:value-of select="$t" /></xsl:attribute>
					</xsl:if>
		  		</xsl:when>
		  		<xsl:otherwise>
					<xsl:attribute name="{name(.)}">
						<xsl:call-template name="digest-variables">
							<xsl:with-param name="text" select="." />
							<xsl:with-param name="data" select="$data" />
							<xsl:with-param name="orig" select="$orig" />
							<xsl:with-param name="parent" select="$parent" />
							<xsl:with-param name="input" select="$input" />
							<xsl:with-param name="var" select="$var" />
							<xsl:with-param name="position" select="$position" />
							<xsl:with-param name="max" select="$max" />
						</xsl:call-template>
					</xsl:attribute>
					<xsl:copy-of select="@tmt:*" />
		  		</xsl:otherwise>
		  	</xsl:choose>
		  </xsl:for-each>
			<xsl:choose>
				<xsl:when test="$data">
				  <xsl:apply-templates>
					<xsl:with-param name="data" select="$data" />
					<xsl:with-param name="orig" select="$orig" />
					<xsl:with-param name="parent" select="$parent" />
					<xsl:with-param name="input" select="$input" />
					<xsl:with-param name="var" select="$var" />
					<xsl:with-param name="position" select="$position" />
					<xsl:with-param name="max" select="$max" />
				  </xsl:apply-templates>
				</xsl:when>
				<xsl:otherwise>
					<xsl:choose>
						<xsl:when test="$na='scriptoulus'">
							<xsl:variable name="d">
								<xsl:apply-templates />
							</xsl:variable>
							<xsl:comment> <xsl:value-of select="$crlf" />
								 <xsl:copy-of select="$d" /> <xsl:value-of select="$crlf" />
							</xsl:comment>
						</xsl:when>
						<xsl:otherwise>
							<xsl:apply-templates />
						</xsl:otherwise>
					</xsl:choose>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:element>
	</xsl:template>	
	
</xsl:stylesheet>
