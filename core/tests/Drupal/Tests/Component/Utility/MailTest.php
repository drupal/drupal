<?php

namespace Drupal\Tests\Component\Utility;

use Drupal\Component\Utility\Mail;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;

/**
 * Test mail helpers implemented in Mail component.
 *
 * @group Utility
 * @group legacy
 *
 * @coversDefaultClass \Drupal\Component\Utility\Mail
 */
class MailTest extends TestCase {
  use ExpectDeprecationTrait;

  /**
   * Tests RFC-2822 'display-name' formatter.
   *
   * @dataProvider providerTestDisplayName
   * @covers ::formatDisplayName
   */
  public function testFormatDisplayName($string, $safe_display_name) {
    $this->expectDeprecation('\Drupal\Component\Utility\Unicode::mimeHeaderEncode() is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. Use \Symfony\Component\Mime\Header\UnstructuredHeader instead. See https://www.drupal.org/node/3207439');
    $this->assertEquals($safe_display_name, Mail::formatDisplayName($string));
  }

  /**
   * Data provider for testFormatDisplayName().
   *
   * @see testFormatDisplayName()
   *
   * @return array
   *   An array containing a string and its 'display-name' safe value.
   */
  public function providerTestDisplayName() {
    return [
      // Simple ASCII characters.
      ['Test site', 'Test site'],
      // ASCII with html entity.
      ['Test &amp; site', 'Test & site'],
      // Non-ASCII characters.
      ['Tést site', '=?UTF-8?B?VMOpc3Qgc2l0ZQ==?='],
      // Non-ASCII with special characters.
      ['Tést; site', '=?UTF-8?B?VMOpc3Q7IHNpdGU=?='],
      // Non-ASCII with html entity.
      ['T&eacute;st; site', '=?UTF-8?B?VMOpc3Q7IHNpdGU=?='],
      // ASCII with special characters.
      ['Test; site', '"Test; site"'],
      // ASCII with special characters as html entity.
      ['Test &lt; site', '"Test < site"'],
      // ASCII with special characters and '\'.
      ['Test; \ "site"', '"Test; \\\\ \"site\""'],
      // String already RFC-2822 compliant.
      ['"Test; site"', '"Test; site"'],
      // String already RFC-2822 compliant.
      ['"Test; \\\\ \"site\""', '"Test; \\\\ \"site\""'],
    ];
  }

}
