<?php

namespace Drupal\Tests\node\Kernel\Plugin\migrate\source\d7;

// cspell:ignore tnid

/**
 * Tests D7 node translation source plugin.
 *
 * @covers \Drupal\node\Plugin\migrate\source\d7\Node
 *
 * @group node
 */
class NodeTranslationTest extends NodeTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'user', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    // Get the source data from parent.
    $tests = parent::providerSource();

    // The expected results.
    $tests[0]['expected_data'] = [
      [
        // Node fields.
        'nid' => 7,
        'vid' => 7,
        'type' => 'article',
        'language' => 'fr',
        'title' => 'node title 5 (title_field)',
        'node_uid' => 1,
        'revision_uid' => 1,
        'status' => 1,
        'created' => 1279292908,
        'changed' => 1279310993,
        'comment' => 0,
        'promote' => 1,
        'sticky' => 0,
        'tnid' => 6,
        'translate' => 0,
        // Node revision fields.
        'log' => '',
        'timestamp' => 1279310993,
        'body' => [
          [
            'value' => 'fr - body 6',
            'summary' => '',
            'format' => 'filtered_html',
          ],
        ],
      ],
    ];

    // Do an automatic count.
    $tests[0]['expected_count'] = NULL;

    // Set up source plugin configuration.
    $tests[0]['configuration'] = [
      'translations' => TRUE,
    ];

    return $tests;
  }

}
