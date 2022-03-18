<?php

namespace Drupal\Tests\rest\Functional\Views;

use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\views\Entity\View;

/**
 * Tests authentication for REST display.
 *
 * @group rest
 */
class RestExportAuthTest extends ViewTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['rest', 'views_ui', 'basic_auth'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public function setUp($import_test_views = TRUE, $modules = []): void {
    parent::setUp($import_test_views, $modules);

    $this->drupalLogin($this->drupalCreateUser(['administer views']));
  }

  /**
   * Checks that correct authentication providers are available for choosing.
   *
   * @link https://www.drupal.org/node/2825204
   */
  public function testAuthProvidersOptions() {
    $view_id = 'test_view_rest_export';
    $view_label = 'Test view (REST export)';
    $view_display = 'rest_export_1';
    $view_rest_path = 'test-view/rest-export';

    // Create new view.
    $this->drupalGet('admin/structure/views/add');
    $this->submitForm([
      'id' => $view_id,
      'label' => $view_label,
      'show[wizard_key]' => 'users',
      'rest_export[path]' => $view_rest_path,
      'rest_export[create]' => TRUE,
    ], 'Save and edit');

    $this->drupalGet("admin/structure/views/nojs/display/$view_id/$view_display/auth");
    // The "basic_auth" will always be available since module,
    // providing it, has the same name.
    $this->assertSession()->fieldExists('edit-auth-basic-auth');
    // The "cookie" authentication provider defined by "user" module.
    $this->assertSession()->fieldExists('edit-auth-cookie');
    // Wrong behavior in "getAuthOptions()" method makes this option available
    // instead of "cookie".
    // @see \Drupal\rest\Plugin\views\display\RestExport::getAuthOptions()
    $this->assertSession()->fieldNotExists('edit-auth-user');

    $this->submitForm(['auth[basic_auth]' => 1, 'auth[cookie]' => 1], 'Apply');
    $this->submitForm([], 'Save');

    $view = View::load($view_id);
    $this->assertEquals(['basic_auth', 'cookie'], $view->getDisplay('rest_export_1')['display_options']['auth']);
  }

}
