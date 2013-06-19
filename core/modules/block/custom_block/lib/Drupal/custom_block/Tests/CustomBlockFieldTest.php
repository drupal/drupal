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
   * @var \Drupal\field\Plugin\Core\Entity\Field
   */
  protected $field;

  /**
   * The created instance.
   *
   * @var \Drupal\field\Plugin\Core\Entity\FieldInstance
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
    $this->field = entity_create('field_entity', array(
      'field_name' => drupal_strtolower($this->randomName()),
      'type' => 'link',
      'cardinality' => 2,
    ));
    $this->field->save();
    $this->instance = entity_create('field_instance', array(
      'field_name' => $this->field->id(),
      'entity_type' => 'custom_block',
      'bundle' => 'link',
      'settings' => array(
        'title' => DRUPAL_OPTIONAL,
      ),
    ));
    $this->instance->save();
    entity_get_form_display('custom_block', 'link', 'default')
      ->setComponent($this->field['field_name'], array(
        'type' => 'link_default',
      ))
      ->save();
    entity_get_display('custom_block', 'link', 'default')
      ->setComponent($this->field['field_name'], array(
        'type' => 'link',
        'label' => 'hidden',
      ))
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
      'settings[label]' => $edit['info'],
      'region' => 'sidebar_first',
    );
    $this->drupalPost(NULL, $instance, t('Save block'));
    // Navigate to home page.
    $this->drupalGet('<front>');
    $this->assertLinkByHref('http://example.com');
    $this->assertText('Example.com');
  }

}
