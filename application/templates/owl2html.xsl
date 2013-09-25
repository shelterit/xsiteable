<?xml version="1.0"?>
<!--
	owl2html v0.3
	A somewhat generic OWL to XHTML presentation conversion.

	# (c) 2003-2004 Morten Frederiksen
	# License: http://www.gnu.org/licenses/gpl
-->
<!DOCTYPE xsl:stylesheet [
	<!ENTITY vann 'http://purl.org/vocab/vann/'>
	<!ENTITY foaf 'http://xmlns.com/foaf/0.1/'>
	<!ENTITY dcterms 'http://purl.org/dc/terms/'>
	<!ENTITY dc 'http://purl.org/dc/elements/1.1/'>
	<!ENTITY owl 'http://www.w3.org/2002/07/owl#'>
	<!ENTITY rdfs 'http://www.w3.org/2000/01/rdf-schema#'>
	<!ENTITY rdf 'http://www.w3.org/1999/02/22-rdf-syntax-ns#'>
]>
<xsl:stylesheet
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns="http://www.w3.org/1999/xhtml"
	xmlns:vann="&vann;"
	xmlns:foaf="&foaf;"
	xmlns:owl="&owl;"
	xmlns:rdf="&rdf;"
	xmlns:rdfs="&rdfs;"
	xmlns:dc="&dc;"
	xmlns:dcterms="&dcterms;"
	exclude-result-prefixes="owl foaf vann rdf rdfs dc dcterms #default"
	version="1.0">
<xsl:include href="meta.xsl"/>
<xsl:output
	method="xml"
	omit-xml-declaration="no"
	doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN"
	doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"
	indent="yes"
	encoding="utf-8"/>

<xsl:param name="css" select="false()"/>
<xsl:param name="uri" select="false()"/>
<xsl:param name="lang" select="'en'"/>

<xsl:key name="resources" match="/*/*[@rdf:about]" use="@rdf:about"/>
<xsl:key name="bnodes" match="/*/*[@rdf:nodeID]" use="@rdf:nodeID"/>

<xsl:template match="/">
	<xsl:variable name="ns">
		<xsl:choose>
			<xsl:when test="$uri and contains($uri,'#')">
				<xsl:value-of select="substring-before($uri,'#')"/>
			</xsl:when>
			<xsl:when test="$uri">
				<xsl:value-of select="$uri"/>
			</xsl:when>
			<xsl:when test="/*/*[rdf:type/@rdf:resource='&owl;Ontology' and contains(@rdf:about,'#')]">
				<xsl:value-of select="substring-before(*/*[rdf:type/@rdf:resource='&owl;Ontology' and contains(@rdf:about,'#')][1]/@rdf:about,'#')"/>
			</xsl:when>
			<xsl:when test="/*/*[rdf:type/@rdf:resource='&owl;Ontology']">
				<xsl:value-of select="*/*[rdf:type/@rdf:resource='&owl;Ontology'][1]/@rdf:about"/>
			</xsl:when>
		</xsl:choose>
	</xsl:variable>
	<xsl:if test="not(/rdf:RDF)">
		<xsl:message terminate="yes">RDF/XML not found</xsl:message>
	</xsl:if>
	<html>
		<head>
			<title>
				<xsl:apply-templates mode="title" select="/*">
					<xsl:with-param name="ns" select="$ns"/>
				</xsl:apply-templates>
				<xsl:text> [</xsl:text>
					<xsl:value-of select="$ns"/>
				<xsl:text>]</xsl:text>
			</title>
			<xsl:choose>
				<xsl:when test="$css">
					<style type="text/css" media="all">
						<xsl:text disable-output-escaping="yes">
