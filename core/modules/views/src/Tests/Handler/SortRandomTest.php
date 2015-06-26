<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Handler\SortRandomTest.
 */

namespace Drupal\views\Tests\Handler;

use Drupal\Core\Cache\Cache;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Tests\ViewUnitTestBase;
use Drupal\views\Views;

/**
 * Tests for core Drupal\views\Plugin\views\sort\Random handler.
 *
 * @group views
 */
class SortRandomTest extends ViewUnitTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view');

  /**
   * Add more items to the test set, to make the order tests more robust.
   *
   * In total we have then 60 entries, which makes a probability of a collision
   * of 1/60!, which is around 1/1E80, which is higher than the estimated amount
   * of protons / electrons in the observable universe, also called the
   * eddington number.
   *
   * @see http://en.wikipedia.org/wiki/Eddington_number
   */
  protected function dataSet() {
    $data = parent::dataSet();
    for ($i = 0; $i < 55; $i++) {
      $data[] = array(
        'name' => 'name_' . $i,
        'age' => $i,
        'job' => 'job_' . $i,
        'created' => rand(0, time()),
        'status' => 1,
      );
    }
    return $data;
  }

  /**
   * Return a basic view with random ordering.
   */
  protected function getBasicRandomView() {
    $view = Views::getView('test_view');
    $view->setDisplay();

    // Add a random ordering.
    $view->displayHandlers->get('default')->overrideOption('sorts', array(
      'random' => array(
        'id' => 'random',
        'field' => 'random',
        'table' => 'views',
      ),
    ));

    return $view;
  }

  /**
   * Tests random ordering of the result set.
   *
   * @see DatabaseSelectTestCase::testRandomOrder()
   */
  public function testRandomOrdering() {
    // Execute a basic view first.
    $view = Views::getView('test_view');
    $this->executeView($view);

    // Verify the result.
    $this->assertEqual(count($this->dataSet()), count($view->result), 'The number of returned rows match.');
    $this->assertIdenticalResultset($view, $this->dataSet(), array(
      'views_test_data_name' => 'name',
      'views_test_data_age' => 'age',
    ));

    // Execute a random view, we expect the result set to be different.
    $view_random = $this->getBasicRandomView();
    $this->executeView($view_random);
    $this->assertEqual(count($this->dataSet()), count($view_random->result), 'The number of returned rows match.');
    $this->assertNotIdenticalResultset($view_random, $view->result, array(
      'views_test_data_name' => 'views_test_data_name',
      'views_test_data_age' => 'views_test_data_name',
    ));

    // Execute a second random view, we expect the result set to be different again.
    $view_random_2 = $this->getBasicRandomView();
    $this->executeView($view_random_2);
    $this->assertEqual(count($this->dataSet()), count($view_random_2->result), 'The number of returned rows match.');
    $this->assertNotIdenticalResultset($view_random, $view->result, array(
      'views_test_data_name' => 'views_test_data_name',
      'views_test_data_age' => 'views_test_data_name',
    ));
  }

  /**
   * Tests random ordering with tags based caching.
   *
   * The random sorting should opt out of caching by defining a max age of 0.
   * At the same time, the row render caching still works.
   */
  public function testRandomOrderingWithRenderCaching() {
    $view_random = $this->getBasicRandomView();

    $display = &$view_random->storage->getDisplay('default');
    $display['display_options']['cache'] = [
      'type' => 'tag',
    ];

    $view_random->storage->save();

    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');
    /** @var \Drupal\Core\Render\RenderCacheInterface $render_cache */
    $render_cache = \Drupal::service('render_cache');

    $original = $build = DisplayPluginBase::buildBasicRenderable($view_random->id(), 'default');
    $result = $renderer->renderPlain($build);

    $original['#cache'] += ['contexts' => []];
    $original['#cache']['contexts'] = Cache::mergeContexts($original['#cache']['contexts'], $this->container->getParameter('renderer.config')['required_cache_contexts']);

    $this->assertFalse($render_cache->get($original), 'Ensure there is no render cache entry.');

    $build = DisplayPluginBase::buildBasicRenderable($view_random->id(), 'default');
    $result2 = $renderer->renderPlain($build);

    // Ensure that the random ordering works and don't produce the same result.
    $this->assertNotEqual($result, $result2);
  }

}
