<?xml version="1.0" encoding="UTF-8"?>

<!DOCTYPE xsl:stylesheet [
	<!ENTITY vann 'http://purl.org/vocab/vann/'>
	<!ENTITY foaf 'http://xmlns.com/foaf/0.1/'>
	<!ENTITY dcterms 'http://purl.org/dc/terms/'>
	<!ENTITY dc 'http://purl.org/dc/elements/1.1/'>
	<!ENTITY owl 'http://www.w3.org/2002/07/owl#'>
	<!ENTITY rdfs 'http://www.w3.org/2000/01/rdf-schema#'>
	<!ENTITY rdf 'http://www.w3.org/1999/02/22-rdf-syntax-ns#'>
        <!ENTITY gist "http://ontologies.semanticarts.com/gist#">
]>

<xsl:stylesheet 

	xmlns=""
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:vann="&vann;"
	xmlns:foaf="&foaf;"
	xmlns:owl="&owl;"
	xmlns:rdf="&rdf;"
	xmlns:rdfs="&rdfs;"
	xmlns:dc="&dc;"
	xmlns:dcterms="&dcterms;"
	xmlns:gist="&gist;"
	exclude-result-prefixes="owl foaf vann rdf rdfs dc dcterms #default"
	
	version="1.0"
>
	<!-- Specify output  exclude-result-prefixes="xsl nut str php exsl"  -->
	<xsl:output method="xml" indent="yes" encoding="utf-8" omit-xml-declaration="yes" />

	<!-- Nice and clean output -->
	<xsl:strip-space elements="*" />


        <!-- rdf:RDF/owl:Class[@rdf:about] -->

        <xsl:key name="key-classes" match="rdf:RDF/owl:Class[@rdf:about]" use="@rdf:about" />
        <xsl:key name="key-props"   match="owl:ObjectProperty[@rdf:about]" use="@rdf:about" />

	
	
	<!-- On with the show! -->
	<xsl:template match="/">
            <xsl:variable name="classes" select="rdf:RDF/owl:Class[@rdf:about][count(. | key('key-classes', @rdf:about)[1]) = 1]" />
            <xsl:variable name="props" select="rdf:RDF/owl:ObjectProperty[@rdf:about][count(. | key('key-props', @rdf:about)[1]) = 1]" />
            class gist {
            <xsl:for-each select="$classes|$props">
               <xsl:sort select="@rdf:about" /> const _<xsl:value-of select="@rdf:about" /> = <xsl:value-of select="position() + 500" /> ;
            </xsl:for-each>
            <!--
            <xsl:for-each select="$classes">
               <xsl:sort select="@rdf:about" /> static function _<xsl:value-of select="@rdf:about" /> () { return <xsl:value-of select="position() + 500" /> ; }
            </xsl:for-each>
            <xsl:for-each select="$props">
               <xsl:sort select="@rdf:about" /> static function _<xsl:value-of select="@rdf:about" /> () { return <xsl:value-of select="position() + 500" /> ; }
            </xsl:for-each> -->
            }
        </xsl:template>

	<xsl:template match="*">
                <xsl:apply-templates />
<!--            <div style="border-left:solid 1px #ccc;border-bottom:solid 1px #ccc;margin:0;margin-left:3px;padding:0;padding-top:1px;padding-left:3px;">
            </div> -->
	</xsl:template>

	<xsl:template match="rdf:RDF/owl:Class[@rdf:about]">
		<li style="border:solid 1px red;"><xsl:value-of select="@rdf:about" /></li>
	</xsl:template>

	<xsl:template match="rdfs:*">   
	</xsl:template>

	<xsl:template match="rdf:*">
	</xsl:template>


	<xsl:template match="owl:ObjectProperty">
	</xsl:template>

	<xsl:template match="rdf:RDF">
            <!-- <div style="border-left:solid 1px #ccc;border-bottom:solid 1px #ccc;margin:0;margin-left:3px;padding:0;padding-top:1px;padding-left:3px;">
                <xsl:apply-templates />
            </div> -->
	</xsl:template>

	<xsl:template match="owl:Ontology">
            <!-- <div style="border-left:solid 1px #red;border-bottom:solid 1px #ccc;margin:0;margin-left:3px;padding:0;padding-top:1px;padding-left:3px;">
                <xsl:apply-templates />
            </div> -->
	</xsl:template>

</xsl:stylesheet>
