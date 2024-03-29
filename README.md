# OJS-Rosetta Plugin

[![TIB-LOGO](LOGO.png)](https://tib.eu)![rosetta_horizonal](https://github.com/withanage/rosetta/assets/1921992/f21f4e9f-2931-44c4-85f0-b15b6a58dda8)


[![validate-sip](https://github.com/withanage/rosetta/actions/workflows/validate-sip.yml/badge.svg)](https://github.com/withanage/rosetta/actions/workflows/validate-sip.yml)

## Table of Contents
-   [OJS-Rosetta Plugin](#ojs-rosetta-plugin)
	-   [Table of Contents](#table-of-contents)
	-   [Introduction](#introduction)
	-   [Installation](#installation)
		-   [Requirements](#requirements)
		-   [Download the plugin](#download-the-plugin)
		-   [Confguration](#confguration)
		-   [Run the Plugin](#run-the-plugin)
		-   [Output](#output)
		-   [Automated deposits](#automated-deposits)
	-   [Technical Information](#technical-information)
-   [Automated Tests](#automated-tests)
	-   [Local](#local)
	-   [Github actions](#github-actions)
-   [Mapped metadata fields](#mapped-metadata-fields)
	-   [Publication](#publication)
	-   [Submission](#submission)
	-   [Authors](#authors)
	-   [Galleys / Publication Formats](#galleys--publication-formats)
	-   [Entity Types](#entity-types)
-   [Miscellaneous](#miscellaneous)
-   [Development](#development)

## Introduction

- Open Journal Systems(OJS) plug-in for importing metadata and data objects  into the long-term archiving system   (ExLibris Rosetta)
-   Creates and Validation of Submission Information Package (SIP) Packages defined by Open Archival Information System (OAIS)
-   Included XML formats (TEI, METS, MODS)
-   SOAP-based communication
-   PDS handle best identification
-   Regression tests support (PHPUnit, GitHub actions)
-   Individual journal selection for deposit
-   Automated interval based deposit

## Installation

### Requirements
This plugin requires a JAVA Runtime Environment in your system for SIP validation. Check with

`java --version`

### Download the plugin
```bash
$OJS_PATH=path # OJS installation path e.g. /var/www/html/ojs-3_3/
git clone  https://github.com/withanage/rosetta/ $OJS/plugins/importexport/rosetta
git checkout stable-3_3_0 # e.g. for OJS 3.3.0
```

### Confguration
#### 1. Activate the plugin
Add the following variables to your OJS global configuration file in`\$OJS/config.inc.php`.
Rosetta Service provider provides you this information.

```bash
[rosetta]
# mandatory
subDirectoryName  =  $LOCAL_FILE_MOUNT_OF_ROSETTA_FILESHARE
# only required for production, for development purposes, do not required.
institution_code = $INSTITUTE_NAME
username = $ROSETTA_USER
password = $ROSETTA_PASSWORD
host = $ROSETTA_DEPOSIT_URL e.g. https://<host>:<port>
materialFlowId = $MATERIAL_FLOWID_FOR_OJS_ROSETTA
producerId = $PRODUCER_ID_FOR_OJS_ROSETTA
testMode = true e.g. true false

#### 2. Add  Journals or specific issues to be deposited
To select the individual Journals , add the acronym of the journal in the [settings.json](settings.json) file in the plugin folder.
e.g. `$OJS/plugins/importexport/rosetta`

```json
{
"businessjournal": [],
"JPKJPK": [],
}
```

To select only  specific issues, add the volume, number and year for each issue in the json array.

```json
{
"JPKJPK": [],
"journal" : [
     	{
	  "JPKJPK": 1,
	  "number": 1,
	  "year": 2022
	}
  ]
}
```

### Run the Plugin

```bash!
php $OJS/tools/importExport.php     RosettaExportPlugin $journal_acronym
#e.g. php /var/www/html/ojs-3_3/tools/importExport.php     RosettaExportPlugin businessjournal
```
### Output

Your Rosetta Deposit files will be created under the `subDirectoryName`  defined in the `config.inc.php`

### Automated deposits

Schedule a recurring task in your operating system. For example \*nix
based cronjob running daily at 8pm

``` {.bash}
0 20 * * * php $OJS/tools/importExport.php RosettaExportPlugin
```

## Technical Information

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

# Automated Tests


## Local
```bash
php $OJS/lib/pkp/lib/vendor/phpunit/phpunit/phpunit --configuration
$OJS/lib/pkp/tests/phpunit-env2.xml --filter
FunctionalRosettaExportTest --test-suffix
FunctionalRosettaExportTest.php -v
$OJS/plugins/importexport/rosetta/tests/functional/RosettaFunctionsTest.php
```
## Github actions

Continuous Integration / Continuous Delivery (CI/CD) Pipeline is already configured for main [github repository](https://github.com/withanage/rosetta/actions) upon push commits.

# Mapped metadata fields

## Publication

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

## Submission

| Metadata | Type |
| ---- | ---- |
| id | integer| | |
|context | string| | |
|dateLastActivity | string | |
| dateSubmitted | string | |
| lastModified | string | | |

## Authors

| Metadata | Type |
| ---- | ---- |
| authors |string | |
| ROR ID| | |
| Orcid Id ID| | |

## Galleys / Publication Formats

| Metadata | Type | Remarks|
| ---- | ---- | ---- |
| Label | string | | |
| Locale |string | | |
| fileId |string | | |
| urlPath |string | | |
| seq |string | | |
| urlRemote |string | | |

## Entity Types

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
#  Development
- [Dulip Withanage](https://orcid.org/0000-0002-4996-7007)


# TODO check

- <dcterms:isPartOf >Open Access E-Journals/TIB OP/TH-Wildau-ENSP/2021/1/1</dcterms:isPartOf>
-
