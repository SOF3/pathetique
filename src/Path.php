<?php

declare(strict_types = 1);

namespace SOFe\Pathetique;

use Directory;
use Generator;
use InvalidArgumentException;
use JsonSerializable;
use Serializable;
use function assert;
use function copy;
use function error_get_last;
use function file_exists;
use function fileinode;
use function implode;
use function is_dir;
use function is_file;
use function is_link;
use function mkdir;
use function readlink;
use function realpath;
use function serialize;
use function strlen;
use function strrpos;
use function symlink;
use function unserialize;

/**
 * Represents a path on a particular filesystem.
 *
 * This class may become abstract in future versions.
 * Do not rely on the finalness of this class.
 *
 * # Immutability
 * All methods in this class do not modify the path value.
 *
 * # Platform behaviour
 * The behaviour of this class is platform-dependent.
 * For example, `Path::new("C:\\")` is treated as an absolute path on Windows,
 * but it is treated as a relative path on Unix.
 *
 * To construct this path for a specific platform,
 * use `Path::forPlatform($path, $platform)`.
 *
 * @see Platform
 */
final class Path implements JsonSerializable, Serializable {
	/** @var string */
	private $string;
	/** @var Platform */
	private $platform;

	/** @var bool */
	private $path;

	/** @var Generator<int, null>|null */
	private $parser;

	/** @var bool */
	private $prefixParsed = false;
	/** @var Prefix|null */
	private $prefix = null;

	/** @var Component[] */
	private $components = [];

	private function __construct(string $path, Platform $platform) {
		$this->string = $path;
		$this->platform = $platform;
		$this->init();
	}

	private function init() : void {
		if($this->string === "") {
			throw new InvalidArgumentException("Empty path is nonsensical");
		}

		$this->parser = $this->parseComponents();
	}

	/**
	 * Converts a string into a Path.
	 *
	 * This assumes the path is a path on the current platform.
	 *
	 * @throws InvalidArgumentException if `$path` is empty.
	 */
	public static function new(string $path) : self {
		return new self($path, Platform::current());
	}

	/**
	 * Converts a string into a Path on a specific platform.
	 *
	 * @throws InvalidArgumentException if `$path` is empty.
	 * @throws InvalidArgumentException if `$path` is an invalid path on `$platform`.
	 */
	public static function forPlatform(string $path, Platform $platform) : self {
		return new self($path, $platform);
	}


	/**
	 * Converts this path to an OS-compatible string.
	 *
	 * If the path platform matches the current runtime platform,
	 * this function can also be used in PHP filesystem functions.
	 *
	 * Consider calling `canonicalize()` first.
	 */
	public function toString() : string {
		return $this->string;
	}

	public function __toString() : string {
		return $this->string;
	}

	public function toNormalizedString() : string {
		$comps = [];
		foreach($this->getComponents() as $component) {
			$string = $component->toNormalizedString();
			if($string !== null) {
				$comps[] = $string;
			}
		}
		return implode($this->platform->getDirectorySeparator(), $comps);
	}

	/**
	 * Displays the path using only printable characters (32 to 127),
	 * replacing any non-printable bytes with `?`.
	 */
	public function displayAscii() : string {
		$string = $this->string;
		for($i = 0; $i < strlen($string); $i++) {
			$ord = ord($string[$i]);
			if($ord < 32 || $ord >= 127) { // ^? is also nonprintable
				$string[$i] = "?";
			}
		}
		return $string;
	}

	/**
	 * Displays the path using only UTF-8 printable characters,
	 * replacing any non-printable bytes with `?`.
	 */
	public function displayUtf8() : string {
		$string = $this->string;

		for($i = 0; $i < strlen($string); $i++) {
			$ord = ord($string[$i]);
			if(32 <= $ord && $ord <= 0x7E) { // ^? is in C0
				continue;
			}
			if(($ord & 0xE0) === 0xC0) {
				if(strlen($string) > $i + 1) {
					$ord2 = ord($string[$i + 1]);
					if(($ord2 & 0xC0) === 0x80) {
						// control characters: U+0080 to U+009F
						if($ord === 0xC2 && (0x80 <= $ord2 && $ord2 <= 0x9F)) {
							$string[$i] = "?";
							$string[$i + 1] = "?";
						}
						$i++;
						continue;
					}
				}
			}
			if(($ord & 0xF0) === 0xE0) {
				if(strlen($string) > $i + 2) {
					$ord2 = ord($string[$i + 1]);
					$ord3 = ord($string[$i + 2]);
					if(($ord2 & 0xC0) === 0x80 && ($ord3 & 0xC0) === 0x80) {
						$i += 2;
						continue;
					}
				}
			}
			if(($ord & 0xF8) === 0xF8) {
				if(strlen($string) > $i + 3) {
					$ord2 = ord($string[$i + 1]);
					$ord3 = ord($string[$i + 2]);
					$ord4 = ord($string[$i + 3]);
					if(($ord2 & 0xC0) === 0x80 && ($ord3 & 0xC0) === 0x80 && ($ord4 & 0xC0) === 0x80) {
						$i += 3;
						continue;
					}
				}
			}

			$string[$i] = "?";
		}

		return $string;
	}


