<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Upgrade\FieldUpgradePathTest.
 */

namespace Drupal\system\Tests\Upgrade;

/**
 * Tests upgrade of system variables.
 */
class FieldUpgradePathTest extends UpgradePathTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Field upgrade test',
      'description' => 'Tests upgrade of Field API.',
      'group' => 'Upgrade path',
    );
  }

  public function setUp() {
    $this->databaseDumpFiles = array(
      drupal_get_path('module', 'system') . '/tests/upgrade/drupal-7.bare.standard_all.database.php.gz',
      drupal_get_path('module', 'system') . '/tests/upgrade/drupal-7.field.database.php',
    );
    parent::setUp();
  }

  /**
   * Tests upgrade of entity displays.
   */
  public function testEntityDisplayUpgrade() {
    $this->assertTrue($this->performUpgrade(), 'The upgrade was completed successfully.');

    // Check that the configuration entries were created.
    $displays = array(
      'default' => config('entity.display.node.article.default')->get(),
      'teaser' => config('entity.display.node.article.teaser')->get(),
    );
    $this->assertTrue(!empty($displays['default']));
    $this->assertTrue(!empty($displays['teaser']));

    // Check that manifest entries for the 'article' node type were correctly
    // created.
    $manifest = config('manifest.entity.display');
    $data = $manifest->get();
    $this->assertEqual($data['node.article.default'], array('name' => 'entity.display.node.article.default'));
    $this->assertEqual($data['node.article.teaser'], array('name' => 'entity.display.node.article.teaser'));

    // Check that the 'body' field is configured as expected.
    $expected = array(
      'default' => array(
        'label' => 'hidden',
        'type' => 'text_default',
        'weight' => 0,
        'settings' => array(),
      ),
      'teaser' => array(
        'label' => 'hidden',
        'type' => 'text_summary_or_trimmed',
        'weight' => 0,
        'settings' => array(
          'trim_length' => 600,
        ),
      ),
    );
    $this->assertEqual($displays['default']['content']['body'], $expected['default']);
    $this->assertEqual($displays['teaser']['content']['body'], $expected['teaser']);

    // Check that the display key in the instance data was removed.
    $body_instance = field_info_instance('node', 'body', 'article');
    $this->assertTrue(!isset($body_instance['display']));

    // Check that the 'language' extra field is configured as expected.
    $expected = array(
      'default' => array(
        'weight' => -1,
        'visible' => 1,
      ),
      'teaser' => array(
        'visible' => 0,
      ),
    );
    $this->assertEqual($displays['default']['content']['language'], $expected['default']);
    $this->assertEqual($displays['teaser']['content']['language'], $expected['teaser']);
  }

}
