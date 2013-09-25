<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet 
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
	version="1.0"
	xmlns:xtm="http://www.topicmaps.org/xtm/1.0/"
	xmlns:str="http://xsltsl.org/string"
	xmlns:xlink="http://www.w3.org/1999/xlink"
	exclude-result-prefixes="xsl str xlink xtm"
>

	<!-- Specify output -->
	<xsl:output method="xml" indent="yes" encoding="utf-8" omit-xml-declaration="yes" />
	
	
	<!-- A bit of included string-handling code -->
	<xsl:include href="../string.xsl" />
	
	
	<!-- Various characters for text control -->
	<xsl:variable name="crlf" select="'&#10;'" />
	<xsl:variable name="fnutt">'</xsl:variable>
	
	<xsl:variable name="assocs" select="/topicMap/association" />
	<xsl:variable name="topics" select="/topicMap/topic" />
	<xsl:variable name="scope" select="/topicMap/topic//scope" />
	
	<xsl:template match="/">
		<xsl:apply-templates />
	</xsl:template>
	
	<!-- Input is XTM, output is TMAXIS -->
	<xsl:template match="topicMap">
		<topicMap 
			topics="{count($topics)}" 
			assocs="{count($assocs)}">
				<xsl:apply-templates />
		</topicMap>
	</xsl:template>

	<xsl:template name="testing-topic">
		
		<topic xml:id="some_topic">
			<name default="My topic">
				<scoped xml:idref="#s-type">Mitt emne</scoped>
				<variant xml:idref="#type1">Topic, my</variant>
			</name>
		</topic>	
		
		
	</xsl:template>
	
	<xsl:template match="association">
		<assoc xml:id="{generate-id(.)}">
			<xsl:if test="@id">
				<xsl:variable name="a"><xsl:call-template name="attr"><xsl:with-param name="text" select="@id" /></xsl:call-template></xsl:variable>
				<xsl:attribute name="xml:id"><xsl:value-of select="$a" /></xsl:attribute>
			</xsl:if>
			<xsl:if test="instanceOf">
				<type>
					<xsl:for-each select="instanceOf">
						<xsl:choose>
							<xsl:when test="position() = 1">
								<xsl:variable name="a"><xsl:call-template name="attr"><xsl:with-param name="text" select="topicRef/@xlink:href" /></xsl:call-template></xsl:variable>
								<xsl:attribute name="xml:idref"><xsl:value-of select="$a" /></xsl:attribute>
							</xsl:when>
							<xsl:otherwise>
								<xsl:variable name="a"><xsl:call-template name="attr"><xsl:with-param name="text" select="topicRef/@xlink:href" /></xsl:call-template></xsl:variable>
								<other xml:idref="{$a}" />
							</xsl:otherwise>
						</xsl:choose>
					</xsl:for-each>
				</type>
			</xsl:if>
			<xsl:for-each select="member">
				<xsl:variable name="t"><xsl:call-template name="attr"><xsl:with-param name="text" select="topicRef/@xlink:href" /></xsl:call-template></xsl:variable>
				<xsl:variable name="r"><xsl:call-template name="attr"><xsl:with-param name="text" select="roleSpec/topicRef/@xlink:href" /></xsl:call-template></xsl:variable>
				<member xml:idref="{$t}" role="{$r}" />
			</xsl:for-each>
		</assoc>
	</xsl:template>
	
	<xsl:template match="topic">
		<topic id="{@id}" xml:id="{generate-id(.)}">
			<xsl:variable name="this-id" select="@id" />
			<xsl:if test="subjectIdentity">
				<xsl:variable name="a"><xsl:call-template name="attr"><xsl:with-param name="text" select="subjectIdentity/subjectIndicatorRef/@xlink:href" /></xsl:call-template></xsl:variable>
				<id href="{$a}" />
			</xsl:if>
			<xsl:if test="instanceOf">
				<type>
					<xsl:for-each select="instanceOf">
						<xsl:choose>
							<xsl:when test="position() = 1">
								<xsl:variable name="a"><xsl:call-template name="attr"><xsl:with-param name="text" select="topicRef/@xlink:href" /></xsl:call-template></xsl:variable>
								<xsl:attribute name="xml:idref"><xsl:value-of select="$a" /></xsl:attribute>
							</xsl:when>
							<xsl:otherwise>
								<xsl:variable name="a"><xsl:call-template name="attr"><xsl:with-param name="text" select="topicRef/@xlink:href" /></xsl:call-template></xsl:variable>
								<other xml:idref="{$a}" />
							</xsl:otherwise>
						</xsl:choose>
					</xsl:for-each>
				</type>
			</xsl:if>
			<xsl:if test="baseName">
				<name>
					<xsl:if test="baseName[not(scope)]">
						<xsl:attribute name="default"><xsl:value-of select="baseName/baseNameString" /></xsl:attribute>
					</xsl:if>
					<xsl:for-each select="baseName">
						<xsl:choose>
							<xsl:when test="scope">
								<xsl:call-template name="refs">
									<xsl:with-param name="select" select="scope/topicRef" />
									<xsl:with-param name="element" select="'scoped'" />
									<xsl:with-param name="val" select="baseNameString" />
								</xsl:call-template>
							</xsl:when>
							<xsl:otherwise>
								<xsl:for-each select="variant">
									<xsl:variable name="a"><xsl:call-template name="attr"><xsl:with-param name="text" select="parameters/topicRef/@xlink:href" /></xsl:call-template></xsl:variable>
									<variant xml:idref="{$a}"><xsl:value-of select="variantName/resourceData" /></variant>
								</xsl:for-each>
							</xsl:otherwise>
						</xsl:choose>
					</xsl:for-each>
				</name>
			</xsl:if>
			<xsl:for-each select="occurrence">
				<occur>
					<xsl:for-each select="instanceOf">
						<xsl:choose>
							<xsl:when test="position() = 1">
								<xsl:variable name="a"><xsl:call-template name="attr"><xsl:with-param name="text" select="topicRef/@xlink:href" /></xsl:call-template></xsl:variable>
								<xsl:attribute name="xml:idref"><xsl:value-of select="$a" /></xsl:attribute>
							</xsl:when>
							<xsl:otherwise>
								<xsl:variable name="a"><xsl:call-template name="attr"><xsl:with-param name="text" select="topicRef/@xlink:href" /></xsl:call-template></xsl:variable>
								<other xml:idref="{$a}" />
							</xsl:otherwise>
						</xsl:choose>
					</xsl:for-each>
					<xsl:choose>
						<xsl:when test="resourceRef/@xlink:href">
							<xsl:attribute name="href"><xsl:call-template name="attr"><xsl:with-param name="text" select="resourceRef/@xlink:href" /></xsl:call-template></xsl:attribute>
						</xsl:when>
						<xsl:when test="resourceData">
							<xsl:copy-of select="resourceData" />
						</xsl:when>
					</xsl:choose>
				</occur>
			</xsl:for-each>
			<relations>
				<xsl:call-template name="refs">
					<xsl:with-param name="select" select="$assocs[member/roleSpec/topicRef/@xlink:href=concat('#',$this-id)]" />
					<xsl:with-param name="element" select="'as-role'" />
				</xsl:call-template>
				<xsl:call-template name="refs">
					<xsl:with-param name="select" select="$assocs[member/topicRef/@xlink:href=concat('#',$this-id)]" />
					<xsl:with-param name="element" select="'as-member'" />
				</xsl:call-template>
				<xsl:call-template name="refs">
					<xsl:with-param name="select" select="$topics[instanceOf/topicRef/@xlink:href=concat('#',$this-id)]" />
					<xsl:with-param name="element" select="'as-type'" />
				</xsl:call-template>

				<!--
				<xsl:variable name="s" select="$scope[topicRef/@xlink:href=concat('#',$this-id)]" />
				<xsl:if test="count($s) != 0">
					<as-scope>
						<xsl:for-each select="$s">
							<xsl:attribute name="{concat('ref-',position())}"><xsl:value-of select="generate-id()" /></xsl:attribute>
						</xsl:for-each>
					</as-scope>
				</xsl:if>
				<xsl:variable name="o" select="$topics[occurrence/instanceOf/topicRef/@xlink:href=concat('#',$this-id)]" />
				-->
				<!-- <assocs count-as-member="{count($m)}" count-as-scope="{count($s)}" count-as-role="{count($c)}" count-as-occurrence="{count($o)}" count-as-type="{count($t)}" /> -->
			</relations>
		</topic>
	</xsl:template>

	<xsl:template name="refs">
		<xsl:param name="select" />
		<xsl:param name="element" select="'unknown'" />
		<xsl:param name="val" select="'false'" />
		<xsl:if test="count($select) != 0">
			<xsl:element name="{$element}">
				<!-- <xsl:attribute name="count"><xsl:value-of select="count($select)" /></xsl:attribute> -->
				<xsl:for-each select="$select">
					<xsl:choose>
						<xsl:when test="@xlink:href">
							<xsl:variable name="a"><xsl:call-template name="attr"><xsl:with-param name="text" select="@xlink:href" /></xsl:call-template></xsl:variable>
							<xsl:attribute name="{concat('idref-',position())}"><xsl:value-of select="$a" /></xsl:attribute>
						</xsl:when>
						<xsl:otherwise>
							<xsl:attribute name="{concat('ref-',position())}"><xsl:value-of select="generate-id()" /></xsl:attribute>
						</xsl:otherwise>
					</xsl:choose>
				</xsl:for-each>
				<xsl:if test="$val!='false'">
					<xsl:value-of select="$val" />
				</xsl:if>
			</xsl:element>
		</xsl:if>
	</xsl:template>	
	
	<xsl:template name="attr">
		<xsl:param name="text" select="." />
		<xsl:value-of select="translate($text,'#','')" />
	</xsl:template>
	
</xsl:stylesheet>
