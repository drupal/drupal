<?php

/**
 * @file
 * Contains \Drupal\block_content\Tests\BlockContentFieldTest.
 */

namespace Drupal\block_content\Tests;

/**
 * Tests the block edit functionality.
 *
 * @todo Consider removing this test when https://drupal.org/node/1822000 is
 * fixed.
 */
class BlockContentFieldTest extends BlockContentTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block', 'block_content', 'link');

  /**
   * The created field.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * The created instance.
   *
   * @var \Drupal\field\Entity\FieldInstanceConfig
   */
  protected $instance;

  /**
   * The block type.
   *
   * @var \Drupal\block_content\Entity\BlockContentType
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

    $this->blockType = $this->createBlockContentType('link');

    // Create a field with settings to validate.
    $this->field = entity_create('field_config', array(
      'name' => drupal_strtolower($this->randomName()),
      'entity_type' => 'block_content',
      'type' => 'link',
      'cardinality' => 2,
    ));
    $this->field->save();
    $this->instance = entity_create('field_instance_config', array(
      'field' => $this->field,
      'bundle' => 'link',
      'settings' => array(
        'title' => DRUPAL_OPTIONAL,
      ),
    ));
    $this->instance->save();
    entity_get_form_display('block_content', 'link', 'default')
      ->setComponent($this->field->getName(), array(
        'type' => 'link_default',
      ))
      ->save();
    entity_get_display('block_content', 'link', 'default')
      ->setComponent($this->field->getName(), array(
        'type' => 'link',
        'label' => 'hidden',
      ))
      ->save();

    // Create a block.
    $this->drupalGet('block/add/link');
    $edit = array(
      'info[0][value]' => $this->randomName(8),
      $this->field->getName() . '[0][url]' => 'http://example.com',
      $this->field->getName() . '[0][title]' => 'Example.com'
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $block = entity_load('block_content', 1);
    $url = 'admin/structure/block/add/block_content:' . $block->uuid() . '/' . \Drupal::config('system.theme')->get('default');
    // Place the block.
    $instance = array(
      'id' => drupal_strtolower($edit['info[0][value]']),
      'settings[label]' => $edit['info[0][value]'],
      'region' => 'sidebar_first',
    );
    $this->drupalPostForm($url, $instance, t('Save block'));
    // Navigate to home page.
    $this->drupalGet('<front>');
    $this->assertLinkByHref('http://example.com');
    $this->assertText('Example.com');
  }

}
