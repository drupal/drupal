<?php

namespace Drupal\Tests\views\Kernel\Plugin;

use Drupal\Core\Form\FormState;
use Drupal\views\Views;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\Entity\Term;

/**
 * Tests the generic entity row plugin.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\row\EntityRow
 */
class RowEntityTest extends ViewsKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['taxonomy', 'text', 'filter', 'field', 'system', 'node', 'user'];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_entity_row'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp();

    $this->installEntitySchema('taxonomy_term');
    $this->installConfig(['taxonomy']);
    \Drupal::service('router.builder')->rebuild();
  }

  /**
   * Tests the entity row handler.
   */
  public function testEntityRow() {
    $vocab = Vocabulary::create(['name' => $this->randomMachineName(), 'vid' => strtolower($this->randomMachineName())]);
    $vocab->save();
    $term = Term::create(['name' => $this->randomMachineName(), 'vid' => $vocab->id()]);
    $term->save();

    $view = Views::getView('test_entity_row');
    $build = $view->preview();
    $this->render($build);

    $this->assertText($term->getName(), 'The rendered entity appears as row in the view.');

    // Tests the available view mode options.
    $form = [];
    $form_state = new FormState();
    $form_state->set('view', $view->storage);
    $view->rowPlugin->buildOptionsForm($form, $form_state);

    $this->assertTrue(isset($form['view_mode']['#options']['default']), 'Ensure that the default view mode is available');
  }

}
