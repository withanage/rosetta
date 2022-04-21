# OJS-Rosetta Plugin

### Table of Contents

- [OJS-Rosetta Plugin](#ojs-rosetta-plugin)
    -   [Build status](#build-status)
    -   [Introduction](#introduction)
        -   [Features](#features)
    -   [Installation](#installation)
    -   [Configuration](#configuration)
        -   [Global variables](#global-variables)
        -   [Individual Journals](#individual-journals)
        -   [Automated deposits](#automated-deposits)
    -   [Technical Information](#technical-information)
    -   [Automated Tests](#automated-tests)
        -   [Local](#local)
        -   [Github actions](#github-actions)
    -   [Mapped metadata fields](#mapped-metadata-fields)
        -   [Publication](#publication)
        -   [Submission](#submission)
        -   [Authors](#authors)
        -   [Galleys / Publication
            Formats](#galleys--publication-formats)
- [Entity Types](#entity-types)


#### Build status

[![validate-sip](https://github.com/withanage/rosetta/actions/workflows/validate-sip.yml/badge.svg)](https://github.com/withanage/rosetta/actions/workflows/validate-sip.yml)

#### Introduction


Open Journal Systems plug-in for importing metadata and data objects
into the long-term archiving system (OJS) Plug-in for exporting metadata
and data objects into the long-term archiving system (ExLibris Rosetta)

### Features

-   Individual journal selection for deposit
-   Automated interval based deposit
-   SOAP-based communication
-   PDS handle best identification
-   Included XML formats (TEI, METS, MODS)
-   Validation support (PHPUnit, Github actions)

### Installation


    $OJS=mypath
    git clone $OJS/plugins/importexport/rosetta
    git clone --branch main https://github.com/withanage/rosetta/

### Configuration


#### Global variables

Add the following variables to your OJS global configuration file in
\$OJS/config.inc.php. Rosetta Service provider provides you this
information.

    [rosetta]
    institution_code=$INSTITUTE_NAME
    username=$ROSETTA_USER
    password=$ROSETTA_PASSWORD
    host = $ROSETTA_DEPOSIT_SOAP_INTERFACE_URL e.g. https://rosetta.develop.lza.tib.eu/dpsws/deposit/DepositWebServices?wsdl
    subDirectoryName  =  $LOCAL_FILE_MOUNT_OF_ROSETTA_FILESHARE (The Server user running the OJS Rosetta script must have write permissions there.)
    producerId = $PRDUCER_ID_FOR_OJS_ROSETTA
    materialFlowId = $MATERIAL_FLOWID_FOR_OJS_ROSETTA

#### Individual Journals

In the Import-Export Plugin section, enable \"SRosetta
Export/Registration Plugin\" for individual magazines available under
Settings-\> Website -\> Plugins -\> Installed Plugins.

### Automated deposits

Schedule a recurring task in your operating system. For example \*nix
based cronjob running daily at 8pm

``` {.bash}

0 20 * * * php $OJS/tools/importExport.php RosettaExportPlugin
```

### Technical Information


-   This application creates a Submission Information Package
    ([SIP](http://exl-edu.com/12_Rosetta/Rosetta%20Essentials/SIP%20Processing/SIP%20Processing%20Configuration/story_html5.html))
    specified by the Open Archival Information System
    ([OAIS](https://public.ccsds.org/pubs/650x0m2.pdf)). For each
    article version a unique SIP package is created. The name of the SIP
    package consists of 3 parts.
    -   Journal Acronym \_ Article Id \_ Version number. e.g.
        `testjournal_1_v2`

```
       Rosetta Local mount
        |
        |_sip_folder e.g.1
            |
            |_content
                 |
                 |
                 |_streams
                 |   |
                 |   |_file1.pdf
                 |   |
                 |   |_file2.xml
                 |
                 |ie1.xml
                 |
                 |dc.xml
```
-   Submission Information Packages (SIP)s are exported to the Rosetta
    system via the Rosetta SOAP interface for submission. Upon
    successful submission, Rosetta returns the stored package ID, and it
    is written to the OJS database table `submission_settings`. Failures
    are logged to \$OJS\_FILES/rosetta.log
-   Automated github CI/CD pipe-line validates the created files.

## Automated Tests


### Local
```
php $OJS/lib/pkp/lib/vendor/phpunit/phpunit/phpunit --configuration
$OJS/lib/pkp/tests/phpunit-env2.xml --filter
FunctionalRosettaExportTest --test-suffix
FunctionalRosettaExportTestCase.php -v
$OJS/plugins/importexport/rosetta/tests/functional/FunctionalRosettaExportTestCase.php
```
### Github actions

Continuous Integration / Continuous Delivery (CI/CD) Pipeline is already
configured for github repositories upon push commits.

## Mapped metadata fields


### Publication

  Metadata                  Type      Implemented                  Remarks
  ------------------------- --------- ---------------------------- ----------------------------
  id                        integer
  abstract                  string    dcterms:abstract
  accessStatus              integer                                ARTICLE\_ACCESS\_OPEN usw.
  authors                   string    dc:contributors
  categories                string
  copyrightHolder           string    dcterms:rightsHolder
  copyrightYear             integer   dcterms:issued
  datePublished             string    dcterms:date
  disciplines               string    dcterms:audience
  issue                     integer
  keywords                  string    dcterms:subject
  languages                 string    dc:language
  lastModified              string    dcterms:modified
  locale                    string    dc:language
  prefix                    string
  primaryContact            string    dcterms:creator in auhtors
  publisherInstitution      string    dc:publisher
  section                   string
  seq                       integer
  subjects                  string
  subTitle                  string
  supportingAgencies        string
  title                     string    dc:title
  version                   integer
  urlPath                   string
  citationsRaw              string
  pages                     string
  coverage                  string
  coverImage                string
  coverImage:dateUploaded   string
  coverImage:altText        string
  pub-id::published-id      string    dc:identifier
  rights                    string
  source                    string
  supportingAgencies        string
  type                      string

### Submission

  Metadata           Type      Implemented
  ------------------ --------- -------------
  id                 integer
  context            string
  dateLastActivity   string
  dateSubmitted      string
  lastModified       string

### Authors

  Metadata      Type     Implemented
  ------------- -------- ------------------
  authors       string   dc:contributor\"
  ROR ID
  Orcid Id ID

### Galleys / Publication Formats {#galleys--publication-formats}

  Metadata    Type     Implemented   Remarks
  ----------- -------- ------------- ---------
  Label       string
  Locale      string
  fileId      string
  urlPath     string
  seq         string
  urlRemote   string

Entity Types
============

  Code             Description
  ---------------- -----------------
  Album            Album
  Article          Article
  AV               Audiovisual
  Book             Book
  Chapter          Chapter
  Conference       Conference
  Database         Database
  DeviceImage      Device Image
  Dissertation     Dissertation
  GreyLiterature   Grey Literature
  Journal          Journal
  JournalIssue     Journal Issue
  JournalVolume    Journal Volume
  Letter           Letter
  Movie            Movie
  Music            Music
  null             None
  Picture          Picture
  Proceedings      Proceedings
  Report           Report
  Website          Website
