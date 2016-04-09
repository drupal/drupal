<?php

namespace Drupal\Tests\file\Unit\Plugin\migrate\source\d6;

use Drupal\file\Plugin\migrate\source\d6\Upload;
use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests d6_upload source plugin.
 *
 * @group file
 */
class UploadTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = Upload::class;

  protected $migrationConfiguration = array(
    'id' => 'test',
    'source' => array(
      'plugin' => 'd6_upload',
    ),
  );

  protected $expectedResults = array(
    array(
      'upload' => array(
        array(
          'fid' => '1',
          'description' => 'file 1-1-1',
          'list' => '0',
        ),
      ),
      'nid' => '1',
      'vid' => '1',
      'type' => 'story',
    ),
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->databaseContents['upload'] = array(
      array(
        'fid' => '1',
        'nid' => '1',
        'vid' => '1',
        'description' => 'file 1-1-1',
        'list' => '0',
        'weight' => '-1',
      ),
    );
    $this->databaseContents['node'] = array(
      array(
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
      ),
    );
    parent::setUp();
  }

}
