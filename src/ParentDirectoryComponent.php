<?php

declare(strict_types = 1);

namespace SOFe\Pathetique;

/**
 * A `..` component.
 *
 * For verbatim paths, a `..` is parsed as a normal component instead of this class.
 */
final class ParentDirectoryComponent implements Component {
	public function toString() : string {
		return "..";
	}

	public function __toString() : string {
		return "..";
	}
}
