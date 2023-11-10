<?php

namespace Drupal\Tests\views\Kernel;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\views\Views;

/**
 * Tests the preprocessing functionality in views.theme.inc.
 *
 * @group views
 */
class ViewsPreprocessTest extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_preprocess'];

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test', 'user', 'node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp();

    $this->installEntitySchema('entity_test');
  }

  /**
   * Tests css classes on displays are cleaned correctly.
   */
  public function testCssClassCleaning() {
    \Drupal::service('theme_installer')->install(['test_theme']);
    $this->config('system.theme')->set('default', 'test_theme')->save();

    $entity = EntityTest::create();
    $entity->save();
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');

    $view = Views::getView('test_preprocess');
    $build = $view->buildRenderable();
    $renderer->renderRoot($build);
    $this->assertStringContainsString('class="entity-test--default entity-test__default', (string) $build['#markup']);
    $view->destroy();

    $view->setDisplay('display_2');
    $build = $view->buildRenderable();
    $renderer->renderRoot($build);
    $markup = (string) $build['#markup'];
    $this->assertStringContainsString('css_class: entity-test--default and-another-class entity-test__default', $markup);
    $this->assertStringContainsString('attributes: class="entity-test--default and-another-class entity-test__default', $markup);
  }

}
