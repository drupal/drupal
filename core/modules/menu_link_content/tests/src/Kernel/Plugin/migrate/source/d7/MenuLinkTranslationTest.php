<?php

namespace Drupal\Tests\menu_link_content\Kernel\Plugin\migrate\source\d7;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests menu link localized translation source plugin.
 *
 * @covers \Drupal\menu_link_content\Plugin\migrate\source\d7\MenuLinkTranslation
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
  public function providerSource() {
    $test = [];
    $test[0]['source_data']['menu_links'] = [
      [
        'menu_name' => 'menu-test-menu',
        'mlid' => 130,
        'plid' => 469,
        'link_path' => 'http://google.com',
        'router_path' => '',
        'link_title' => 'Google',
        'options' => 'a:1:{s:10:"attributes";a:1:{s:5:"title";s:16:"Test menu link 1";}}',
        'module' => 'menu',
        'hidden' => 0,
        'external' => 1,
        'has_children' => 0,
        'expanded' => 0,
        'weight' => 0,
        'depth' => 2,
        'customized' => 1,
        'p1' => '469',
        'p2' => '467',
        'p3' => '0',
        'p4' => '0',
        'p5' => '0',
        'p6' => '0',
        'p7' => '0',
        'p8' => '0',
        'p9' => '0',
        'updated' => '0',
        'language' => 'und',
        'i18n_tsid' => '0',
        'ml_language' => 'und',
        'lid' => '1',
        'property' => 'title',
        'lt_language' => 'fr',
        'translation' => 'fr - title translation',
        'title_translated' => 'fr - title translation',
        'description_translated' => 'fr - description translation',
      ],
    ];
    $test[0]['source_data']['i18n_string'] = [
      [
        'lid' => 1,
        'textgroup' => 'menu',
        'context' => 'item:130:title',
        'objectid' => 130,
        'type' => 'item',
        'property' => 'title',
        'objectindex' => 130,
        'format' => 0,
      ],
      [
        'lid' => 2,
        'textgroup' => 'menu',
        'context' => 'item:130:description',
        'objectid' => 130,
        'type' => 'item',
        'property' => 'description',
        'objectindex' => 130,
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
    ];

    $test[0]['expected_results'] = [
      [
        'menu_name' => 'menu-test-menu',
        'mlid' => 130,
        'plid' => 469,
        'link_path' => 'http://google.com',
        'router_path' => '',
        'link_title' => 'Google',
        'module' => 'menu',
        'hidden' => 0,
        'external' => 1,
        'has_children' => 0,
        'expanded' => 0,
        'weight' => 0,
        'depth' => 2,
        'customized' => 1,
        'p1' => '469',
        'p2' => '467',
        'p3' => '0',
        'p4' => '0',
        'p5' => '0',
        'p6' => '0',
        'p7' => '0',
        'p8' => '0',
        'p9' => '0',
        'updated' => '0',
        'language' => 'fr',
        'i18n_tsid' => '0',
        'parent_link_path' => NULL,
        'property' => 'title',
        'lid' => '1',
        'lt_language' => 'fr',
        'translation' => 'fr - title translation',
        'description_translated' => 'fr - description translation',
        'title_translated' => 'fr - title translation',
      ],
      [
        'menu_name' => 'menu-test-menu',
        'mlid' => 130,
        'plid' => 469,
        'link_path' => 'http://google.com',
        'router_path' => '',
        'link_title' => 'Google',
        'module' => 'menu',
        'hidden' => 0,
        'external' => 1,
        'has_children' => 0,
        'expanded' => 0,
        'weight' => 0,
        'depth' => 2,
        'customized' => 1,
        'p1' => '469',
        'p2' => '467',
        'p3' => '0',
        'p4' => '0',
        'p5' => '0',
        'p6' => '0',
        'p7' => '0',
        'p8' => '0',
        'p9' => '0',
        'updated' => '0',
        'language' => 'fr',
        'i18n_tsid' => '0',
        'parent_link_path' => NULL,
        'property' => 'description',
        'lid' => '2',
        'lt_language' => 'fr',
        'translation' => 'fr - description translation',
        'description_translated' => 'fr - description translation',
        'title_translated' => 'fr - title translation',
      ],
    ];

    return $test;
  }

}
