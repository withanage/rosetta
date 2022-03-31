# OJS-Rosetta Plugin

## Introduction

## Configuration

- die Institution in Rosetta, in die du ingesten willst (TIB),
- der Username in Rosetta für deine Submission Application (tibojsautodep) und
- das Passwort für deinen User in Rosetta (das Passwort für den User habe ich dir mal zugeschickt).
- materialFlowId - of the material flow used: 76780103
- subDirectoryName - of the load directory: /exchange/lza/lza-tib/ojs-test
- producerId - of the Producer: 76780038

DEPOSIT_WSDL_URL => https://rosetta.develop.lza.tib.eu/dpsws/deposit/DepositWebServices?wsdl
PRODUCER_WSDL_URL => https://rosetta.develop.lza.tib.eu/dpsws/deposit/ProducerWebServices?wsdl
SIP_STATUS_WSDL_URL => https://rosetta.develop.lza.tib.eu/dpsws/repository/SipWebServices?wsdl

## Installation

# Remove later

```
similar to medra websewrvice
cd /tmp
rm -rf /tmp/jpkjpk-2.zip
rm -rf /var/www/html/ojs/cache
wget http://localhost/ojs
php /var/www/html/ojs/tools/importExport.php RosettaExportPlugin publicknowledge;
unzip -o /tmp/jpkjpk-2-v3.zip
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
plugins/importexport/rosetta/tests/functional/FunctionalRosettaExportTestCase.php
