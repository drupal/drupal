<?php

/**
 * @file
 * Definition of Drupal\node\Tests\MultiStepNodeFormBasicOptionsTest.
 */

namespace Drupal\node\Tests;

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
  protected $field_name;

  /**
   * Tests changing the default values of basic options to ensure they persist.
   */
  function testMultiStepNodeFormBasicOptions() {
    // Prepare a user to create the node.
    $web_user = $this->drupalCreateUser(array('administer nodes', 'create page content'));
    $this->drupalLogin($web_user);

    // Create an unlimited cardinality field.
    $this->field_name = drupal_strtolower($this->randomMachineName());
    entity_create('field_storage_config', array(
      'name' => $this->field_name,
      'entity_type' => 'node',
      'type' => 'text',
      'cardinality' => -1,
    ))->save();

    // Attach an instance of the field to the page content type.
    entity_create('field_config', array(
      'field_name' => $this->field_name,
      'entity_type' => 'node',
      'bundle' => 'page',
      'label' => $this->randomMachineName() . '_label',
    ))->save();
    entity_get_form_display('node', 'page', 'default')
      ->setComponent($this->field_name, array(
        'type' => 'text_textfield',
      ))
      ->save();

    $edit = array(
      'title[0][value]' => 'a',
      'promote[value]' => FALSE,
      'sticky[value]' => 1,
      "{$this->field_name}[0][value]" => $this->randomString(32),
    );
    $this->drupalPostForm('node/add/page', $edit, t('Add another item'));
    $this->assertNoFieldChecked('edit-promote-value', 'Promote stayed unchecked');
    $this->assertFieldChecked('edit-sticky-value', 'Sticky stayed checked');
  }

}
