<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Plugin\RowEntityTest.
 */

namespace Drupal\views\Tests\Plugin;

use Drupal\Core\Form\FormState;
use Drupal\views\Views;
use Drupal\views\Tests\ViewUnitTestBase;

/**
 * Tests the generic entity row plugin.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\row\EntityRow
 */
class RowEntityTest extends ViewUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('taxonomy', 'text', 'filter', 'field', 'entity', 'system', 'menu_link');

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_entity_row');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('taxonomy_term');
    $this->installConfig(array('taxonomy'));
    \Drupal::service('router.builder')->rebuild();
  }

  /**
   * Tests the entity row handler.
   */
  public function testEntityRow() {
    $vocab = entity_create('taxonomy_vocabulary', array('name' => $this->randomMachineName(), 'vid' => strtolower($this->randomMachineName())));
    $vocab->save();
    $term = entity_create('taxonomy_term', array('name' => $this->randomMachineName(), 'vid' => $vocab->id() ));
    $term->save();

    $view = Views::getView('test_entity_row');
    $this->render($view->preview());

    $this->assertText($term->getName(), 'The rendered entity appears as row in the view.');

    // Tests the available view mode options.
    $form = array();
    $form_state = new FormState();
    $form_state['view'] = $view->storage;
    $view->rowPlugin->buildOptionsForm($form, $form_state);

    $this->assertTrue(isset($form['view_mode']['#options']['default']), 'Ensure that the default view mode is available');
  }

}
