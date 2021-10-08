<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:mets="http://www.loc.gov/METS/"
				xmlns="http://www.exlibrisgroup.com/dps/dnx"
				xpath-default-namespace="http://www.exlibrisgroup.com/dps/dnx"
				exclude-result-prefixes="#all"
				version="3.0">


	<!-- This section contains variables. Comment those out, that you don't need! -->

	<!--
	##################################################################
	###############       AR Policy Section    #######################
	############### Choose 1 arID and 1 arDesc #######################
	###############                            #######################
	##################################################################
	-->

	<!--
	##################################################################
	#######################        TEST      #######################
	###############                            #######################
	##################################################################

	<xsl:variable name="arId">53625</xsl:variable>
	<xsl:variable name="arDesc">TIB_STAFF only</xsl:variable>

	<xsl:variable name="arId">AR_EVERYONE</xsl:variable>
	<xsl:variable name="arDesc">Keine Beschränkung</xsl:variable>

	<xsl:variable name="arId">53626</xsl:variable>
	<xsl:variable name="arDesc">TIB Graue Literatur - nur CD Erstellung</xsl:variable>

	<xsl:variable name="arId">53627</xsl:variable>
	<xsl:variable name="arDesc">TIB Graue Literatur – nur Print Erstellung</xsl:variable>

	<xsl:variable name="arId">53628</xsl:variable>
	<xsl:variable name="arDesc">TIB Graue Literatur – CD und Print Erstellung</xsl:variable>

	<xsl:variable name="arId">478669</xsl:variable>
	<xsl:variable name="arDesc">TIB_IP_RANGE</xsl:variable>

	##################################################################
	#######################        PROD      #######################
	###############                            #######################
	##################################################################


	<xsl:variable name="arId">14996</xsl:variable>
	<xsl:variable name="arDesc">TIB_STAFF only</xsl:variable>

	<xsl:variable name="arId">AR_EVERYONE</xsl:variable>
	<xsl:variable name="arDesc">Keine Beschränkung</xsl:variable>

	<xsl:variable name="arId">14997</xsl:variable>
	<xsl:variable name="arDesc">TIB Graue Literatur - nur CD Erstellung</xsl:variable>

	<xsl:variable name="arId">14998</xsl:variable>
	<xsl:variable name="arDesc">TIB Graue Literatur – nur Print Erstellung</xsl:variable>

	<xsl:variable name="arId">14999</xsl:variable>
	<xsl:variable name="arDesc">TIB Graue Literatur – CD und Print Erstellung</xsl:variable>

	<xsl:variable name="arId">776148</xsl:variable>
	<xsl:variable name="arDesc">TIB_IP_RANGE</xsl:variable>

	-->
	<xsl:variable name="arId">AR_EVERYONE</xsl:variable>
	<xsl:variable name="arDesc">Keine Beschränkung</xsl:variable>


	<!--
	##################################################################
	###############        Status Section      #######################
	###############           Choose 1         #######################
	###############                            #######################
	##################################################################
	-->
	<!--
	<xsl:variable name="status">SUPPRESSED</xsl:variable>
	<xsl:variable name="status">ACTIVE</xsl:variable>-->

	<xsl:variable name="status">ACTIVE</xsl:variable>


	<!--
	##################################################################
	###############    ieEntityType Section    #######################
	###############           Choose 1         #######################
	###############                            #######################
	##################################################################
	-->
	<!--
	<xsl:variable name="ieEntityType">Book</xsl:variable>
	<xsl:variable name="ieEntityType">Article</xsl:variable>
	<xsl:variable name="ieEntityType">Conference</xsl:variable>
	<xsl:variable name="ieEntityType">Dissertation</xsl:variable>
	<xsl:variable name="ieEntityType">GreyLiterature</xsl:variable>
	<xsl:variable name="ieEntityType">Report</xsl:variable>
	<xsl:variable name="ieEntityType">Journal</xsl:variable>
	<xsl:variable name="ieEntityType">Database</xsl:variable>
	<xsl:variable name="ieEntityType">DeviceImage</xsl:variable>
	-->

	<xsl:variable name="ieEntityType">GreyLiterature</xsl:variable>


	<!--
	##################################################################
	###############    UserDefinedA Section    #######################
	###############           Choose 1         #######################
	###############                            #######################
	##################################################################
	-->

	<!--
	<xsl:variable name="userDefinedA">BMBF_Digitalisate</xsl:variable>
	<xsl:variable name="userDefinedA">BMBF_born-digital</xsl:variable>
	<xsl:variable name="userDefinedA">BMBF_device-image</xsl:variable>
	<xsl:variable name="userDefinedA">non-BMBF_born-digital</xsl:variable>
	<xsl:variable name="userDefinedA">non-BMBF_device-image</xsl:variable>
	<xsl:variable name="userDefinedA">Diss-LUH_born-digital</xsl:variable>
	<xsl:variable name="userDefinedA">LUH_born-digital</xsl:variable>
	<xsl:variable name="userDefinedA">MR_born-digital</xsl:variable>
	<xsl:variable name="userDefinedA">MR_device-image</xsl:variable>
	<xsl:variable name="userDefinedA">MAM_born-digital</xsl:variable>
	<xsl:variable name="userDefinedA">DELFT_Digitalisate</xsl:variable>
	<xsl:variable name="userDefinedA">MR_BENRAN_Digitalisate</xsl:variable>
	-->

	<xsl:variable name="userDefinedA">MR_born-digital</xsl:variable>

	<!--
	##################################################################
	###############    UserDefinedB Section    #######################
	###############           Choose 1         #######################
	###############                            #######################
	##################################################################
	-->
	<!--<xsl:variable name="userDefinedB">01_PWD</xsl:variable>

	<xsl:variable name="userDefinedB">02_NV</xsl:variable>
	<xsl:variable name="userDefinedB">03_Damaged_Data_Carrier</xsl:variable>
	-->
	<!--
	<xsl:variable name="userDefinedB">01_PWD</xsl:variable>
	-->

	<xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes"/>

	<xsl:template match="@*|node()">
		<xsl:copy copy-namespaces="no">
			<xsl:apply-templates select="@*|node()"/>
		</xsl:copy>
	</xsl:template>

	<!-- This section adds accessRightsID and accessRightsDescription on the IE level -->
	<!-- Todo: delete extra DNX tag -->
	<xsl:template match="mets:amdSec[@ID='ie-amd']/mets:rightsMD[@ID='ie-amd-rights']/mets:mdWrap/mets:xmlData/dnx">
		<xsl:copy copy-namespaces="no">
			<xsl:apply-templates select="@* | *"/>
			<xsl:element name="section">
				<xsl:attribute name="id">
					<xsl:value-of select="'accessRightsPolicy'"></xsl:value-of>
				</xsl:attribute>
				<xsl:element name="record">
					<xsl:element name="key">
						<xsl:attribute name="id">
							<xsl:value-of select="'policyId'"></xsl:value-of>
						</xsl:attribute>
						<xsl:value-of select="$arId"/>
					</xsl:element>
					<xsl:element name="key">
						<xsl:attribute name="id">
							<xsl:value-of select="'policyDescription'"></xsl:value-of>
						</xsl:attribute>
						<xsl:value-of select="$arDesc"/>
					</xsl:element>
				</xsl:element>
			</xsl:element>
		</xsl:copy>
	</xsl:template>

	<!-- This section adds generalIECharacteristics status, ieEntityType, UserDefinedA and UserDefinedB -->
	<xsl:template match="mets:amdSec[@ID='ie-amd']/mets:techMD[@ID='ie-amd-tech']/mets:mdWrap/mets:xmlData/dnx">
		<xsl:copy copy-namespaces="no">
			<xsl:apply-templates select="@* | *"/>
			<xsl:element name="section">
				<xsl:attribute name="id">
					<xsl:value-of select="'generalIECharacteristics'"></xsl:value-of>
				</xsl:attribute>
				<xsl:element name="record">
					<xsl:element name="key">
						<xsl:attribute name="id">
							<xsl:value-of select="'status'"></xsl:value-of>
						</xsl:attribute>
						<xsl:value-of select="$status"/>
					</xsl:element>
					<xsl:element name="key">
						<xsl:attribute name="id">
							<xsl:value-of select="'IEEntityType'"></xsl:value-of>
						</xsl:attribute>
						<xsl:value-of select="$ieEntityType"/>
					</xsl:element>
					<xsl:element name="key">
						<xsl:attribute name="id">
							<xsl:value-of select="'UserDefinedA'"></xsl:value-of>
						</xsl:attribute>
						<xsl:value-of select="$userDefinedA"/>
					</xsl:element>
					<!-- comment out when no User DefinedB is neccessary-->
					<!--
					<xsl:element name="key">
						<xsl:attribute name="id">
							<xsl:value-of select="'UserDefinedB'"></xsl:value-of>
						</xsl:attribute><xsl:value-of select="$userDefinedB" />
					</xsl:element>
					-->
					<!-- comment out when no User DefinedB is neccessary-->
				</xsl:element>
			</xsl:element>
		</xsl:copy>
	</xsl:template>


	<xsl:strip-space elements="*"/>

</xsl:stylesheet>
