<?php

declare(strict_types = 1);

namespace SOFe\Pathetique;

use function strlen;

final class DeviceNsPrefix extends Prefix {
	/** @var string */
	private $device;

	public function __construct(string $device) {
		$this->device = $device;
	}

	public function getDevice() : string {
		return $this->device;
	}

	public function getFullPrefix() : string {
		return "\\\\.\\{$this->device}";
	}

	public function getLength() : int {
		return 4 + strlen($this->device);
	}

	public function isVerbatim() : bool {
		return false;
	}
}
