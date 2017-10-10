<?php

namespace Drupal\Tests\node\Functional;

use Drupal\Component\Utility\Unicode;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests the persistence of basic options through multiple steps.
 *
 * @group node
 */
class MultiStepNodeFormBasicOptionsTest extends NodeTestBase {

  /**
   * The field name to create.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * Tests changing the default values of basic options to ensure they persist.
   */
  public function testMultiStepNodeFormBasicOptions() {
    // Prepare a user to create the node.
    $web_user = $this->drupalCreateUser(['administer nodes', 'create page content']);
    $this->drupalLogin($web_user);

    // Create an unlimited cardinality field.
    $this->fieldName = Unicode::strtolower($this->randomMachineName());
    FieldStorageConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => 'node',
      'type' => 'text',
      'cardinality' => -1,
    ])->save();

    // Attach an instance of the field to the page content type.
    FieldConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => 'node',
      'bundle' => 'page',
      'label' => $this->randomMachineName() . '_label',
    ])->save();
    entity_get_form_display('node', 'page', 'default')
      ->setComponent($this->fieldName, [
        'type' => 'text_textfield',
      ])
      ->save();

    $edit = [
      'title[0][value]' => 'a',
      'promote[value]' => FALSE,
      'sticky[value]' => 1,
      "{$this->fieldName}[0][value]" => $this->randomString(32),
    ];
    $this->drupalPostForm('node/add/page', $edit, t('Add another item'));
    $this->assertNoFieldChecked('edit-promote-value', 'Promote stayed unchecked');
    $this->assertFieldChecked('edit-sticky-value', 'Sticky stayed checked');
  }

}
