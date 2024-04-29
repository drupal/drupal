<?php

declare(strict_types=1);

namespace Drupal\Tests\menu_link_content\Kernel\Plugin\migrate\source\d6;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

// cspell:ignore mlid objectid objectindex plid

/**
 * Tests menu link translation source plugin.
 *
 * @covers \Drupal\menu_link_content\Plugin\migrate\source\d6\MenuLinkTranslation
 * @group menu_link_content
 */
class MenuLinkTranslationTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['menu_link_content', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public static function providerSource() {
    $test = [];
    $test[0]['source_data']['menu_links'] = [
      [
        'menu_name' => 'menu-test-menu',
        'mlid' => 138,
        'plid' => 0,
        'link_path' => 'admin',
        'router_path' => 'admin',
        'link_title' => 'Test 1',
        'options' => 'a:1:{s:10:"attributes";a:1:{s:5:"title";s:16:"Test menu link 1";}}',
        'module' => 'menu',
        'hidden' => 0,
        'external' => 0,
        'has_children' => 1,
        'expanded' => 0,
        'weight' => 15,
        'depth' => 1,
        'customized' => 1,
        'p1' => '138',
        'p2' => '0',
        'p3' => '0',
        'p4' => '0',
        'p5' => '0',
        'p6' => '0',
        'p7' => '0',
        'p8' => '0',
        'p9' => '0',
        'updated' => '0',
      ],
      [
        'menu_name' => 'menu-test-menu',
        'mlid' => 139,
        'plid' => 138,
        'link_path' => 'admin/modules',
        'router_path' => 'admin/modules',
        'link_title' => 'Test 2',
        'options' => 'a:1:{s:10:"attributes";a:1:{s:5:"title";s:16:"Test menu link 2";}}',
        'module' => 'menu',
        'hidden' => 0,
        'external' => 0,
        'has_children' => 0,
        'expanded' => 0,
        'weight' => 12,
        'depth' => 2,
        'customized' => 1,
        'p1' => '138',
        'p2' => '139',
        'p3' => '0',
        'p4' => '0',
        'p5' => '0',
        'p6' => '0',
        'p7' => '0',
        'p8' => '0',
        'p9' => '0',
        'updated' => '0',
      ],
      [
        'menu_name' => 'menu-test-menu',
        'mlid' => 140,
        'plid' => 0,
        'link_path' => 'https://www.drupal.org',
        'router_path' => 'admin/modules',
        'link_title' => 'Test 2',
        'options' => 'a:1:{s:10:"attributes";a:1:{s:5:"title";s:16:"Test menu link 2";}}',
        'module' => 'menu',
        'hidden' => 0,
        'external' => 0,
        'has_children' => 0,
        'expanded' => 0,
        'weight' => 12,
        'depth' => 2,
        'customized' => 1,
        'p1' => '0',
        'p2' => '0',
        'p3' => '0',
        'p4' => '0',
        'p5' => '0',
        'p6' => '0',
        'p7' => '0',
        'p8' => '0',
        'p9' => '0',
        'updated' => '0',
      ],
      [
        'menu_name' => 'menu-test-menu',
        'mlid' => 141,
        'plid' => 0,
        'link_path' => 'https://api.drupal.org/api/drupal/8.3.x',
        'router_path' => 'admin/modules',
        'link_title' => 'Test 3',
        'options' => 'a:1:{s:10:"attributes";a:1:{s:5:"title";s:16:"Test menu link 3";}}',
        'module' => 'menu',
        'hidden' => 0,
        'external' => 0,
        'has_children' => 0,
        'expanded' => 0,
        'weight' => 12,
        'depth' => 2,
        'customized' => 1,
        'p1' => '0',
        'p2' => '0',
        'p3' => '0',
        'p4' => '0',
        'p5' => '0',
        'p6' => '0',
        'p7' => '0',
        'p8' => '0',
        'p9' => '0',
        'updated' => '0',
      ],
    ];
    $test[0]['source_data']['i18n_strings'] = [
      [
        'lid' => 1,
        'objectid' => 139,
        'type' => 'item',
        'property' => 'title',
        'objectindex' => 0,
        'format' => 0,
      ],
      [
        'lid' => 2,
        'objectid' => 139,
        'type' => 'item',
        'property' => 'description',
        'objectindex' => 0,
        'format' => 0,
      ],
      [
        'lid' => 3,
        'objectid' => 140,
        'type' => 'item',
        'property' => 'description',
        'objectindex' => 0,
        'format' => 0,
      ],
      [
        'lid' => 4,
        'objectid' => 141,
        'type' => 'item',
        'property' => 'title',
        'objectindex' => 0,
        'format' => 0,
      ],
    ];
    $test[0]['source_data']['locales_target'] = [
      [
        'lid' => 1,
        'language' => 'fr',
        'translation' => 'fr - title translation',
        'plid' => 0,
        'plural' => 0,
        'i18n_status' => 0,
      ],
      [
        'lid' => 2,
        'language' => 'fr',
        'translation' => 'fr - description translation',
        'plid' => 0,
        'plural' => 0,
        'i18n_status' => 0,
      ],
      [
        'lid' => 3,
        'language' => 'zu',
        'translation' => 'zu - description translation',
        'plid' => 0,
        'plural' => 0,
        'i18n_status' => 0,
      ],
      [
        'lid' => 4,
        'language' => 'zu',
        'translation' => 'zu - title translation',
        'plid' => 0,
        'plural' => 0,
        'i18n_status' => 0,
      ],
    ];

    $test[0]['expected_data'] = [
      [
        'menu_name' => 'menu-test-menu',
        'mlid' => 139,
        'property' => 'title',
        'language' => 'fr',
        'link_title' => 'Test 2',
        'description' => 'Test menu link 2',
        'title_translated' => 'fr - title translation',
        'description_translated' => 'fr - description translation',
      ],
      [
        'menu_name' => 'menu-test-menu',
        'mlid' => 139,
        'property' => 'description',
        'language' => 'fr',
        'link_title' => 'Test 2',
        'description' => 'Test menu link 2',
        'title_translated' => 'fr - title translation',
        'description_translated' => 'fr - description translation',
      ],
      [
        'menu_name' => 'menu-test-menu',
        'mlid' => 140,
        'property' => 'description',
        'language' => 'zu',
        'link_title' => 'Test 2',
        'description' => 'Test menu link 2',
        'title_translated' => NULL,
        'description_translated' => 'zu - description translation',
      ],
      [
        'menu_name' => 'menu-test-menu',
        'mlid' => 141,
        'property' => 'title',
        'language' => 'zu',
        'link_title' => 'Test 3',
        'description' => 'Test menu link 3',
        'title_translated' => 'zu - title translation',
        'description_translated' => NULL,
      ],
    ];

    return $test;
  }

}
