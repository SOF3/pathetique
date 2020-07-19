<?php

declare(strict_types = 1);

namespace SOFe\Pathetique;

interface Component {
	/**
	 * Returns the directory-separator-free string for this component.
	 *
	 * Note that the leading `\` in `\\server\share` on DOS is not considered a directory separator,
	 * while the leading `/` in a Unix absolute path is considered a directory separator.
	 *
	 * Consequently, joining `toString()` of all components with `Platform->getDirectorySeparator()`
	 * yields the original path.
	 */
	public function toString() : string;

	public function __toString() : string;
}
