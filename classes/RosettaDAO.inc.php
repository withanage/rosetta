<?php
import('lib.pkp.classes.db.DAO');
import('plugins.importexport.rosetta.classes.Rosetta');

class RosettaDAO extends DAO
{
	/**
	 * @param $rosettaId
	 * @param null $submissionId
	 * @return Rosetta|null
	 */
	function getById($rosettaId, $submissionId = null)
	{
		$params = array((int)$rosettaId);
		if ($submissionId) $params[] = (int)$submissionId;
		$result = $this->retrieve('SELECT * FROM rosetta WHERE rosetta_id = ?' . ($submissionId ? ' AND submission_id = ?' : ''), $params);
		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = $this->_fromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		return $returner;
	}

	/**
	 * @param $row
	 * @return Rosetta
	 */
	function _fromRow($row)
	{
		$rosetta = $this->newDataObject();
		$rosetta->setId($row['rosetta_id']);
		$rosetta->setContextId($row['context_id']);
		$rosetta->setSubmissionId($row['submission_id']);
		$rosetta->setSIP($row['sip']);
		$rosetta->setResponse($row['response']);
		$rosetta->setStatus($row['status']);
		$rosetta->setDateUploaded($row['date_uploaded']);
		$rosetta->setDateModified($row['date_modified']);
		return $rosetta;
	}

	/**
	 * @return Rosetta
	 */
	function newDataObject()
	{
		return new Rosetta();
	}

	/**
	 * @param $submissionId
	 * @param null $contextId
	 * @return DAOResultFactory
	 */
	function getBySubmissionId($submissionId, $contextId = null)
	{
		$params = array((int)$submissionId);
		if ($contextId) $params[] = (int)$contextId;
		$result = $this->retrieve('SELECT * FROM rosetta WHERE submission_id = ?' . ($contextId ? ' AND context_id = ?' : ''), $params);
		return new DAOResultFactory($result, $this, '_fromRow');
	}

	/**
	 * @param $rosetta
	 * @return mixed
	 */
	function insertObject($rosetta)
	{
		$this->update('INSERT INTO rosetta (rosetta_id,context_id,  submission_id, sip, response, status, date_uploaded, date_modified) VALUES (?, ?, ?, ?, ?, ?,?,?)', array((int)$rosetta->getContextId(), (int)$rosetta->getSubmissionId(), $rosetta->getSIP(), $rosetta->getResponse(), (int)$rosetta->getStatus(), $rosetta->getDateUploaded(), $rosetta->getDateModified()));
		$rosetta->setId($this->getInsertId());
		return $rosetta->getId();
	}

	/**
	 * @return int
	 */
	function getInsertId()
	{
		return $this->_getInsertId('rosetta', 'rosetta_id');
	}

	/**
	 * @param $rosetta
	 */
	function updateObject($rosetta)
	{
		$this->update('UPDATE	rosetta SET	response = ?, status = ?, date_uploaded = ?, date_modified = ?, WHERE rosetta_id = ?', array($rosetta->getResponse(), (int)$rosetta->getStatus(), $rosetta->getDateUploaded(), $rosetta->getDateModified(), (int)$rosetta->getId()));
	}

	/**
	 * @param $rosetta
	 */
	function deleteObject($rosetta)
	{
		$this->deleteById($rosetta->getId());
	}

	/**
	 * @param $rosettaId
	 */
	function deleteById($rosettaId)
	{
		$this->update('DELETE FROM rosetta WHERE rosetta_id = ?', (int)$rosettaId);
	}

	function getEntriesByStatus($contextId, $status, $rangeInfo = null)
	{
		$params = array(0, (int)$contextId);
		$params[] = (int)$status;
		$result = $this->retrieveRange(
			'SELECT *
 	FROM rosetta
 	WHERE context_id = ? AND status = ?',
			$params, $rangeInfo
		);
		return new DAOResultFactory($result, $this, '_fromRow');
	}
}
