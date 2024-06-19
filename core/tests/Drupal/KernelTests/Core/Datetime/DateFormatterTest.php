<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Datetime;

use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;

// cspell:ignore marzo

/**
 * Tests date formatting.
 *
 * @group Common
 * @coversDefaultClass \Drupal\Core\Datetime\DateFormatter
 */
class DateFormatterTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['language', 'system'];

  /**
   * Arbitrary langcode for a custom language.
   */
  const LANGCODE = 'xx';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['system']);

    $this->setSetting('locale_custom_strings_' . self::LANGCODE, [
      '' => ['Sunday' => 'domingo'],
      'Long month name' => ['March' => 'marzo'],
    ]);

    $formats = $this->container->get('entity_type.manager')
      ->getStorage('date_format')
      ->loadMultiple(['long', 'medium', 'short']);
    $formats['long']->setPattern('l, j. F Y - G:i')->save();
    $formats['medium']->setPattern('j. F Y - G:i')->save();
    $formats['short']->setPattern('Y M j - g:ia')->save();

    ConfigurableLanguage::createFromLangcode(static::LANGCODE)->save();
  }

  /**
   * Tests DateFormatter::format().
   *
   * @covers ::format
   */
  public function testFormat(): void {
    /** @var \Drupal\Core\Datetime\DateFormatterInterface $formatter */
    $formatter = $this->container->get('date.formatter');
    /** @var \Drupal\Core\Language\LanguageManagerInterface $language_manager */
    $language_manager = $this->container->get('language_manager');

    $timestamp = strtotime('2007-03-26T00:00:00+00:00');
    $this->assertSame('Sunday, 25-Mar-07 17:00:00 PDT', $formatter->format($timestamp, 'custom', 'l, d-M-y H:i:s T', 'America/Los_Angeles', 'en'), 'Test all parameters.');
    $this->assertSame('domingo, 25-Mar-07 17:00:00 PDT', $formatter->format($timestamp, 'custom', 'l, d-M-y H:i:s T', 'America/Los_Angeles', self::LANGCODE), 'Test translated format.');
    $this->assertSame('l, 25-Mar-07 17:00:00 PDT', $formatter->format($timestamp, 'custom', '\\l, d-M-y H:i:s T', 'America/Los_Angeles', self::LANGCODE), 'Test an escaped format string.');
    $this->assertSame('\\domingo, 25-Mar-07 17:00:00 PDT', $formatter->format($timestamp, 'custom', '\\\\l, d-M-y H:i:s T', 'America/Los_Angeles', self::LANGCODE), 'Test format containing backslash character.');
    $this->assertSame('\\l, 25-Mar-07 17:00:00 PDT', $formatter->format($timestamp, 'custom', '\\\\\\l, d-M-y H:i:s T', 'America/Los_Angeles', self::LANGCODE), 'Test format containing backslash followed by escaped format string.');
    $this->assertSame('Monday, 26-Mar-07 01:00:00 BST', $formatter->format($timestamp, 'custom', 'l, d-M-y H:i:s T', 'Europe/London', 'en'), 'Test a different time zone.');
    $this->assertSame('Thu, 01/01/1970 - 00:00', $formatter->format(0, 'custom', '', 'UTC', 'en'), 'Test custom format with empty string.');

    // Make sure we didn't change the configuration override language.
    $this->assertSame('en', $language_manager->getConfigOverrideLanguage()->getId(), 'Configuration override language not disturbed,');

    // Test bad format string will use the fallback format.
    $this->assertSame($formatter->format($timestamp, 'fallback'), $formatter->format($timestamp, 'bad_format_string'), 'Test fallback format.');
    $this->assertSame('en', $language_manager->getConfigOverrideLanguage()->getId(), 'Configuration override language not disturbed,');

    // Change the default language and timezone.
    $this->config('system.site')->set('default_langcode', static::LANGCODE)->save();
    date_default_timezone_set('America/Los_Angeles');

    // Reset the language manager so new negotiations attempts will fall back on
    // on the new language.
    $language_manager->reset();
    $this->assertSame('en', $language_manager->getConfigOverrideLanguage()->getId(), 'Configuration override language not disturbed,');

    $this->assertSame('Sunday, 25-Mar-07 17:00:00 PDT', $formatter->format($timestamp, 'custom', 'l, d-M-y H:i:s T', 'America/Los_Angeles', 'en'), 'Test a different language.');
    $this->assertSame('Monday, 26-Mar-07 01:00:00 BST', $formatter->format($timestamp, 'custom', 'l, d-M-y H:i:s T', 'Europe/London'), 'Test a different time zone.');
    $this->assertSame('domingo, 25-Mar-07 17:00:00 PDT', $formatter->format($timestamp, 'custom', 'l, d-M-y H:i:s T'), 'Test custom date format.');
    $this->assertSame('domingo, 25. marzo 2007 - 17:00', $formatter->format($timestamp, 'long'), 'Test long date format.');
    $this->assertSame('25. marzo 2007 - 17:00', $formatter->format($timestamp, 'medium'), 'Test medium date format.');
    $this->assertSame('2007 Mar 25 - 5:00pm', $formatter->format($timestamp, 'short'), 'Test short date format.');
    $this->assertSame('25. marzo 2007 - 17:00', $formatter->format($timestamp), 'Test default date format.');
    // Test HTML time element formats.
    $this->assertSame('2007-03-25T17:00:00-0700', $formatter->format($timestamp, 'html_datetime'), 'Test html_datetime date format.');
    $this->assertSame('2007-03-25', $formatter->format($timestamp, 'html_date'), 'Test html_date date format.');
    $this->assertSame('17:00:00', $formatter->format($timestamp, 'html_time'), 'Test html_time date format.');
    $this->assertSame('03-25', $formatter->format($timestamp, 'html_yearless_date'), 'Test html_yearless_date date format.');
    $this->assertSame('2007-W12', $formatter->format($timestamp, 'html_week'), 'Test html_week date format.');
    $this->assertSame('2007-03', $formatter->format($timestamp, 'html_month'), 'Test html_month date format.');
    $this->assertSame('2007', $formatter->format($timestamp, 'html_year'), 'Test html_year date format.');

    // Make sure we didn't change the configuration override language.
    $this->assertSame('en', $language_manager->getConfigOverrideLanguage()->getId(), 'Configuration override language not disturbed,');

    // Test bad format string will use the fallback format.
    $this->assertSame($formatter->format($timestamp, 'fallback'), $formatter->format($timestamp, 'bad_format_string'), 'Test fallback format.');
    $this->assertSame('en', $language_manager->getConfigOverrideLanguage()->getId(), 'Configuration override language not disturbed,');

    // HTML is not escaped by the date formatter, it must be escaped later.
    $this->assertSame("<script>alert('2007');</script>", $formatter->format($timestamp, 'custom', '\<\s\c\r\i\p\t\>\a\l\e\r\t\(\'Y\'\)\;\<\/\s\c\r\i\p\t\>'), 'Script tags not removed from dates.');
    $this->assertSame('<em>2007</em>', $formatter->format($timestamp, 'custom', '\<\e\m\>Y\<\/\e\m\>'), 'Em tags are not removed from dates.');
  }

  /**
   * Tests that an RFC2822 formatted date always returns an English string.
   *
   * @see http://www.faqs.org/rfcs/rfc2822.html
   *
   * @covers ::format
   */
  public function testRfc2822DateFormat(): void {
    $days_of_week_abbr = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    foreach ($days_of_week_abbr as $day_of_week_abbr) {
      $this->setSetting('locale_custom_strings_' . self::LANGCODE, [
        'Abbreviated weekday' => [$day_of_week_abbr => $this->randomString(3)],
      ]);
    }
    /** @var \Drupal\Core\Datetime\DateFormatterInterface $formatter */
    $formatter = $this->container->get('date.formatter');

    // Check that RFC2822 format date is returned regardless of langcode.
    $this->assertEquals('Sat, 02 Feb 2019 13:30:00 +0100', $formatter->format(1549110600, 'custom', 'r', 'Europe/Berlin', static::LANGCODE));
    $this->assertEquals('Sat, 02 Feb 2019 13:30:00 +0100', $formatter->format(1549110600, 'custom', 'r', 'Europe/Berlin'));
  }

}
