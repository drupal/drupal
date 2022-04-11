<?php

namespace Drupal\Tests\system\Kernel\Plugin\migrate\source;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests actions source plugin.
 *
 * @covers \Drupal\system\Plugin\migrate\source\Action
 * @group action
 */
class ActionTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['action', 'migrate_drupal', 'system'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];

    $tests[0][0]['actions'] = [
      [
        'aid' => 'Redirect to node list page',
        'type' => 'system',
        'callback' => 'system_goto_action',
        'parameters' => 'a:1:{s:3:"url";s:4:"node";}',
        'description' => 'Redirect to node list page',
      ],
      [
        'aid' => 'Test notice email',
        'type' => 'system',
        'callback' => 'system_send_email_action',
        'parameters' => 'a:3:{s:9:"recipient";s:7:"%author";s:7:"subject";s:4:"Test";s:7:"message";s:4:"Test',
        'description' => 'Test notice email',
      ],
      [
        'aid' => 'comment_publish_action',
        'type' => 'comment',
        'callback' => 'comment_publish_action',
        'parameters' => NULL,
        'description' => NULL,
      ],
      [
        'aid' => 'node_publish_action',
        'type' => 'comment',
        'callback' => 'node_publish_action',
        'parameters' => NULL,
        'description' => NULL,
      ],
    ];
    // The expected results are identical to the source data.
    $tests[0][1] = $tests[0][0]['actions'];

    return $tests;
  }

}