	public function serialize() : string {
		return serialize([
			"string" => $this->string,
			"platform" => $this->platform->isWindows(),
		]);
	}

	public function unserialize($ser) : void {
		$de = unserialize($ser);
		$this->string = $de["string"];
		$this->platform = $de["platform"] ? Platform::windows() : Platform::unix();
		$this->init();
	}

	/**
	 * @return mixed
	 */
	public function jsonSerialize() {
		return $this->string;
	}


	//////////////
	// PLATFORM //
	//////////////

	/**
	 * Gets the Platform used by this path.
	 */
	public function getPlatform() : Platform {
		return $this->platform;
	}

	/**
	 * Tries to convert this path for the current platform,
	 * returning `null` if conversion is not possible.
	 *
	 * Conversion is not possible if:
	 * - the path is an absolute path
	 *   (this would not even make sense for two machines of the same platform)
	 * - the path contains illegal characters on the current platform
	 *   (e.g. converting filenames with `\` to a Windows platform)
	 *
	 * All Windows relative paths can be converted to Unix platform.
	 */
	public function toCurrentPlatform() : ?Path {
		// TODO unimplemented
	}


	////////////
	// PREFIX //
	////////////

	public function getPrefix() : ?Prefix {
		if(!$this->prefixParsed) {
			foreach($this->getComponents() as $component) {
				break; // only one iteration is enough
			}
		}

		assert($this->prefixParsed);
		return $this->prefix;
	}

	/**
	 * Returns whether this path is relative
	 */
	public function isAbsolute() : bool {
		return $this->getPrefix() !== null;
	}

	/**
	 * Returns whether this path is relative
	 */
	public function isRelative() : bool {
		return $this->getPrefix() === null;
	}

	public function hasTrailingSeparator() : bool {
		return $this->platform->isDirectorySeparator($this->string[strlen($this->string) - 1]);
	}


	///////////////
	// COMPONENT //
	///////////////

	// All methods in this section are lexical operations that may not work as expected.
	// Documentation should attach warning about `.`, `..` and symlink resolution.

	/**
	 * Returns the *lexical* parent of this path.
	 *
	 * This method is not generally useful,
	 * because it is NOT `..` or symlink sensitive.
	 * It simply removes the last path component.
	 *
	 * To get the *real* parent of this path on the filesystem,
	 * use `$path->join("..")`.
	 * Further use `canonicalize()` to produce a "clean" canonical path.
	 *
	 * If there is no lexical parent (i.e. there is only one lexical component),
	 * this method returns `null`.
	 *
	 * This method ignores any trailing directory separator.
	 *
	 * # Examples
	 * - `Path::new(".")->getLexicalParent()->toString()`: `null`
	 * - `Path::new("..")->getLexicalParent()->toString()`: `null`
	 * - `Path::new("index.html")->getLexicalParent()->toString()`: `null`
	 * - On Windows: `Path::new("C:\\")->getLexicalParent()`: `null`
	 * - On Linux: `Path::new("/")->getLexicalParent()`: `null`
	 */
	public function getLexicalParent() : ?Path {
		// TODO unimplemented
	}

	/**
	 * Extracts the *lexical* file name from the path.
	 *
	 * Returns "." and ".." for trailing current-directory and parent-directory components.
	 *
	 * Returns null if the path only contains a prefix.
	 *
	 * Trailng directory separators are automatically removed.
	 */
	public function getFileName() : ?string {
		$last = null;
		foreach($this->getComponents() as $component) {
			if(!($component instanceof TrailingSeparatorComponent) && !($component instanceof PrefixComponent)) {
				$last = $component;
			}
		}
		return $last !== null ? $last->toString() : null;
	}

