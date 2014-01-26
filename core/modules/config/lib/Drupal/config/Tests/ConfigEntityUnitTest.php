<?php

/**
 * @file
 * Contains Drupal\config\Tests\ConfigEntityUnitTest.
 */

namespace Drupal\config\Tests;

use Drupal\Core\Entity\EntityInterface;
use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Unit tests for configuration controllers and objects.
 */
class ConfigEntityUnitTest extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('config_test');

  /**
   * The config_test entity storage controller.
   *
   * @var \Drupal\Core\Config\Entity\ConfigStorageControllerInterface
   */
  protected $storage;

  public static function getInfo() {
    return array(
      'name' => 'Configuration entity methods',
      'description' => 'Unit tests for configuration entity base methods.',
      'group' => 'Configuration',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->storage = $this->container->get('entity.manager')->getStorageController('config_test');
  }

  /**
   * Tests storage controller methods.
   */
  public function testStorageControllerMethods() {
    $info = \Drupal::entityManager()->getDefinition('config_test');

    $expected = $info->getConfigPrefix() . '.';
    $this->assertIdentical($this->storage->getConfigPrefix(), $expected);

    // Test the static extractID() method.
    $expected_id = 'test_id';
    $config_name = $info->getConfigPrefix() . '.' . $expected_id;
    $storage = $this->storage;
    $this->assertIdentical($storage::getIDFromConfigName($config_name, $info->getConfigPrefix()), $expected_id);

    // Create three entities, two with the same style.
    $style = $this->randomName(8);
    for ($i = 0; $i < 2; $i++) {
      $entity = $this->storage->create(array(
        'id' => $this->randomName(),
        'label' => $this->randomString(),
        'style' => $style,
      ));
      $entity->save();
    }
    $entity = $this->storage->create(array(
      'id' => $this->randomName(),
      'label' => $this->randomString(),
      // Use a different length for the entity to ensure uniqueness.
      'style' => $this->randomName(9),
    ));
    $entity->save();

    $entities = $this->storage->loadByProperties();
    $this->assertEqual(count($entities), 3, 'Three entities are loaded when no properties are specified.');

    $entities = $this->storage->loadByProperties(array('style' => $style));
    $this->assertEqual(count($entities), 2, 'Two entities are loaded when the style property is specified.');

    // Assert that both returned entities have a matching style property.
    foreach ($entities as $entity) {
      $this->assertIdentical($entity->get('style'), $style, 'The loaded entity has the correct style value specified.');
    }
  }

  /**
   * Tests getOriginalId() and setOriginalId().
   */
  protected function testGetOriginalId() {
    $entity = $this->storage->create(array());
    $id = $this->randomName();
    $this->assertIdentical(spl_object_hash($entity->setOriginalId($id)), spl_object_hash($entity));
    $this->assertIdentical($entity->getOriginalId(), $id);
  }

}
