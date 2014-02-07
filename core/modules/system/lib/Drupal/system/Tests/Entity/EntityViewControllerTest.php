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
    // Create some dummy entity_test entities.
    for ($i = 0; $i < 2; $i++) {
      $random_label = $this->randomName();
      $data = array('bundle' => 'entity_test', 'name' => $random_label);
      $entity_test = $this->container->get('entity.manager')->getStorageController('entity_test')->create($data);
      $entity_test->save();
      $this->entities[] = $entity_test;
    }

  }

  /**
   * Tests EntityViewController.
   */
  function testEntityViewController() {
    foreach ($this->entities as $entity) {
      $this->drupalGet('entity_test/' . $entity->id());
      $this->assertRaw($entity->label());
      $this->assertRaw('full');

      $this->drupalGet('entity_test_converter/' . $entity->id());
      $this->assertRaw($entity->label());
      $this->assertRaw('full');

      $this->drupalGet('entity_test_no_view_mode/' . $entity->id());
      $this->assertRaw($entity->label());
      $this->assertRaw('full');
    }
  }

  /**
   * Tests field item attributes.
   */
  public function testFieldItemAttributes() {
    // Make sure the test field will be rendered.
    entity_get_display('entity_test', 'entity_test', 'default')
      ->setComponent('field_test_text', array('type' => 'text_default'))
      ->save();

    // Create an entity and save test value in field_test_text.
    $test_value = $this->randomName();
    $entity = entity_create('entity_test');
    $entity->field_test_text = $test_value;
    $entity->save();

    // Browse to the entity and verify that the attribute is rendered in the
    // field item HTML markup.
    $this->drupalGet('entity_test/' . $entity->id());
    $xpath = $this->xpath('//div[@data-field-item-attr="foobar" and text()=:value]', array(':value' => $test_value));
    $this->assertTrue($xpath, 'The field item attribute has been found in the rendered output of the field.');

    // Enable the RDF module to ensure that two modules can add attributes to
    // the same field item.
    \Drupal::moduleHandler()->install(array('rdf'));
    // Set an RDF mapping for the field_test_text field. This RDF mapping will
    // be turned into RDFa attributes in the field item output.
    $mapping = rdf_get_mapping('entity_test', 'entity_test');
    $mapping->setFieldMapping('field_test_text', array(
      'properties' => array('schema:text'),
    ))->save();
    // Browse to the entity and verify that the attributes from both modules
    // are rendered in the field item HTML markup.
    $this->drupalGet('entity_test/' . $entity->id());
    $xpath = $this->xpath('//div[@data-field-item-attr="foobar" and @property="schema:text" and text()=:value]', array(':value' => $test_value));
    $this->assertTrue($xpath, 'The field item attributes from both modules have been found in the rendered output of the field.');
  }

}
