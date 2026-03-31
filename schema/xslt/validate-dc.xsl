<?xml version="1.0" encoding="UTF-8"?>
<!--
  validate-dc.xsl — Business-rule validation for Rosetta Dublin Core SIP files.
  Returns <validation-report> with <error> children. Zero errors = valid.
-->
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:dc="http://purl.org/dc/elements/1.1/"
    xmlns:dcterms="http://purl.org/dc/terms/"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">

  <xsl:output method="xml" indent="yes" encoding="UTF-8"/>

  <xsl:template match="/">
    <validation-report file="dc.xml">
      <!-- Root element -->
      <xsl:if test="not(dc:record)">
        <error rule="DC-001">Root element must be dc:record</error>
      </xsl:if>

      <xsl:apply-templates select="dc:record"/>
    </validation-report>
  </xsl:template>

  <xsl:template match="dc:record">
    <!-- Required elements -->
    <xsl:if test="not(dc:title)">
      <error rule="DC-010">dc:title is required</error>
    </xsl:if>
    <xsl:if test="normalize-space(dc:title) = ''">
      <error rule="DC-011">dc:title must not be empty</error>
    </xsl:if>

    <xsl:if test="not(dc:creator)">
      <error rule="DC-020">dc:creator is required (at least one author)</error>
    </xsl:if>
    <xsl:for-each select="dc:creator">
      <xsl:if test="normalize-space(.) = ''">
        <error rule="DC-021">dc:creator must not be empty</error>
      </xsl:if>
    </xsl:for-each>

    <xsl:if test="not(dc:date)">
      <error rule="DC-030">dc:date is required</error>
    </xsl:if>
    <xsl:if test="normalize-space(dc:date) = ''">
      <error rule="DC-031">dc:date must not be empty</error>
    </xsl:if>

    <xsl:if test="not(dc:type)">
      <error rule="DC-040">dc:type is required</error>
    </xsl:if>

    <!-- Rosetta-specific type values -->
    <xsl:if test="not(dc:type[. = 'status-type:publishedVersion'])">
      <error rule="DC-041">dc:type must include 'status-type:publishedVersion'</error>
    </xsl:if>
    <xsl:if test="not(dc:type[. = 'doc-type:article'])">
      <error rule="DC-042">dc:type must include 'doc-type:article'</error>
    </xsl:if>

    <!-- License -->
    <xsl:if test="not(dcterms:license)">
      <error rule="DC-050">dcterms:license is required</error>
    </xsl:if>

    <!-- Publisher -->
    <xsl:if test="not(dc:publisher)">
      <error rule="DC-060">dc:publisher is required</error>
    </xsl:if>
    <xsl:if test="normalize-space(dc:publisher) = ''">
      <error rule="DC-061">dc:publisher must not be empty</error>
    </xsl:if>

    <!-- Language -->
    <xsl:if test="not(dc:language)">
      <error rule="DC-070">dc:language is required</error>
    </xsl:if>

    <!-- Language format: should be xx-XX not xx_XX -->
    <xsl:if test="dc:language[contains(., '_')]">
      <error rule="DC-071">dc:language must use hyphen (e.g. en-US), not underscore</error>
    </xsl:if>
  </xsl:template>

</xsl:stylesheet>
