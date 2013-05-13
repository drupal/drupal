<?php

/**
 * @file
 * Contains \Drupal\custom_block\Tests\CustomBlockFieldTest.
 */

namespace Drupal\custom_block\Tests;

/**
 * Tests the block edit functionality.
 *
 * @todo Consider removing this test when https://drupal.org/node/1822000 is
 * fixed.
 */
class CustomBlockFieldTest extends CustomBlockTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block', 'custom_block', 'link');

  /**
   * The created field.
   *
   * @var array
   */
  protected $field;

  /**
   * The created instance
   *
   * @var array
   */
  protected $instance;

  /**
   * The block type.
   *
   * @var \Drupal\custom_block\Plugin\Core\Entity\CustomBlockType
   */
  protected $blockType;


  /**
   * Declares test information.
   */
  public static function getInfo() {
    return array(
      'name' => 'Custom Block field test',
      'description' => 'Test block fieldability.',
      'group' => 'Custom Block',
    );
  }

  /**
   * Checks block edit functionality.
   */
  public function testBlockFields() {
    $this->drupalLogin($this->adminUser);

    $this->blockType = $this->createCustomBlockType('link');

    // Create a field with settings to validate.
    $this->field = array(
      'field_name' => drupal_strtolower($this->randomName()),
      'type' => 'link',
      'cardinality' => 2,
    );
    field_create_field($this->field);
    $this->instance = array(
      'field_name' => $this->field['field_name'],
      'entity_type' => 'custom_block',
      'bundle' => 'link',
      'settings' => array(
        'title' => DRUPAL_OPTIONAL,
      ),
      'widget' => array(
        'type' => 'link_default',
      ),
    );
    $display_options = array(
      'type' => 'link',
      'label' => 'hidden',
    );
    field_create_instance($this->instance);
    entity_get_display('custom_block', 'link', 'default')
      ->setComponent($this->field['field_name'], $display_options)
      ->save();

    // Create a block.
    $this->drupalGet('block/add/link');
    $edit = array(
      'info' => $this->randomName(8),
      $this->field['field_name'] . '[und][0][url]' => 'http://example.com',
      $this->field['field_name'] . '[und][0][title]' => 'Example.com'
    );
    $this->drupalPost(NULL, $edit, t('Save'));
    // Place the block.
    $instance = array(
      'machine_name' => drupal_strtolower($edit['info']),
      'label' => $edit['info'],
      'region' => 'sidebar_first',
    );
    $this->drupalPost(NULL, $instance, t('Save block'));
    // Navigate to home page.
    $this->drupalGet('<front>');
    $this->assertLinkByHref('http://example.com');
    $this->assertText('Example.com');
  }

}
