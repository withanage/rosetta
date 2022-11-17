# OJS-Rosetta Plugin

[![validate-sip](https://github.com/withanage/rosetta/actions/workflows/validate-sip.yml/badge.svg)](https://github.com/withanage/rosetta/actions/workflows/validate-sip.yml)

### Table of Contents

- [OJS-Rosetta Plugin](#ojs-rosetta-plugin)
    - [Introduction](#introduction)
        -   [Features](#features)
    - [Installation](#installation)
    - [Configuration](#configuration)
        -   [Global variables](#global-variables)
        -   [Individual Journals](#individual-journals)
        -   [Automated deposits](#automated-deposits)
    - [Technical Information](#technical-information)
    - [Automated Tests](#automated-tests)
        -   [Local](#local)
        -   [Github actions](#github-actions)
    - [Mapped metadata fields](#mapped-metadata-fields)
        - [Publication](#publication)
        - [Submission](#submission)
        - [Authors](#authors)
        - [Galleys / Publication Formats](#galleys--publication-formats)
        - [Entity Types](#entity-types)


#### Introduction

Open Journal Systems(OJS) plug-in for importing metadata and data objects
into the long-term archiving system   (ExLibris Rosetta)



### Features

-   Individual journal selection for deposit
-   Automated interval based deposit
-   SOAP-based communication
-   PDS handle best identification
-   Included XML formats (TEI, METS, MODS)
-   Regression tests support (PHPUnit, GitHub actions)

### Installation
	# This plugin requires a JAVA Runtime Environment in your system for SIP validation.

    $OJS=mypath
    git clone $OJS/plugins/importexport/rosetta
    git checkout stable-3_2_1 # for OJS 3.2.1
    git checkout stable-3_3_0 # for OJS 3.3.0
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

		host = $ROSETTA_DEPOSIT_URL e.g. https://<deposit-load-balancer-host>:<port>/deposit/DepositWebServices?wsdl

    subDirectoryName  =  $LOCAL_FILE_MOUNT_OF_ROSETTA_FILESHARE

    materialFlowId = $MATERIAL_FLOWID_FOR_OJS_ROSETTA

#### Individual Journals


To select the individual Journals , add the acronym of the journal in the [settings.json](settings.json) file in the plugin folder.

```json
"journal":[]
```

To select only  specific issues, add the volume, number and year for each issue in the json array.

```json
 "journal" : [
	{
	  "volume": 1,
	  "number": 1,
	  "year": 2022
	}
  ],

```


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
	dc.xml
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
```
-   Submission Information Packages (SIP)s are exported to the Rosetta
    system via the Rosetta SOAP interface for submission. Upon
    successful submission, Rosetta returns the stored package ID, and it
    is written to the OJS database table `submission_settings`. Failures
    are logged to \$OJS\_FILES/rosetta.log
-   Automated GitHub CI/CD pipe-line validates the created files.

## Automated Tests


### Local
```bash
php $OJS/lib/pkp/lib/vendor/phpunit/phpunit/phpunit --configuration
$OJS/lib/pkp/tests/phpunit-env2.xml --filter
FunctionalRosettaExportTest --test-suffix
FunctionalRosettaExportTest.php -v
$OJS/plugins/importexport/rosetta/tests/functional/FunctionalRosettaExportTest.php
```
### Github actions

Continuous Integration / Continuous Delivery (CI/CD) Pipeline is already configured for main [github repository](https://github.com/withanage/rosetta/actions) upon push commits.

## Mapped metadata fields


### Publication

| Metadata | Type | Mapping | Remarks|
| ---- | ---- | ---- | ---- |
|id | integer | |
| abstract |string | dcterms:abstract|
| accessStatus | integer |  | ARTICLE_ACCESS_OPEN usw. |
| authors |string | dc:contributors|
| categories| string | |
| copyrightHolder |string |dcterms:rightsHolder |
| copyrightYear| integer | dcterms:issued |
| datePublished |string | dcterms:date |
| disciplines |string | dcterms:audience |
|issue | integer |  |
| keywords|string | dcterms:subject|
| languages |string |dc:language |
|lastModified |string | dcterms:modified|
| locale |string | dc:language |
| prefix |string | |
| primaryContact|string | dcterms:creator in authors |
| publisherInstitution|string | dc:publisher |
| section |string | |
| seq| integer | |
| subjects |string | |
| subTitle |string | |
| supportingAgencies |string | |
| title |string | dc:title|
| version | integer | |
| urlPath |string | |
| citationsRaw |string |  | Zitationen getrennt durch  Line-breaks|
| pages |string | |
| coverage |string | |
| coverImage |string | |
| coverImage:dateUploaded |string | |
| coverImage:altText |string | |
| pub-id::published-id|string |dc:identifier |
| rights |string | |
| source |string | |
| supportingAgencies |string | |
| type |string | |

### Submission

| Metadata | Type |
| ---- | ---- |
| id | integer| | |
|context | string| | |
|dateLastActivity | string | |
| dateSubmitted | string | |
| lastModified | string | | |

### Authors

| Metadata | Type |
| ---- | ---- |
| authors |string | |
| ROR ID| | |
| Orcid Id ID| | |

### Galleys / Publication Formats

| Metadata | Type | Remarks|
| ---- | ---- | ---- |
| Label | string | | |
| Locale |string | | |
| fileId |string | | |
| urlPath |string | | |
| seq |string | | |
| urlRemote |string | | |

### Entity Types

| Code|Description|
| --- | --- |
|Album|Album|
|Article|Article|
|AV|Audiovisual|
|Book|Book|
|Chapter|Chapter|
|Conference|Conference|
|Database|Database|
|DeviceImage|Device Image|
|Dissertation|Dissertation|
|GreyLiterature|Grey Literature|
|Journal|Journal|
|JournalIssue|Journal Issue|
|JournalVolume|Journal Volume|
|Letter|Letter|
|Movie|Movie|
|Music|Music|
|null|None|
|Picture|Picture|
|Proceedings|Proceedings|
|Report|Report|
|Website|Website|


# Miscellaneous
- Correct Schema for debugging
```xml
<mets:mets xmlns:xlink="http://www.w3.org/1999/xlink"  xmlns:mets="http://www.exlibrisgroup.com/xsd/dps/rosettaMets"
		   xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.exlibrisgroup.com/xsd/dps/rosettaMets file:/home/withanage/projects/Rosetta.dps-sdk-projects/current/dps-sdk-projects/dps-sdk-deposit/src/xsd/mets_rosetta.xsd">

```

