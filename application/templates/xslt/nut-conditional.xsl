<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet 
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:nut="http://schema.shelter.nu/nut"
	xmlns:tmt="http://www.massimocorner.com/libraries/"
	xmlns:str="http://xsltsl.org/string"
	xmlns:php="http://php.net/xsl"
	version="1.0"
>

	<!-- Conditional comments : IE 6/7 only, used for giving those browsers
		 their own CSS -->
	
	<xsl:template match="nut:ie-cond-open">
		<xsl:text disable-output-escaping="yes"><![CDATA[<!--[]]></xsl:text><xsl:value-of select="@test" /><xsl:text disable-output-escaping="yes"><![CDATA[]>]]></xsl:text>
	</xsl:template>
	
	<xsl:template match="nut:ie-cond-close">
		<xsl:text disable-output-escaping="yes"><![CDATA[<![endif]-->]]></xsl:text>
	</xsl:template>
	
	<xsl:template match="nut:ie-cond">
		<xsl:param name="data" select="." />
		<xsl:param name="parent" select="." />
		<xsl:param name="input" select="." />
		<xsl:param name="position" select="0" />
		<xsl:param name="max" select="0" />
		<xsl:text disable-output-escaping="yes"><![CDATA[<!--[]]>if </xsl:text><xsl:value-of select="@test" /><xsl:text disable-output-escaping="yes"><![CDATA[]>]]></xsl:text>
                    <xsl:apply-templates>
                            <xsl:with-param name="data" select="$data" />
                            <xsl:with-param name="parent" select="$parent" />
                            <xsl:with-param name="input" select="$input" />
                            <xsl:with-param name="position" select="$position" />
                            <xsl:with-param name="max" select="$max" />
                    </xsl:apply-templates>
		<xsl:text disable-output-escaping="yes"><![CDATA[<![endif]-->]]></xsl:text>
	</xsl:template>
	
	<!-- A flock of testing conditions -->
	
	<xsl:template match="nut:if|nut:case|nut:when|nut:else">
		<xsl:param name="data" select="." />
		<xsl:param name="parent" select="." />
		<xsl:param name="input" select="." />
		<xsl:param name="position" select="0" />
		<xsl:param name="max" select="0" />
		<xsl:param name="count" select="@count|@if-count|@size" />
		<xsl:param name="value-of" select="@value-of" />
		<xsl:param name="user-check" select="@user-check" />
		<xsl:param name="user-group" select="@user-group|@users-group|@security-group" />
		<xsl:param name="user-role" select="@user-role|@users-role|@has-role|@role" />
		<xsl:param name="not-user" select="@not-user|@not-users" />
		<xsl:param name="user" select="@user|@users" />
		<xsl:param name="allowed" select="@allowed" />
		<xsl:param name="not-allowed" select="@not-allowed" />
		<xsl:param name="default" select="@default" />
		<xsl:choose>
			<xsl:when test="$allowed">
                            <xsl:variable name="t" select="php:function ( 'security_user_check_function', $allowed, $default )" />
                            <!-- <b>allowed:[<xsl:value-of select="$allowed" />=<xsl:copy-of select="$t" />]</b> -->
                            <xsl:choose>
                                <xsl:when test="$t">
                                    <xsl:apply-templates>
                                            <xsl:with-param name="data" select="$data" />
                                            <xsl:with-param name="parent" select="$parent" />
                                            <xsl:with-param name="input" select="$input" />
                                            <xsl:with-param name="position" select="$position" />
                                            <xsl:with-param name="max" select="$max" />
                                    </xsl:apply-templates>
                                </xsl:when>
                                <xsl:otherwise>
                                    <span class="access-denied">Access to [<b><xsl:value-of select="$allowed" /></b>] <i style="color:#999">(with default [<xsl:value-of select="$default" />])</i> denied.</span>
                                </xsl:otherwise>
                            </xsl:choose>
			</xsl:when>
			<xsl:when test="$not-allowed">
                            <xsl:variable name="t" select="php:function ( 'security_user_check_function', $not-allowed, $default )" />
                            <!-- <b>not-allowed:[<xsl:value-of select="$not-allowed" />=<xsl:copy-of select="$t" />]</b> -->
                            <xsl:if test="not($t)">
                                <xsl:apply-templates>
                                        <xsl:with-param name="data" select="$data" />
                                        <xsl:with-param name="parent" select="$parent" />
                                        <xsl:with-param name="input" select="$input" />
                                        <xsl:with-param name="position" select="$position" />
                                        <xsl:with-param name="max" select="$max" />
                                </xsl:apply-templates>
                            </xsl:if>
			</xsl:when>
			<xsl:when test="$user-check">
                            <xsl:variable name="t" select="php:function ( 'security_user_check_lookup', $user-check )" />
                            <xsl:if test="$t">
                                    <xsl:apply-templates>
                                            <xsl:with-param name="data" select="$data" />
                                            <xsl:with-param name="parent" select="$parent" />
                                            <xsl:with-param name="input" select="$input" />
                                            <xsl:with-param name="position" select="$position" />
                                            <xsl:with-param name="max" select="$max" />
                                    </xsl:apply-templates>
                            </xsl:if>
			</xsl:when>
			<xsl:when test="$user-group">
                            <xsl:variable name="t" select="php:function ( 'security_group_lookup', $user-group )" />
                            <xsl:if test="$t">
                                    <xsl:apply-templates>
                                            <xsl:with-param name="data" select="$data" />
                                            <xsl:with-param name="parent" select="$parent" />
                                            <xsl:with-param name="input" select="$input" />
                                            <xsl:with-param name="position" select="$position" />
                                            <xsl:with-param name="max" select="$max" />
                                    </xsl:apply-templates>
                            </xsl:if>
			</xsl:when>
			<xsl:when test="$user-role">
                            <xsl:variable name="t" select="php:function ( 'security_role_lookup', $user-role )" />
                            <xsl:if test="$t">
                                    <xsl:apply-templates>
                                            <xsl:with-param name="data" select="$data" />
                                            <xsl:with-param name="parent" select="$parent" />
                                            <xsl:with-param name="input" select="$input" />
                                            <xsl:with-param name="position" select="$position" />
                                            <xsl:with-param name="max" select="$max" />
                                    </xsl:apply-templates>
                            </xsl:if>
			</xsl:when>
			<xsl:when test="$not-user">
                            <xsl:variable name="t" select="php:function ( 'user_lookup', $not-user )" />
                            <xsl:if test="not($t)">
                                    <xsl:apply-templates>
                                            <xsl:with-param name="data" select="$data" />
                                            <xsl:with-param name="parent" select="$parent" />
                                            <xsl:with-param name="input" select="$input" />
                                            <xsl:with-param name="position" select="$position" />
                                            <xsl:with-param name="max" select="$max" />
                                    </xsl:apply-templates>
                            </xsl:if>
			</xsl:when>
			<xsl:when test="$user">
                            <xsl:variable name="t" select="php:function ( 'user_lookup', $user )" />
                            <xsl:if test="$t">
                                    <xsl:apply-templates>
                                            <xsl:with-param name="data" select="$data" />
                                            <xsl:with-param name="parent" select="$parent" />
                                            <xsl:with-param name="input" select="$input" />
                                            <xsl:with-param name="position" select="$position" />
                                            <xsl:with-param name="max" select="$max" />
                                    </xsl:apply-templates>
                            </xsl:if>
			</xsl:when>
			<xsl:when test="$count">
                            <xsl:choose>
                                <xsl:when test="$count = '*'">
                                    <!-- [<xsl:copy-of select="$data" />] -->
                                    <xsl:variable name="selection" select="$data/*" />
                                    <xsl:variable name="c" select="count($selection)" />
                                    <!-- [<xsl:value-of select="$c" />] -->
                                    <xsl:choose>
                                            <xsl:when test="@more-than|@over|@greater-than">
                                                    <xsl:if test="number($c) &gt; number(@more-than)">
                                                            <xsl:apply-templates>
                                                                    <xsl:with-param name="data" select="$data" />
                                                                    <xsl:with-param name="parent" select="$parent" />
                                                                    <xsl:with-param name="input" select="$input" />
                                                                    <xsl:with-param name="position" select="$position" />
                                                                    <xsl:with-param name="max" select="$max" />
                                                            </xsl:apply-templates>
                                                    </xsl:if>
                                            </xsl:when>
                                            <xsl:when test="@less-than|@under|@lower-than">
                                                    <xsl:if test="number($c) &gt; number(@more-than)">
                                                            <xsl:apply-templates>
                                                                    <xsl:with-param name="data" select="$data" />
                                                                    <xsl:with-param name="parent" select="$parent" />
                                                                    <xsl:with-param name="input" select="$input" />
                                                                    <xsl:with-param name="position" select="$position" />
                                                                    <xsl:with-param name="max" select="$max" />
                                                            </xsl:apply-templates>
                                                    </xsl:if>
                                            </xsl:when>
                                            <xsl:when test="@unlike|@not-equal|@not-like">
                                                    <xsl:if test="number($c) != number(@unlike)">
                                                            <xsl:apply-templates>
                                                                    <xsl:with-param name="data" select="$data" />
                                                                    <xsl:with-param name="parent" select="$parent" />
                                                                    <xsl:with-param name="input" select="$input" />
                                                                    <xsl:with-param name="position" select="$position" />
                                                                    <xsl:with-param name="max" select="$max" />
                                                            </xsl:apply-templates>
                                                    </xsl:if>
                                            </xsl:when>
                                            <xsl:when test="@like|@equal">
                                                    <xsl:if test="number($c) = number(@unlike)">
                                                            <xsl:apply-templates>
                                                                    <xsl:with-param name="data" select="$data" />
                                                                    <xsl:with-param name="parent" select="$parent" />
                                                                    <xsl:with-param name="input" select="$input" />
                                                                    <xsl:with-param name="position" select="$position" />
                                                                    <xsl:with-param name="max" select="$max" />
                                                            </xsl:apply-templates>
                                                    </xsl:if>
                                            </xsl:when>
                                    </xsl:choose>
        			</xsl:when>
                                <xsl:otherwise>
                                    <xsl:variable name="selection" select="$data[@name=$count]/*" />
                                    <xsl:variable name="c" select="count($selection)" />
                                    <!-- [<xsl:copy-of select="$data[@name=$count]/*" />]
                                    [<xsl:value-of select="$c" />-<xsl:value-of select="number(@more-than)" />] -->
                                    <xsl:choose>
                                            <xsl:when test="@more-than">
                                                    <xsl:if test="number($c) &gt; number(@more-than)">
                                                            <xsl:apply-templates>
                                                                    <xsl:with-param name="data" select="$data" />
                                                                    <xsl:with-param name="parent" select="$parent" />
                                                                    <xsl:with-param name="input" select="$input" />
                                                                    <xsl:with-param name="position" select="$position" />
                                                                    <xsl:with-param name="max" select="$max" />
                                                            </xsl:apply-templates>
                                                    </xsl:if>
                                            </xsl:when>
                                            <xsl:when test="@unlike|@not-equal|@not-like">
                                                    <xsl:if test="number($c) != number(@unlike)">
                                                            <xsl:apply-templates>
                                                                    <xsl:with-param name="data" select="$data" />
                                                                    <xsl:with-param name="parent" select="$parent" />
                                                                    <xsl:with-param name="input" select="$input" />
                                                                    <xsl:with-param name="position" select="$position" />
                                                                    <xsl:with-param name="max" select="$max" />
                                                            </xsl:apply-templates>
                                                    </xsl:if>
                                            </xsl:when>
                                            <xsl:when test="@like|@equal">
                                                    <xsl:if test="number($c) = number(@unlike)">
                                                            <xsl:apply-templates>
                                                                    <xsl:with-param name="data" select="$data" />
                                                                    <xsl:with-param name="parent" select="$parent" />
                                                                    <xsl:with-param name="input" select="$input" />
                                                                    <xsl:with-param name="position" select="$position" />
                                                                    <xsl:with-param name="max" select="$max" />
                                                            </xsl:apply-templates>
                                                    </xsl:if>
                                            </xsl:when>
                                    </xsl:choose>
                                </xsl:otherwise>
                            </xsl:choose>
			</xsl:when>
			<xsl:when test="@exist">
				<xsl:variable name="select"><xsl:call-template name="nut:value-of">
					<xsl:with-param name="select" select="@exist" />
					<xsl:with-param name="data" select="$data" />
					<xsl:with-param name="parent" select="$parent" />
					<xsl:with-param name="input" select="$input" />
					<xsl:with-param name="position" select="$position" />
					<xsl:with-param name="max" select="$max" />
					<xsl:with-param name="notfound" select="'blank'" />
				</xsl:call-template></xsl:variable>
				<xsl:if test="$select != ''">
					<xsl:apply-templates>
					group	<xsl:with-param name="data" select="$data" />
						<xsl:with-param name="parent" select="$parent" />
						<xsl:with-param name="input" select="$input" />
						<xsl:with-param name="position" select="$position" />
						<xsl:with-param name="max" select="$max" />
					</xsl:apply-templates>
				</xsl:if>
			</xsl:when>
			<xsl:when test="@user">
                            <!-- User authentication and credentials -->
                            <xsl:variable name="select"><xsl:call-template name="nut:value-of">
                                    <xsl:with-param name="select" select="@user" />
                                    <xsl:with-param name="data" select="$data" />
                                    <xsl:with-param name="parent" select="$parent" />
                                    <xsl:with-param name="input" select="$input" />
                                    <xsl:with-param name="position" select="$position" />
                                    <xsl:with-param name="max" select="$max" />
                                    <xsl:with-param name="notfound" select="'blank'" />
                            </xsl:call-template></xsl:variable>
				<xsl:if test="$select != ''">
					<xsl:apply-templates>
						<xsl:with-param name="data" select="$data" />
						<xsl:with-param name="parent" select="$parent" />
						<xsl:with-param name="input" select="$input" />
						<xsl:with-param name="position" select="$position" />
						<xsl:with-param name="max" select="$max" />
					</xsl:apply-templates>
				</xsl:if>
			</xsl:when>
			<xsl:when test="$value-of">
				<xsl:variable name="select"><xsl:call-template name="nut:value-of">
					<xsl:with-param name="select" select="$value-of" />
					<xsl:with-param name="data" select="$data" />
					<xsl:with-param name="parent" select="$parent" />
					<xsl:with-param name="input" select="$input" />
					<xsl:with-param name="position" select="$position" />
					<xsl:with-param name="max" select="$max" />
					<xsl:with-param name="notfound" select="'blank'" />
				</xsl:call-template></xsl:variable>
				<xsl:variable name="test"><xsl:call-template name="nut:value-of">
					<xsl:with-param name="select" select="@like|@unlike|@not-equal|@equal|@contains|@less-than|@less|@more|@more-than" />
					<xsl:with-param name="data" select="$data" />
					<xsl:with-param name="parent" select="$parent" />
					<xsl:with-param name="input" select="$input" />
					<xsl:with-param name="position" select="$position" />
					<xsl:with-param name="max" select="$max" />
					<xsl:with-param name="notfound" select="'blank'" />
				</xsl:call-template></xsl:variable>
				 <!--  ([<xsl:value-of select="$select" />][<xsl:value-of select="$test" />])  -->
				<xsl:choose>
					<xsl:when test="@less|@less-than">
						<xsl:if test="normalize-space($select) &lt; normalize-space($test)">
							<xsl:apply-templates>
								<xsl:with-param name="data" select="$data" />
								<xsl:with-param name="parent" select="$parent" />
								<xsl:with-param name="input" select="$input" />
								<xsl:with-param name="position" select="$position" />
								<xsl:with-param name="max" select="$max" />
							</xsl:apply-templates>
						</xsl:if>
					</xsl:when>
					<xsl:when test="@more|@more-than">
						<xsl:if test="number(normalize-space($select)) &gt; number(normalize-space($test))">
							<xsl:apply-templates>
								<xsl:with-param name="data" select="$data" />
								<xsl:with-param name="parent" select="$parent" />
								<xsl:with-param name="input" select="$input" />
								<xsl:with-param name="position" select="$position" />
								<xsl:with-param name="max" select="$max" />
							</xsl:apply-templates>
						</xsl:if>
					</xsl:when>
					<xsl:when test="@unlike|@not-equal|@not-like">
                                            <!-- [<xsl:value-of select="$select" />]/[<xsl:value-of select="$test" />] -->
						<xsl:if test="normalize-space($select) != normalize-space($test)">
							<xsl:apply-templates>
								<xsl:with-param name="data" select="$data" />
								<xsl:with-param name="parent" select="$parent" />
								<xsl:with-param name="input" select="$input" />
								<xsl:with-param name="position" select="$position" />
								<xsl:with-param name="max" select="$max" />
							</xsl:apply-templates>
						</xsl:if>
					</xsl:when>
					<xsl:when test="@contains">
						<xsl:if test="contains(normalize-space($select),normalize-space($test))">
							<xsl:apply-templates>
								<xsl:with-param name="data" select="$data" />
								<xsl:with-param name="parent" select="$parent" />
								<xsl:with-param name="input" select="$input" />
								<xsl:with-param name="position" select="$position" />
								<xsl:with-param name="max" select="$max" />
							</xsl:apply-templates>
						</xsl:if>
					</xsl:when>
					<xsl:when test="@like|@equal">
						<xsl:if test="normalize-space($select) = normalize-space($test)">
							<xsl:apply-templates>
								<xsl:with-param name="data" select="$data" />
								<xsl:with-param name="parent" select="$parent" />
								<xsl:with-param name="input" select="$input" />
								<xsl:with-param name="position" select="$position" />
								<xsl:with-param name="max" select="$max" />
							</xsl:apply-templates>
						</xsl:if>
					</xsl:when>
				</xsl:choose>
			</xsl:when>
		</xsl:choose>
	</xsl:template>
	
	<xsl:template match="nut:switch">
		<xsl:param name="data" />
		<xsl:param name="parent" select="." />
		<xsl:param name="input" />
		<xsl:param name="position" />
		<xsl:param name="max" select="0" />
		<xsl:variable name="search">
			<xsl:apply-templates select="nut:case|nut:when|nut:if">
				<xsl:with-param name="data" select="$data" />
				<xsl:with-param name="parent" select="$parent" />
				<xsl:with-param name="input" select="$input" />
				<xsl:with-param name="position" select="$position" />
				<xsl:with-param name="max" select="$max" />
			</xsl:apply-templates>
		</xsl:variable>
		<xsl:choose>
			<xsl:when test="string($search)">
				<xsl:copy-of select="$search" />
			</xsl:when>
			<xsl:otherwise>
				<xsl:apply-templates select="nut:else|nut:otherwise">
					<xsl:with-param name="data" select="$data" />
					<xsl:with-param name="parent" select="$parent" />
					<xsl:with-param name="input" select="$input" />
					<xsl:with-param name="position" select="$position" />					
					<xsl:with-param name="max" select="$max" />
				</xsl:apply-templates>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
	
</xsl:stylesheet>
