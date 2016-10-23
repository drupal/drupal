<?php

namespace Drupal\Tests\node\Kernel\Plugin\migrate\source\d6;

/**
 * Tests D6 node translation source plugin.
 *
 * @covers \Drupal\node\Plugin\migrate\source\d6\Node
 *
 * @group node
 */
class NodeTranslationTest extends NodeTest {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'user', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    // Get the source data from parent.
    $tests = parent::providerSource();

    // The expected results.
    $tests[0]['expected_data'] = [
      [
        'nid' => 7,
        'vid' => 7,
        'type' => 'story',
        'language' => 'fr',
        'title' => 'node title 7',
        'node_uid' => 1,
        'revision_uid' => 2,
        'status' => 1,
        'created' => 1279290910,
        'changed' => 1279308995,
        'comment' => 0,
        'promote' => 1,
        'moderate' => 0,
        'sticky' => 0,
        'tnid' => 6,
        'translate' => 0,
        // Node revision fields.
        'body' => 'body for node 7',
        'teaser' => 'body for node 7',
        'log' => '',
        'timestamp' => 1279308995,
        'format' => 1,
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
