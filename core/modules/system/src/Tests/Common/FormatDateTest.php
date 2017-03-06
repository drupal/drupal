<?php

namespace Drupal\system\Tests\Common;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the format_date() function.
 *
 * @group Common
 */
class FormatDateTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['language'];

  /**
   * Arbitrary langcode for a custom language.
   */
  const LANGCODE = 'xx';

  protected function setUp() {
    parent::setUp('language');

    $this->config('system.date')
      ->set('timezone.user.configurable', 1)
      ->save();
    $formats = $this->container->get('entity.manager')
      ->getStorage('date_format')
      ->loadMultiple(['long', 'medium', 'short']);
    $formats['long']->setPattern('l, j. F Y - G:i')->save();
    $formats['medium']->setPattern('j. F Y - G:i')->save();
    $formats['short']->setPattern('Y M j - g:ia')->save();
    $this->refreshVariables();

    $this->settingsSet('locale_custom_strings_' . self::LANGCODE, [
      '' => ['Sunday' => 'domingo'],
      'Long month name' => ['March' => 'marzo'],
    ]);

    ConfigurableLanguage::createFromLangcode(static::LANGCODE)->save();
    $this->resetAll();
  }

  /**
   * Tests admin-defined formats in format_date().
   */
  public function testAdminDefinedFormatDate() {
    // Create and log in an admin user.
    $this->drupalLogin($this->drupalCreateUser(['administer site configuration']));

    // Add new date format.
    $edit = [
      'id' => 'example_style',
      'label' => 'Example Style',
      'date_format_pattern' => 'j M y',
    ];
    $this->drupalPostForm('admin/config/regional/date-time/formats/add', $edit, t('Add format'));

    // Add a second date format with a different case than the first.
    $edit = [
      'id' => 'example_style_uppercase',
      'label' => 'Example Style Uppercase',
      'date_format_pattern' => 'j M Y',
    ];
    $this->drupalPostForm('admin/config/regional/date-time/formats/add', $edit, t('Add format'));
    $this->assertText(t('Custom date format added.'));

    $timestamp = strtotime('2007-03-10T00:00:00+00:00');
    $this->assertIdentical(format_date($timestamp, 'example_style', '', 'America/Los_Angeles'), '9 Mar 07');
    $this->assertIdentical(format_date($timestamp, 'example_style_uppercase', '', 'America/Los_Angeles'), '9 Mar 2007');
    $this->assertIdentical(format_date($timestamp, 'undefined_style'), format_date($timestamp, 'fallback'), 'Test format_date() defaulting to `fallback` when $type not found.');
  }

}
