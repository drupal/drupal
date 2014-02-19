<?php

/**
 * @file
 * Contains \Drupal\link\Tests\LinkItemTest.
 */

namespace Drupal\link\Tests;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\field\Tests\FieldUnitTestBase;

/**
 * Tests the new entity API for the link field type.
 */
class LinkItemTest extends FieldUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('link');

  public static function getInfo() {
    return array(
      'name' => 'Link field item',
      'description' => 'Tests the new entity API for the link field type.',
      'group' => 'Field types',
    );
  }

  public function setUp() {
    parent::setUp();

    // Create an link field and instance for validation.
    entity_create('field_config', array(
      'name' => 'field_test',
      'entity_type' => 'entity_test',
      'type' => 'link',
    ))->save();
    entity_create('field_instance_config', array(
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
    $url = 'http://www.drupal.org';
    $title = $this->randomName();
    $class = $this->randomName();
    $entity->field_test->url = $url;
    $entity->field_test->title = $title;
    $entity->field_test->first()->get('attributes')->set('class', $class);
    $entity->name->value = $this->randomName();
    $entity->save();

    // Verify that the field value is changed.
    $id = $entity->id();
    $entity = entity_load('entity_test', $id);
    $this->assertTrue($entity->field_test instanceof FieldItemListInterface, 'Field implements interface.');
    $this->assertTrue($entity->field_test[0] instanceof FieldItemInterface, 'Field item implements interface.');
    $this->assertEqual($entity->field_test->url, $url);
    $this->assertEqual($entity->field_test[0]->url, $url);
    $this->assertEqual($entity->field_test->title, $title);
    $this->assertEqual($entity->field_test[0]->title, $title);
    $this->assertEqual($entity->field_test->attributes['class'], $class);

    // Verify changing the field value.
    $new_url = 'http://drupal.org';
    $new_title = $this->randomName();
    $new_class = $this->randomName();
    $entity->field_test->url = $new_url;
    $entity->field_test->title = $new_title;
    $entity->field_test->first()->get('attributes')->set('class', $new_class);
    $this->assertEqual($entity->field_test->url, $new_url);
    $this->assertEqual($entity->field_test->title, $new_title);
    $this->assertEqual($entity->field_test->attributes['class'], $new_class);

    // Read changed entity and assert changed values.
    $entity->save();
    $entity = entity_load('entity_test', $id);
    $this->assertEqual($entity->field_test->url, $new_url);
    $this->assertEqual($entity->field_test->title, $new_title);
    $this->assertEqual($entity->field_test->attributes['class'], $new_class);
  }

}
