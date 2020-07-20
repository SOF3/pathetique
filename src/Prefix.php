<?php

declare(strict_types = 1);

namespace SOFe\Pathetique;

interface Prefix {
	/**
	 * Converts this prefix to a string.
	 *
	 * This is equivalent to the result of `PrefixComponent->toString()`.
	 */
	public function toString() : string;

	public function __toString() : string;
}