@import url("</xsl:text>
						<xsl:value-of select="$css"/>
						<xsl:text disable-output-escaping="yes">");&#10;    </xsl:text>
					</style>
				</xsl:when>
				<xsl:otherwise>
					<style type="text/css"><![CDATA[
body { background-color: #f0f0f0 }
h1,h2,h3 { margin: 1em 0 0.2em 0; clear: both }
h4 { clear: left; margin: 0; padding: 0 }
span.language { display: block; float: right }
p.comment { border: #888 1px solid; padding: 0.5em; margin: 0 0 0.5em 0 }
.details { float: right; margin: 0 0 0 0.5em; padding: 0.5em; border: #888 1px dashed }
.details ul { margin: 0 0 0 1em; padding: 0 }
dl.meta { clear: left; margin: 0; padding: 0 }
dl.meta dt,dl.meta dd { display: inline; margin: 0; padding: 0 }
dl.meta dl.meta { margin-left: 1em }
span.meta-label { font-style: italic }
pre.example {
	clear: left;
	float: left;
	background-color: #ffe;
	border: 1px solid #666;
	overflow: auto;
	padding: 0.5em;
	margin: 0.5em 0;
}
img { border: none; }
					]]></style>
				</xsl:otherwise>
			</xsl:choose>
		</head>
		<body>
			<xsl:apply-templates select="*">
				<xsl:with-param name="ns" select="$ns"/>
			</xsl:apply-templates>
		</body>
	</html>
</xsl:template>

<xsl:template mode="title" match="/rdf:RDF">
	<xsl:param name="ns"/>
	<xsl:choose>
		<xsl:when test="*[@rdf:about=$ns and rdfs:label]">
			<xsl:apply-templates mode="title" select="*[@rdf:about=$ns and rdfs:label][1]">
				<xsl:with-param name="ns" select="$ns"/>
			</xsl:apply-templates>
		</xsl:when>
		<xsl:when test="*[@rdf:about=$ns and dc:title]">
			<xsl:apply-templates mode="title" select="*[@rdf:about=$ns and dc:title][1]">
				<xsl:with-param name="ns" select="$ns"/>
			</xsl:apply-templates>
		</xsl:when>
		<xsl:when test="*[rdf:type/@rdf:resource='&owl;Ontology' and rdfs:label]">
			<xsl:apply-templates mode="title" select="*[rdf:type/@rdf:resource='&owl;Ontology' and rdfs:label][1]">
				<xsl:with-param name="ns" select="$ns"/>
			</xsl:apply-templates>
		</xsl:when>
		<xsl:when test="*[rdf:type/@rdf:resource='&owl;Ontology' and dc:title]">
			<xsl:apply-templates mode="title" select="*[rdf:type/@rdf:resource='&owl;Ontology' and dc:title][1]">
				<xsl:with-param name="ns" select="$ns"/>
			</xsl:apply-templates>
		</xsl:when>
		<xsl:otherwise>
			<xsl:text>OWL Ontology</xsl:text>
		</xsl:otherwise>
	</xsl:choose>
</xsl:template>

<xsl:template mode="title" match="*">
	<xsl:param name="ns"/>
	<xsl:choose>
		<xsl:when test="rdfs:label">
			<xsl:apply-templates mode="literal-value" select="rdfs:label"/>
		</xsl:when>
		<xsl:when test="dc:title">
			<xsl:apply-templates mode="literal-value" select="dc:title"/>
		</xsl:when>
		<xsl:when test="$ns!='' and starts-with(@rdf:about,$ns)">
			<xsl:value-of select="substring-after(@rdf:about,$ns)"/>
		</xsl:when>
		<xsl:otherwise>
			<xsl:text>?</xsl:text>
		</xsl:otherwise>
	</xsl:choose>
</xsl:template>

<xsl:template mode="id" match="*">
	<xsl:param name="ns"/>
	<xsl:choose>
		<xsl:when test="starts-with(@rdf:about,$ns) and contains(substring-after(@rdf:about,$ns),'#')">
			<xsl:value-of select="substring-after(substring-after(@rdf:about,$ns),'#')"/>
		</xsl:when>
		<xsl:when test="starts-with(@rdf:about,$ns)">
			<xsl:value-of select="substring-after(@rdf:about,$ns)"/>
		</xsl:when>
	</xsl:choose>
</xsl:template>

<xsl:template match="rdf:RDF">
	<xsl:param name="ns"/>
	<div class="ontology">
		<!-- Language selection -->
		<xsl:apply-templates mode="language" select="*[rdf:type[@rdf:resource='&owl;Ontology'] and rdfs:comment]/rdfs:comment[@xml:lang and @xml:lang!=$lang]">
			<xsl:with-param name="ns" select="$ns"/>
		</xsl:apply-templates>
		<!-- General information -->
		<h1>
			<xsl:apply-templates mode="title" select="/*">
				<xsl:with-param name="ns" select="$ns"/>
			</xsl:apply-templates>
		</h1>
		<xsl:choose>
			<xsl:when test="*[rdf:type/@rdf:resource='&owl;Ontology' and rdfs:comment]">
				<p class="comment">
					<xsl:apply-templates mode="literal-value" select="*[rdf:type/@rdf:resource='&owl;Ontology' and rdfs:comment][1]/rdfs:comment">
						<xsl:with-param name="ns" select="$ns"/>
					</xsl:apply-templates>
				</p>
			</xsl:when>
		</xsl:choose>
		<!-- TOC -->
		<xsl:if test="count(*[rdf:type[@rdf:resource='&owl;Class' or @rdf:resource='&owl;DeprecatedClass' or @rdf:resource='&rdfs;Class']])!=0 and *[rdf:type[@rdf:resource='&owl;Class' or @rdf:resource='&owl;DeprecatedClass' or @rdf:resource='&rdfs;Class']]&lt;20">
			<div class="details">
				<h4>Classes</h4>
				<ul>
					<xsl:for-each select="*[rdf:type[@rdf:resource='&owl;Class' or @rdf:resource='&owl;DeprecatedClass' or @rdf:resource='&rdfs;Class'] and not(rdfs:subClassOf[@rdf:resource=/*/*[rdf:type[@rdf:resource='&owl;Class' or @rdf:resource='&owl;DeprecatedClass' or @rdf:resource='&rdfs;Class']]/@rdf:about])]">
						<li>
							<a href="{@rdf:about}">
								<xsl:apply-templates mode="title" select="key('resources',@rdf:about)">
									<xsl:with-param name="ns" select="$ns"/>
								</xsl:apply-templates>
							</a>
							<xsl:apply-templates mode="sub-classes" select=".">
								<xsl:with-param name="ns" select="$ns"/>
							</xsl:apply-templates>
						</li>
					</xsl:for-each>
				</ul>
			</div>
		</xsl:if>
		<xsl:if test="*[rdf:type[@rdf:resource='&owl;AnnotationProperty' or @rdf:resource='&owl;OntologyProperty' or @rdf:resource='&owl;DatatypeProperty' or @rdf:resource='&owl;DeprecatedProperty' or @rdf:resource='&owl;FunctionalProperty' or @rdf:resource='&owl;InverseFunctionalProperty' or @rdf:resource='&owl;ObjectProperty' or @rdf:resource='&owl;SymmetricProperty' or @rdf:resource='&owl;TransitiveProperty' or @rdf:resource='&rdf;Property'] or rdfs:subPropertyOf]">
			<div class="details">
				<h4>Properties</h4>
				<ul>
					<xsl:for-each select="*[rdf:type[@rdf:resource='&owl;AnnotationProperty' or @rdf:resource='&owl;OntologyProperty' or @rdf:resource='&owl;DatatypeProperty' or @rdf:resource='&owl;DeprecatedProperty' or @rdf:resource='&owl;FunctionalProperty' or @rdf:resource='&owl;InverseFunctionalProperty' or @rdf:resource='&owl;ObjectProperty' or @rdf:resource='&owl;SymmetricProperty' or @rdf:resource='&owl;TransitiveProperty' or @rdf:resource='&rdf;Property'] or rdfs:subPropertyOf]">
						<li>
							<a href="{@rdf:about}">
								<xsl:apply-templates mode="title" select=".">
									<xsl:with-param name="ns" select="$ns"/>
								</xsl:apply-templates>
							</a>
						</li>
					</xsl:for-each>
				</ul>
			</div>
		</xsl:if>
		<dl class="meta">
			<dt>
				<span class="meta-label">namespace:</span>
			</dt>
			<dd>
				<a href="{$ns}">
					<xsl:choose>
						<xsl:when test="$uri!=''">
							<xsl:value-of select="$uri"/>
						</xsl:when>
						<xsl:otherwise>
							<xsl:value-of select="$ns"/>
						</xsl:otherwise>
					</xsl:choose>
				</a>
			</dd>
		</dl>
		<xsl:apply-templates mode="meta" select="*[rdf:type/@rdf:resource='&owl;Ontology' and rdfs:comment]/*"/>
		<!-- Classes -->
		<xsl:if test="*[rdf:type[@rdf:resource='&owl;Class' or @rdf:resource='&owl;DeprecatedClass' or @rdf:resource='&rdfs;Class']]">
			<div class="classes">
				<h2>Classes</h2>
				<xsl:apply-templates mode="class" select="*[rdf:type[@rdf:resource='&owl;Class' or @rdf:resource='&owl;DeprecatedClass' or @rdf:resource='&rdfs;Class']]">
					<xsl:sort select="@rdf:about"/>
					<xsl:with-param name="ns" select="$ns"/>
				</xsl:apply-templates>
			</div>
		</xsl:if>
		<!-- Properties -->
		<xsl:if test="*[rdf:type[@rdf:resource='&owl;AnnotationProperty' or @rdf:resource='&owl;OntologyProperty' or @rdf:resource='&owl;DatatypeProperty' or @rdf:resource='&owl;DeprecatedProperty' or @rdf:resource='&owl;FunctionalProperty' or @rdf:resource='&owl;InverseFunctionalProperty' or @rdf:resource='&owl;ObjectProperty' or @rdf:resource='&owl;SymmetricProperty' or @rdf:resource='&owl;TransitiveProperty' or @rdf:resource='&rdf;Property'] or rdfs:subPropertyOf]">
			<div class="properties">
				<h2>Properties</h2>
				<xsl:apply-templates mode="property" select="*[rdf:type[@rdf:resource='&owl;AnnotationProperty' or @rdf:resource='&owl;OntologyProperty' or @rdf:resource='&owl;DatatypeProperty' or @rdf:resource='&owl;DeprecatedProperty' or @rdf:resource='&owl;FunctionalProperty' or @rdf:resource='&owl;InverseFunctionalProperty' or @rdf:resource='&owl;ObjectProperty' or @rdf:resource='&owl;SymmetricProperty' or @rdf:resource='&owl;TransitiveProperty' or @rdf:resource='&rdf;Property'] or rdfs:subPropertyOf]">
					<xsl:sort select="@rdf:about"/>
					<xsl:with-param name="ns" select="$ns"/>
				</xsl:apply-templates>
			</div>
		</xsl:if>
	</div>
</xsl:template>

<xsl:template mode="language" match="*">
	<xsl:param name="ns"/>
	<span class="language">
		<a href="{$ns}.{@xml:lang}.html">
			<xsl:value-of select="@xml:lang"/>
		</a>
	</span>
</xsl:template>

<xsl:template mode="meta" priority="0.9" match="vann:example|rdf:type[@rdf:resource='&owl;Ontology']|rdfs:label|rdfs:comment|rdfs:domain|foaf:topic">
</xsl:template>

<xsl:template mode="meta-value" priority="0.9" match="rdfs:isDefinedBy|rdfs:subClassOf|rdfs:subPropertyOf|owl:disjointWith|rdfs:range">
	<a href="{@rdf:resource}">
		<xsl:value-of select="@rdf:resource"/>
	</a>
</xsl:template>

<xsl:template mode="term" match="*">
	<xsl:param name="ns"/>
	<h3>
		<xsl:variable name="id">
			<xsl:apply-templates mode="id" select=".">
				<xsl:with-param name="ns" select="$ns"/>
			</xsl:apply-templates>
		</xsl:variable>
		<xsl:choose>
			<xsl:when test="$id!=''">
				<a name="{$id}">
					<xsl:apply-templates mode="title" select=".">
						<xsl:with-param name="ns" select="$ns"/>
					</xsl:apply-templates>
				</a>
				<xsl:text> (</xsl:text>
				<a href="{@rdf:about}">
					<code>
						<xsl:value-of select="$id"/>
					</code>
				</a>
				<xsl:text>)</xsl:text>
			</xsl:when>
			<xsl:otherwise>
				<xsl:apply-templates mode="title" select=".">
					<xsl:with-param name="ns" select="$ns"/>
				</xsl:apply-templates>
				<xsl:text> [</xsl:text>
				<xsl:value-of select="@rdf:about"/>
				<xsl:text>]</xsl:text>
			</xsl:otherwise>
		</xsl:choose>
	</h3>
	<xsl:if test="rdfs:comment">
		<p class="comment">
			<xsl:apply-templates mode="literal-value" select="rdfs:comment">
				<xsl:with-param name="ns" select="$ns"/>
			</xsl:apply-templates>
		</p>
	</xsl:if>
	<xsl:if test="vann:example">
		<h4>Example usage:</h4>
		<pre class="example">
			<xsl:value-of select="vann:example"/>
		</pre>
	</xsl:if>
</xsl:template>

<xsl:template mode="class" match="*">
	<xsl:param name="ns"/>
	<div class="class">
		<xsl:apply-templates mode="term" select=".">
			<xsl:with-param name="ns" select="$ns"/>
		</xsl:apply-templates>
		<xsl:variable name="class" select="@rdf:about"/>
		<xsl:if test="/*/*[rdf:type[@rdf:resource='&owl;AnnotationProperty' or @rdf:resource='&owl;OntologyProperty' or @rdf:resource='&owl;DatatypeProperty' or @rdf:resource='&owl;DeprecatedProperty' or @rdf:resource='&owl;FunctionalProperty' or @rdf:resource='&owl;InverseFunctionalProperty' or @rdf:resource='&owl;ObjectProperty' or @rdf:resource='&owl;SymmetricProperty' or @rdf:resource='&owl;TransitiveProperty' or @rdf:resource='&rdf;Property']]">
		<xsl:if test="/*/*[
						rdfs:domain[
							@rdf:resource=$class
							or @rdf:resource=key('resources',$class)/rdfs:subClassOf/@rdf:resource
							or @rdf:resource=key('resources',key('resources',$class)/rdfs:subClassOf/@rdf:resource)/rdfs:subClassOf/@rdf:resource
						] or @rdf:about=/*/*[
							rdf:type[@rdf:resource='&owl;Restriction']
							and (@rdf:about=key('resources',$class)/rdfs:subClassOf/@rdf:resource
								or @rdf:about=key('resources',key('resources',$class)/rdfs:subClassOf/@rdf:resource)/rdfs:subClassOf/@rdf:resource
								or @rdf:about=key('resources',key('resources',key('resources',$class)/rdfs:subClassOf/@rdf:resource)/rdfs:subClassOf/@rdf:resource)/rdfs:subClassOf/@rdf:resource)
							]/owl:onProperty/@rdf:resource]">
		<div class="details">
			<h4>Applicable properties</h4>
			<ul>
				<xsl:for-each select="/*/*[
						rdfs:domain[
							@rdf:resource=$class
							or @rdf:resource=key('resources',$class)/rdfs:subClassOf/@rdf:resource
							or @rdf:resource=key('resources',key('resources',$class)/rdfs:subClassOf/@rdf:resource)/rdfs:subClassOf/@rdf:resource
						] or @rdf:about=/*/*[
							rdf:type[@rdf:resource='&owl;Restriction']
							and (@rdf:about=key('resources',$class)/rdfs:subClassOf/@rdf:resource
								or @rdf:about=key('resources',key('resources',$class)/rdfs:subClassOf/@rdf:resource)/rdfs:subClassOf/@rdf:resource
								or @rdf:about=key('resources',key('resources',key('resources',$class)/rdfs:subClassOf/@rdf:resource)/rdfs:subClassOf/@rdf:resource)/rdfs:subClassOf/@rdf:resource)
							]/owl:onProperty/@rdf:resource]">
					<xsl:sort select="rdfs:label"/>
					<li>
						<a href="{@rdf:about}">
							<xsl:apply-templates mode="title" select="key('resources',self::*/@rdf:about)">
								<xsl:with-param name="ns" select="$ns"/>
							</xsl:apply-templates>
						</a>
					</li>
				</xsl:for-each>
			</ul>
		</div>
		</xsl:if>
		</xsl:if>
		<xsl:if test="/*/*[rdfs:subClassOf/@rdf:resource=$class]">
			<div class="details">
				<h4>Sub-classes</h4>
				<xsl:apply-templates mode="sub-classes" select=".">
					<xsl:with-param name="ns" select="$ns"/>
				</xsl:apply-templates>
			</div>
		</xsl:if>
		<xsl:for-each select="*">
			<xsl:variable name="this" select="concat(namespace-uri(),local-name())"/>
			<xsl:if test="not(@xml:lang) or @xml:lang=$lang or count(../*[concat(namespace-uri(),local-name())=$this and @xml:lang=$lang])=0 and @xml:lang='en'">
				<xsl:apply-templates mode="meta" select="."/>
			</xsl:if>
		</xsl:for-each>
	</div>
</xsl:template>

<xsl:template mode="property" match="*">
	<xsl:param name="ns"/>
	<div class="property">
		<xsl:apply-templates mode="term" select=".">
			<xsl:with-param name="ns" select="$ns"/>
		</xsl:apply-templates>
		<xsl:if test="rdfs:domain">
			<div class="details">
				<h4>
					<xsl:text>Domain</xsl:text>
					<xsl:if test="count(rdfs:domain)>1">
						<xsl:text>s</xsl:text>
					</xsl:if>
				</h4>
				<ul>
					<xsl:for-each select="rdfs:domain">
						<li>
							<a href="{@rdf:resource}">
								<xsl:choose>
									<xsl:when test="key('resources',@rdf:resource)">
										<xsl:apply-templates mode="title" select="key('resources',@rdf:resource)">
											<xsl:with-param name="ns" select="$ns"/>
										</xsl:apply-templates>
									</xsl:when>
									<xsl:otherwise>
										<xsl:value-of select="@rdf:resource"/>
									</xsl:otherwise>
								</xsl:choose>
							</a>
							<xsl:apply-templates mode="sub-classes" select="key('resources',@rdf:resource)">
								<xsl:with-param name="ns" select="$ns"/>
							</xsl:apply-templates>
						</li>
					</xsl:for-each>
				</ul>
			</div>
		</xsl:if>
		<xsl:apply-templates mode="property-properties" select="*"/>
	</div>
</xsl:template>

<xsl:template mode="property-properties" priority="0.2" match="owl:inverseOf">
	<dl class="meta">
		<dt>
			<xsl:apply-templates mode="meta-label" select="."/>
		</dt>
		<dd>
			<a href="{@rdf:resource}">
				<xsl:value-of select="@rdf:resource"/>
			</a>
		</dd>
	</dl>
</xsl:template>

<xsl:template mode="property-properties" priority="0.1" match="*">
	<xsl:variable name="this" select="concat(namespace-uri(),local-name())"/>
	<xsl:if test="not(@xml:lang) or @xml:lang=$lang or count(../*[concat(namespace-uri(),local-name())=$this and @xml:lang=$lang])=0 and @xml:lang='en'">
		<xsl:apply-templates mode="meta" select="."/>
	</xsl:if>
</xsl:template>

<xsl:template mode="sub-classes" match="*">
	<xsl:param name="ns"/>
	<xsl:variable name="class" select="@rdf:about"/>
	<xsl:if test="/*/*[rdfs:subClassOf/@rdf:resource=$class]">
		<ul>
			<xsl:for-each select="/*/*[rdfs:subClassOf/@rdf:resource=$class]">
				<li>
					<a href="{@rdf:about}">
						<xsl:apply-templates mode="title" select=".">
							<xsl:with-param name="ns" select="$ns"/>
						</xsl:apply-templates>
					</a>
					<xsl:apply-templates mode="sub-classes" select=".">
						<xsl:with-param name="ns" select="$ns"/>
					</xsl:apply-templates>
				</li>
			</xsl:for-each>
		</ul>
	</xsl:if>
</xsl:template>

<xsl:template mode="literal-value" match="*">
	<xsl:variable name="this" select="concat(namespace-uri(),local-name())"/>
	<xsl:choose>
		<!-- Just one value or value in the preferred language -->
		<xsl:when test="count(../*[concat(namespace-uri(),local-name())=$this])=1
				or starts-with(@xml:lang,$lang)">
			<xsl:value-of select="."/>
		</xsl:when>
		<!-- A value in the preferred language is present, but it's not this one -->
		<xsl:when test="../*[concat(namespace-uri(),local-name())=$this and starts-with(@xml:lang,$lang)]">
		</xsl:when>
		<!-- Fall back to English if present -->
		<xsl:when test="starts-with(@xml:lang,'en') or not(@xml:lang)">
			<xsl:value-of select="."/>
		</xsl:when>
		<xsl:when test="../*[concat(namespace-uri(),local-name())=$this and (starts-with(@xml:lang,'en') or not(@xml:lang))]">
		</xsl:when>
		<!-- Multiple values present, but not one that is in the preferred language or English -->
		<xsl:otherwise>
			<span lang="{@xml:lang}">
				<xsl:value-of select="."/>
			</span>
			<xsl:text> [</xsl:text>
			<xsl:value-of select="@xml:lang"/>
			<xsl:text>]</xsl:text>
			<xsl:if test="following-sibling::*[concat(namespace-uri(),local-name())=$this]">
				<xsl:text>, </xsl:text>
			</xsl:if>
		</xsl:otherwise>
	</xsl:choose>
</xsl:template>

</xsl:stylesheet>
