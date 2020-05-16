<?php

declare(strict_types = 1);

namespace SOFe\Pathetique;

use const DIRECTORY_SEPARATOR;
use const PHP_OS_FAMILY;
use function array_pop;
use function array_slice;
use function assert;
use function count;
use function implode;
use function min;
use function strlen;
use function substr;
use Generator;

final class Path {
	/** @var string */
	private $string;

	/** @var bool */
	private $prefixInited = false;
	/** @var Prefix|null */
	private $prefix = null;

	/**
	 * @var int[][]|string[][]|null
	 * @phpstan-var array{int, string}[]|null
	 */
	private $components = null;

	public static function new(string $path) : self {
		$self = new self;
		$self->string = $path;
		return $self;
	}

	/**
	 * Creates a path from its components.
	 *
	 * The order of components must be consistent with the definition in `getComponents()`.
	 *
	 * @param int[][]|string[][] $components
	 * @phpstan-param array{int, string}[] $components
	 */
	public static function fromComponents(array $components) : self {
		$self = new self;

		if(count($components) === 0) {
			$self->string = ".";
			$self->components = [[Component::CURRENT_DIR, "."]];
			return $self;
		}

		if($components[0][0] === Component::ROOT_DIR) {
			$path = $components[0][1];
			$start = 1;
		} elseif($components[0][0] === Component::PREFIX) {
			$path = $components[0][1] . DIRECTORY_SEPARATOR;
			$start = 2;
		} else {
			$path = "";
			$start = 0;
		}

		$path .= implode(DIRECTORY_SEPARATOR, array_map(static function($component) : string {
			return $component[1];
		}, array_slice($components, $start)));

		$self->string = $path;
		$self->components = $components;
		return $self;
	}

	private function __construct() {
	}

	public function __toString() : string {
		return $this->toString();
	}

	/**
	 * Returns the raw, user-readable, OS-compatible string representation of this path.
	 *
	 * @return string
	 */
	public function toString() : string {
		return $this->string;
	}

	public function isAbsolute() : bool {
		if(!$this->hasRoot()) {
			return false;
		}

		return !Utils::isWindows() || $this->getPrefix() !== null;
	}

	public function isRelative() : bool {
		return !$this->isAbsolute();
	}

	public function getPrefix() : ?Prefix {
		if(!Utils::isWindows()) {
			return null;
		}

		if(!$this->prefixInited) {
			$this->prefixInited = true;
			$this->prefix = Prefix::parse($this->string);
		}

		return $this->prefix;
	}

	public function hasRoot() : bool {
		$string = $this->string;
		$prefix = $this->getPrefix();
		$start = $prefix !== null ? strlen($prefix->getFullPrefix()) : 0;

		if(strlen($string) >= $start + 1 && Utils::isSeparator($string[$start])) {
			return true;
		}
		if($prefix !== null && !($prefix instanceof DiskPrefix)) {
			return true;
		}

		return false;
	}


