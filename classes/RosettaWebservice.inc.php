<?php

/**
 * @file plugins/importexport/Rosetta/classes/MedraWebservice.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class MedraWebservice
 * @ingroup plugins_importexport_medra_classes
 *
 * @brief A wrapper for the Rosetta web service 2.0.
 *
 * NB: We do not use PHP's SoapClient because it is not PHP4 compatible and
 * it doesn't support multipart SOAP messages.
 */


import('lib.pkp.classes.xml.XMLNode');

define('ROSETTA_WS_ENDPOINT_DEV', 'https://rosetta.develop.lza.tib.eu/dpsws/deposit/DepositWebServices?wsdl');
define('ROSETTA_WS_ENDPOINT', '');
define('ROSETTA_WS_RESPONSE_OK', 200);


class RosettaWebservice {


	var $_context;
	/** @var Plugin The current import/export plugin */
	var $_plugin;

	/**
	 * Constructor
	 * @param $context Context
	 * @param $plugin Plugin
	 */
	function __construct($context, $plugin) {
		$this->_context = $context;
		$this->_plugin = $plugin;
	}


	function deposit() {

		$soapMessage =
			'<soap:Envelope soap:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"' .
			'xmlns:dbs="http://dps.exlibris.com/"' .
			'xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"' .
			'xmlns:soapenc="http://schemas.xmlsoap.org/soap/encoding/"' .
			'xmlns:xsd="http://www.w3.org/2001/XMLSchema"' .
			'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">' .
			'<soap:Body>' .
			'<dbs:submitDepositActivity>' .
			'<arg1>76780103</arg1>' .
			'<arg2>/exchange/lza/lza-tib/ojs-test</arg2>' .
			'<arg3>76780038</arg3>' .
			'</dbs:submitDepositActivity>' .
			'</soap:Body>' .
			'</soap:Envelope>';


		// Prepare HTTP session.
		import('lib.pkp.classes.helpers.PKPCurlHelper');
		$curlCh = PKPCurlHelper::getCurlObject();

		curl_setopt($curlCh, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlCh, CURLOPT_POST, true);

		curl_setopt($curlCh, CURLOPT_URL, $this->_endpoint);
		$extraHeaders = array(
			'SOAPAction: ""',
			'Content-Type: text/xml',
		);
		curl_setopt($curlCh, CURLOPT_HTTPHEADER, $extraHeaders);
		curl_setopt($curlCh, CURLOPT_POSTFIELDS, $soapMessage);

		$result = true;
		$response = curl_exec($curlCh);

		// We do not localize our error messages as they are all
		// fatal errors anyway and must be analyzed by technical staff.
		if ($response === false) {
			$result = 'OJS-Rosetta: Expected string response.';
		}

		if ($result === true && ($status = curl_getinfo($curlCh, CURLINFO_HTTP_CODE)) != ROSETTA_WS_RESPONSE_OK) {
			$result = 'OJS-Rosetta: Expected ' . ROSETTA_WS_RESPONSE_OK . ' response code, got ' . $status . ' instead.';
		}

		curl_close($curlCh);


		return $result;
	}

	/**
	 * Create a mime part with the given content.
	 * @param $contentId string
	 * @param $content string
	 * @return string
	 */
	function _getMimePart($contentId, $content) {
		return
			"Content-Type: text/xml; charset=utf-8\r\n" .
			"Content-ID: <${contentId}>\r\n" .
			"\r\n" .
			$content . "\r\n";
	}

	/**
	 * Create a globally unique MIME content ID.
	 * @param $prefix string
	 * @return string
	 */
	function _getContentId($prefix) {
		return $prefix . md5(uniqid()) . '@Rosetta.org';
	}

	/**
	 * Escape XML entities.
	 * @param $string string
	 */
	function _escapeXmlEntities($string) {
		return XMLNode::xmlentities($string);
	}
}
