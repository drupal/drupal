<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel\Plugin;

use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Entity\View;
use Drupal\views\Views;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests exposed form actions for block displays with custom link targets.
 */
#[Group('views')]
#[RunTestsInSeparateProcesses]
class ExposedFormLinkDisplayTest extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp(FALSE);

    $view = View::create([
      'id' => 'test_exposed_block_link_display',
      'label' => 'Test exposed block link display',
      'module' => 'views',
      'base_table' => 'views_test_data',
      'base_field' => 'id',
    ]);

    $executable = $view->getExecutable();
    $executable->newDisplay('default', 'Default', 'default');
    $executable->newDisplay('page', 'Page', 'page_1');
    $executable->newDisplay('block', 'Block', 'block_1');

    $default_display = $executable->displayHandlers->get('default');
    $default_display->setOption('fields', [
      'name' => [
        'id' => 'name',
        'table' => 'views_test_data',
        'field' => 'name',
        'plugin_id' => 'standard',
      ],
    ]);
    $default_display->setOption('filters', [
      'age' => [
        'id' => 'age',
        'table' => 'views_test_data',
        'field' => 'age',
        'plugin_id' => 'numeric',
        'exposed' => TRUE,
        'expose' => [
          'identifier' => 'age',
          'label' => 'Age',
          'operator_id' => 'age_op',
        ],
      ],
    ]);

    $page_display = $executable->displayHandlers->get('page_1');
    $page_display->overrideOption('path', 'views-test-page');

    $block_display = $executable->displayHandlers->get('block_1');
    $block_display->overrideOption('link_display', 'custom_url');
    $block_display->overrideOption('link_url', '/custom-link');

    $view->save();
  }

  /**
   * Tests that exposed form action uses the custom link display path.
   */
  public function testExposedFormActionUsesCustomLinkDisplay(): void {
    $view = Views::getView('test_exposed_block_link_display');
    $view->setDisplay('block_1');
    $view->initHandlers();

    $form = $view->display_handler->getPlugin('exposed_form')->renderExposedForm(TRUE);

    $this->assertNotEmpty($form, 'The exposed form was built for the block display.');
    $this->assertSame('/custom-link', $form['#action']);

    $page_path = $view->displayHandlers->get('page_1')->getPath();
    $this->assertNotSame($page_path, $form['#action']);
    $this->assertNotSame('/' . $page_path, $form['#action']);

    $current_path = $this->container->get('path.current')->getPath();
    $this->assertNotSame($current_path, $form['#action']);
  }

}
