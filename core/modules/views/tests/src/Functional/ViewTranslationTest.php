<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Functional;

use Drupal\Component\Utility\Xss;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\views\Views;

// cspell:ignore titel

/**
 * Tests views title translation.
 *
 * @group views
 */
class ViewTranslationTest extends ViewTestBase {

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_view'];

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'locale', 'language', 'config_translation', 'views_ui'];

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
    $this->drupalPlaceBlock('system_breadcrumb_block');

    // Add Dutch language programmatically.
    ConfigurableLanguage::createFromLangcode('nl')->save();

    // Enable page caching.
    $config = $this->config('system.performance');
    $config->set('cache.page.max_age', 3600);
    $config->save();
  }

  /**
   * Tests that the view route title is translated.
   */
  public function testViewTitleTranslation(): void {
    $view = Views::getView('test_view');

    // Create a test display, add path and default language title.
    $view->storage->addDisplay('page');
    $displays = $view->storage->get('display');
    $displays['default']['display_options']['title'] = 'Title EN';
    $displays['page_1']['display_options']['path'] = 'test-view';
    $view->storage->set('display', $displays);
    $view->save();
    // We need to rebuild the routes to discover the route to the
    // view display.
    \Drupal::service('router.builder')->rebuild();

    $admin_user = $this->drupalCreateUser(['translate configuration']);
    $this->drupalLogin($admin_user);

    $edit = [
      'translation[config_names][views.view.test_view][display][default][display_options][title]' => 'Titel NL',
    ];
    $this->drupalGet('admin/structure/views/view/test_view/translate/nl/edit');
    $this->submitForm($edit, 'Save translation');
    $this->drupalLogout();

    $this->drupalGet('test-view');
    $this->assertSession()->titleEquals('Title EN | Drupal');
    $this->assertEquals('MISS', $this->getSession()->getResponseHeader('X-Drupal-Cache'));

    // Make sure the use of a title callback does not prevent caching of the
    // View page.
    $this->drupalGet('test-view');
    $this->assertEquals('HIT', $this->getSession()->getResponseHeader('X-Drupal-Cache'));

    // Test the breadcrumb on a deeper page because by default the breadcrumb
    // doesn't render the current page title. It doesn't matter for the
    // breadcrumb that the requested page does not exist.
    $this->drupalGet('test-view/not-relevant');
    $this->assertSession()->linkExists('Title EN');
    $this->assertSession()->linkNotExists('Titel NL');

    // Test that the title is translated.
    $this->drupalGet('nl/test-view');
    $this->assertSession()->titleEquals('Titel NL | Drupal');
    $this->assertEquals('MISS', $this->getSession()->getResponseHeader('X-Drupal-Cache'));
    $this->drupalGet('test-view');
    $this->assertEquals('HIT', $this->getSession()->getResponseHeader('X-Drupal-Cache'));

    // Test that the breadcrumb link is also translated.
    $this->drupalGet('nl/test-view/not-relevant');
    $this->assertSession()->linkExists('Titel NL');
    $this->assertSession()->linkNotExists('Title EN');

    // Make sure that the title gets sanitized.
    $displays = $view->storage->get('display');
    $unsafe_title = 'This is an unsafe title <script>alert("click me!")</script>';
    $safe_title = Xss::filter($unsafe_title);
    $displays['default']['display_options']['title'] = $unsafe_title;
    $view->storage->set('display', $displays);
    $view->save();
    $this->drupalGet('test-view');
    $this->assertSession()->titleEquals($safe_title . ' | Drupal');
  }

}
