<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Functional\Handler;

use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\views\Views;

/**
 * Tests the plugin base of the area handler.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\area\AreaPluginBase
 * @see \Drupal\views_test\Plugin\views\area\TestExample
 */
class AreaTest extends ViewTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_example_area'];

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'views_ui'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = ['views_test_config']): void {
    parent::setUp($import_test_views, $modules);

    $this->enableViewsTestModule();
  }

  protected function viewsData() {
    $data = parent::viewsData();
    $data['views']['test_example'] = [
      'title' => 'Test Example area',
      'help' => 'A area handler which just exists for tests.',
      'area' => [
        'id' => 'test_example',
      ],
    ];

    return $data;
  }

  /**
   * Tests the generic UI of an area handler.
   */
  public function testUI(): void {
    $admin_user = $this->drupalCreateUser([
      'administer views',
      'administer site configuration',
    ]);
    $this->drupalLogin($admin_user);

    $types = ['header', 'footer', 'empty'];
    $labels = [];
    foreach ($types as $type) {
      $edit_path = 'admin/structure/views/nojs/handler/test_example_area/default/' . $type . '/test_example';

      // First setup an empty label.
      $this->drupalGet($edit_path);
      $this->submitForm([], 'Apply');
      $this->assertSession()->pageTextContains('Test Example area');

      // Then setup a no empty label.
      $labels[$type] = $this->randomMachineName();
      $this->drupalGet($edit_path);
      $this->submitForm(['options[admin_label]' => $labels[$type]], 'Apply');
      // Make sure that the new label appears on the site.
      $this->assertSession()->pageTextContains($labels[$type]);

      // Test that the settings (empty/admin_label) are accessible.
      $this->drupalGet($edit_path);
      $this->assertSession()->fieldExists('options[admin_label]');
      if ($type !== 'empty') {
        $this->assertSession()->fieldExists('options[empty]');
      }
    }
  }

  /**
   * Tests global tokens.
   */
  public function testRenderAreaToken(): void {
    $admin_user = $this->drupalCreateUser([
      'administer views',
      'administer site configuration',
    ]);
    $this->drupalLogin($admin_user);

    $view = Views::getView('test_example_area');
    $view->initHandlers();

    $this->drupalGet('admin/structure/views/nojs/handler/test_example_area/default/empty/test_example');

    // Test that the list is token present.
    $this->assertSession()->elementExists('xpath', '//ul[@class="global-tokens"]');

    $empty_handler = &$view->empty['test_example'];

    // Test the list of available tokens.
    $available = $empty_handler->getAvailableGlobalTokens();
    foreach (['site', 'view'] as $type) {
      $this->assertNotEmpty($available[$type]);
      $this->assertIsArray($available[$type]);

      // Test that each item exists in the list.
      foreach ($available[$type] as $token => $info) {
        $this->assertSession()->pageTextContains("[$type:$token]");
      }
    }

    // Test the rendered output of a token.
    $empty_handler->options['string'] = '[site:name]';

    // Test we have the site:name token in the output.
    $output = $view->preview();
    $output = (string) $this->container->get('renderer')->renderRoot($output);
    $expected = \Drupal::token()->replace('[site:name]');
    $this->assertStringContainsString($expected, $output);
  }

  /**
   * Tests overriding the view title using the area title handler.
   */
  public function testTitleArea(): void {
    $view = Views::getView('frontpage');
    $view->initDisplay('page_1');

    // Add the title area handler to the empty area.
    $view->displayHandlers->get('page_1')->overrideOption('empty', [
      'title' => [
        'id' => 'title',
        'table' => 'views',
        'field' => 'title',
        'admin_label' => '',
        'empty' => '0',
        'title' => 'Overridden title',
        'plugin_id' => 'title',
      ],
    ]);

    $view->storage->enable()->save();

    $this->drupalGet('node');
    $this->assertSession()->pageTextContains('Overridden title');
  }

}
