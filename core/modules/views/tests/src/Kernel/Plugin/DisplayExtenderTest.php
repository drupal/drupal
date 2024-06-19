<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel\Plugin;

use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views_test_data\Plugin\views\display_extender\DisplayExtenderTest as DisplayExtenderTestData;
use Drupal\views\Views;

/**
 * Tests the display extender plugins.
 *
 * @group views
 * @see \Drupal\views_test_data\Plugin\views\display_extender\DisplayExtenderTest
 */
class DisplayExtenderTest extends ViewsKernelTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_view'];

  /**
   * Tests display extenders.
   */
  public function testDisplayExtenders(): void {
    $this->config('views.settings')->set('display_extenders', ['display_extender_test'])->save();
    $this->assertCount(1, Views::getEnabledDisplayExtenders(), 'Make sure that there is only one enabled display extender.');

    $view = Views::getView('test_view');
    $view->initDisplay();

    $this->assertCount(1, $view->display_handler->getExtenders(), 'Make sure that only one extender is initialized.');

    $display_extender = $view->display_handler->getExtenders()['display_extender_test'];
    $this->assertInstanceOf(DisplayExtenderTestData::class, $display_extender);

    $view->preExecute();
    $this->assertTrue($display_extender->testState['preExecute'], 'Make sure the display extender was able to react on preExecute.');
    $view->execute();
    $this->assertTrue($display_extender->testState['query'], 'Make sure the display extender was able to react on query.');
  }

  /**
   * Tests display extenders validation.
   */
  public function testDisplayExtendersValidate(): void {
    $this->config('views.settings')->set('display_extenders', ['display_extender_test_3'])->save();

    $view = Views::getView('test_view');
    $errors = $view->validate();

    foreach ($view->displayHandlers as $id => $display) {
      $this->assertArrayHasKey($id, $errors);
      $this->assertContains('Display extender test error.', $errors[$id], "Error message found for $id display");
    }
  }

}
