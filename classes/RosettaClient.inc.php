<?php
declare(strict_types=1);

class SubmitDeposit
{
	protected ?string $pdsHandle;
	protected ?string $materialFlowId;
	protected ?string $subDirectoryName;
	protected ?string $producerId;
	protected ?string $depositSetId;

	public function __construct()
	{
	}

	public function submitDepositActivity(string $pdsHandle, string $materialFlowId, string $subDirectoryName, string $producerId, string $depositSetId): self
	{
		$this->depositSetId = $depositSetId;
		$this->materialFlowId = $materialFlowId;
		$this->pdsHandle = $pdsHandle;
		$this->producerId = $producerId;
		$this->subDirectoryName = $subDirectoryName;
		return $this;

	}
}

$wsdl = "https://rosetta.develop.lza.tib.eu/dpsws/deposit/DepositWebServices?wsdl";
$options = [
	'cache_wsdl' => WSDL_CACHE_NONE,
	'classmap' => [
		'SubmitDeposit' => SubmitDeposit::class,
	],
	'exceptions' => true,
	'soap_version' => SOAP_1_1,
	'trace' => 1,
];
$client = new SoapClient($wsdl, $options);
$header = new SoapHeader('http://soapinterop.org/echoheader/',
	'Authorization',
	base64_encode('tibojsauthdep-institutionCode-tib:Pbojs4TR=dA'));
$client->__setSoapHeaders($header);
$sd = new  SubmitDeposit();
$s = ($sd)->submitDepositActivity("", "76780103", "/exchange/lza/lza-tib/ojs-test", "76780038", "");
try {
	$response = $client->submitDepositActivity($s);
} catch (Exception $e) {
	throw new Exception("Response:\n" . $client->__getLastResponse() . "\n" . $client->__getLastRequest());
}

var_dump(
	$client->__getLastRequest(),
	$client->__getLastResponse(),
	$response
);


/**
 * #!/bin/bash
 * user_name=tibojsautodep
 * institution_code=TIB
 * pass_word='Pbojs4TR=dA'
 * proxy_url=https://rosetta.develop.lza.tib.eu/dpsws/deposit/DepositWebServices?wsdl
 *
 * materialFlowId=76780103
 * subDirectoryName=/exchange/lza/lza-tib/ojs-test
 * producerId=76780038
 *
 * credentials_base64=$(echo -n "${user_name}-institutionCode-${institution_code}:${pass_word}" | base64)
 *
 *
 * curl -X POST -H "Content-Type: text/xml" --header "Authorization: local $credentials_base64"  \
 * -H 'SOAPAction:""' \
 * --data-binary @request.xml $proxy_url
 * <?xml version="1.0" encoding="UTF-8"?>
 * <soap:Envelope
 * soap:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"
 * xmlns:dbs="http://dps.exlibris.com/"
 * xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"
 * xmlns:soapenc="http://schemas.xmlsoap.org/soap/encoding/"
 * xmlns:xsd="http://www.w3.org/2001/XMLSchema"
 * xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
 * <soap:Body>
 * <dbs:submitDepositActivity>
 * <arg1>76780103</arg1>
 * <arg2>/exchange/lza/lza-tib/ojs-test</arg2>
 * <arg3>76780038</arg3>
 * </dbs:submitDepositActivity>
 * </soap:Body>
 * </soap:Envelope>
 *
 *
 *
 *
 * /**
 *
 *
 *
 * array(6) {
 * [0]=>
 * string(155) "getDepositActivityBySubmitDateByMaterialFlowResponse getDepositActivityBySubmitDateByMaterialFlow(getDepositActivityBySubmitDateByMaterialFlow $parameters)"
 * [1]=>
 * string(113) "getDepositActivityByUpdateDateResponse getDepositActivityByUpdateDate(getDepositActivityByUpdateDate $parameters)"
 * [2]=>
 * string(56) "getHeartBitResponse getHeartBit(getHeartBit $parameters)"
 * [3]=>
 * string(113) "getDepositActivityBySubmitDateResponse getDepositActivityBySubmitDate(getDepositActivityBySubmitDate $parameters)"
 * [4]=>
 * string(86) "submitDepositActivityResponse submitDepositActivity(submitDepositActivity $parameters)"
 * [5]=>
 * string(155) "getDepositActivityByUpdateDateByMaterialFlowResponse getDepositActivityByUpdateDateByMaterialFlow(getDepositActivityByUpdateDateByMaterialFlow $parameters)"
 * }
 * array(12) {
 * [0]=>
 * string(181) "struct getDepositActivityBySubmitDateByMaterialFlow {
 * string arg0;
 * string arg1;
 * string arg2;
 * string arg3;
 * string arg4;
 * string arg5;
 * string arg6;
 * string arg7;
 * string arg8;
 * }"
 * [1]=>
 * string(93) "struct getDepositActivityBySubmitDateByMaterialFlowResponse {
 * string submitDateResultByMF;
 * }"
 * [2]=>
 * string(153) "struct getDepositActivityByUpdateDate {
 * string arg0;
 * string arg1;
 * string arg2;
 * string arg3;
 * string arg4;
 * string arg5;
 * string arg6;
 * string arg7;
 * }"
 * [3]=>
 * string(75) "struct getDepositActivityByUpdateDateResponse {
 * string updateDateResult;
 * }"
 * [4]=>
 * string(22) "struct getHeartBit {
 * }"
 * [5]=>
 * string(46) "struct getHeartBitResponse {
 * string return;
 * }"
 * [6]=>
 * string(153) "struct getDepositActivityBySubmitDate {
 * string arg0;
 * string arg1;
 * string arg2;
 * string arg3;
 * string arg4;
 * string arg5;
 * string arg6;
 * string arg7;
 * }"
 * [7]=>
 * string(75) "struct getDepositActivityBySubmitDateResponse {
 * string submitDateResult;
 * }"
 * [8]=>
 * string(102) "struct submitDepositActivity {
 * string arg0;
 * string arg1;
 * string arg2;
 * string arg3;
 * string arg4;
 * }"
 * [9]=>
 * string(63) "struct submitDepositActivityResponse {
 * string depositResult;
 * }"
 * [10]=>
 * string(181) "struct getDepositActivityByUpdateDateByMaterialFlow {
 * string arg0;
 * string arg1;
 * string arg2;
 * string arg3;
 * string arg4;
 * string arg5;
 * string arg6;
 * string arg7;
 * string arg8;
 * }"
 * [11]=>
 * string(93) "struct getDepositActivityByUpdateDateByMaterialFlowResponse {
 * string updateDateResultByMF;
 * }"
 * }
 */
