<?php

declare(strict_types = 1);

namespace SOFe\Pathetique;

/**
 * A Windows verbatim path prefix using UNC (Uniform Naming Convention), e.g. `\\?\UNC\server\share`.
 */
final class VerbatimUncPrefix implements Prefix {
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

	public function toString() : string {
		return "\\\\?\\UNC\\" . $this->server . "\\" . $this->share;
	}

	public function __toString() : string {
		return $this->toString();
	}
}
