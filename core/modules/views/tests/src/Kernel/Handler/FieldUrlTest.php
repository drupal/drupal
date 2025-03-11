<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel\Handler;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;

/**
 * Tests the core Drupal\views\Plugin\views\field\Url handler.
 *
 * @group views
 */
class FieldUrlTest extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_view'];

  /**
   * Defines the Views data for the test entity.
   */
  public function viewsData() {
    $data = parent::viewsData();
    $data['views_test_data']['name']['field']['id'] = 'url';
    return $data;
  }

  /**
   * Tests the rendering of a field as a plain text value and as a link.
   */
  public function testFieldUrl(): void {
    $view = Views::getView('test_view');
    $view->setDisplay();

    $view->displayHandlers->get('default')->overrideOption('fields', [
      'name' => [
        'id' => 'name',
        'table' => 'views_test_data',
        'field' => 'name',
        'relationship' => 'none',
        'display_as_link' => FALSE,
      ],
    ]);

    $this->executeView($view);

    $this->assertEquals('John', $view->field['name']->advancedRender($view->result[0]));

    // Make the URL a link.
    $view->destroy();
    $view->setDisplay();

    $view->displayHandlers->get('default')->overrideOption('fields', [
      'name' => [
        'id' => 'name',
        'table' => 'views_test_data',
        'field' => 'name',
        'relationship' => 'none',
      ],
    ]);

    $this->executeView($view);

    $this->assertEquals(Link::fromTextAndUrl('John', Url::fromUri('base:John'))->toString(), $view->field['name']->advancedRender($view->result[0]));
  }

}
