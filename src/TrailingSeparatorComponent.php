<?php

declare(strict_types = 1);

namespace SOFe\Pathetique;

/**
 * The trailing directory separator in a path.
 */
final class TrailingSeparatorComponent implements Component {
	public function toString() : string {
		return "";
	}

	public function toNormalizedString() : string {
		return "";
	}

	public function __toString() : string {
		return "";
	}
}
