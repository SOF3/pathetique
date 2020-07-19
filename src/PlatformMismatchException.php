<?php

declare(strict_types = 1);

namespace SOFe\Pathetique;

/**
 * Attempt to use a path of an incompatible platform.
 *
 * This exception is thrown when a path is constructed for a different platform than the running one
 * but is used to interact with the running filesystem.
 *
 * Use `Path->toCurrentPlatform()` to *attempt* to use the path on this platform.
 */
class PlatformMismatchException extends IOException {
	public function __construct(Platform $current, Platform $other) {
		parent::__construct("Attempt to use a $other path to interact with a $current system");
	}
}
