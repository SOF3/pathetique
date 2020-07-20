<?php

declare(strict_types = 1);

namespace SOFe\Pathetique;

/**
 * The leading `/` in Unix absolute paths.
 */
final class UnixPrefix implements Prefix {
	public function toString() : string {
		return "";
	}

	public function __toString() : string {
		return $this->toString();
	}
}
