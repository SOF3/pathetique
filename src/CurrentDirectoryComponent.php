<?php

declare(strict_types = 1);

namespace SOFe\Pathetique;

/**
 * A `.` component.
 *
 * For verbatim paths, a `.` is parsed as a normal component instead of this class.
 */
final class CurrentDirectoryComponent implements Component {
	public function toString() : string {
		return ".";
	}

	public function toNormalizedString() : ?string {
		return null;
	}

	public function __toString() : string {
		return ".";
	}
}
