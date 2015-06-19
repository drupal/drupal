<?php

/**
 * @file
 * Contains \Drupal\Tests\migrate_drupal\Unit\source\d6\ViewModeTest.
 */

namespace Drupal\Tests\migrate_drupal\Unit\source\d6;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests D6 view mode source plugin.
 *
 * @group migrate_drupal
 */
class ViewModeTest extends MigrateSqlSourceTestCase {

  // The plugin system is not working during unit testing so the source plugin
  // class needs to be manually specified.
  const PLUGIN_CLASS = 'Drupal\migrate_drupal\Plugin\migrate\source\d6\ViewMode';

  // The fake Migration configuration entity.
  protected $migrationConfiguration = array(
    // The ID of the entity, can be any string.
    'id' => 'view_mode_test',
    // Leave it empty for now.
    'idlist' => array(),
    'source' => array(
      'plugin' => 'd6_field_instance_view_mode',
    ),
  );

  protected $expectedResults = array(
    array(
      'entity_type' => 'node',
      'view_mode' => '4',
    ),
    array(
      'entity_type' => 'node',
      'view_mode' => 'teaser',
    ),
    array(
      'entity_type' => 'node',
      'view_mode' => 'full',
    ),
  );


  /**
   * {@inheritdoc}
   */
  protected function setUp() {

    $this->databaseContents['content_node_field_instance'][] = array(
      'display_settings' => serialize(array(
        'weight' => '31',
        'parent' => '',
        'label' => array(
          'format' => 'above',
        ),
        'teaser' => array(
          'format' => 'default',
          'exclude' => 0,
        ),
        'full' => array(
          'format' => 'default',
          'exclude' => 0,
        ),
        4 => array(
          'format' => 'default',
          'exclude' => 0,
        ),
      )),
    );

    parent::setUp();
  }

}
