<?php

declare(strict_types = 1);

namespace SOFe\Pathetique;

/**
 * A `..` component.
 */
final class ParentDirectoryComponent implements Component {
	public function toString() : string {
		return "..";
	}

	public function __toString() : string {
		return "..";
	}
}
