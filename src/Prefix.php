<?php

declare(strict_types = 1);

namespace SOFe\Pathetique;

/**
 * The prefix for an absolute path.
 *
 * On Unix platforms, the only prefix is `/` (UnixPrefix).
 */
interface Prefix {
	/**
	 * Returns whether this prefix indicates a verbatim (`\\?\`) path.
	 */
	public function isVerbatim() : bool;

	/**
	 * Converts this prefix to a string.
	 *
	 * This is equivalent to the result of `PrefixComponent->toString()`.
	 */
	public function toString() : string;

	public function __toString() : string;
}
