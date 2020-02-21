<?php

namespace Drupal\Tests\language\Kernel\Plugin\migrate\source\d7;

use Drupal\Tests\system\Kernel\Plugin\migrate\source\MenuTest;

/**
 * Tests i18n menu links source plugin.
 *
 * @covers \Drupal\language\Plugin\migrate\source\d7\LanguageContentSettingsMenu
 *
 * @group language
 */
class LanguageContentMenuSettingsTest extends MenuTest {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['menu_link_content', 'language', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    // Get the source data from parent.
    $tests = parent::providerSource();

    foreach ($tests as &$test) {
      // Add the extra columns provided by i18n_menu.
      foreach ($test['source_data']['menu_custom'] as &$vocabulary) {
        $vocabulary['language'] = 'und';
        $vocabulary['i18n_mode'] = 2;
      }
      foreach ($test['expected_data'] as &$expected) {
        $expected['language'] = 'und';
        $expected['i18n_mode'] = 2;
      }
    }
    return $tests;
  }

}
