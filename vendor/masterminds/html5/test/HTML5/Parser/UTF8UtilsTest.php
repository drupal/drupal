<?php

namespace Masterminds\HTML5\Tests\Parser;

use Masterminds\HTML5\Parser\UTF8Utils;

class UTF8UtilsTest extends \Masterminds\HTML5\Tests\TestCase
{
	public function testConvertToUTF8() {
		$out = UTF8Utils::convertToUTF8('éàa', 'ISO-8859-1');
		$this->assertEquals('Ã©Ã a', $out);
	}

	/**
	 * @todo add tests for invalid codepoints
	 */
	public function testCheckForIllegalCodepoints() {
		$smoke = "Smoke test";
		$err = UTF8Utils::checkForIllegalCodepoints($smoke);
		$this->assertEmpty($err);

		$data = "Foo Bar \0 Baz";
		$errors = UTF8Utils::checkForIllegalCodepoints($data);
		$this->assertContains('null-character', $errors);
	}
}