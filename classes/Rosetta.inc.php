<?php

class Rosetta extends DataObject {
	/**
	 * @return int
	 */
	function getContextId() {
		return $this->getData('contextId');
	}

	/**
	 * Set context ID.
	 * @param $contextId int
	 */
	function setContextId($contextId) {
		return $this->setData('contextId', $contextId);
	}

	/**
	 * Get submission ID.
	 * @return int
	 */
	function getSubmissionId() {
		return $this->getData('submissionId');
	}

	/**
	 * Set submission ID.
	 * @param $submissionId int
	 */
	function setSubmissionId($submissionId) {
		return $this->setData('submissionId', $submissionId);
	}

	/**
	 * @return string
	 */
	function getSIP() {
		return $this->getData('sip');
	}

	/**
	 * @param $sip
	 */
	function setSIP($sip) {
		return $this->setData('submissionId', $sip);
	}

	/**
	 * @return string
	 */
	function getResponse() {
		return $this->getData('response');
	}

	/**
	 * @return string
	 */
	function getStatus() {
		return $this->getData('status');
	}

	function setStatus($status) {
		return $this->setData('status', $status);
	}

	/**
	 * Get uploaded date of file.
	 * @return date
	 */
	function getDateUploaded() {
		return $this->getData('dateUploaded');
	}

	/**
	 * Set uploaded date of file.
	 * @param $dateUploaded date
	 */
	function setDateUploaded($dateUploaded) {
		return $this->SetData('dateUploaded', $dateUploaded);
	}

	/**
	 * Get modified date of file.
	 * @return date
	 */
	function getDateModified() {
		return $this->getData('dateModified');
	}

	/**
	 * Set modified date of file.
	 * @param $dateModified date
	 */
	function setDateModified($dateModified) {
		return $this->SetData('dateModified', $dateModified);
	}
}
