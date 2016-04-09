<?php

namespace Drupal\Tests\link\Kernel;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;
use Drupal\link\LinkItemInterface;

/**
 * Tests the new entity API for the link field type.
 *
 * @group link
 */
class LinkItemTest extends FieldKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('link');

  protected function setUp() {
    parent::setUp();

    // Create a generic, external, and internal link fields for validation.
    FieldStorageConfig::create([
      'field_name' => 'field_test',
      'entity_type' => 'entity_test',
      'type' => 'link',
    ])->save();
    FieldConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'field_test',
      'bundle' => 'entity_test',
      'settings' => ['link_type' => LinkItemInterface::LINK_GENERIC],
    ])->save();
    FieldStorageConfig::create([
      'field_name' => 'field_test_external',
      'entity_type' => 'entity_test',
      'type' => 'link',
    ])->save();
    FieldConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'field_test_external',
      'bundle' => 'entity_test',
      'settings' => ['link_type' => LinkItemInterface::LINK_EXTERNAL],
    ])->save();
    FieldStorageConfig::create([
      'field_name' => 'field_test_internal',
      'entity_type' => 'entity_test',
      'type' => 'link',
    ])->save();
    FieldConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'field_test_internal',
      'bundle' => 'entity_test',
      'settings' => ['link_type' => LinkItemInterface::LINK_INTERNAL],
    ])->save();
  }

  /**
   * Tests using entity fields of the link field type.
   */
  public function testLinkItem() {
    // Create entity.
    $entity = EntityTest::create();
    $url = 'https://www.drupal.org?test_param=test_value';
    $parsed_url = UrlHelper::parse($url);
    $title = $this->randomMachineName();
    $class = $this->randomMachineName();
    $entity->field_test->uri = $parsed_url['path'];
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
    $this->assertEqual($entity->field_test->uri, $parsed_url['path']);
    $this->assertEqual($entity->field_test[0]->uri, $parsed_url['path']);
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
    $this->assertEqual($entity->field_test->uri, $parsed_url['path']);
    $this->assertEqual($entity->field_test->options['attributes']['class'], $class);
    $this->assertEqual($entity->field_test->options['query'], $parsed_url['query']);

    // Verify changing the field value.
    $new_url = 'https://www.drupal.org';
    $new_title = $this->randomMachineName();
    $new_class = $this->randomMachineName();
    $entity->field_test->uri = $new_url;
    $entity->field_test->title = $new_title;
    $entity->field_test->first()->get('options')->set('query', NULL);
    $entity->field_test->first()->get('options')->set('attributes', array('class' => $new_class));
    $this->assertEqual($entity->field_test->uri, $new_url);
    $this->assertEqual($entity->field_test->title, $new_title);
    $this->assertEqual($entity->field_test->options['attributes']['class'], $new_class);
    $this->assertNull($entity->field_test->options['query']);

    // Read changed entity and assert changed values.
    $entity->save();
    $entity = entity_load('entity_test', $id);
    $this->assertEqual($entity->field_test->uri, $new_url);
    $this->assertEqual($entity->field_test->title, $new_title);
    $this->assertEqual($entity->field_test->options['attributes']['class'], $new_class);

    // Check that if we only set uri the default values for title and options
    // are also initialized.
    $entity->field_test = ['uri' => 'internal:/node/add'];
    $this->assertEqual($entity->field_test->uri, 'internal:/node/add');
    $this->assertNull($entity->field_test->title);
    $this->assertIdentical($entity->field_test->options, []);

    // Check that if set uri and serialize options then the default values are
    // properly initialized.
    $entity->field_test = [
      'uri' => 'internal:/node/add',
      'options' => serialize(['query' => NULL]),
    ];
    $this->assertEqual($entity->field_test->uri, 'internal:/node/add');
    $this->assertNull($entity->field_test->title);
    $this->assertNull($entity->field_test->options['query']);

    // Check that if we set the direct value of link field it correctly set the
    // uri and the default values of the field.
    $entity->field_test = 'internal:/node/add';
    $this->assertEqual($entity->field_test->uri, 'internal:/node/add');
    $this->assertNull($entity->field_test->title);
    $this->assertIdentical($entity->field_test->options, []);

    // Check that setting LinkItem value NULL doesn't generate any error or
    // warning.
    $entity->field_test[0] = NULL;
    $this->assertNull($entity->field_test[0]->getValue());

    // Test the generateSampleValue() method for generic, external, and internal
    // link types.
    $entity = EntityTest::create();
    $entity->field_test->generateSampleItems();
    $entity->field_test_external->generateSampleItems();
    $entity->field_test_internal->generateSampleItems();
    $this->entityValidateAndSave($entity);
  }

}
