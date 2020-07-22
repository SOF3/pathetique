<?php

declare(strict_types = 1);

namespace SOFe\Pathetique;

use Generator;
use Iterator;
use function closedir;
use function opendir;
use function readdir;

/**
 * Helper class used to ensure that a dropped opendir resource is not leaked.
 *
 * See `Path::scan()` for details.
 *
 * @implements Iterator<string, Path>
 */
final class DirectoryGuard implements Iterator {
	/** @var Path */
	private $path;
	/** @var resource|null */
	private $dir;
	/**
	 * @var Generator
	 * @phpstan-var Generator<string, Path, null, void>
	 */
	private $delegate;

	/**
	 * @internal this method is semver exempt.
	 * Use `$path->scan()` instead of constructing this class directly.
	 */
	public function __construct(Path $path) {
		$this->path = $path;
		$this->dir = Utils::throwIo(@opendir($path->toString()));
		$this->delegate = $this->generate();
	}

	/**
	 * @return Generator
	 * @phpstan-return Generator<string, Path, null, void>
	 */
	private function generate() {
		while(true) {
			if($this->dir === null) {
				throw new IOException("Attempt to scan closed directory");
			}
			$name = readdir($this->dir);
			if($name === false) {
				break;
			}
			if($name === "." || $name === "..") {
				continue;
			}
			yield $name => $this->path->join($name);
		}
		$this->close();
	}

	/**
	 * Closes this directory handle.
	 *
	 * This method is safe to call multiple times on the same object.
	 */
	public function close() : void {
		if($this->dir !== null) {
			closedir($this->dir);
			$this->dir = null;
		}
	}

	/**
	 * Do not rely on the automatic garbage collection!
	 */
	public function __destruct() {
		$this->close();
	}


	public function current() : Path {
		return $this->delegate->current();
	}

	public function key() : string {
		return $this->delegate->key();
	}

	public function next() : void {
		$this->delegate->next();
	}

	public function rewind() : void {
		$this->delegate->rewind();
	}

	public function valid() : bool {
		return $this->delegate->valid();
	}
}