	/**
	 * Returns the *lexical* base name of this path.
	 * Returns `null` if the last component is a prefix, `.` or `..`.
	 *
	 * Trailng directory separators are automatically removed.
	 *
	 * Note that there is no universal standard on the precise definition of "base name" and "extension".
	 * This function strives to provide a stable but perhaps nonstandard algorithm of extension detection.
	 *
	 * # Examples
	 * - `Path::new(".")->getBaseName()`: `null`
	 * - `Path::new("..")->getBaseName()`: `null`
	 * - `Path::new(".gitignore")->getBaseName()`: `".gitignore"`
	 * - `Path::new("index.d.ts")->getBaseName()`: `"index.d"`
	 */
	public function getBaseName() : ?string {
		$name = $this->getFileName();
		if($name === null || $name === "." || $name === "..") {
			return null;
		}

		$pos = strrpos($name, ".");
		if($pos === 0 || $pos === false) {
			return $name;
		} else {
			return substr($name, 0, $pos);
		}
	}

	/**
	 * Returns the *lexical* base name of this path,
	 * or `null` if none is available.
	 *
	 * This method ignores any trailing directory separator.
	 *
	 * Note that there is no universal standard on the precise definition of "base name" and "extension".
	 * This function strives to provide a stable but perhaps nonstandard algorithm of extension detection.
	 *
	 * # Examples
	 * - `Path::new(".")->getExtension()`: `null`
	 * - `Path::new("..")->getExtension()`: `null`
	 * - `Path::new(".gitignore")->getExtension()`: `""`
	 * - `Path::new("index.d.ts")->getExtension()`: `"ts"`
	 */
	public function getExtension() : ?string {
		$name = $this->getFileName();
		if($name === null || $name === "." || $name === "..") {
			return null;
		}

		$pos = strrpos($name, ".");
		if($pos === 0 || $pos === false) {
			return null;
		} else {
			return substr($name, $pos + 1);
		}
	}

	/**
	 * Returns a clone of this path with the file extension set as `$extension`,
	 * or without an extension if `$extension` is null.
	 *
	 * @throws InvalidArgumentException if `$this` only has a prefix or ends with `.` or `..`
	 * @throws InvalidArgumentException if `$extension` contains invalid characters for a path.
	 */
	public function withExtension(?string $extension) : self {
		$name = $this->getBaseName();
		if($name === null) {
			throw new InvalidArgumentException("Cannot use withExtension for paths ending with prefix, . or ..");
		}

		if($extension != null) {
			$name = "$name.$extension";
		}

		$parent = $this->getLexicalParent();
		return $parent !== null ? $parent->join($name) : Path::new($name);
	}

	/**
	 * Parses components from the path and pushes results to $this->components.
	 *
	 * The generator yields void when it adds a new value to $this->components.
	 * If no more components can be found, it sets $this->parser to null
	 * and returns void.
	 *
	 * @phpstan-return Generator<int, null, void, void>
	 */
	private function parseComponents() : Generator {
		if($this->platform->isWindows()) {
			$this->prefix = Utils::parseWindowsPrefix($this->string, $index);
		} else {
			if($this->string[0] === "/") {
				$this->prefix = new UnixPrefix;
				$index = 1;
			} else {
				$this->prefix = null;
				$index = 0;
			}
		}
		$this->prefixParsed = true;
		if($this->prefix !== null) {
			$this->components[] = new PrefixComponent($this->prefix);
			yield;
			$isVerbatim = $this->prefix->isVerbatim();
			assert(
				strlen($this->string) === $index - 1 ||
				$this->platform->isDirectorySeparator($this->string[$index - 1])
			);
		} else {
			$isVerbatim = false;
		}

		$currentComponent = "";
		while($index < strlen($this->string)) {
			if($this->platform->isDirectorySeparator($this->string[$index], $isVerbatim)) {
				$this->platform->validateComponent($currentComponent, $this->prefix !== null && $this->prefix->isVerbatim());
				$comp = Utils::parseComponent($currentComponent, $isVerbatim, false);
				if($comp !== null) {
					$this->components[] = $comp;
					yield;
				}
			} else {
				$currentComponent .= $this->string[$index];
			}
		}

		$comp = Utils::parseComponent($currentComponent, $isVerbatim, true);
		if($comp !== null) {
			$this->components[] = $comp;
		}
		yield;

		$this->parser = null;
	}

