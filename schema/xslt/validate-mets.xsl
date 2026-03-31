<?xml version="1.0" encoding="UTF-8"?>
<!--
  validate-mets.xsl — Business-rule validation for Rosetta METS IE (ie1.xml) files.
  Returns <validation-report> with <error> children. Zero errors = valid.
-->
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:mets="http://www.exlibrisgroup.com/xsd/dps/rosettaMets"
    xmlns:dc="http://purl.org/dc/elements/1.1/"
    xmlns:dcterms="http://purl.org/dc/terms/"
    xmlns:mods="http://www.loc.gov/mods/v3"
    xmlns:xlink="http://www.w3.org/1999/xlink">

  <xsl:output method="xml" indent="yes" encoding="UTF-8"/>

  <xsl:template match="/">
    <validation-report file="ie1.xml">
      <!-- Root element -->
      <xsl:if test="not(mets:mets)">
        <error rule="METS-001">Root element must be mets:mets</error>
      </xsl:if>

      <xsl:apply-templates select="mets:mets"/>
    </validation-report>
  </xsl:template>

  <xsl:template match="mets:mets">
    <!-- ============================================================ -->
    <!--  STRUCTURAL SECTIONS                                          -->
    <!-- ============================================================ -->

    <!-- dmdSec required -->
    <xsl:if test="not(mets:dmdSec)">
      <error rule="METS-010">mets:dmdSec is required</error>
    </xsl:if>
    <xsl:if test="mets:dmdSec and not(mets:dmdSec[@ID='ie-dmd'])">
      <error rule="METS-011">dmdSec must have ID='ie-dmd'</error>
    </xsl:if>

    <!-- dmdSec must wrap Dublin Core -->
    <xsl:if test="mets:dmdSec and not(mets:dmdSec/mets:mdWrap[@MDTYPE='DC'])">
      <error rule="METS-012">dmdSec mdWrap must have MDTYPE='DC'</error>
    </xsl:if>

    <!-- amdSec required -->
    <xsl:if test="not(mets:amdSec)">
      <error rule="METS-020">At least one mets:amdSec is required</error>
    </xsl:if>
    <xsl:if test="not(mets:amdSec[@ID='ie-amd'])">
      <error rule="METS-021">amdSec with ID='ie-amd' is required</error>
    </xsl:if>

    <!-- fileSec required -->
    <xsl:if test="not(mets:fileSec)">
      <error rule="METS-030">mets:fileSec is required</error>
    </xsl:if>

    <!-- structMap required -->
    <xsl:if test="not(mets:structMap)">
      <error rule="METS-040">mets:structMap is required</error>
    </xsl:if>
    <xsl:if test="mets:structMap and not(mets:structMap[@TYPE='PHYSICAL'])">
      <error rule="METS-041">structMap must have TYPE='PHYSICAL'</error>
    </xsl:if>

    <!-- ============================================================ -->
    <!--  DUBLIN CORE inside dmdSec                                    -->
    <!-- ============================================================ -->
    <xsl:variable name="dc" select="mets:dmdSec/mets:mdWrap/mets:xmlData/dc:record"/>

    <xsl:if test="mets:dmdSec and not($dc)">
      <error rule="METS-050">dmdSec must contain a dc:record element</error>
    </xsl:if>

    <xsl:if test="$dc and not($dc/dc:title)">
      <error rule="METS-051">Embedded dc:record must contain dc:title</error>
    </xsl:if>
    <xsl:if test="$dc and not($dc/dc:creator)">
      <error rule="METS-052">Embedded dc:record must contain dc:creator</error>
    </xsl:if>
    <xsl:if test="$dc and not($dc/dc:date)">
      <error rule="METS-053">Embedded dc:record must contain dc:date</error>
    </xsl:if>

    <!-- ============================================================ -->
    <!--  DNX IE characteristics                                       -->
    <!-- ============================================================ -->
    <xsl:variable name="ieDnx"
        select="mets:amdSec[@ID='ie-amd']/mets:techMD/mets:mdWrap/mets:xmlData/dnx |
                mets:amdSec[@ID='ie-amd']/mets:techMD/mets:mdWrap/mets:xmlData/*[local-name()='dnx']"/>

    <xsl:if test="mets:amdSec[@ID='ie-amd'] and not($ieDnx)">
      <error rule="METS-060">ie-amd techMD must contain a dnx element</error>
    </xsl:if>

    <xsl:if test="$ieDnx and not($ieDnx/*[local-name()='section'][@id='generalIECharacteristics'])">
      <error rule="METS-061">DNX must contain section id='generalIECharacteristics'</error>
    </xsl:if>

    <xsl:variable name="ieChars" select="$ieDnx/*[local-name()='section'][@id='generalIECharacteristics']/*[local-name()='record']"/>
    <xsl:if test="$ieChars and not($ieChars/*[local-name()='key'][@id='status'])">
      <error rule="METS-062">generalIECharacteristics must contain key id='status'</error>
    </xsl:if>
    <xsl:if test="$ieChars and not($ieChars/*[local-name()='key'][@id='IEEntityType'])">
      <error rule="METS-063">generalIECharacteristics must contain key id='IEEntityType'</error>
    </xsl:if>

    <!-- ============================================================ -->
    <!--  MODS metadata (sourceMD)                                     -->
    <!-- ============================================================ -->
    <xsl:variable name="sourceMD" select="mets:amdSec[@ID='ie-amd']/*[local-name()='sourceMD']"/>
    <xsl:if test="mets:amdSec[@ID='ie-amd'] and not($sourceMD)">
      <error rule="METS-070">ie-amd must contain a sourceMD section</error>
    </xsl:if>

    <xsl:variable name="mods" select="$sourceMD/mets:mdWrap/mets:xmlData/mods:mods"/>
    <xsl:if test="$sourceMD and not($mods)">
      <error rule="METS-071">sourceMD must contain mods:mods element</error>
    </xsl:if>
    <xsl:if test="$mods and not($mods/mods:titleInfo)">
      <error rule="METS-072">mods:mods must contain titleInfo</error>
    </xsl:if>
    <xsl:if test="$mods and not($mods/mods:name)">
      <error rule="METS-073">mods:mods must contain at least one name element</error>
    </xsl:if>

    <!-- ============================================================ -->
    <!--  Representation amdSec                                        -->
    <!-- ============================================================ -->
    <xsl:if test="not(mets:amdSec[@ID='rep1-amd'])">
      <error rule="METS-080">amdSec with ID='rep1-amd' is required</error>
    </xsl:if>

    <xsl:variable name="repDnx"
        select="mets:amdSec[@ID='rep1-amd']/mets:techMD/mets:mdWrap/mets:xmlData/*[local-name()='dnx']"/>
    <xsl:if test="mets:amdSec[@ID='rep1-amd'] and not($repDnx)">
      <error rule="METS-081">rep1-amd techMD must contain a dnx element</error>
    </xsl:if>

    <xsl:variable name="repChars" select="$repDnx/*[local-name()='section'][@id='generalRepCharacteristics']/*[local-name()='record']"/>
    <xsl:if test="$repChars and not($repChars/*[local-name()='key'][@id='preservationType'])">
      <error rule="METS-082">generalRepCharacteristics must contain key id='preservationType'</error>
    </xsl:if>

    <!-- ============================================================ -->
    <!--  File sections                                                -->
    <!-- ============================================================ -->
    <xsl:if test="mets:fileSec and not(mets:fileSec/mets:fileGrp)">
      <error rule="METS-090">fileSec must contain at least one fileGrp</error>
    </xsl:if>

    <xsl:for-each select="mets:fileSec/mets:fileGrp/mets:file">
      <xsl:if test="not(@ID)">
        <error rule="METS-091">Every mets:file must have an ID attribute</error>
      </xsl:if>
      <xsl:if test="not(@ADMID)">
        <error rule="METS-092">Every mets:file must have an ADMID attribute</error>
      </xsl:if>
      <xsl:if test="not(mets:FLocat)">
        <error rule="METS-093">Every mets:file must contain a FLocat element</error>
      </xsl:if>
      <xsl:if test="mets:FLocat and not(mets:FLocat/@*[local-name()='href'])">
        <error rule="METS-094">FLocat must have an xlink:href attribute</error>
      </xsl:if>
    </xsl:for-each>

    <!-- ============================================================ -->
    <!--  File-level amdSec fixity                                     -->
    <!-- ============================================================ -->
    <xsl:for-each select="mets:amdSec[contains(@ID, 'fid')]">
      <xsl:variable name="fileDnx" select="mets:techMD/mets:mdWrap/mets:xmlData/*[local-name()='dnx']"/>
      <xsl:variable name="fixity" select="$fileDnx/*[local-name()='section'][@id='fileFixity']/*[local-name()='record']"/>
      <xsl:if test="$fixity and not($fixity/*[local-name()='key'][@id='fixityType'])">
        <error rule="METS-100">
          <xsl:text>fileFixity section in </xsl:text>
          <xsl:value-of select="@ID"/>
          <xsl:text> must contain fixityType</xsl:text>
        </error>
      </xsl:if>
      <xsl:if test="$fixity and not($fixity/*[local-name()='key'][@id='fixityValue'])">
        <error rule="METS-101">
          <xsl:text>fileFixity section in </xsl:text>
          <xsl:value-of select="@ID"/>
          <xsl:text> must contain fixityValue</xsl:text>
        </error>
      </xsl:if>
    </xsl:for-each>

    <!-- ============================================================ -->
    <!--  Cross-reference integrity                                    -->
    <!-- ============================================================ -->

    <!-- Every fptr FILEID must reference an existing file ID -->
    <xsl:for-each select="mets:structMap//mets:fptr">
      <xsl:variable name="fid" select="@FILEID"/>
      <xsl:if test="not(//mets:file[@ID = $fid])">
        <error rule="METS-110">
          <xsl:text>structMap fptr references non-existent file ID: </xsl:text>
          <xsl:value-of select="$fid"/>
        </error>
      </xsl:if>
    </xsl:for-each>

    <!-- Every file ADMID must reference an existing amdSec ID -->
    <xsl:for-each select="mets:fileSec/mets:fileGrp/mets:file">
      <xsl:variable name="admid" select="@ADMID"/>
      <xsl:if test="not(//mets:amdSec[@ID = $admid])">
        <error rule="METS-111">
          <xsl:text>file ADMID references non-existent amdSec: </xsl:text>
          <xsl:value-of select="$admid"/>
        </error>
      </xsl:if>
    </xsl:for-each>

  </xsl:template>

</xsl:stylesheet>
