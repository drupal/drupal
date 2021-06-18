<?php

namespace Drupal\Tests\menu_link_content\Kernel\Plugin\migrate\source;

use Drupal\Component\Utility\Unicode;
use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests the menu link source plugin.
 *
 * @covers \Drupal\menu_link_content\Plugin\migrate\source\MenuLink
 *
 * @group menu_link_content
 */
class MenuLinkTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['menu_link_content', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];

    // The source data.
    $tests[0]['source_data']['menu_links'] = [
      [
        // Customized menu link, provided by system module.
        'menu_name' => 'menu-test-menu',
        'mlid' => 140,
        'plid' => 0,
        'link_path' => 'admin/config/system/cron',
        'router_path' => 'admin/config/system/cron',
        'link_title' => 'Cron',
        'options' => [],
        'module' => 'system',
        'hidden' => 0,
        'external' => 0,
        'has_children' => 0,
        'expanded' => 0,
        'weight' => 0,
        'depth' => 0,
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
        'language' => 'und',
        'i18n_tsid' => '0',
        'skip_translation' => TRUE,
      ],
      [
        // D6 customized menu link, provided by menu module.
        'menu_name' => 'menu-test-menu',
        'mlid' => 141,
        'plid' => 0,
        'link_path' => 'node/141',
        'router_path' => 'node/%',
        'link_title' => 'Node 141',
        'options' => [],
        'module' => 'menu',
        'hidden' => 0,
        'external' => 0,
        'has_children' => 0,
        'expanded' => 0,
        'weight' => 0,
        'depth' => 0,
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
        'language' => 'und',
        'i18n_tsid' => '0',
        'skip_translation' => TRUE,
      ],
      [
        // D6 non-customized menu link, provided by menu module.
        'menu_name' => 'menu-test-menu',
        'mlid' => 142,
        'plid' => 0,
        'link_path' => 'node/142',
        'router_path' => 'node/%',
        'link_title' => 'Node 142',
        'options' => [],
        'module' => 'menu',
        'hidden' => 0,
        'external' => 0,
        'has_children' => 0,
        'expanded' => 0,
        'weight' => 0,
        'depth' => 0,
        'customized' => 0,
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
        'language' => 'en',
        'i18n_tsid' => '1',
        'skip_translation' => TRUE,
      ],
      [
        'menu_name' => 'menu-test-menu',
        'mlid' => 138,
        'plid' => 0,
        'link_path' => 'admin',
        'router_path' => 'admin',
        'link_title' => 'Test 1',
        'options' => ['attributes' => ['title' => 'Test menu link 1']],
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
        'language' => 'und',
        'i18n_tsid' => '0',
        'skip_translation' => TRUE,
      ],
      [
        'menu_name' => 'menu-test-menu',
        'mlid' => 139,
        'plid' => 138,
        'link_path' => 'admin/modules',
        'router_path' => 'admin/modules',
        'link_title' => 'Test 2',
        'options' => ['attributes' => ['title' => 'Test menu link 2']],
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
        'language' => 'und',
        'i18n_tsid' => '0',
        'skip_translation' => TRUE,
      ],
      [
        'menu_name' => 'menu-user',
        'mlid' => 143,
        'plid' => 0,
        'link_path' => 'admin/build/menu-customize/navigation',
        'router_path' => 'admin/build/menu-customize/%',
        'link_title' => 'Navigation',
        'options' => [],
        'module' => 'menu',
        'hidden' => 0,
        'external' => 0,
        'has_children' => 0,
        'expanded' => 0,
        'weight' => 0,
        'depth' => 0,
        'customized' => 0,
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
        'language' => 'und',
        'i18n_tsid' => '0',
        'skip_translation' => TRUE,
      ],
      [
        // D7 non-customized menu link, provided by menu module.
        'menu_name' => 'menu-test2-menu',
        'mlid' => 300,
        'plid' => 0,
        'link_path' => 'node/142',
        'router_path' => 'node/%',
        'link_title' => 'Node 142',
        'options' => [],
        'module' => 'menu',
        'hidden' => 0,
        'external' => 0,
        'has_children' => 0,
        'expanded' => 0,
        'weight' => 0,
        'depth' => 0,
        'customized' => 0,
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
        'language' => 'fr',
        'i18n_tsid' => '1',
        'skip_translation' => FALSE,
      ],
    ];

    // Add long link title attributes to source data.
    $title = $this->getRandomGenerator()->string('500');
    $tests[0]['source_data']['menu_links'][0]['options']['attributes']['title'] = $title;

    // Build the expected results.
    $expected = $tests[0]['source_data']['menu_links'];

    // Add long link title attributes to expected results.
    $expected[0]['description'] = Unicode::truncate($title, 255);

    // Don't expect D6 menu link to a custom menu, provided by menu module.
    unset($expected[5]);

    array_walk($tests[0]['source_data']['menu_links'], function (&$row) {
      $row['options'] = serialize($row['options']);
    });

    // Adjust the order to match the order used in the query. The expected[5] is
    // not returned by the source query because it is an admin menu link.
    $tests[0]['expected_data'] = [];
    $tests[0]['expected_data'][] = $expected[0];
    $tests[0]['expected_data'][] = $expected[1];
    $tests[0]['expected_data'][] = $expected[2];
    $tests[0]['expected_data'][] = $expected[6];
    $tests[0]['expected_data'][] = $expected[3];
    $tests[0]['expected_data'][] = $expected[4];

    // Tests retrieval of links from multiple menus.
    $tests[1] = $tests[0];
    $tests[1]['expected_count'] = NULL;
    $tests[1]['configuration'] = [
      'menu_name' => ['menu-test-menu', 'menu-test2-menu'],
    ];

    // Tests retrieval of links from a single menu.
    $tests[2] = $tests[1];
    $tests[2]['configuration'] = [
      'menu_name' => 'menu-test2-menu',
    ];
    $tests[2]['expected_data'] = [$expected[6]];

    // Tests retrieval of links from a not existing menu.
    $tests[3] = $tests[1];
    $tests[3]['configuration'] = [
      'menu_name' => 'menu-not-exists',
    ];
    $tests[3]['expected_data'] = [];
    return $tests;
  }

}
