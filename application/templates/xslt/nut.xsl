<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet 
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:nut="http://schema.shelter.nu/nut"
	xmlns:tmt="http://www.massimocorner.com/libraries/"
	xmlns:str="http://xsltsl.org/string"
	version="1.0"
>
	
	<!-- Match the 'template' directive, and start rendering -->
	
	<xsl:template match="nut:template">
		<xsl:param name="data" select="." />
		<xsl:param name="orig" select="." />
		<xsl:param name="position" select="0" />
		<xsl:apply-templates>
			<xsl:with-param name="data" select="$data" />
			<xsl:with-param name="orig" select="$orig" />
			<xsl:with-param name="position" select="$position" />
		</xsl:apply-templates>
	</xsl:template>

	
	<!-- Continue rendering from the current context. Mainly used when building the
		 main framework. -->
	
	<xsl:template match="nut:apply-templates">
		<xsl:param name="data" select="." />
		<xsl:param name="orig" select="." />
		<xsl:param name="input" />
		<xsl:param name="parent" />
		<xsl:param name="position" select="0" />
		<xsl:choose>
			<xsl:when test="@select = '$page/template'">
				<xsl:apply-templates select="document(concat('../pages/',$pageTemplate))/*">
					<xsl:with-param name="data" select="$data" />
					<xsl:with-param name="orig" select="$orig" />
					<xsl:with-param name="parent" select="$parent" />
					<xsl:with-param name="input" select="$input" />
					<xsl:with-param name="position" select="$position" />
				</xsl:apply-templates>
			</xsl:when>
			<xsl:otherwise><xsl:apply-templates>
				<xsl:with-param name="data" select="$data" />
				<xsl:with-param name="orig" select="$orig" />
				<xsl:with-param name="parent" select="$parent" />
				<xsl:with-param name="input" select="$input" />
				<xsl:with-param name="position" select="$position" />
			</xsl:apply-templates></xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	
	<!-- Imports and applies another template -->
	
	<xsl:template match="nut:import">
		<xsl:param name="data" select="." />
		<xsl:param name="orig" select="." />
		<xsl:param name="input" />
		<xsl:param name="parent" />
		<xsl:param name="position" select="0" />
		<xsl:apply-templates select="document(concat('../',@template,'.xml'))/*">
			<xsl:with-param name="data" select="$data" />
			<xsl:with-param name="orig" select="$orig" />
			<xsl:with-param name="parent" select="$parent" />
			<xsl:with-param name="input" select="$input" />
			<xsl:with-param name="position" select="$position" />
		</xsl:apply-templates>
	</xsl:template>

	
	<!-- Render a selection; used when generic templating is considered evil -->
	
	<xsl:template match="nut:render">
		<xsl:param name="template" select="@template" />
		<xsl:param name="select" select="@select" />
		<xsl:apply-templates select="$objects[@type=$select]">
			<xsl:with-param name="template" select="$template" />
		</xsl:apply-templates>
	</xsl:template>

	
	<!-- Display the input XML, either as a dump, or with @select='profile' a
		 pretty table with the profile / timing information, if it's there. -->
	

	<xsl:template match="nut:debug">
		<xsl:choose>
			<xsl:when test="@select = 'profile'">
				<table cellspacing="0" cellpadding="1" border="0">
					<xsl:for-each select="$profile/item">
						<tr>
							<td valign="top" style="padding-right:20px;border-bottom:dotted 1px #888;color:#942;font-weight:bold;"><xsl:value-of select="@clocked-at" /></td>
								<td valign="top" style="padding-right:20px;border-bottom:dotted 1px #888;"><xsl:value-of select="@name" /></td>
							<td valign="top" style="padding-right:20px;border-bottom:dotted 1px #888;color:#666;"><xsl:value-of select="@time-lapse" /></td>
						</tr>
					</xsl:for-each>
				</table>
			</xsl:when>
			<xsl:otherwise>
				<br /><br />----------------------------------------------<br />
				<pre style="background-color:#afb;">
					<xsl:copy-of select="$input" />
				</pre>
				<br /><br />----------------------------------------------<br />
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	
	<!-- Dumps XML, but only the currently selected context -->
	
	<xsl:template match="nut:dump-context">
		<xsl:param name="data" />
		<pre style="background-color:#def;color:#583;padding:5px;border:dotted 1px #987;">
			[<xsl:copy-of select="$data" />]
		</pre>
	</xsl:template>
	
	
</xsl:stylesheet>
