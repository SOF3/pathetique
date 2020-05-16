<?php

declare(strict_types = 1);

namespace SOFe\Pathetique;

use function strlen;

final class VerbatimUncPrefix extends Prefix {
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

	public function getFullPrefix() : string {
		return "\\\\?\\UNC\\{$this->server}\\{$this->share}";
	}

	public function getLength() : int {
		return 8 + strlen($this->server) + 1 + strlen($this->share);
	}

	public function isVerbatim() : bool {
		return true;
	}
}
