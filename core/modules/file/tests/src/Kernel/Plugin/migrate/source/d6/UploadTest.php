<?php

namespace Drupal\Tests\file\Kernel\Plugin\migrate\source\d6;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests D6 d6_upload source plugin.
 *
 * @covers \Drupal\file\Plugin\migrate\source\d6\Upload
 *
 * @group file
 */
class UploadTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['file', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];

    // The source data.
    $tests[0]['source_data']['upload'] = [
      [
        'fid' => '1',
        'nid' => '1',
        'vid' => '1',
        'description' => 'file 1-1-1',
        'list' => '0',
        'weight' => '-1',
      ],
      [
        'fid' => '3',
        'nid' => '12',
        'vid' => '15',
        'description' => 'file 12-15-3',
        'list' => '0',
        'weight' => '0',
      ],
    ];

    $tests[0]['source_data']['node'] = [
      [
        'nid' => '1',
        'vid' => '1',
        'type' => 'story',
        'language' => '',
        'title' => 'Test title',
        'uid' => '1',
        'status' => '1',
        'created' => '1388271197',
        'changed' => '1420861423',
        'comment' => '0',
        'promote' => '0',
        'moderate' => '0',
        'sticky' => '0',
        'tnid' => '0',
        'translate' => '0',
      ],
      [
        'nid' => '12',
        'vid' => '15',
        'type' => 'page',
        'language' => 'zu',
        'title' => 'Abantu zulu',
        'uid' => '1',
        'status' => '1',
        'created' => '1444238800',
        'changed' => '1444238808',
        'comment' => '0',
        'promote' => '0',
        'moderate' => '0',
        'sticky' => '0',
        'tnid' => '12',
        'translate' => '0',
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] = [
      [
        'upload' => [
          [
            'fid' => '1',
            'description' => 'file 1-1-1',
            'list' => '0',
          ],
        ],
        'language' => '',
        'nid' => '1',
        'vid' => '1',
        'type' => 'story',
      ],
      [
        'upload' => [
          [
            'fid' => '3',
            'description' => 'file 12-15-3',
            'list' => '0',
          ],
        ],
        'language' => 'zu',
        'nid' => '12',
        'vid' => '15',
        'type' => 'page',
      ],
    ];

    return $tests;
  }

}
