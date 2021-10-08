pdsHandle erzeugt: https://knowledge.exlibrisgroup.com/Rosetta/Knowledge_Articles/How_to_create_a_PDS_handle
Angaben, die du wahrscheinlich zum Erzeugen des pdsHandle oder generell für die Authentifizierung brauchst:

- die Institution in Rosetta, in die du ingesten willst (TIB),
- der Username in Rosetta für deine Submission Application (tibojsautodep) und
- das Passwort für deinen User in Rosetta (das Passwort für den User habe ich dir mal zugeschickt).
- materialFlowId - of the material flow used: 76780103
- subDirectoryName - of the load directory: /exchange/lza/lza-tib/ojs-test
- producerId - of the Producer: 76780038

Passwort habe ich dir vor einer ganzen Weile zugeschickt, als ich die Konfiguration in Rosetta DEV angelegt habe. Es
sollte für die DEV-Anbindung Pbojs4TR=dA sein.

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
|id | integer | :question:|
| abstract |string | dcterms:abstract|
| accessStatus | integer | :question: | ARTICLE_ACCESS_OPEN usw. |
| authors |string | dc:contributors|
| categories| string |:question: |
| copyrightHolder |string |dcterms:rightsHolder |
| copyrightYear| integer | dcterms:issued |
| datePublished |string | dcterms:date |
| disciplines |string | dcterms:audience |
|issue | integer | :question: |
| keywords|string | dcterms:subject|
| languages |string |dc:language |
|lastModified |string | dcterms:modified|
| locale |string | dc:language |
| prefix |string | :question:|
| primaryContact|string | dcterms:creator in auhtors |
| publisherInstitution|string | dc:publisher |
| section |string | :question:|
| seq| integer | :question:|
| subjects |string | :question:|
| subTitle |string | :question:|
| supportingAgencies |string | :question:|
| title |string | dc:title|
| version | integer | :question:|
| urlPath |string | :question:|
| citationsRaw |string | :question: | Zitationen getrennt durch  Line-breaks|
| pages |string | :question:|
| coverage |string | :question:|
| coverImage |string | :question:|
| coverImage:dateUploaded |string | :question:|
| coverImage:altText |string | :question:|
| pub-id::published-id|string |dc:identifier |
| rights |string | :question:|
| source |string | :question:|
| supportingAgencies |string | :question:|
| type |string | :question:|

### Submission

| Metadata | Type | Implemented |
| ---- | ---- | ---- |
| id | integer| :question:| |
|context | string| :question:| |
|dateLastActivity | string | :question:|
| dateSubmitted | string | :question:|
| lastModified | string | :question:| |

### Authors

| Metadata | Type | Implemented
| ---- | ---- | ---- |
| authors |string |dc:contributor" |
| ROR ID| :question:| |
| Orcid Id ID| :question:| |

### Galleys / Publication Formats

| Metadata | Type | Implemented | Remarks|
| ---- | ---- | ---- | ---- |
| Label | string | :question:| |
| Locale |string | :question:| |
| fileId |string | :question:| |
| urlPath |string | :question:| |
| seq |string | :question:| |
| urlRemote |string | :question:| |

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