	/**
	 * Returns the components of this path.
	 *
	 * Each element of the returned array is an [int, string] tuple.
	 * The int is one of the constants in the `Component` class.
	 * The string is the content of the component.
	 *
	 * The string contains a trailing directory separator
	 * if and only if it corresponds to the leading directory separator in an absolute path,
	 * i.e. the leading `/` in `/dev/null`.
	 *
	 * Unless the path contains a verbatim prefix,
	 * intermediate (i.e. not the leading one in relative paths) current-directory components `./`
	 * are not yielded.
	 *
	 * - If the path contains a prefix, it first yields the prefix,
	 * then an empty `ROOT_DIR` component.
	 * - If the path does not contain a prefix but starts with a directory separator,
	 * it yields a `ROOT_DIR` component with the leading slash.
	 * - If the path is empty, equals `.` or starts with `./`, it yields `CURRENT_DIR`.
	 * - For each of the subsequent components,
	 *   - it yields `[CURRENT_DIR, "."]` for `./` if the path is a verbatim path;
	 *   - it skips for `./` if the path is not a verbatim path;
	 *   - it yields `[PARENT_DIR, ".."]` for `../`;
	 *   - it yields `NORMAL` with only filename contents otherwise.
	 *
	 * @return int[][]|string[][]
	 * @phpstan-return array{int, string}[]
	 */
	public function getComponents() : array {
		if($this->components === null) {
			$comps = [];

			$prefix = $this->getPrefix();
			$prefixLength = $prefix !== null ? $prefix->getLength() : 0;
			if($prefixLength > 0) {
				$comps[] = [Component::PREFIX, substr($this->string, 0, $prefixLength)];
				$comps[] = [Component::ROOT_DIR, ""];
			}

			if(strlen($this->string) > $prefixLength && Utils::isSeparator($this->string[$prefixLength])) {
				if($prefix === null) {
					$comps[] = [Component::ROOT_DIR, $this->string[0]];
				}
				$start = $prefixLength + 1;
			} elseif($prefix !== null && !($prefix instanceof DiskPrefix) && !$prefix->isVerbatim()) {
				$comps[] = [Component::ROOT_DIR, ""];
				$start = $prefixLength + 1;
			} elseif(strlen($this->string) === 0 ||
				$this->string[0] === "." && (
					strlen($this->string) === 1 || Utils::isSeparator($this->string[1]))) {
				assert($prefix === null, "Prefixed path cannot start with .");
				$comps[] = [Component::CURRENT_DIR, "."];
				$start = 2;
			} else {
				$start = 0;
			}

			while(strlen($this->string) > $start) {
				$slash = Utils::findSlash($this->string, $start, true) ?? strlen($this->string);
				$comp = substr($this->string, $start, $slash - $start);
				$start = min($slash + 1, strlen($this->string));

				if($comp === ".") {
					if($prefix !== null && $prefix->isVerbatim()) {
						$comps[] = [Component::CURRENT_DIR, "."];
					}
					// if not verbatim, simplify */./* to */*
				} elseif($comp === "..") {
					$comps[] = [Component::PARENT_DIR, ".."];
				} elseif($comp !== "") {
					$comps[] = [Component::NORMAL, $comp];
				}
			}

			$this->components = $comps;
		}
		return $this->components;
	}

	/**
	 * Returns the lexical parent of this path.
	 *
	 * This method only removes the last path component if any,
	 * and behaves very differently from the filesystem `../`.
	 * In particular, if the last component is `../`,
	 * this would delete the `../`,
	 * i.e. entering deeper into the filesystem;
	 * if the last component is a symbolic link,
	 * this would return the parent directory of the symbolic link
	 * rather than the parent directory of the file pointed by the symbolic link;
	 * if the last component is `./` in a verbatim path,
	 * only the `./` is removed.
	 *
	 * If the path only contains `PREFIX` and `ROOT_DIR`, or if the path is `./`,
	 * `null` is returned.
	 *
	 * If this path only contains one normal or parent-directory component,
	 * the path `./` is returned.
	 */
	public function getLexicalParent() : ?Path {
		$comps = $this->getComponents();
		if(count($comps) === 1 && $comps[0][0] === Component::ROOT_DIR ||
			count($comps) === 2 && $comps[1][0] === Component::ROOT_DIR ||
			count($comps) === 1 && $comps[0][0] === Component::CURRENT_DIR) {
			return null;
		}

		if(count($comps) === 1) {
			assert($comps[0][0] === Component::PARENT_DIR || $comps[0][0] === Component::NORMAL);
			return self::new(".");
		}

		array_pop($comps);
		return self::fromComponents($comps);
	}

	public function getFileName() : ?string {
		$comps = $this->getComponents();
		[$type, $value] = $comps[count($comps) - 1];
		return $type === Component::NORMAL ? $value : null;
	}

	public function stripPrefix(Path $prefix) : ?Path {
		$comps = $this->getComponents();
		$prefixComps = $prefix->getComponents();

		foreach($prefixComps as $i => $c) {
			if(!isset($comps[$i]) || $comps[$i] !== $c) {
				return null;
			}
		}

		return self::fromComponents(array_slice($comps, count($prefixComps)));
	}

	/**
	 * Checks whether two paths are component-wise equal.
	 *
	 * This does not resolve any environment or filesystem contexts.
	 * In particular, this does not cancel `..`
	 * (because the part before `..` might be a symbolic link),
	 * does not remove `./` in verbatim paths (since they are not allowed),
	 * does not remove verbatim prefixes,
	 * and does not resolve current directories.
	 */
	public function equals(Path $that) : bool {
		$c1 = $this->getComponents();
		$c2 = $that->getComponents();
		if(count($c1) !== count($c2)) {
			return false;
		}

		foreach($c1 as $i => $c) {
			if($c !== $c2[$i]) {
				return false;
			}
		}

		return true;
	}
}
