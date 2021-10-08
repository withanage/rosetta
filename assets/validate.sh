cd /tmp
rm -rf /tmp/jpkjpk-2.zip
rm -rf /var/www/html/ojs/cache
wget http://localhost/ojs
php /var/www/html/ojs/tools/importExport.php RosettaExportPlugin  publicknowledge;
unzip -o /tmp/jpkjpk-2-v1.zip
#xmllint --schema /home/withanage/projects/Rosetta.dps-sdk-projects/current/dps-sdk-projects/dps-sdk-deposit/src/xsd/mets_rosetta.xsd /tmp/content/ie1.xml --noout
#xmllint --schema /home/withanage/projects/Rosetta.dps-sdk-projects/current/dps-sdk-projects/dps-sdk-deposit/src/xsd/mets_rosetta.xsd /var/www/html/ojs/plugins/importexport/rosetta/komplexe_sip_struktur_mehrere_ebenen/GBV1006125345/content/ie1.xml --noout
#xmllint --schema /home/withanage/projects/Rosetta.dps-sdk-projects/current/dps-sdk-projects/dps-sdk-deposit/src/xsd/mets_rosetta.xsd /home/withanage/projects/Rosetta.dps-sdk-projects/current/dps-sdk-projects/dps-sdk-deposit/data/depositExamples/DepositExample1/content/ie1.xml --noout

