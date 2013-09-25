<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet 
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:nut="http://schema.shelter.nu/nut"
	xmlns:tmt="http://www.massimocorner.com/libraries/"
	xmlns:str="http://xsltsl.org/string"
	xmlns:php="http://php.net/xsl"
	version="1.0"
>	
	
	<!-- What position are we currently at? -->
	
	<xsl:template match="nut:position">
		<xsl:param name="position" select="@position" />
		<xsl:value-of select="$position" />
	</xsl:template>
	
	
	<!-- Modulo looks at $position, and gives 'true' for even numbers, 'false' for odd. -->

	<xsl:template match="nut:modulo">
		<xsl:param name="position" select="@position" />
		<xsl:variable name="m" select="$position mod 2" />
		<xsl:choose>
			<xsl:when test="$m = 1">true</xsl:when>
			<xsl:otherwise>false</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
        
	
	<!-- Generic templating mimicing xsl:for-each -->
	
	<xsl:template name="nut:do-for-each">
		<xsl:param name="template" />
		<xsl:param name="input" select="." />
		<xsl:param name="data" select="." />
		<xsl:param name="orig" />
		<xsl:param name="parent" />
		<xsl:param name="position" select="0" />
		<xsl:for-each select="$data">
			<xsl:apply-templates select="$template">
				<xsl:with-param name="data" select="." />
				<xsl:with-param name="orig" select="$orig" />
				<xsl:with-param name="parent" select="$parent" />
				<xsl:with-param name="input" select="$input" />
				<xsl:with-param name="position" select="position()" />
				<xsl:with-param name="max" select="count($data)" />
			</xsl:apply-templates>
		</xsl:for-each>
	</xsl:template>
	
	<xsl:template name="xpath2">
	</xsl:template>
	
	
	<!--   'LOON-SOMETHING/item/itemList'    -->
	<!--   'item/itemList'    -->
	<!--   'itemList'    -->
	
	<xsl:template match="nut:for-each|nut:context|nut:object" name="xpath">
		<xsl:param name="select" select="@select" />
		<xsl:param name="data" select="." />
		<xsl:param name="orig" select="$data" />
		<xsl:param name="parent" select="." />
		<xsl:param name="action" select="'false'" />
		<xsl:param name="position" select="0" />
		<xsl:param name="max" select="0" />
		<xsl:param name="current" select="." />
		<xsl:param name="cmd" select="name($current)" />
		<xsl:param name="input" select="@input" />
		<xsl:variable name="pop"><xsl:choose><xsl:when test="@input"><xsl:value-of select="@input" /></xsl:when><xsl:otherwise><xsl:value-of select="$input" /></xsl:otherwise></xsl:choose></xsl:variable>
		<xsl:variable name="inp"><xsl:call-template name="digest-variables">
			<xsl:with-param name="text" select="$pop" />
			<xsl:with-param name="data" select="$data" />
			<xsl:with-param name="orig" select="$orig" />
			<xsl:with-param name="position" select="$position" />
			<xsl:with-param name="max" select="$max" />
		</xsl:call-template></xsl:variable>
		<!-- ['<xsl:value-of select="$pop" />'-'<xsl:value-of select="@input" />'='<xsl:value-of select="$inp" />'] -->
		<xsl:variable name="dynamom"><xsl:call-template name="digest-variables">
			<xsl:with-param name="text" select="$select" />
			<xsl:with-param name="data" select="$data" />
			<xsl:with-param name="orig" select="$orig" />
			<xsl:with-param name="position" select="$position" />
			<xsl:with-param name="input" select="$inp" />
			<xsl:with-param name="max" select="$max" />
		</xsl:call-template></xsl:variable>
		<xsl:variable name="dynamo" select="normalize-space($dynamom)" />
		<xsl:choose>
			<xsl:when test="$dynamo"> 
                		<xsl:variable name="widget" select="substring-before(substring-after($dynamo,'$'),'/')" />
				<xsl:variable name="after" select="substring-after($dynamo,'/')" />
				<xsl:variable name="front" select="substring-before($dynamo,'/')" />
				<xsl:variable name="item"><xsl:choose><xsl:when test="$front"><xsl:value-of select="$front" /></xsl:when><xsl:otherwise><xsl:value-of select="$dynamo" /></xsl:otherwise></xsl:choose></xsl:variable>
                                <!-- [<xsl:value-of select="$item" />] -->
				<xsl:choose>
					<xsl:when test="$widget != ''">
                                            <!-- [<xsl:value-of select="$widget" />]
                                            [<xsl:value-of select="$after" />]
                                            [[<xsl:copy-of select="php:function('plugin_get', $widget, $after)" />]] -->
                                            <xsl:variable name="d" select="php:function('plugin_get', $widget, $after)" />
                                            <!-- [<xsl:copy-of select="$d" />] -->
                                            <xsl:if test="$d/*">
                                                <xsl:call-template name="xpath">
                                                        <xsl:with-param name="select" select="'*'" />
                                                        <xsl:with-param name="data" select="$d/*" />
                                                        <xsl:with-param name="orig" select="$orig" />
                                                        <xsl:with-param name="parent" select="$data" />
                                                        <xsl:with-param name="cmd" select="$cmd" />
                                                        <xsl:with-param name="current" select="$current" />
                                                        <xsl:with-param name="input" select="$inp" />
                                                        <xsl:with-param name="position" select="$position" />
                                                        <xsl:with-param name="max" select="$max" />
                                                </xsl:call-template>
                                            </xsl:if>
					</xsl:when>
					<xsl:when test="$after != '' and $front = ''">
						<xsl:call-template name="xpath">
							<xsl:with-param name="select" select="$after" />
							<xsl:with-param name="data" select="$objects" />
							<xsl:with-param name="orig" select="$orig" />
							<xsl:with-param name="parent" select="$data" />
							<xsl:with-param name="cmd" select="$cmd" />
							<xsl:with-param name="current" select="$current" />
							<xsl:with-param name="input" select="$inp" />
							<xsl:with-param name="position" select="$position" />
							<xsl:with-param name="max" select="$max" />
						</xsl:call-template>
					</xsl:when>
					<xsl:when test="substring-before($item,'_') = 'xs' and $data">
						<xsl:variable name="selection" select="$objects[@name=$item]" />
						<xsl:call-template name="xpath">
							<xsl:with-param name="select" select="$after" />
							<xsl:with-param name="data" select="$selection" />
							<xsl:with-param name="orig" select="$orig" />
							<xsl:with-param name="parent" select="$data" />
							<xsl:with-param name="cmd" select="$cmd" />
							<xsl:with-param name="current" select="$current" />
							<xsl:with-param name="input" select="$inp" />
							<xsl:with-param name="position" select="$position" />
							<xsl:with-param name="max" select="$max" />
						</xsl:call-template>								
					</xsl:when>
					<xsl:when test="substring-before($item,':') = 'xs'">
						<xsl:variable name="trick" select="substring-after($item,':')" />
						<xsl:choose>
							<xsl:when test="$trick='*'">
								<xsl:variable name="selection" select="$data/item/item" />
								<xsl:call-template name="xpath">
									<xsl:with-param name="select" select="$after" />
									<xsl:with-param name="data" select="$selection" />
									<xsl:with-param name="orig" select="$orig" />
									<xsl:with-param name="parent" select="$data" />
									<xsl:with-param name="cmd" select="$cmd" />
									<xsl:with-param name="current" select="$current" />
									<xsl:with-param name="input" select="$inp" />
									<xsl:with-param name="position" select="$position" />
									<xsl:with-param name="max" select="$max" />
								</xsl:call-template>								
							</xsl:when>
							<xsl:otherwise>
								<xsl:variable name="selection" select="$data/item[@name=$trick]" />
								<xsl:call-template name="xpath">
									<xsl:with-param name="select" select="$after" />
									<xsl:with-param name="data" select="$selection" />
									<xsl:with-param name="orig" select="$orig" />
									<xsl:with-param name="parent" select="$data" />
									<xsl:with-param name="cmd" select="$cmd" />
									<xsl:with-param name="current" select="$current" />
									<xsl:with-param name="input" select="$inp" />
									<xsl:with-param name="position" select="$position" />
									<xsl:with-param name="max" select="$max" />
								</xsl:call-template>								
							</xsl:otherwise>
						</xsl:choose>
					</xsl:when>
					<xsl:when test="$dynamo = '*'">
                                            <xsl:variable name="selection" select="$data/*[name()!='report']" />
						<xsl:call-template name="xpath">
							<xsl:with-param name="select" select="$after" />
							<xsl:with-param name="data" select="$selection" />
							<xsl:with-param name="orig" select="$orig" />
							<xsl:with-param name="parent" select="$data" />
							<xsl:with-param name="cmd" select="$cmd" />
							<xsl:with-param name="current" select="$current" />
							<xsl:with-param name="input" select="$inp" />
							<xsl:with-param name="position" select="$position" />
							<xsl:with-param name="max" select="$max" />
						</xsl:call-template>								
					</xsl:when>
					<xsl:otherwise>
						<xsl:choose>
							<xsl:when test="$item = '.'">
								<xsl:variable name="selection" select="$data/." />
                                                                <!-- [.=<xsl:copy-of select="$data" />] -->
								<xsl:call-template name="xpath">
									<xsl:with-param name="select" select="$after" />
									<xsl:with-param name="data" select="$data" />
									<xsl:with-param name="orig" select="$orig" />
									<xsl:with-param name="parent" select="$data" />
									<xsl:with-param name="cmd" select="$cmd" />
									<xsl:with-param name="current" select="$current" />
									<xsl:with-param name="input" select="$inp" />
									<xsl:with-param name="position" select="$position" />
									<xsl:with-param name="max" select="$max" />
								</xsl:call-template>								
							</xsl:when>
							<xsl:when test="$item = 'name()'">
								<xsl:variable name="selection" select="concat(name())" />
                                                                <!--[<xsl:value-of select="count($selection)" />]  -->
								<xsl:call-template name="xpath">
									<xsl:with-param name="select" select="$after" />
									<xsl:with-param name="data" select="$selection" />
									<xsl:with-param name="orig" select="$orig" />
									<xsl:with-param name="parent" select="$data" />
									<xsl:with-param name="cmd" select="$cmd" />
									<xsl:with-param name="current" select="$current" />
									<xsl:with-param name="input" select="$inp" />
									<xsl:with-param name="position" select="$position" />
									<xsl:with-param name="max" select="$max" />
								</xsl:call-template>								
							</xsl:when>
							<xsl:otherwise>
								<xsl:if test="$data">
									<xsl:variable name="selection" select="$data[@name=$item]" />
                                                                        <!-- (<xsl:copy-of select="$data[@name='groups']" />)
                                                                        [<xsl:value-of select="count($selection)" />] -->
									<xsl:call-template name="xpath">
										<xsl:with-param name="select" select="$after" />
										<xsl:with-param name="data" select="$selection" />
										<xsl:with-param name="orig" select="$orig" />
										<xsl:with-param name="parent" select="$data" />
										<xsl:with-param name="cmd" select="$cmd" />
										<xsl:with-param name="current" select="$current" />
										<xsl:with-param name="input" select="$inp" />
										<xsl:with-param name="position" select="$position" />
										<xsl:with-param name="max" select="$max" />
									</xsl:call-template>
								</xsl:if>								
							</xsl:otherwise>
						</xsl:choose>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:when>
			<xsl:otherwise>
				<xsl:choose>
					<xsl:when test="$cmd = 'nut:for-each'">
						<xsl:variable name="this" select="$current/*" />
                                                <!-- (<xsl:value-of select="count($data)" />) -->
						<xsl:call-template name="nut:do-for-each">
							<xsl:with-param name="template" select="$this" />
							<xsl:with-param name="data" select="$data" />
							<xsl:with-param name="orig" select="$orig" />
							<xsl:with-param name="parent" select="$data/.." />
							<xsl:with-param name="current" select="$current" />
							<xsl:with-param name="input" select="$inp" />
							<xsl:with-param name="position" select="$position" />
							<xsl:with-param name="max" select="$max" />
						</xsl:call-template>
					</xsl:when>
					<xsl:otherwise><!-- [<xsl:value-of select="count($data)" />] -->
						<xsl:apply-templates>
							<xsl:with-param name="data" select="$data" />
							<xsl:with-param name="orig" select="$orig" />
							<xsl:with-param name="parent" select="$data" />
							<xsl:with-param name="input" select="$inp" />
							<xsl:with-param name="position" select="$position" />
							<xsl:with-param name="max" select="$max" />
						</xsl:apply-templates>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	
	<!-- value-of ; generic variable output mechanism -->
	
	<xsl:template match="nut:value-of|nut:copy-of" name="nut:value-of">
		<xsl:param name="data" select="." />
		<xsl:param name="parent" />
		<xsl:param name="orig" select="." />
		<xsl:param name="position" select="999" />
		<xsl:param name="max" select="0" />
                <xsl:param name="notfound" select="''" />
		<xsl:param name="select" select="@select" />
		<xsl:param name="input" select="@input" />
		<xsl:variable name="sel" select="$select" />

		<xsl:variable name="full-item" select="substring-after($sel,'$')" />
		<xsl:variable name="widget" select="substring-before(substring-after($sel,'@'),'/')" />
		<xsl:variable name="obj" select="substring-before(substring-after($sel,'$'),'/')" />
		<xsl:variable name="cmd" select="substring-before(substring-after($sel,'#'),' ')" />
		<xsl:variable name="var" select="substring-after($sel,'/')" />
		<xsl:variable name="atr" select="substring-after($sel,'@')" />
		
		<!-- $glob = name of xs_* item for shortcut -->
		<xsl:variable name="glob" select="$global/*[name()=$obj]" />
		
		<!-- find any $glob shortcutted items in the input response -->
		<xsl:variable name="find" select="$objects[@name=$glob]" />
		
		<!-- First character of input name -->
		<xsl:variable name="literal" select="substring($sel, 1, 1)" />
		
		<xsl:choose>
			
			<!-- Generic text support -->
			
			<xsl:when test="$literal = $fnutt">
				<xsl:copy-of select="substring-before(substring-after($sel,$fnutt),$fnutt)" />
			</xsl:when>

			<xsl:when test="$literal = '.'">
                                <xsl:variable name="selx" select="$data/.|$data/text()" />
                                <xsl:choose>
                                        <xsl:when test="name()='nut:copy-of'"><xsl:copy-of select="$selx" /></xsl:when>
                                        <xsl:otherwise><xsl:value-of select="$selx" /></xsl:otherwise>
                                </xsl:choose>
			</xsl:when>

                        <!-- Widgets -->

			<xsl:when test="$widget">
                            [<xsl:value-of select="$sel" />]
                            [<xsl:value-of select="$widget" />]
                            <!-- <xsl:value-of select="php:function ( 'widget_property', $widget, $var )" /> -->
                        </xsl:when>

			<!-- Commands and logic -->
			
			<xsl:when test="$cmd = 'if'">
                                <!-- (<xsl:value-of select="$sel" />) -->
				<xsl:variable name="case" select="substring-before(substring-after($sel,' '),' ')" />
				<xsl:variable name="this" select="substring-before($case,'=')" />
				<xsl:variable name="that" select="substring-after($case,'=')" />
                               <!-- case=[<xsl:value-of select="$case" />] -->
				<xsl:variable name="rest"   select="substring-after($sel, concat($case,' '))" />
                               <!-- rest=[<xsl:value-of select="$rest" />] -->
                                <xsl:variable name="t" select="substring($rest,1,1)" />
                                <xsl:variable name="do"><xsl:choose><xsl:when test="$t = $fnutt"><xsl:value-of select="concat($fnutt,substring-before(substring($rest,2),$fnutt),$fnutt)" /></xsl:when><xsl:otherwise><xsl:value-of select="substring-before($rest,' ')" /></xsl:otherwise></xsl:choose></xsl:variable>
				<xsl:variable name="else" select="substring-after($rest,concat($do,' '))" />
                               <!-- do=[<xsl:value-of select="$do" />]
                               else=[<xsl:value-of select="$else" />]
                               this=[<xsl:value-of select="$this" />] that=[<xsl:value-of select="$that" />] -->

				<xsl:variable name="this-dyn"><xsl:call-template name="nut:value-of">
					<xsl:with-param name="select" select="$this" />
					<xsl:with-param name="data" select="$data" />
					<xsl:with-param name="orig" select="$orig" />
					<xsl:with-param name="position" select="$position" />
					<xsl:with-param name="max" select="$max" />
					<xsl:with-param name="notfound" select="'blank'" />
				</xsl:call-template></xsl:variable>
				<xsl:variable name="that-dyn"><xsl:call-template name="nut:value-of">
					<xsl:with-param name="select" select="$that" />
					<xsl:with-param name="data" select="$data" />
					<xsl:with-param name="orig" select="$orig" />
					<xsl:with-param name="position" select="$position" />
					<xsl:with-param name="max" select="$max" />
					<xsl:with-param name="notfound" select="'blank'" />
				</xsl:call-template></xsl:variable>
				<xsl:choose>
					<xsl:when test="normalize-space($this-dyn) = normalize-space($that-dyn)"><xsl:call-template name="nut:value-of">
						<xsl:with-param name="select" select="$do" />
						<xsl:with-param name="data" select="$data" />
						<xsl:with-param name="orig" select="$orig" />
						<xsl:with-param name="position" select="$position" />
						<xsl:with-param name="max" select="$max" />
                                                <xsl:with-param name="notfound" select="'blank'" />
					</xsl:call-template></xsl:when>
					<xsl:otherwise>
						<xsl:if test="$else"><xsl:call-template name="nut:value-of">
							<xsl:with-param name="select" select="$else" />
							<xsl:with-param name="data" select="$data" />
							<xsl:with-param name="position" select="$position" />
							<xsl:with-param name="orig" select="$orig" />
							<xsl:with-param name="max" select="$max" />
        					<xsl:with-param name="notfound" select="'blank'" />
						</xsl:call-template></xsl:if>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:when>
			
			<xsl:when test="$cmd = 'lookup'">
				<xsl:variable name="id" select="substring-before(substring-after($sel,' '),' ')" />
				<xsl:variable name="this-id"><xsl:call-template name="nut:value-of">
					<xsl:with-param name="select" select="$id" />
					<xsl:with-param name="data" select="$data" />
					<xsl:with-param name="orig" select="$orig" />
					<xsl:with-param name="position" select="$position" />
					<xsl:with-param name="max" select="$max" />
					<xsl:with-param name="notfound" select="'blank'" />
				</xsl:call-template></xsl:variable>
				<xsl:variable name="db" select="substring-after(substring-after($sel,' '),' ')" />
				<xsl:value-of select="$objects[@name=$db]/item/item[@name='property_id'][normalize-space(.)=normalize-space($this-id)]/../item[@name='value']" />
			</xsl:when>
			
			<xsl:when test="$cmd = 'label'">
				<xsl:variable name="id" select="substring-after($sel,' ')" />
				<xsl:value-of select="php:function ( 'label', $id, $language )" />
			</xsl:when>
			
			<xsl:when test="$cmd = 'add'">
				<xsl:variable name="case" select="substring-after($sel,' ')" />
				<xsl:variable name="this" select="substring-before($case,'+')" />
				<xsl:variable name="that" select="substring-after($case,'+')" />
				<xsl:variable name="this-dyn"><xsl:call-template name="nut:value-of">
					<xsl:with-param name="select" select="$this" />
					<xsl:with-param name="data" select="$data" />
					<xsl:with-param name="orig" select="$orig" />
					<xsl:with-param name="position" select="$position" />
					<xsl:with-param name="max" select="$max" />
					<xsl:with-param name="notfound" select="'blank'" />
				</xsl:call-template></xsl:variable>
				<xsl:variable name="that-dyn"><xsl:call-template name="nut:value-of">
					<xsl:with-param name="select" select="$that" />
					<xsl:with-param name="data" select="$data" />
					<xsl:with-param name="orig" select="$orig" />
					<xsl:with-param name="position" select="$position" />
					<xsl:with-param name="max" select="$max" />
					<xsl:with-param name="notfound" select="'blank'" />
				</xsl:call-template></xsl:variable>
				<xsl:value-of select="number($this-dyn)+number($that-dyn)" />
			</xsl:when>
			
			<!-- Error -->
			
			<xsl:when test="$full-item = 'error'">
				<xsl:value-of select="$data/@error|$data/../@error|$data/../../@error" />
			</xsl:when>
			<!--
			<xsl:when test="$full-item = 'name'">
				<xsl:variable name="selx" select="$data/@name" />
				<xsl:choose>
					<xsl:when test="name()='nut:copy-of'"><xsl:copy-of select="$selx" /></xsl:when>
					<xsl:otherwise><xsl:value-of select="$selx" /></xsl:otherwise>
				</xsl:choose>
			</xsl:when> -->
			<xsl:when test="$full-item = 'name()'">
				<xsl:variable name="selx" select="name($data)" />
				<xsl:choose>
					<xsl:when test="name()='nut:copy-of'"><xsl:copy-of select="$selx" /></xsl:when>
					<xsl:otherwise><xsl:value-of select="$selx" /></xsl:otherwise>
				</xsl:choose>
			</xsl:when>
			
			<!-- Generic XS ontology support -->
			
			<xsl:when test="$obj = 'item'">
				<xsl:variable name="par" select="substring-after($sel,'/')" />
				<xsl:variable name="at" select="substring-after($par,'@')" />
                                <!-- [<xsl:copy-of select="$data" />] -->
				<xsl:choose>
					<xsl:when test="$at">
                                              <!-- [<xsl:value-of select="$at" />] -->
						<xsl:variable name="selx" select="$data/@*[name()=$at]" />
                                             <!-- [<xsl:copy-of select="$data/@*" />] -->
						<xsl:choose>
							<xsl:when test="name()='nut:copy-of'"><xsl:copy-of select="$selx" /></xsl:when>
							<xsl:otherwise><xsl:value-of select="$selx" /></xsl:otherwise>
						</xsl:choose>
					</xsl:when>
					<xsl:when test="$par = ''">
						<xsl:variable name="selx" select="$data/*" />
						<xsl:choose>
							<xsl:when test="name()='nut:copy-of'"><xsl:copy-of select="$selx/*|$selx/text()" /></xsl:when>
							<xsl:otherwise><xsl:value-of select="$selx" /></xsl:otherwise>
						</xsl:choose>
					</xsl:when>
					<xsl:when test="$par = '.'">
						<xsl:variable name="selx" select="$data/.|$data/text()" />
						<xsl:choose>
							<xsl:when test="name()='nut:copy-of'"><xsl:copy-of select="$selx" /></xsl:when>
							<xsl:otherwise><xsl:value-of select="$selx" /></xsl:otherwise>
						</xsl:choose>
					</xsl:when>
					<xsl:when test="$par = 'name()'">
						<xsl:variable name="selx" select="$data/@name" />
                                                <!-- [<xsl:copy-of select="$data" />] -->
						<xsl:choose>
							<xsl:when test="name()='nut:copy-of'"><xsl:copy-of select="$selx" /></xsl:when>
							<xsl:otherwise><xsl:value-of select="$selx" /></xsl:otherwise>
						</xsl:choose>
					</xsl:when>
					<xsl:otherwise>
    
                                            <xsl:if test="$data">
						<xsl:variable name="selx" select="$data/item[@name=$par]" />
						<xsl:choose>
							<xsl:when test="name()='nut:copy-of'"><xsl:copy-of select="$selx/*|$selx/text()" /></xsl:when>
							<xsl:otherwise><xsl:value-of select="$selx" /></xsl:otherwise>
						</xsl:choose>
                                            </xsl:if>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:when>
			
			<xsl:when test="$obj = 'parent'">
				<xsl:variable name="par" select="substring-after($sel,'/')" />
				<xsl:choose>
					<xsl:when test="$par = ''">
						<xsl:variable name="selx" select="$parent/*" />
						<xsl:choose>
							<xsl:when test="name()='nut:copy-of'"><xsl:copy-of select="$selx/*|$selx/text()" /></xsl:when>
							<xsl:otherwise><xsl:value-of select="$selx" /></xsl:otherwise>
						</xsl:choose>
					</xsl:when>
					<xsl:when test="$par = '.'">
						<xsl:variable name="selx" select="$parent/.|$parent/text()" />
						<xsl:choose>
							<xsl:when test="name()='nut:copy-of'"><xsl:copy-of select="$selx" /></xsl:when>
							<xsl:otherwise><xsl:value-of select="$selx" /></xsl:otherwise>
						</xsl:choose>
					</xsl:when>
					<xsl:when test="$par = 'name()'">
						<xsl:variable name="selx" select="name($parent)" />
						<xsl:choose>
							<xsl:when test="name()='nut:copy-of'"><xsl:copy-of select="$selx" /></xsl:when>
							<xsl:otherwise><xsl:value-of select="$selx" /></xsl:otherwise>
						</xsl:choose>
					</xsl:when>
					<xsl:otherwise>
						<xsl:variable name="selx" select="$parent/*[name()=$par]|$parent/item[@name=$par]|$parent[@name=$par]|$parent/@*[name()=$par]" />
						<xsl:choose>
							<xsl:when test="name()='nut:copy-of'"><xsl:copy-of select="$selx/*|$selx/text()" /></xsl:when>
							<xsl:otherwise><xsl:value-of select="$selx" /></xsl:otherwise>
						</xsl:choose>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:when>
			 
			<xsl:when test="$obj = 'orig' and $orig">
				<xsl:variable name="par" select="substring-after($sel,'/')" />
				<xsl:choose>
					<xsl:when test="$par = ''">
						<xsl:variable name="selx" select="$orig/*" />
						<xsl:choose>
							<xsl:when test="name()='nut:copy-of'"><xsl:copy-of select="$selx/*|$selx/text()" /></xsl:when>
							<xsl:otherwise><xsl:value-of select="$selx" /></xsl:otherwise>
						</xsl:choose>
					</xsl:when>
					<xsl:when test="$par = '.'">
						<xsl:variable name="selx" select="$orig/.|$orig/text()" />
						<xsl:choose>
							<xsl:when test="name()='nut:copy-of'"><xsl:copy-of select="$selx" /></xsl:when>
							<xsl:otherwise><xsl:value-of select="$selx" /></xsl:otherwise>
						</xsl:choose>
					</xsl:when>
					<xsl:when test="$par = 'name()'">
						<xsl:variable name="selx" select="name($orig)" />
						<xsl:choose>
							<xsl:when test="name()='nut:copy-of'"><xsl:copy-of select="$selx" /></xsl:when>
							<xsl:otherwise><xsl:value-of select="$selx" /></xsl:otherwise>
						</xsl:choose>
					</xsl:when>
					<xsl:otherwise>
						<xsl:variable name="selx" select="$orig/*[name()=$par]|$orig/item[@name=$par]|$orig[@name=$par]|$orig/@*[name()=$par]" />
						<xsl:choose>
							<xsl:when test="name()='nut:copy-of'"><xsl:copy-of select="$selx/*|$selx/text()" /></xsl:when>
							<xsl:otherwise><xsl:value-of select="$selx" /></xsl:otherwise>
						</xsl:choose>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:when>
			 
			<!-- Counting -->
			
			<xsl:when test="$obj = 'count' or $full-item = 'count' or $sel = 'count()'"><xsl:value-of select="count($data)" /></xsl:when>
			<xsl:when test="$sel = 'count(*)'"><xsl:value-of select="count($data/*[name()!='report'])" /></xsl:when>
			
			<!-- Full-item -->

			<!-- <xsl:when test="$full-item"> [<xsl:value-of select="$full-item" />] </xsl:when> -->
                        
			
			<xsl:when test="$full-item = 'mod'"><xsl:variable name="m" select="$position mod 2" /><xsl:choose>
				<xsl:when test="$m = 1">true</xsl:when>
				<xsl:otherwise>false</xsl:otherwise>
			</xsl:choose></xsl:when>
			<xsl:when test="$full-item = 'position'"><xsl:value-of select="$position" /></xsl:when>
			<xsl:when test="$full-item = 'name'"><xsl:value-of select="$data[1]/@name" /></xsl:when>
			<xsl:when test="$full-item = '$name'"><xsl:value-of select="name(.)" /></xsl:when>
			<xsl:when test="$full-item = '%name'"><xsl:value-of select="name($data[1])" /></xsl:when>
			<xsl:when test="$full-item = 'input'"><xsl:value-of select="$input" /></xsl:when>
			<xsl:when test="$full-item = 'max'"><xsl:value-of select="$max" /></xsl:when>
			
			<!-- when there is a shortcut, but it's not found in the input response -->
			<xsl:when test="$glob and not($find)">
				<xsl:if test="$data"><xsl:if test="count($data/*) &lt; 1"><xsl:value-of select="$data/*[name()=$var]" /></xsl:if></xsl:if>
			</xsl:when>			
			
			<!-- items where @name = '$some_path/[THIS_PART]'  -->
			<xsl:when test="$find/item[@name=$var]">
				<xsl:variable name="selx" select="$find/item[@name=$var]" />
				<xsl:choose>
					<xsl:when test="name()='nut:copy-of'">
                        <xsl:choose>
                            <xsl:when test="@urldecode"><xsl:call-template name="str:url-decode"><xsl:with-param name="encoded" select="$selx/*|$selx/text()" /></xsl:call-template></xsl:when>
                            <xsl:otherwise><xsl:copy-of select="$selx/*|$selx/text()" /></xsl:otherwise>
                        </xsl:choose>
                    </xsl:when>
					<xsl:otherwise><xsl:value-of select="$selx" /></xsl:otherwise>
				</xsl:choose>
			</xsl:when>
			
			<!-- item/@name = $atr -->
			
			<xsl:when test="$atr">
				<xsl:value-of select="$data/item[@name=$atr]" />
			</xsl:when>
			
			<xsl:when test="$full-item">
                <!-- otherwise -->
                <xsl:if test="not($obj)">
                	<xsl:variable name="run" select="document(concat('../pages/',$pageTemplate))//*" />
                    <xsl:apply-templates select="$run" mode="css">
                        <xsl:with-param name="data" select="$objects" />
                        <xsl:with-param name="this" select="." />
                        <xsl:with-param name="selected" select="$full-item" />
                    </xsl:apply-templates>
                </xsl:if>
            </xsl:when>

			<xsl:otherwise><xsl:if test="$debug = 'true'"> {DEBUG 
				{obj=<xsl:value-of select="$obj" />}
				{glob=<xsl:value-of select="$glob" />}
				{var=<xsl:value-of select="$var" />}
				{res=<xsl:value-of select="$find[@name=$var]" />}
				{count($find)=<xsl:value-of select="count($find)" />}
				{count($global)=<xsl:value-of select="count($global)" /> [<xsl:value-of select="name($global)" />]}
			} </xsl:if>

                <xsl:if test="$debug != 'true'"><xsl:choose>
                    <xsl:when test="normalize-space($notfound) = 'blank'"><xsl:value-of select="normalize-space($sel)" /></xsl:when>
                    <xsl:otherwise><xsl:value-of select="$sel" /></xsl:otherwise>
                </xsl:choose></xsl:if></xsl:otherwise>
			
		</xsl:choose>
		
	</xsl:template>
	
	
	<!-- Parses and tokenizes {...} in input to any string -->
	
	<xsl:template name="digest-variables">
		<xsl:param name="text" />
		<xsl:param name="data" />
		<xsl:param name="orig" />
		<xsl:param name="parent" />
		<xsl:param name="input" />
		<xsl:param name="notfound" />
		<xsl:param name="position" select="0" />
		<xsl:param name="max" select="0" />
		<xsl:choose>
			
			<!-- Test for the exsistance of a '{', probably meaning some variable -->
			<xsl:when test="substring-after($text,'{$') or substring-after($text,'{@') or substring-after($text,'{#')">
				<xsl:variable name="before" select="substring-before($text,'{')" />
				<xsl:variable name="start" select="substring-after($text,'{')" />
				<xsl:variable name="item" select="substring-before($start,'}')" />
				<xsl:variable name="rest" select="substring-after($start,'}')" />
				
				<xsl:copy-of select="$before" />

				<!-- Parse current values -->
				<xsl:call-template name="nut:value-of">
					<xsl:with-param name="select" select="$item" />
					<xsl:with-param name="data" select="$data" />
					<xsl:with-param name="orig" select="$orig" />
					<xsl:with-param name="parent" select="$parent" />
					<xsl:with-param name="input" select="$input" />
					<xsl:with-param name="position" select="$position" />
					<xsl:with-param name="max" select="$max" />
					<!-- <xsl:with-param name="notfound" select="'blank'" /> -->
				</xsl:call-template>
				
				<!-- Recursing until there is no more -->
				<xsl:call-template name="digest-variables">
					<xsl:with-param name="text" select="$rest" />
					<xsl:with-param name="data" select="$data" />
					<xsl:with-param name="orig" select="$orig" />
					<xsl:with-param name="parent" select="$parent" />
					<xsl:with-param name="input" select="$input" />
					<xsl:with-param name="position" select="$position" />
					<xsl:with-param name="max" select="$max" />
				</xsl:call-template>
				
			</xsl:when>
			
			<!-- No '{', so dump and move on -->
			<xsl:otherwise>
				<xsl:value-of select="$text" />
			</xsl:otherwise>
			
		</xsl:choose>
	</xsl:template>
	
</xsl:stylesheet>
