<?php

namespace Drupal\Tests\image\Unit\Plugin\migrate\source\d6;

use Drupal\image\Plugin\migrate\source\d6\ImageCachePreset;
use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests the d6_imagecache_presets source plugin.
 *
 * @group image
 */
class ImageCachePresetTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = ImageCachePreset::class;

  protected $migrationConfiguration = array(
    'id' => 'test',
    'source' => array(
      'plugin' => 'd6_imagecache_presets',
    ),
  );

  protected $expectedResults = array(
    array(
      'presetid' => '1',
      'presetname' => 'slackjaw_boys',
      'actions' => array(
        array(
          'actionid' => '3',
          'presetid' => '1',
          'weight' => '0',
          'module' => 'imagecache',
          'action' => 'imagecache_scale_and_crop',
          'data' => array(
            'width' => '100%',
            'height' => '100%',
          ),
        ),
      ),
    ),
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->databaseContents['imagecache_preset'] = array(
      array(
        'presetid' => '1',
        'presetname' => 'slackjaw_boys',
      ),
    );
    $this->databaseContents['imagecache_action'] = array(
      array(
        'actionid' => '3',
        'presetid' => '1',
        'weight' => '0',
        'module' => 'imagecache',
        'action' => 'imagecache_scale_and_crop',
        'data' => 'a:2:{s:5:"width";s:4:"100%";s:6:"height";s:4:"100%";}',
      ),
    );
    parent::setUp();
  }

}
