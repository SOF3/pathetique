<?php

declare(strict_types = 1);

namespace SOFe\Pathetique;

/**
 * A Windows device namespace path prefix, e.g. `\\.\COM42`.
 */
final class DeviceNsPrefix implements Prefix {
	/** @var string */
	private $name;

	public function __construct(string $name) {
		$this->name = $name;
	}

	public function getName() : string {
		return $this->name;
	}

	public function toString() : string {
		return "\\\\.\\" . $this->name;
	}

	public function __toString() : string {
		return $this->toString();
	}
}
