<?php

namespace Drupal\Tests\file\Unit\Plugin\migrate\source\d6;

use Drupal\file\Plugin\migrate\source\d6\UploadInstance;
use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests d6_upload_instance source plugin.
 *
 * @group file
 */
class UploadInstanceTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = UploadInstance::class;

  protected $migrationConfiguration = array(
    'id' => 'test',
    'source' => array(
      'plugin' => 'd6_upload_instance',
    ),
  );

  protected $expectedResults = array(
    array(
      'node_type' => 'article',
      'max_filesize' => '16MB',
      'file_extensions' => 'txt pdf',
    ),
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->databaseContents['node_type'] = array(
      array(
        'type' => 'article',
      ),
      array(
        'type' => 'company',
      ),
    );
    $this->databaseContents['variable'] = array(
      array(
        'name' => 'upload_article',
        'value' => serialize(TRUE),
      ),
      array(
        'name' => 'upload_company',
        'value' => serialize(FALSE),
      ),
      array(
        'name' => 'upload_uploadsize_default',
        'value' => serialize(16),
      ),
      array(
        'name' => 'upload_extensions_default',
        'value' => serialize('txt pdf'),
      ),
    );
    parent::setUp();
  }

}
