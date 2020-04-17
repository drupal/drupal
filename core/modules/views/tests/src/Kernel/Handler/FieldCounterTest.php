<?php

namespace Drupal\Tests\views\Kernel\Handler;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;

/**
 * Tests the Drupal\views\Plugin\views\field\Counter handler.
 *
 * @group views
 */
class FieldCounterTest extends ViewsKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['user'];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_view'];

  public function testSimple() {
    $view = Views::getView('test_view');
    $view->setDisplay();
    $view->displayHandlers->get('default')->overrideOption('fields', [
      'counter' => [
        'id' => 'counter',
        'table' => 'views',
        'field' => 'counter',
        'relationship' => 'none',
      ],
      'name' => [
        'id' => 'name',
        'table' => 'views_test_data',
        'field' => 'name',
        'relationship' => 'none',
      ],
    ]);
    $view->preview();

    $counter = $view->style_plugin->getField(0, 'counter');
    $this->assertEqual($counter, '1', new FormattableMarkup('Make sure the expected number (@expected) patches with the rendered number (@counter)', ['@expected' => 1, '@counter' => $counter]));
    $counter = $view->style_plugin->getField(1, 'counter');
    $this->assertEqual($counter, '2', new FormattableMarkup('Make sure the expected number (@expected) patches with the rendered number (@counter)', ['@expected' => 2, '@counter' => $counter]));
    $counter = $view->style_plugin->getField(2, 'counter');
    $this->assertEqual($counter, '3', new FormattableMarkup('Make sure the expected number (@expected) patches with the rendered number (@counter)', ['@expected' => 3, '@counter' => $counter]));
    $view->destroy();
    $view->storage->invalidateCaches();

    $view->setDisplay();
    $rand_start = rand(5, 10);
    $view->displayHandlers->get('default')->overrideOption('fields', [
      'counter' => [
        'id' => 'counter',
        'table' => 'views',
        'field' => 'counter',
        'relationship' => 'none',
        'counter_start' => $rand_start,
      ],
      'name' => [
        'id' => 'name',
        'table' => 'views_test_data',
        'field' => 'name',
        'relationship' => 'none',
      ],
    ]);
    $view->preview();

    $counter = $view->style_plugin->getField(0, 'counter');
    $expected_number = 0 + $rand_start;
    $this->assertEqual($counter, (string) $expected_number, new FormattableMarkup('Make sure the expected number (@expected) patches with the rendered number (@counter)', ['@expected' => $expected_number, '@counter' => $counter]));
    $counter = $view->style_plugin->getField(1, 'counter');
    $expected_number = 1 + $rand_start;
    $this->assertEqual($counter, (string) $expected_number, new FormattableMarkup('Make sure the expected number (@expected) patches with the rendered number (@counter)', ['@expected' => $expected_number, '@counter' => $counter]));
    $counter = $view->style_plugin->getField(2, 'counter');
    $expected_number = 2 + $rand_start;
    $this->assertEqual($counter, (string) $expected_number, new FormattableMarkup('Make sure the expected number (@expected) patches with the rendered number (@counter)', ['@expected' => $expected_number, '@counter' => $counter]));
  }

  /**
   * Tests the counter field when using a pager.
   */
  public function testPager() {
    $view = Views::getView('test_view');
    $view->setDisplay();
    $view->displayHandlers->get('default')->overrideOption('fields', [
      'counter' => [
        'id' => 'counter',
        'table' => 'views',
        'field' => 'counter',
        'relationship' => 'none',
      ],
      'name' => [
        'id' => 'name',
        'table' => 'views_test_data',
        'field' => 'name',
        'relationship' => 'none',
      ],
    ]);
    $view->displayHandlers->get('default')->setOption('pager', [
      'type' => 'mini',
      'options' => ['items_per_page' => 1],
    ]);

    $view->preview();

    $counter = $view->style_plugin->getField(0, 'counter');
    $this->assertEquals('1', $counter);
    $view->destroy();

    // Go to the second page.
    $view->setCurrentPage(1);
    $view->preview();

    $counter = $view->style_plugin->getField(0, 'counter');
    $this->assertEquals('2', $counter);
    $view->destroy();

    // Go to the third page.
    $view->setCurrentPage(2);
    $view->preview();

    $counter = $view->style_plugin->getField(0, 'counter');
    $this->assertEquals('3', $counter);

    $view->destroy();

    // Test using the counter start option.
    $counter_start = 1000000;
    $view->setDisplay();
    $view->displayHandlers->get('default')->overrideOption('fields', [
      'counter' => [
        'id' => 'counter',
        'table' => 'views',
        'field' => 'counter',
        'relationship' => 'none',
        'counter_start' => $counter_start,
      ],
      'name' => [
        'id' => 'name',
        'table' => 'views_test_data',
        'field' => 'name',
        'relationship' => 'none',
      ],
    ]);

    $view->preview();

    $counter = $view->style_plugin->getField(0, 'counter');
    $this->assertEquals($counter_start, $counter);
    $view->destroy();

    // Go to the second page.
    $view->setCurrentPage(1);
    $view->preview();

    $counter = $view->style_plugin->getField(0, 'counter');
    $this->assertEquals($counter_start + 1, $counter);
    $view->destroy();

    // Go to the third page.
    $view->setCurrentPage(2);
    $view->preview();

    $counter = $view->style_plugin->getField(0, 'counter');
    $this->assertEquals($counter_start + 2, $counter);
  }

}
