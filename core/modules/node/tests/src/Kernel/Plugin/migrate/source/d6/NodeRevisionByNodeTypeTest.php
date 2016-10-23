<?php

namespace Drupal\Tests\node\Kernel\Plugin\migrate\source\d6;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests D6 node revision source plugin.
 *
 * @covers \Drupal\node\Plugin\migrate\source\d6\NodeRevision
 *
 * @group node
 */
class NodeRevisionByNodeTypeTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'user', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];

    // The source data.
    $tests[0]['source_data']['node'] = [
      [
        'nid' => 1,
        'type' => 'page',
        'language' => 'en',
        'status' => 1,
        'created' => 1279051598,
        'changed' => 1279051598,
        'comment' => 2,
        'promote' => 1,
        'moderate' => 0,
        'sticky' => 0,
        'tnid' => 0,
        'translate' => 0,
        'vid' => 4,
        'uid' => 1,
        'title' => 'title for revision 4 (node 1)',
      ],
      [
        'nid' => 2,
        'type' => 'article',
        'language' => 'en',
        'status' => 1,
        'created' => 1279290908,
        'changed' => 1279308993,
        'comment' => 0,
        'promote' => 1,
        'moderate' => 0,
        'sticky' => 0,
        'tnid' => 0,
        'translate' => 0,
        'vid' => 2,
        'uid' => 1,
        'title' => 'title for revision 2 (node 2)',
      ],
    ];
    $tests[0]['source_data']['node_revisions'] = [
      [
        'nid' => 1,
        'vid' => 1,
        'uid' => 1,
        'title' => 'title for revision 1 (node 1)',
        'body' => 'body for revision 1 (node 1)',
        'teaser' => 'teaser for revision 1 (node 1)',
        'log' => 'log for revision 1 (node 1)',
        'format' => 1,
        'timestamp' => 1279051598,
      ],
      [
        'nid' => 1,
        'vid' => 3,
        'uid' => 1,
        'title' => 'title for revision 3 (node 1)',
        'body' => 'body for revision 3 (node 1)',
        'teaser' => 'teaser for revision 3 (node 1)',
        'log' => 'log for revision 3 (node 1)',
        'format' => 1,
        'timestamp' => 1279051598,
      ],
      [
        'nid' => 1,
        'vid' => 4,
        'uid' => 1,
        'title' => 'title for revision 4 (node 1)',
        'body' => 'body for revision 4 (node 1)',
        'teaser' => 'teaser for revision 4 (node 1)',
        'log' => 'log for revision 4 (node 1)',
        'format' => 1,
        'timestamp' => 1279051598,
      ],
      [
        'nid' => 2,
        'vid' => 2,
        'uid' => 1,
        'title' => 'title for revision 2 (node 2)',
        'body' => 'body for revision 2 (node 2)',
        'teaser' => 'teaser for revision 2 (node 2)',
        'log' => 'log for revision 2 (node 2)',
        'format' => 1,
        'timestamp' => 1279308993,
      ],
    ];

    // The expected results.
    // There are three revisions of nid 1; vid 4 is the current one. The
    // NodeRevision plugin should capture every revision EXCEPT that one.
    // nid 2 will be ignored because source plugin configuration specifies
    // a particular node type.
    $tests[0]['expected_data'] = [
      [
        'nid' => 1,
        'type' => 'page',
        'language' => 'en',
        'status' => 1,
        'created' => 1279051598,
        'changed' => 1279051598,
        'comment' => 2,
        'promote' => 1,
        'moderate' => 0,
        'sticky' => 0,
        'tnid' => 1,
        'translate' => 0,
        'vid' => 1,
        'node_uid' => 1,
        'revision_uid' => 1,
        'title' => 'title for revision 1 (node 1)',
        'body' => 'body for revision 1 (node 1)',
        'teaser' => 'teaser for revision 1 (node 1)',
        'log' => 'log for revision 1 (node 1)',
        'format' => 1,
      ],
      [
        'nid' => 1,
        'type' => 'page',
        'language' => 'en',
        'status' => 1,
        'created' => 1279051598,
        'changed' => 1279051598,
        'comment' => 2,
        'promote' => 1,
        'moderate' => 0,
        'sticky' => 0,
        'tnid' => 1,
        'translate' => 0,
        'vid' => 3,
        'node_uid' => 1,
        'revision_uid' => 1,
        'title' => 'title for revision 3 (node 1)',
        'body' => 'body for revision 3 (node 1)',
        'teaser' => 'teaser for revision 3 (node 1)',
        'log' => 'log for revision 3 (node 1)',
        'format' => 1,
      ],
    ];

    // Do an automatic count.
    $tests[0]['expected_count'] = NULL;

    // Set up source plugin configuration.
    $tests[0]['configuration'] = [
      'node_type' => 'page',
    ];

    return $tests;
  }

}
