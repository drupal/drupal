<?php

/**
 * @file
 * Contains \Drupal\block_content\Tests\BlockContentFieldTest.
 */

namespace Drupal\block_content\Tests;
use Drupal\Component\Utility\Unicode;

/**
 * Tests block fieldability.
 *
 * @group block_content
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
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorage;

  /**
   * The created field.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * The block type.
   *
   * @var \Drupal\block_content\Entity\BlockContentType
   */
  protected $blockType;


  /**
   * Checks block edit functionality.
   */
  public function testBlockFields() {
    $this->drupalLogin($this->adminUser);

    $this->blockType = $this->createBlockContentType('link');

    // Create a field with settings to validate.
    $this->fieldStorage = entity_create('field_storage_config', array(
      'field_name' => Unicode::strtolower($this->randomMachineName()),
      'entity_type' => 'block_content',
      'type' => 'link',
      'cardinality' => 2,
    ));
    $this->fieldStorage->save();
    $this->field = entity_create('field_config', array(
      'field_storage' => $this->fieldStorage,
      'bundle' => 'link',
      'settings' => array(
        'title' => DRUPAL_OPTIONAL,
      ),
    ));
    $this->field->save();
    entity_get_form_display('block_content', 'link', 'default')
      ->setComponent($this->fieldStorage->getName(), array(
        'type' => 'link_default',
      ))
      ->save();
    entity_get_display('block_content', 'link', 'default')
      ->setComponent($this->fieldStorage->getName(), array(
        'type' => 'link',
        'label' => 'hidden',
      ))
      ->save();

    // Create a block.
    $this->drupalGet('block/add/link');
    $edit = array(
      'info[0][value]' => $this->randomMachineName(8),
      $this->fieldStorage->getName() . '[0][uri]' => 'http://example.com',
      $this->fieldStorage->getName() . '[0][title]' => 'Example.com'
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $block = entity_load('block_content', 1);
    $url = 'admin/structure/block/add/block_content:' . $block->uuid() . '/' . $this->config('system.theme')->get('default');
    // Place the block.
    $instance = array(
      'id' => Unicode::strtolower($edit['info[0][value]']),
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
