<?php

declare(strict_types = 1);

namespace SOFe\Pathetique;

/**
 * A Windows path prefix using UNC (Uniform Naming Convention), e.g. `\\server\share`.
 */
final class UncPrefix implements Prefix {
	/** @var string */
	private $server;
	/** @var string */
	private $share;

	public function __construct(string $server, string $share) {
		$this->server = $server;
		$this->share = $share;
	}

	public function getServer() : string {
		return $this->server;
	}

	public function getShare() : string {
		return $this->share;
	}

	public function isVerbatim() : bool {
		return false;
	}

	public function toString() : string {
		return "\\\\" . $this->server . "\\" . $this->share;
	}

	public function __toString() : string {
		return $this->toString();
	}
}
