<?php

declare(strict_types=1);

namespace Drupal\Tests\block\Kernel\Plugin\migrate\source\d7;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

// cspell:ignore objectid objectindex plid textgroup

/**
 * Tests i18n block source plugin.
 *
 * @covers \Drupal\block\Plugin\migrate\source\d7\BlockTranslation
 *
 * @group content_translation
 */
class BlockTranslationTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public static function providerSource() {

    // The source data.
    $tests[0]['source_data']['block'] = [
      [
        'bid' => 1,
        'module' => 'system',
        'delta' => 'main',
        'theme' => 'bartik',
        'status' => 1,
        'weight' => 0,
        'region' => 'content',
        'custom' => '0',
        'visibility' => 0,
        'pages' => '',
        'title' => '',
        'cache' => -1,
        'i18n_mode' => 1,
      ],
      [
        'bid' => 2,
        'module' => 'system',
        'delta' => 'navigation',
        'theme' => 'bartik',
        'status' => 1,
        'weight' => 0,
        'region' => 'sidebar_first',
        'custom' => '0',
        'visibility' => 0,
        'pages' => '',
        'title' => 'Navigation',
        'cache' => -1,
        'i18n_mode' => 1,
      ],
    ];
    $tests[0]['source_data']['block_role'] = [
      [
        'module' => 'block',
        'delta' => 1,
        'rid' => 2,
      ],
      [
        'module' => 'block',
        'delta' => 2,
        'rid' => 2,
      ],
      [
        'module' => 'block',
        'delta' => 2,
        'rid' => 100,
      ],
    ];
    $tests[0]['source_data']['i18n_string'] = [
      [
        'lid' => 1,
        'textgroup' => 'block',
        'context' => '1',
        'objectid' => 'navigation',
        'type' => 'system',
        'property' => 'title',
        'objectindex' => 0,
        'format' => '',
      ],
      [
        'lid' => 2,
        'textgroup' => 'block',
        'context' => '1',
        'objectid' => 'main',
        'type' => 'system',
        'property' => 'title',
        'objectindex' => 0,
        'format' => '',
      ],
    ];

    $tests[0]['source_data']['locales_target'] = [
      [
        'lid' => 1,
        'translation' => 'fr - Navigation',
        'language' => 'fr',
        'plid' => 0,
        'plural' => 0,
        'i18n_status' => 0,
      ],
    ];
    $tests[0]['source_data']['role'] = [
      [
        'rid' => 2,
        'name' => 'authenticated user',
      ],
    ];
    $tests[0]['source_data']['system'] = [
      [
        'filename' => 'modules/system/system.module',
        'name' => 'system',
        'type' => 'module',
        'owner' => '',
        'status' => '1',
        'throttle' => '0',
        'bootstrap' => '0',
        'schema_version' => '7055',
        'weight' => '0',
        'info' => 'a:0:{}',
      ],
    ];
    // The expected results.
    $tests[0]['expected_data'] = [
      [
        'bid' => 2,
        'module' => 'system',
        'delta' => 'navigation',
        'theme' => 'bartik',
        'status' => 1,
        'weight' => 0,
        'region' => 'sidebar_first',
        'custom' => '0',
        'visibility' => 0,
        'pages' => '',
        'title' => 'Navigation',
        'cache' => -1,
        'i18n_mode' => 1,
        'lid' => 1,
        'translation' => 'fr - Navigation',
        'language' => 'fr',
        'plid' => 0,
        'plural' => 0,
        'i18n_status' => 0,
      ],
    ];

    // Change the name of the locale_target i18n status field.
    $tests[1] = $tests[0];
    foreach ($tests[1]['source_data']['locales_target'] as &$lt) {
      $lt['status'] = $lt['i18n_status'];
      unset($lt['i18n_status']);
    }
    $tests[1]['expected_data'] = [
      [
        'bid' => 2,
        'module' => 'system',
        'delta' => 'navigation',
        'theme' => 'bartik',
        'status' => 1,
        'weight' => 0,
        'region' => 'sidebar_first',
        'custom' => '0',
        'visibility' => 0,
        'pages' => '',
        'title' => 'Navigation',
        'cache' => -1,
        'i18n_mode' => 1,
        'lid' => 1,
        'translation' => 'fr - Navigation',
        'language' => 'fr',
        'plid' => 0,
        'plural' => 0,
        'i18n_status' => 0,
      ],
    ];
    return $tests;
  }

}