	/**
	 * Returns an iterator for the lexical components in this Path.
	 *
	 * To get the *real* ancestors of this path on the filesystem,
	 * use `$path->toCanonical()->getComponents()`.
	 *
	 * @phpstan-return iterable<Component>
	 * @throws InvalidArgumentException this exception is actually thrown during iteration
	 *
	 * @see Component
	 */
	public function getComponents() : iterable {
		$i = 0;
		while(true) {
			if(!isset($this->components[$i])) {
				if($this->parser !== null) {
					$this->parser->next();
				}
				if(!isset($this->components[$i])) {
					break;
				}
			}
			yield $this->components[$i++];
		}
	}


	/**
	 * Validates this path.
	 *
	 * This function call is unnecssary if other component-parsing functions are called.
	 * The implementation is equivalent to exhausting `getComponents()` once.
	 *
	 * @throws InvalidArgumentException
	 */
	public function validate() : void {
		foreach($this->getComponents() as $_) {
		}
	}

	/**
	 * Checks whether the path starts with the same *lexical* components as `$base`.
	 *
	 * To check whether this path is really under `$base`,
	 * call `canonicalize()` first.
	 */
	public function startsWith(Path $base) : bool {
		// TODO unimplemented
	}

	/**
	 * Checks whether the path ends with the same *lexical* components as `$child`.
	 *
	 * To check whether this path really contains `$base`,
	 * call `canonicalize()` first.
	 */
	public function endsWith(Path $base) : bool {
		// TODO unimplemented
	}

	/**
	 * Lexically appends `$other` to `$this`.
	 *
	 * This is an alias for `$this->joinPath($other)`.
	 */
	public function join(string $other) : Path {
		return $this->joinPath(Path::forPlatform($other, $this->platform));
	}

	/**
	 * Lexically appends `$other` to `$this`.
	 *
	 * If `$other` is absolute, `$other` is returned.
	 * Otherwise, this method treats `$this` as a directory,
	 * and resolves `$path` as a relative path from the `$this` directory.
	 *
	 * This method is same as `resolve` but always appends `/` to `$this`.
	 */
	public function joinPath(Path $other) : Path {
		// TODO unimplemented
	}

	/**
	 * Lexically resolves `$other` in the context of `$this`.
	 *
	 * This method is same as `join` for paths ending with `/`.
	 *
	 * The behaviour of this method is similar to resolving paths in HTML.
	 * In particular,
	 * - if `$other` is absolute, `$other` is returned.
	 * - `Path::new("a/b/")->resolve(Path::new("c"))` returns `Path::new("a/b/c")`.
	 * - `Path::new("a/b")->resolve(Path::new("c"))` returns `Path::new("a/c")`.
	 */
	public function resolve(Path $other) : Path {
		// TODO unimplemented
	}


	////////////////
	// FILESYSTEM //
	////////////////

	// All methods in this section must call `$this->platform->check()`,
	// and copy this line in documentation:
	// * @throws PlatformMismatchException if this path is not compatible with the current platform.

	/**
	 * Returns the canonicalized version of this path.
	 *
	 * This method *may or may not* return $this if the path is already canonicalized.
	 *
	 * The return value never has a trailing directory separator unless it only consists of the prefix.
	 *
	 * @throws IOException if an IO error occurred during canonicalization.
	 * @throws PlatformMismatchException if this path is not compatible with the current platform.
	 */
	public function canonicalize() : self {
		$this->platform->check();
		$path = Utils::throwIo(@realpath($this->toString()));
		return self::new(realpath($this->toString()));
	}

	/**
	 * Returns the canonicalized version of this path,
	 * or return $this if not possible.
	 *
	 * This is an error-free version of `canonicalize`.
	 */
	public function tryCanonicalize() : self {
		try {
			return $this->canonicalize();
		} catch(IOException $e) {
			return $this;
		}
	}

