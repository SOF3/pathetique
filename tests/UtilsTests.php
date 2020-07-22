<?php

declare(strict_types = 1);

namespace SOFe\Pathetique;

use PHPUnit\Framework\TestCase;
use function strlen;

final class UtilsTest extends TestCase {
	public function testStartsWith() : void {
		self::assertTrue(Utils::startsWith("mississippi", "ssi", 5));
	}

	public function testStrposMulti() : void {
		self::assertSame(4, Utils::strposMulti("cdabcd", "cd", 2));
		self::assertSame(4, Utils::strposMulti("cdabcd", "dc", 2));
	}

	public function testParseWindowsPrefix() : void {
		$prefix = Utils::parseWindowsPrefix("\\\\?\\UNC\\server\\share\\file", $index);
		self::assertSame($index, strlen("\\\\?\\UNC\\server\\share\\"));
		self::assertInstanceOf(VerbatimUncPrefix::class, $prefix);
		self::assertSame($prefix->getServer(), "server");
		self::assertSame($prefix->getShare(), "share");

		$prefix = Utils::parseWindowsPrefix("\\\\?\\UNC\\server\\share", $index);
		self::assertSame($index, strlen("\\\\?\\UNC\\server\\share"));
		self::assertInstanceOf(VerbatimUncPrefix::class, $prefix);
		self::assertSame($prefix->getServer(), "server");
		self::assertSame($prefix->getShare(), "share");

		$prefix = Utils::parseWindowsPrefix("\\\\?\\UNC\\server", $index);
		self::assertSame($index, strlen("\\\\?\\UNC\\server"));
		self::assertInstanceOf(VerbatimUncPrefix::class, $prefix);
		self::assertSame($prefix->getServer(), "server");
		self::assertSame($prefix->getShare(), "");


		$prefix = Utils::parseWindowsPrefix("\\\\?\\path\\file", $index);
		self::assertSame($index, strlen("\\\\?\\path\\"));
		self::assertInstanceOf(VerbatimPrefix::class, $prefix);
		self::assertSame($prefix->getPath(), "path");

		$prefix = Utils::parseWindowsPrefix("\\\\?\\path", $index);
		self::assertSame($index, strlen("\\\\?\\path"));
		self::assertInstanceOf(VerbatimPrefix::class, $prefix);
		self::assertSame($prefix->getPath(), "path");


		$prefix = Utils::parseWindowsPrefix("\\\\?\\c:\\bar", $index);
		self::assertSame($index, strlen("\\\\?\\c:\\"));
		self::assertInstanceOf(VerbatimDiskPrefix::class, $prefix);
		self::assertSame($prefix->getDisk(), "C"); // uppercase
		self::assertSame($prefix->toString(), "\\\\?\\c:"); // case-preserving
	}
}
