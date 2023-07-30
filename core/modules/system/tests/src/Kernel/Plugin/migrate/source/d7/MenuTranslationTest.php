<?php

namespace Drupal\Tests\system\Kernel\Plugin\migrate\source\d7;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

// cspell:ignore objectid objectindex

/**
 * Tests the menu translation source plugin.
 *
 * @covers Drupal\system\Plugin\migrate\source\d7\MenuTranslation
 * @group system
 */
class MenuTranslationTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];
    $tests[0]['source_data']['menu_custom'] = [
      [
        'menu_name' => 'navigation',
        'title' => 'Navigation',
        'description' => 'Navigation description',
        'language' => 'und',
        'i18n_mode' => 0,
      ],
      [
        'menu_name' => 'menu-name-2',
        'title' => 'menu custom value 2',
        'description' => 'menu custom description value 2',
        'language' => 'und',
        'i18n_mode' => 0,
      ],
    ];
    $tests[0]['source_data']['i18n_string'] = [
      [
        'lid' => 1,
        'textgroup' => 'menu',
        'context' => ' menu:navigation:description',
        'objectid' => 'navigation',
        'type' => 'menu',
        'property' => 'description',
        'objectindex' => 0,
        'format' => '',
      ],
      [
        'lid' => 2,
        'textgroup' => 'menu',
        'context' => ' menu:navigation:title',
        'objectid' => 'navigation',
        'type' => 'menu',
        'property' => 'title',
        'objectindex' => 0,
        'format' => '',
      ],
    ];
    $tests[0]['source_data']['locales_target'] = [
      [
        'lid' => 1,
        'translation' => 'navigation description translation',
        'language' => 'fr',
        'plid' => 0,
        'plural' => 0,
        'i18n_status' => 0,
      ],
      [
        'lid' => 2,
        'translation' => 'navigation translation',
        'language' => 'fr',
        'plid' => 0,
        'plural' => 0,
        'i18n_status' => 0,
      ],
    ];
    $tests[0]['expected_results'] = [
      [
        'menu_name' => 'navigation',
        'type' => 'menu',
        'property' => 'description',
        'translation' => 'navigation description translation',
        'language' => 'fr',
        'objectid' => 'navigation',
      ],
      [
        'menu_name' => 'navigation',
        'type' => 'menu',
        'property' => 'title',
        'translation' => 'navigation translation',
        'language' => 'fr',
        'objectid' => 'navigation',
      ],
    ];
    return $tests;
  }

}
