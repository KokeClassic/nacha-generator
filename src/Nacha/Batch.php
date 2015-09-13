<?php

namespace Nacha;

use Nacha\Record\BatchHeader;
use Nacha\Record\BatchFooter;
use Nacha\Record\DebitEntry;
use Nacha\Record\CcdEntry;

class Batch {

	// Service Class Codes
	const MIXED        = 200;
	const CREDITS_ONLY = 220;
	const DEBITS_ONLY  = 225;

	private $header;
	private $debitEntries = [];
	private $creditEntries = [];

	public function __construct() {
		$this->header = new BatchHeader();
	}

	public function getHeader() {
		return $this->header;
	}
	public function getTotalEntryCount() {
		return count($this->debitEntries) + count($this->creditEntries);
	}
	public function getTotalDebitAmount() {
		$amount = 0;
		foreach ($this->debitEntries as $entry) {
			$amount += (int)(string)$entry->getAmount();
		}
		return $amount;
	}
	public function getTotalCreditAmount() {
		$amount = 0;
		foreach ($this->creditEntries as $entry) {
			$amount += (int)(string)$entry->getAmount();
		}
		return $amount;
	}
	public function addDebitEntry(DebitEntry $entry) {
		$this->debitEntries[] = $entry;
		return $this;
	}
	public function addCreditEntry(CcdEntry $entry) {
		$this->creditEntries[] = $entry;
		return $this;
	}

	public function __toString() {
		$entries = '';

		$footer = (new BatchFooter)
			->setEntryAddendaCount($this->getTotalEntryCount())
			->setEntryHash('9101298') // @todo calculate this
			->setCompanyIdNumber((string)$this->header->getCompanyId())
			->setOriginatingDfiId((string)$this->header->getOriginatingDFiId())
			->setBatchNumber((string)$this->getHeader()->getBatchNumber());

		foreach ($this->debitEntries as $entry) {
			$entries .= (string)$entry."\n";
		}

		foreach ($this->creditEntries as $entry) {
			$entries .= (string)$entry."\n";
		}

		// calculate service code
		// default service code
		$this->header->setServiceClassCode(self::MIXED);
		if (count($this->debitEntries) > 0 && count($this->creditEntries) > 0) {
			$this->header->setServiceClassCode(self::MIXED);
		} else if (count($this->debitEntries) > 0 && count($this->creditEntries) == 0) {
			$this->header->setServiceClassCode(self::DEBITS_ONLY);
		} else if (count($this->debitEntries) == 0 && count($this->creditEntries) > 0) {
			$this->header->setServiceClassCode(self::CREDITS_ONLY);
		}

		$footer->setTotalDebitAmount($this->getTotalDebitAmount());
		$footer->setTotalCreditAmount($this->getTotalCreditAmount());
		$footer->setServiceClassCode((string)$this->header->getServiceClassCode());

		return (string)$this->header."\n".$entries.$footer;
	}

}