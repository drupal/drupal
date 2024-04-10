<?php

declare(strict_types=1);

namespace Drupal\Tests\language\Kernel\Plugin\migrate\source\d7;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests menu source plugin.
 *
 * @covers \Drupal\language\Plugin\migrate\source\d7\LanguageContentSettings
 *
 * @group language
 */
class LanguageContentSettingsTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['language', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public static function providerSource() {
    $tests = [];

    // The source data.
    $tests[0]['source_data']['node_type'] = [
      [
        'type' => 'article',
        'name' => 'Article',
        'base' => 'node_content',
        'module' => 'node',
        'description' => 'Use <em>articles</em> for time-sensitive content like news, press releases or blog posts.',
        'help' => 'Help text for articles',
        'has_title' => 1,
        'title_label' => 'Title',
        'custom' => 1,
        'modified' => 1,
        'locked' => 0,
        'disabled' => 0,
        'orig_type' => 'article',
      ],
      [
        'type' => 'blog',
        'name' => 'Blog entry',
        'base' => 'blog',
        'module' => 'blog',
        'description' => 'Use for multi-user blogs. Every user gets a personal blog.',
        'help' => 'Blog away, good sir!',
        'has_title' => 1,
        'title_label' => 'Title',
        'custom' => 0,
        'modified' => 1,
        'locked' => 1,
        'disabled' => 0,
        'orig_type' => 'blog',
      ],
    ];

    foreach ($tests[0]['source_data']['node_type'] as $node_type) {
      $tests[0]['expected_data'][] = [
        'type' => $node_type['type'],
        'language_content_type' => NULL,
        'i18n_lock_node' => 0,
      ];
    }

    return $tests;
  }

}
