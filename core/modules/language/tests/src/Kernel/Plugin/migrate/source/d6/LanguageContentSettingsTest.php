<?php

declare(strict_types=1);

namespace Drupal\Tests\language\Kernel\Plugin\migrate\source\d6;

use Drupal\language\Plugin\migrate\source\d6\LanguageContentSettings;
use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests menu source plugin.
 */
#[CoversClass(LanguageContentSettings::class)]
#[Group('language')]
#[RunTestsInSeparateProcesses]
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
        'module' => 'node',
        'description' => 'An <em>article</em>, content type.',
        'help' => '',
        'has_title' => 1,
        'title_label' => 'Title',
        'has_body' => 1,
        'body_label' => 'Body',
        'min_word_count' => 0,
        'custom' => 1,
        'modified' => 1,
        'locked' => 0,
        'orig_type' => 'story',
      ],
      [
        'type' => 'company',
        'name' => 'Company',
        'module' => 'node',
        'description' => 'Company node type',
        'help' => '',
        'has_title' => 1,
        'title_label' => 'Name',
        'has_body' => 1,
        'body_label' => 'Description',
        'min_word_count' => 0,
        'custom' => 0,
        'modified' => 1,
        'locked' => 0,
        'orig_type' => 'company',
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