	/**
	 * Checks whether two files represent the same canonical paths.
	 *
	 * This returns `false` if any of the two paths belong to another platform
	 * or refer to nonexistent files.
	 * For example, for `$path = Path::new("no such file")`, `$path->equals($path)` returns false.
	 * Therefore, this method is neither reflexive nor consistent.
	 *
	 * This method does not whether the two files are *physically* identical.
	 * On Unix platforms, to check whether they are hard links to the same file,
	 * use `$path->getFileId()`.
	 * To check whether they are files mounted on different filesystems (hence different inodes),
	 * refer to [this StackOverflow answer](https://stackoverflow.com/a/31097497/3990767).
	 *
	 * @throws IOException if an IO error occurred while interacting with the filesystem.
	 * @throws PlatformMismatchException if this path is not compatible with the current platform.
	 */
	public function isCanonicallyEqual(Path $other) : bool {
		if(!$this->exists() || !$other->exists()) { // platform checked
			return false;
		}

		return $this->canonicalize()->toString() === $other->canonicalize()->toString();
	}

	/**
	 * Constructs the shortest relative path from the parent directory of $this to $dest if they share some common ancestor,
	 * otherwise returns an absolute path.
	 *
	 * This method canonicalizes both objects, and is symlink-safe.
	 *
	 * This method is the reverse of `resolve`.
	 * In other words, if `$result = $src->findPath($dest)`,
	 * then  `$src->resolve($result)` represents the same file as `$dest`.
	 *
	 * @throws IOException if an IO error occurred during canonicalization.
	 * @throws PlatformMismatchException if this path is not compatible with the current platform.
	 */
	public function findPath(Path $dest) : self {
		// TODO unimplemented
		// NOTE Beware the difference between "foo/" and "foo" in the implementation.
	}

	/**
	 * Retrieves the file inode. This correpsonds to the `fileinode` PHP function.
	 *
	 * @throws IOException if an IO error occurred during canonicalization.
	 * @throws PlatformMismatchException if this path is not compatible with the current platform.
	 */
	public function getFileId() : int {
		$this->platform->check();
		return Utils::throwIo(@fileinode($this->toString()));
	}


	/**
	 * Checks whether the path refers to an existent file.
	 *
	 * If the path refers to an eventually-dead symlink,
	 * this function also returns false.
	 *
	 * This is an alias to the PHP `file_exists` function.
	 *
	 * @throws PlatformMismatchException if this path is not compatible with the current platform.
	 */
	public function exists() : bool {
		$this->platform->check();
		return file_exists($this->toString());
	}

	/**
	 * Checks whether the path refers to an existent regular file.
	 *
	 * This function traverses symlinks.
	 *
	 * This is an alias to the PHP `is_file` function.
	 *
	 * @throws PlatformMismatchException if this path is not compatible with the current platform.
	 */
	public function isFile() : bool {
		$this->platform->check();
		return is_file($this->toString());
	}

	/**
	 * Checks whether the path refers to an existent file.
	 *
	 * This function traverses symlinks.
	 *
	 * This is an alias to the PHP `is_dir` function.
	 *
	 * @throws PlatformMismatchException if this path is not compatible with the current platform.
	 */
	public function isDir() : bool {
		$this->platform->check();
		return is_dir($this->toString());
	}

	/**
	 * Checks whether the path refers to a symbolic link.
	 *
	 * This function returns true even if the symbolic link links to a nonexistent file.
	 *
	 * This is an alias to the PHP `is_link` function.
	 *
	 * @throws PlatformMismatchException if this path is not compatible with the current platform.
	 */
	public function isLink() : bool {
		$this->platform->check();
		return is_link($this->toString());
	}

	/**
	 * Creates a directory at the path, optionally creating parent directories.
	 *
	 * This method does NOT throw an exception if the directory already exists.
	 *
	 * @throws IOException if an IO error occurred while creating the directory and the directory does not exist
	 * @throws PlatformMismatchException if this path is not compatible with the current platform.
	 */
	public function mkdir(bool $recursive = false, int $mode = 0777) : void {
		$this->platform->check();

		// Source: https://stackoverflow.com/a/57939677/3990767
		$path = $this->toString();
		if(!is_dir($path) && !mkdir($path) && !is_dir($path)) {
			$error = error_get_last();
			$message = "Failed to create directory";
			if(isset($error["message"])) {
				$message .= ": {$error["message"]}";
			}
			throw new IOException($message);
		}
	}

