<?php

declare(strict_types = 1);

namespace SOFe\Pathetique;

/**
 * A Windows verbatim path prefix using UNC (Uniform Naming Convention), e.g. `\\?\UNC\server\share`.
 */
final class VerbatimUncPrefix implements Prefix {
	public function toString() : string {
		// TODO unimplemented
	}

	public function __toString() : string {
		// TODO unimplemented
	}
}
