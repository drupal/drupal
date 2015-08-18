<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Handler\AreaEntityTest.
 */

namespace Drupal\views\Tests\Handler;

use Drupal\block\Entity\Block;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormState;
use Drupal\views\Entity\View;
use Drupal\views\Tests\ViewKernelTestBase;
use Drupal\views\Views;

/**
 * Tests the generic entity area handler.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\area\Entity
 */
class AreaEntityTest extends ViewKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['entity_test', 'user', 'block'];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_entity_area');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpFixtures() {
    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test');
    $this->installConfig(['entity_test']);

    Block::create([
      'id' => 'test_block',
      'plugin' => 'system_main_block',
    ])->save();

    parent::setUpFixtures();
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
    /** @var \Drupal\Core\Entity\EntityInterface[] $entities */
    $entities = array();
    for ($i = 0; $i < 3; $i++) {
      $random_label = $this->randomMachineName();
      $data = array('bundle' => 'entity_test', 'name' => $random_label);
      $entity_test = $this->container->get('entity.manager')
        ->getStorage('entity_test')
        ->create($data);

      $uuid_map[0] = 'aa0c61cb-b7bb-4795-972a-493dabcf529c';
      $uuid_map[1] = '62cef0ff-6f30-4f7a-b9d6-a8ed5a3a6bf3';
      $uuid_map[2] = '3161d6e9-3326-4719-b513-8fa68a731ba2';
      $entity_test->uuid->value = $uuid_map[$i];

      $entity_test->save();
      $entities[] = $entity_test;
      \Drupal::state()
        ->set('entity_test_entity_access.view.' . $entity_test->id(), $i != 2);
    }

    $this->doTestCalculateDependencies();
    $this->doTestRender($entities);
  }

  /**
   * Tests rendering the entity area handler.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   The entities.
   */
  public function doTestRender($entities) {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');
    $view = Views::getView('test_entity_area');
    $preview = $view->preview('default', [$entities[1]->id()]);
    $this->setRawContent(\Drupal::service('renderer')->renderRoot($preview));
    $view_class = 'js-view-dom-id-' . $view->dom_id;
    $header_xpath = '//div[@class = "' . $view_class . '"]/header[1]';
    $footer_xpath = '//div[@class = "' . $view_class . '"]/footer[1]';

    $result = $this->xpath($header_xpath);
    $this->assertTrue(strpos(trim((string) $result[0]), $entities[0]->label()) !== FALSE, 'The rendered entity appears in the header of the view.');
    $this->assertTrue(strpos(trim((string) $result[0]), 'full') !== FALSE, 'The rendered entity appeared in the right view mode.');

    $result = $this->xpath($footer_xpath);
    $this->assertTrue(strpos(trim((string) $result[0]), $entities[1]->label()) !== FALSE, 'The rendered entity appears in the footer of the view.');
    $this->assertTrue(strpos(trim((string) $result[0]), 'full') !== FALSE, 'The rendered entity appeared in the right view mode.');

    $preview = $view->preview('default', array($entities[1]->id()));
    $this->setRawContent($renderer->renderRoot($preview));

    $result = $this->xpath($header_xpath);
    $this->assertTrue(strpos(trim((string) $result[0]), $entities[0]->label()) !== FALSE, 'The rendered entity appears in the header of the view.');
    $this->assertTrue(strpos(trim((string) $result[0]), 'full') !== FALSE, 'The rendered entity appeared in the right view mode.');

    $result = $this->xpath($footer_xpath);
    $this->assertTrue(strpos(trim((string) $result[0]), $entities[1]->label()) !== FALSE, 'The rendered entity appears in the footer of the view.');
    $this->assertTrue(strpos(trim((string) $result[0]), 'full') !== FALSE, 'The rendered entity appeared in the right view mode.');

    // Mark entity_test test view_mode as customizable.
    $entity_view_mode = \Drupal::entityManager()->getStorage('entity_view_mode')->load('entity_test.test');
    $entity_view_mode->enable();
    $entity_view_mode->save();

    // Change the view mode of the area handler.
    $view = Views::getView('test_entity_area');
    $item = $view->getHandler('default', 'header', 'entity_entity_test');
    $item['view_mode'] = 'test';
    $view->setHandler('default', 'header', 'entity_entity_test', $item);

    $preview = $view->preview('default', array($entities[1]->id()));
    $this->setRawContent($renderer->renderRoot($preview));
    $view_class = 'js-view-dom-id-' . $view->dom_id;
    $result = $this->xpath('//div[@class = "' . $view_class . '"]/header[1]');
    $this->assertTrue(strpos(trim((string) $result[0]), $entities[0]->label()) !== FALSE, 'The rendered entity appears in the header of the view.');
    $this->assertTrue(strpos(trim((string) $result[0]), 'test') !== FALSE, 'The rendered entity appeared in the right view mode.');

    // Test entity access.
    $view = Views::getView('test_entity_area');
    $preview = $view->preview('default', array($entities[2]->id()));
    $this->setRawContent($renderer->renderRoot($preview));
    $view_class = 'js-view-dom-id-' . $view->dom_id;
    $result = $this->xpath('//div[@class = "' . $view_class . '"]/footer[1]');
    $this->assertTrue(strpos($result[0], $entities[2]->label()) === FALSE, 'The rendered entity does not appear in the footer of the view.');

    // Test the available view mode options.
    $form = array();
    $form_state = (new FormState())
      ->set('type', 'header');
    $view->display_handler->getHandler('header', 'entity_entity_test')->buildOptionsForm($form, $form_state);
    $this->assertTrue(isset($form['view_mode']['#options']['test']), 'Ensure that the test view mode is available.');
    $this->assertTrue(isset($form['view_mode']['#options']['default']), 'Ensure that the default view mode is available.');
  }

  /**
   * Tests the calculation of the rendered dependencies.
   */
  public function doTestCalculateDependencies() {
    $view = View::load('test_entity_area');

    $dependencies = $view->calculateDependencies();
    // Ensure that both config and content entity dependencies are calculated.
    $this->assertEqual([
      'config' => ['block.block.test_block'],
      'content' => ['entity_test:entity_test:aa0c61cb-b7bb-4795-972a-493dabcf529c'],
    ], $dependencies);
  }

}
