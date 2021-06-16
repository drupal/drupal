<?php

namespace Drupal\Tests\file\Kernel\Plugin\migrate\source\d6;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests D6 d6_upload_instance source plugin.
 *
 * @covers \Drupal\file\Plugin\migrate\source\d6\UploadInstance
 *
 * @group file
 */
class UploadInstanceTest extends MigrateSqlSourceTestBase {

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
    $tests[0]['source_data']['node_type'] = [
      [
        'type' => 'article',
      ],
      [
        'type' => 'company',
      ],
    ];

    $tests[0]['source_data']['variable'] = [
      [
        'name' => 'upload_article',
        'value' => serialize(TRUE),
      ],
      [
        'name' => 'upload_company',
        'value' => serialize(FALSE),
      ],
      [
        'name' => 'upload_uploadsize_default',
        'value' => serialize(16),
      ],
      [
        'name' => 'upload_extensions_default',
        'value' => serialize('txt pdf'),
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] = [
      [
        'node_type' => 'article',
        'max_filesize' => '16MB',
        'file_extensions' => 'txt pdf',
      ],
    ];

    return $tests;
  }

}
