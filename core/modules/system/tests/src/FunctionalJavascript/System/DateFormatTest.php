<?php

namespace Drupal\Tests\system\FunctionalJavascript\System;

use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests that date formats UI with JavaScript enabled.
 *
 * @group system
 */
class DateFormatTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create admin user and log in admin user.
    $this->drupalLogin($this->drupalCreateUser(['administer site configuration']));
    $this->drupalPlaceBlock('local_actions_block');
  }

  /**
   * Tests XSS via date format configuration.
   */
  public function testDateFormatXss() {
    $page = $this->getSession()->getPage();
    $assert = $this->assertSession();

    $date_format = DateFormat::create([
      'id' => 'xss_short',
      'label' => 'XSS format',
      'pattern' => '\<\s\c\r\i\p\t\>\a\l\e\r\t\(\"\X\S\S\")\;\<\/\s\c\r\i\p\t\>',
    ]);
    $date_format->save();
    $this->drupalGet('admin/config/regional/date-time');
    $assert->assertEscaped('<script>alert("XSS");</script>', 'The date format was properly escaped');
    $this->drupalGet('admin/config/regional/date-time/formats/manage/xss_short');
    $assert->assertEscaped('<script>alert("XSS");</script>', 'The date format was properly escaped');

    // Add a new date format with HTML in it.
    $this->drupalGet('admin/config/regional/date-time/formats/add');
    $date_format = '& \<\e\m\>Y\<\/\e\m\>';
    $page->fillField('date_format_pattern', $date_format);
    $assert->waitForText('Displayed as');
    $assert->assertEscaped('<em>' . date("Y") . '</em>');
    $page->fillField('label', 'date_html_pattern');
    // Wait for the machine name ID to be completed.
    $assert->waitForLink('Edit');
    $page->pressButton('Add format');
    $assert->pageTextContains('Custom date format added.');
    $assert->assertEscaped('<em>' . date("Y") . '</em>');
  }

}