	/**
	 * Scans the contents of this directory.
	 *
	 * WARNING: The returned value automatically closes the readdir handle when it is garbage-collected.
	 * Users are however still strongly advised to manually call the close() method
	 * if the iterator is not fully traversed.
	 *
	 * This does not visit subdirectories.
	 * Use `scanRecursively()` for a pre-order traversal of recursive contents.
	 *
	 * Due to PHP platform restrictions, it is not possible to distinguish between
	 * end of a directory scan and an error during directory scan
	 * (this is a TODO fix).
	 * Thus, the returned iterator does not throw exceptions during iteration.
	 *
	 * @return DirectoryGuard
	 *
	 * @throws IOException if an IO error occurred while opening the directory.
	 * @throws PlatformMismatchException if this path is not compatible with the current platform.
	 */
	public function scan() : DirectoryGuard {
		$this->platform->check();
		return new DirectoryGuard($this);
	}

	/**
	 * Scans the contents of this directory *recursively* in preorder traversal.
	 *
	 * WARNING: The returned generator automatically closes the readdir handle
	 * when its underlying DirectoryGuard garbage-collected.
	 * However if the generator is not fully traversed,
	 * users are still strongly advised to call `send(true)` on the generator,
	 * which will abort the generator.
	 *
	 * The generator returns a bool, which indicates whether `send(true)` was called on the generator.
	 *
	 * Symlinks are skipped by default.
	 * Pass `$withSymlinks` as `true` to yield symlinks.
	 *
	 * No matter `$withSymlinks` is true or not, files under symlinked directories will not be visited.
	 *
	 * WARNING: Do not use `iterator_to_array` with this generator,
	 * because yielded key values are always `null` and would overwrite the array contents.
	 *
	 * @return Generator
	 * @phpstan-return Generator<null, Path, true|null, bool>
	 * @throws IOException an IOException is thrown with the same conditions as in `Path::scan()`, but only during traversal.
	 * @throws PlatformMismatchException if this path is not compatible with the current platform.
	 */
	public function scanRecursively(bool $withSymlinks = false) : Generator {
		$scan = $this->scan();
		foreach($scan as $path) {
			if($path->isLink()) {
				if($withSymlinks) {
					$break = yield null => $path;
					if($break === true) {
						$scan->close();
						return true;
					}
				}
				continue;
			}

			$break = yield null => $path;
			if($break === true) {
				$scan->close();
				return true;
			}

			if($path->isDir()) {
				$break = yield from $path->scanRecursively($withSymlinks);
				if($break === true) {
					$scan->close();
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Copies $this to $dest in the intuitive manner.
	 *
	 * This is eqiuvalent to the PHP `copy` function if `$this->isFile()`.
	 *
	 * If `$this->isLink()`, this will create a symlink with the same destination
	 * (no matter it exists or not).
	 *
	 * If `$this->isDirectory()`, this will copy the directory recursively.
	 *
	 * If none of the above is true, a `RuntimeException` is thrown as other file types are unsupported.
	 *
	 * @throws IOException if an IO error occurred.
	 * @throws PlatformMismatchException if this path is not compatible with the current platform.
	 */
	public function copy(Path $dest) : void {
		if($this->isLink()) {
			$contents = Utils::throwIo(@readlink($this->toString()));
			/** @var string $contents */
			Utils::throwIo(@symlink($contents, $dest->toString()));
			return;
		}

		if($this->isFile()) {
			Utils::throwIo(@copy($this->toString(), $dest->toString()));
			return;
		}

		if($this->isDir()) {
			$scan = $this->scan();
			$dest->mkdir();
			try {
				foreach($scan as $name => $path) {
					$path->copy($dest->join($name));
				}
			} finally {
				$scan->close();
			}
			return;
		}

		throw new RuntimeException("Unsupported file type at {$this->displayUtf8()}");
	}

	/**
	 * Deletes $this recursively.
	 *
	 * This method does NOT follow symbolic links.
	 * Use `canonicalize()` if symlink traversal is desired.
	 * If `$this->isLink()`, only the symbolic link itself is deleted.
	 *
	 * @throws IOException if an IO error occurred.
	 * @throws PlatformMismatchException if this path is not compatible with the current platform.
	 */
	public function delete() : void {
		if($this->isDir()) {
			$scan = $this->scan();
			try {
				foreach($scan as $path) {
					$path->delete();
				}
			} finally {
				$scan->close();
			}
		} else {
			Utils::throwIo(@unlink($this->toString()));
		}
	}
}
