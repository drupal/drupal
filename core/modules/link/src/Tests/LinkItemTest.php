<?php

/**
 * @file
 * Contains \Drupal\link\Tests\LinkItemTest.
 */

namespace Drupal\link\Tests;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\field\Tests\FieldUnitTestBase;

/**
 * Tests the new entity API for the link field type.
 *
 * @group link
 */
class LinkItemTest extends FieldUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('link');

  protected function setUp() {
    parent::setUp();

    // Create a link field for validation.
    entity_create('field_storage_config', array(
      'name' => 'field_test',
      'entity_type' => 'entity_test',
      'type' => 'link',
    ))->save();
    entity_create('field_config', array(
      'entity_type' => 'entity_test',
      'field_name' => 'field_test',
      'bundle' => 'entity_test',
    ))->save();
  }

  /**
   * Tests using entity fields of the link field type.
   */
  public function testLinkItem() {
    // Create entity.
    $entity = entity_create('entity_test');
    $url = 'http://www.drupal.org?test_param=test_value';
    $parsed_url = UrlHelper::parse($url);
    $title = $this->randomMachineName();
    $class = $this->randomMachineName();
    $entity->field_test->url = $parsed_url['path'];
    $entity->field_test->title = $title;
    $entity->field_test->first()->get('options')->set('query', $parsed_url['query']);
    $entity->field_test->first()->get('options')->set('attributes', array('class' => $class));
    $entity->name->value = $this->randomMachineName();
    $entity->save();

    // Verify that the field value is changed.
    $id = $entity->id();
    $entity = entity_load('entity_test', $id);
    $this->assertTrue($entity->field_test instanceof FieldItemListInterface, 'Field implements interface.');
    $this->assertTrue($entity->field_test[0] instanceof FieldItemInterface, 'Field item implements interface.');
    $this->assertEqual($entity->field_test->url, $parsed_url['path']);
    $this->assertEqual($entity->field_test[0]->url, $parsed_url['path']);
    $this->assertEqual($entity->field_test->title, $title);
    $this->assertEqual($entity->field_test[0]->title, $title);
    $this->assertEqual($entity->field_test->options['attributes']['class'], $class);
    $this->assertEqual($entity->field_test->options['query'], $parsed_url['query']);

    // Update only the entity name property to check if the link field data will
    // remain intact.
    $entity->name->value = $this->randomMachineName();
    $entity->save();
    $id = $entity->id();
    $entity = entity_load('entity_test', $id);
    $this->assertEqual($entity->field_test->url, $parsed_url['path']);
    $this->assertEqual($entity->field_test->options['attributes']['class'], $class);
    $this->assertEqual($entity->field_test->options['query'], $parsed_url['query']);

    // Verify changing the field value.
    $new_url = 'http://drupal.org';
    $new_title = $this->randomMachineName();
    $new_class = $this->randomMachineName();
    $entity->field_test->url = $new_url;
    $entity->field_test->title = $new_title;
    $entity->field_test->first()->get('options')->set('query', NULL);
    $entity->field_test->first()->get('options')->set('attributes', array('class' => $new_class));
    $this->assertEqual($entity->field_test->url, $new_url);
    $this->assertEqual($entity->field_test->title, $new_title);
    $this->assertEqual($entity->field_test->options['attributes']['class'], $new_class);
    $this->assertNull($entity->field_test->options['query']);

    // Read changed entity and assert changed values.
    $entity->save();
    $entity = entity_load('entity_test', $id);
    $this->assertEqual($entity->field_test->url, $new_url);
    $this->assertEqual($entity->field_test->title, $new_title);
    $this->assertEqual($entity->field_test->options['attributes']['class'], $new_class);

    // Test the generateSampleValue() method.
    $entity = entity_create('entity_test');
    $entity->field_test->generateSampleItems();
    $this->entityValidateAndSave($entity);
  }

}
