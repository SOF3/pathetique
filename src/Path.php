<?php

declare(strict_types = 1);

namespace SOFe\Pathetique;

use InvalidArgumentException;
use JsonSerializable;
use Serializable;
use function deserialize;
use function file_exists;
use function fileinode;
use function is_dir;
use function is_file;
use function realpath;
use function serialize;

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

	private function __construct(string $path, Platform $platform) {
		if($path === "") {
			throw new InvalidArgumentException("Empty path is nonsensical");
		}

		$this->path = $path;
		$this->platform = $platform;
		$this->init();
	}

	private function init() : void {
		// TODO implement
	}

	/**
	 * Converts a string into a Path.
	 *
	 * This assumes the path is a path on the current platform.
	 *
	 * @throws InvalidArgumentException if `$path` is empty.
	 * @throws InvalidArgumentException if `$path` is an invalid path on the current platform.
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
	public static function forPlatform(string $path, Platform $platform) {
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

	/**
	 * Displays the path using only printable characters (32 to 127),
	 * replacing any non-printable bytes with `?`.
	 */
	public function displayAscii() : string {
		// TODO unimplemented
	}

	/**
	 * Displays the path using only UTF-8 printable characters,
	 * replacing any non-printable bytes with `?`.
	 */
	public function displayUtf8() : string {
		// TODO unimplemented
	}


	public function serialize() : string {
		return serialize([
			"string" => $this->string,
			"platform" => $this->platform->isWindows(),
		]);
	}

	public function deserialize(string $ser) : void {
		$de = deserialize($ser);
		$this->string = $de["string"];
		$this->platform = $de["platform"] ? Platform::windows() : Platform::unix();
		$this->init();
	}

	public function jsonSerialize() : array {
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
	 */
	public function toCurrentPlatform() : ?Path {
		// TODO unimplemented
	}


	////////////
	// PREFIX //
	////////////

	/**
	 * Returns whether this path is relative
	 */
	public function isAbsolute() : bool {
		// TODO unimplemented
	}

	/**
	 * Returns whether this path is relative
	 */
	public function isRelative() : bool {
		// TODO unimplemented
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
	 * This method ignores any trailing directory separator.
	 */
	public function getFileName() : string {
		// TODO unimplemented
	}

	/**
	 * Returns the *lexical* base name of this path.
	 * Returns `null` if the last component is `.` or `..`.
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
		// TODO unimplemented
	}

	/**
	 * Returns the *lexical* base name of this path,
	 * or `null` if none is available.
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
		// TODO unimplemented
	}

	/**
	 * Returns a clone of this path with the file extension set as `$extension`,
	 * or without an extension if `$extension` is null.
	 *
	 * @throws InvalidArgumentException if `$this` ends with `.` or `..`
	 * @throws InvalidArgumentException if `$extension` contains invalid characters for a path.
	 */
	public function withExtension(?string $extension) : self {
		// TODO unimplemented
	}

	/**
	 * Returns an iterator for the lexical components in this Path.
	 *
	 * To get the *real* ancestors of this path on the filesystem,
	 * use `$path->toCanonical()->getComponents()`.
	 *
	 * @see Component
	 */
	public function getComponents() : iterable {
		// TODO unimplemented
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
	 * If `$other` is absolute, `$other` is returned.
	 * Otherwise, this method treats `$this` as a directory,
	 * and resolves `$path` as a relative path from the `$this` directory.
	 *
	 * This method is same as `join` but always appends `/` to `$this`.
	 */
	public function join(Path $other) : Path {
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
}
