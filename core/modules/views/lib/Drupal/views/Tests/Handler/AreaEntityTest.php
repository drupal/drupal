<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Handler\AreaEntityTest.
 */

namespace Drupal\views\Tests\Handler;

use Drupal\views\Tests\ViewTestBase;
use Drupal\views\Tests\ViewUnitTestBase;

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
    $entity_info = $this->container->get('entity.manager')->getDefinitions();

    $expected_entities = array_filter($entity_info, function($info) {
      return !empty($info['controllers']['render']);
    });

    // Test that all expected entity types have data.
    foreach (array_keys($expected_entities) as $entity) {
      $this->assertTrue(!empty($data['entity_' . $entity]), format_string('Views entity area data found for @entity', array('@entity' => $entity)));
      // Test that entity_type is set correctly in the area data.
      $this->assertEqual($entity, $data['entity_' . $entity]['area']['entity_type'], format_string('Correct entity_type set for @entity', array('@entity' => $entity)));
    }

    $expected_entities = array_filter($entity_info, function($info) {
      return empty($info['controllers']['render']);
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
    for ($i = 0; $i < 2; $i++) {
      $random_label = $this->randomName();
      $data = array('bundle' => 'entity_test_render', 'name' => $random_label);
      $entity_test = $this->container->get('entity.manager')->getStorageController('entity_test_render')->create($data);
      $entity_test->save();
      $entities[] = $entity_test;
    }

    $view = views_get_view('test_entity_area');
    $preview = $view->preview('default', array($entities[1]->id()));
    $this->drupalSetContent(drupal_render($preview));

    $result = $this->xpath('//div[@class = "view-header"]');
    $this->assertTrue(strpos(trim((string) $result[0]), $entities[0]->label()) !== FALSE, 'The rendered entity appears in the header of the view.');
    $this->assertTrue(strpos(trim((string) $result[0]), 'full') !== FALSE, 'The rendered entity appeared in the right view mode.');

    $result = $this->xpath('//div[@class = "view-footer"]');
    $this->assertTrue(strpos(trim((string) $result[0]), $entities[1]->label()) !== FALSE, 'The rendered entity appears in the footer of the view.');
    $this->assertTrue(strpos(trim((string) $result[0]), 'full') !== FALSE, 'The rendered entity appeared in the right view mode.');

    // Change the view mode of the area handler.
    $view = views_get_view('test_entity_area');
    $item = $view->getItem('default', 'header', 'entity_entity_test_render');
    $item['view_mode'] = 'test';
    $view->setItem('default', 'header', 'entity_entity_test_render', $item);

    $preview = $view->preview('default', array($entities[1]->id()));
    $this->drupalSetContent(drupal_render($preview));

    $result = $this->xpath('//div[@class = "view-header"]');
    $this->assertTrue(strpos(trim((string) $result[0]), $entities[0]->label()) !== FALSE, 'The rendered entity appears in the header of the view.');
    $this->assertTrue(strpos(trim((string) $result[0]), 'test') !== FALSE, 'The rendered entity appeared in the right view mode.');

    // Test the available view mode options.
    $form = array();
    $form_state = array();
    $form_state['type'] = 'header';
    $view->display_handler->getHandler('header', 'entity_entity_test_render')->buildOptionsForm($form, $form_state);
    $this->assertTrue(isset($form['view_mode']['#options']['full']), 'Ensure that the full view mode is available.');
    $this->assertTrue(isset($form['view_mode']['#options']['test']), 'Ensure that the test view mode is available.');
    $this->assertTrue(isset($form['view_mode']['#options']['default']), 'Ensure that the default view mode is available.');
  }

}
