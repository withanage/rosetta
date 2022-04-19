# OJS-Rosetta Plugin

#### Build status
[![validate-sip](https://github.com/withanage/rosetta/actions/workflows/validate-sip.yml/badge.svg)](https://github.com/withanage/rosetta/actions/workflows/validate-sip.yml)

## Introduction

Open Journal Systems plug-in for importing metadata and data objects into the long-term archiving system (OJS) Plug-in for exporting metadata and data objects into the long-term archiving system (ExLibris Rosetta)

### Features

- Individual journal selection for deposit
- Automated interval based deposit
- SOAP-based communication
- PDS  handle best identification
- Included XML formats (TEI, METS, MODS)
- Validation support (PHPUnit, Github actions)


## Installation


```
$OJS=mypath
git clone $OJS/plugins/importexport/rosetta
git clone --branch main https://github.com/withanage/rosetta/
```


##  Configuration

Add the following config variables to your $OJS/config.inc.php
Your service provider may provide you this information.

``
[rosetta]
institution_code=$INSTITUTE_NAME
username=$ROSETTA_USER
password=$ROSETTA_PASSWORD
host = $ROSETTA_DEPOSIT_SOAP_INTERFACE_URL e.g. https://rosetta.develop.lza.tib.eu/dpsws/deposit/DepositWebServices?wsdl
subDirectoryName  =  $LOCAL_FILE_MOUNT_OF_ROSETTA_FILESHARE (The Server user running the OJS Rosetta script must have write permissions there.)
producerId = $PRDUCER_ID_FOR_OJS_ROSETTA
materialFlowId = $MATERIAL_FLOWID_FOR_OJS_ROSETTA
```



### Allgemein

in OJS sind meiste Felder multilingual

=======

### Publication

| Metadata | Type | Implemented | Remarks|
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
| primaryContact|string | dcterms:creator in auhtors |
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

| Metadata | Type | Implemented |
| ---- | ---- | ---- |
| id | integer| | |
|context | string| | |
|dateLastActivity | string | |
| dateSubmitted | string | |
| lastModified | string | | |

### Authors

| Metadata | Type | Implemented
| ---- | ---- | ---- |
| authors |string |dc:contributor" |
| ROR ID| | |
| Orcid Id ID| | |

### Galleys / Publication Formats

| Metadata | Type | Implemented | Remarks|
| ---- | ---- | ---- | ---- |
| Label | string | | |
| Locale |string | | |
| fileId |string | | |
| urlPath |string | | |
| seq |string | | |
| urlRemote |string | | |

# Entity Types

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

php tools/runScheduledTasks.php plugins/generic/usageStats/scheduledTasks.xml

### Clean

pdsHandle erzeugt: https://knowledge.exlibrisgroup.com/Rosetta/Knowledge_Articles/How_to_create_a_PDS_handle
Angaben, die du wahrscheinlich zum Erzeugen des pdsHandle oder generell für die Authentifizierung brauchst:

### Testing

php lib/pkp/lib/vendor/phpunit/phpunit/phpunit --configuration lib/pkp/tests/phpunit-env1.xml
plugins/importexport/rosetta/tests/functional/FunctionalRosettaExportTestCase.
