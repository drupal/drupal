<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Entity\EntityViewControllerTest.
 */

namespace Drupal\system\Tests\Entity;

use Drupal\simpletest\WebTestBase;
use Drupal\Core\Language\Language;

/**
 * Tests \Drupal\Core\Entity\Controller\EntityViewController.
 */
class EntityViewControllerTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity_test');

  /**
   * Array of test entities.
   *
   * @var array
   */
  protected $entities = array();

  public static function getInfo() {
    return array(
      'name' => 'Entity View Controller',
      'description' => 'Tests EntityViewController functionality.',
      'group' => 'Entity API',
    );
  }

  function setUp() {
    parent::setUp();
    // Create some dummy entity_test_render entities.
    for ($i = 0; $i < 2; $i++) {
      $random_label = $this->randomName();
      $data = array('bundle' => 'entity_test_render', 'name' => $random_label);
      $entity_test = $this->container->get('entity.manager')->getStorageController('entity_test_render')->create($data);
      $entity_test->save();
      $this->entities[] = $entity_test;
    }

  }

  /**
   * Tests EntityViewController.
   */
  function testEntityViewController() {
    foreach ($this->entities as $entity) {
      $this->drupalGet('entity-test-render/' . $entity->id());
      $this->assertRaw($entity->label());
      $this->assertRaw('full');

      $this->drupalGet('entity-test-render-converter/' . $entity->id());
      $this->assertRaw($entity->label());
      $this->assertRaw('full');

      $this->drupalGet('entity-test-render-no-view-mode/' . $entity->id());
      $this->assertRaw($entity->label());
      $this->assertRaw('full');
    }
  }
}
