<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Handler\AreaEntityTest.
 */

namespace Drupal\views\Tests\Handler;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\views\Tests\ViewTestBase;
use Drupal\views\Views;

/**
 * Tests the generic entity area handler.
 *
 * @see \Drupal\views\Plugin\views\area\Entity
 */
class AreaEntityTest extends ViewTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity_test');

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_entity_area');

  public static function getInfo() {
    return array(
      'name' => 'Area: Entity',
      'description' => 'Tests the generic entity area handler.',
      'group' => 'Views Handlers',
    );
  }

  protected function setUp() {
    parent::setUp();

    $this->enableViewsTestModule();
  }

  /**
   * Tests views data for entity area handlers.
   */
  public function testEntityAreaData() {
    $data = $this->container->get('views.views_data')->get('views');
    $entity_types = $this->container->get('entity.manager')->getDefinitions();

    $expected_entities = array_filter($entity_types, function (EntityTypeInterface $entity_type) {
      return $entity_type->hasViewBuilderClass();
    });

    // Test that all expected entity types have data.
    foreach (array_keys($expected_entities) as $entity) {
      $this->assertTrue(!empty($data['entity_' . $entity]), format_string('Views entity area data found for @entity', array('@entity' => $entity)));
      // Test that entity_type is set correctly in the area data.
      $this->assertEqual($entity, $data['entity_' . $entity]['area']['entity_type'], format_string('Correct entity_type set for @entity', array('@entity' => $entity)));
    }

    $expected_entities = array_filter($entity_types, function (EntityTypeInterface $type) {
      return !$type->hasViewBuilderClass();
    });

    // Test that no configuration entity types have data.
    foreach (array_keys($expected_entities) as $entity) {
      $this->assertTrue(empty($data['entity_' . $entity]), format_string('Views config entity area data not found for @entity', array('@entity' => $entity)));
    }
  }

  /**
   * Tests the area handler.
   */
  public function testEntityArea() {

    $entities = array();
    for ($i = 0; $i < 3; $i++) {
      $random_label = $this->randomName();
      $data = array('bundle' => 'entity_test', 'name' => $random_label);
      $entity_test = $this->container->get('entity.manager')->getStorageController('entity_test')->create($data);
      $entity_test->save();
      $entities[] = $entity_test;
      \Drupal::state()->set('entity_test_entity_access.view.' . $entity_test->id(), $i != 2);
    }

    $view = Views::getView('test_entity_area');
    $preview = $view->preview('default', array($entities[1]->id()));
    $this->drupalSetContent(drupal_render($preview));

    $result = $this->xpath('//div[@class = "view-header"]');
    $this->assertTrue(strpos(trim((string) $result[0]), $entities[0]->label()) !== FALSE, 'The rendered entity appears in the header of the view.');
    $this->assertTrue(strpos(trim((string) $result[0]), 'full') !== FALSE, 'The rendered entity appeared in the right view mode.');

    $result = $this->xpath('//div[@class = "view-footer"]');
    $this->assertTrue(strpos(trim((string) $result[0]), $entities[1]->label()) !== FALSE, 'The rendered entity appears in the footer of the view.');
    $this->assertTrue(strpos(trim((string) $result[0]), 'full') !== FALSE, 'The rendered entity appeared in the right view mode.');

    // Change the view mode of the area handler.
    $view = Views::getView('test_entity_area');
    $item = $view->getHandler('default', 'header', 'entity_entity_test');
    $item['view_mode'] = 'test';
    $view->setHandler('default', 'header', 'entity_entity_test', $item);

    $preview = $view->preview('default', array($entities[1]->id()));
    $this->drupalSetContent(drupal_render($preview));

    $result = $this->xpath('//div[@class = "view-header"]');
    $this->assertTrue(strpos(trim((string) $result[0]), $entities[0]->label()) !== FALSE, 'The rendered entity appears in the header of the view.');
    $this->assertTrue(strpos(trim((string) $result[0]), 'test') !== FALSE, 'The rendered entity appeared in the right view mode.');

    // Test entity access.
    $view = Views::getView('test_entity_area');
    $preview = $view->preview('default', array($entities[2]->id()));
    $this->drupalSetContent(drupal_render($preview));
    $result = $this->xpath('//div[@class = "view-footer"]');
    $this->assertTrue(strpos($result[0], $entities[2]->label()) === FALSE, 'The rendered entity does not appear in the footer of the view.');

    // Test the available view mode options.
    $form = array();
    $form_state = array();
    $form_state['type'] = 'header';
    $view->display_handler->getHandler('header', 'entity_entity_test')->buildOptionsForm($form, $form_state);
    $this->assertTrue(isset($form['view_mode']['#options']['full']), 'Ensure that the full view mode is available.');
    $this->assertTrue(isset($form['view_mode']['#options']['test']), 'Ensure that the test view mode is available.');
    $this->assertTrue(isset($form['view_mode']['#options']['default']), 'Ensure that the default view mode is available.');
  }

}
