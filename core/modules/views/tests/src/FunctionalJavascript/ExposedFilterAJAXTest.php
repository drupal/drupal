<?php

declare(strict_types=1);

namespace Drupal\Tests\views\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\views\Tests\ViewTestData;

/**
 * Tests the basic AJAX functionality of Views exposed forms.
 *
 * @group views
 */
class ExposedFilterAJAXTest extends WebDriverTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'views',
    'views_test_modal',
    'user_test_views',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_user_name'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Enable AJAX on the /admin/content View.
    \Drupal::configFactory()->getEditable('views.view.content')
      ->set('display.default.display_options.use_ajax', TRUE)
      ->save();

    // Import user_test_views and set it to use ajax.
    ViewTestData::createTestViews(get_class($this), ['user_test_views']);
    \Drupal::configFactory()->getEditable('views.view.test_user_name')
      ->set('display.default.display_options.use_ajax', TRUE)
      ->save();

    // Create a Content type and two test nodes.
    $this->createContentType(['type' => 'page']);
    $this->createNode(['title' => 'Page One']);
    $this->createNode(['title' => 'Page Two']);

    // Create a user privileged enough to use exposed filters and view content.
    $user = $this->drupalCreateUser([
      'administer site configuration',
      'access content',
      'access content overview',
      'edit any page content',
      'view the administration theme',
    ]);
    $this->drupalLogin($user);
  }

  /**
   * Tests if exposed filtering via AJAX works for the "Content" View.
   */
  public function testExposedFiltering(): void {
    // Create an account that can update the sticky flag.
    $user = $this->drupalCreateUser([
      'access content overview',
      'administer nodes',
      'edit any page content',
    ]);
    $this->drupalLogin($user);

    // Visit the View page.
    $this->drupalGet('admin/content');

    $session = $this->getSession();

    // Ensure that the Content we're testing for is present.
    $html = $session->getPage()->getHtml();
    $this->assertStringContainsString('Page One', $html);
    $this->assertStringContainsString('Page Two', $html);

    // Search for "Page One".
    $this->submitForm(['title' => 'Page One'], 'Filter');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Verify that only the "Page One" Node is present.
    $html = $session->getPage()->getHtml();
    $this->assertStringContainsString('Page One', $html);
    $this->assertStringNotContainsString('Page Two', $html);

    // Search for "Page Two".
    $this->submitForm(['title' => 'Page Two'], 'Filter');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Verify that only the "Page Two" Node is present.
    $html = $session->getPage()->getHtml();
    $this->assertStringContainsString('Page Two', $html);
    $this->assertStringNotContainsString('Page One', $html);

    // Submit bulk actions form to ensure that the previous AJAX submit does not
    // break it.
    $this->submitForm([
      'action' => 'node_make_sticky_action',
      'node_bulk_form[0]' => TRUE,
    ], 'Apply to selected items');

    // Verify that the action was performed.
    $this->assertSession()->pageTextContains('Make content sticky was applied to 1 item.');

    // Reset the form.
    $this->submitForm([], 'Reset');

    $this->assertSession()->pageTextContains('Page One');
    $this->assertSession()->pageTextContains('Page Two');
    $this->assertFalse($session->getPage()->hasButton('Reset'));
  }

  /**
   * Tests if exposed filtering via AJAX theme negotiation works.
   */
  public function testExposedFilteringThemeNegotiation(): void {
    // Install 'claro' and configure it as administrative theme.
    $this->container->get('theme_installer')->install(['claro']);
    $this->config('system.theme')->set('admin', 'claro')->save();

    // Visit the View page.
    $this->drupalGet('admin/content');

    // Search for "Page One".
    $this->submitForm(['title' => 'Page One'], 'Filter');
    $this->assertSession()->assertExpectedAjaxRequest(1);

    // Verify that the theme is the 'claro' admin theme and not the default
    // theme ('stark').
    $settings = $this->getDrupalSettings();
    $this->assertNotNull($settings['ajaxPageState']['theme_token']);
    $this->assertEquals('claro', $settings['ajaxPageState']['theme']);
  }

  /**
   * Tests if exposed filtering via AJAX works in a modal.
   */
  public function testExposedFiltersInModal(): void {
    $this->drupalGet('views-test-modal/modal');

    $assert = $this->assertSession();

    $assert->elementExists('named', ['link', 'Administer content'])->click();
    $dialog = $assert->waitForElementVisible('css', '.views-test-modal');

    $session = $this->getSession();
    // Ensure that the Content we're testing for is present.
    $html = $session->getPage()->getHtml();
    $this->assertStringContainsString('Page One', $html);
    $this->assertStringContainsString('Page Two', $html);

    // Search for "Page One".
    $session->getPage()->fillField('title', 'Page One');
    $assert->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Filter');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Verify that only the "Page One" Node is present.
    $html = $session->getPage()->getHtml();
    $this->assertStringContainsString('Page One', $html);
    $this->assertStringNotContainsString('Page Two', $html);

    // Close and re-open the modal.
    $assert->buttonExists('Close', $dialog)->press();
    $assert->elementExists('named', ['link', 'Administer content'])->click();
    $assert->waitForElementVisible('css', '.views-test-modal');

    // Ensure that the Content we're testing for is present.
    $html = $session->getPage()->getHtml();
    $this->assertStringContainsString('Page One', $html);
    $this->assertStringContainsString('Page Two', $html);

    // Search for "Page One".
    $session->getPage()->fillField('title', 'Page One');
    $assert->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Filter');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Verify that only the "Page One" Node is present.
    $html = $session->getPage()->getHtml();
    $this->assertStringContainsString('Page One', $html);
    $this->assertStringNotContainsString('Page Two', $html);
  }

  /**
   * Tests exposed filtering via AJAX with a button element.
   */
  public function testExposedFilteringWithButtonElement(): void {
    // Install theme to test with template system.
    \Drupal::service('theme_installer')->install(['views_test_theme']);

    // Make base theme default then test for hook invocations.
    $this->config('system.theme')
      ->set('default', 'views_test_theme')
      ->save();

    $this->drupalGet('admin/content');

    $session = $this->getSession();
    // Ensure that the Content we're testing for is present.
    $html = $session->getPage()->getHtml();
    $this->assertStringContainsString('Page One', $html);
    $this->assertStringContainsString('Page Two', $html);
    $button_tag = $session->getPage()->findButton('edit-submit-content')->getTagName();

    // Make sure the submit button has been transformed to a button element.
    $this->assertEquals('button', $button_tag);

    $drupal_settings = $this->getDrupalSettings();
    $ajax_views_before = $drupal_settings['views']['ajaxViews'];

    // Search for "Page One".
    $this->submitForm(['title' => 'Page One'], 'Filter');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Verify that only the "Page One" Node is present.
    $html = $session->getPage()->getHtml();
    $this->assertStringContainsString('Page One', $html);
    $this->assertStringNotContainsString('Page Two', $html);
    $drupal_settings = $this->getDrupalSettings();
    $ajax_views_after = $drupal_settings['views']['ajaxViews'];

    // Make sure that the views_dom_id didn't change, which would indicate that
    // the page reloaded instead of doing an AJAX update.
    $this->assertSame($ajax_views_before, $ajax_views_after);
  }

  /**
   * Tests that errors messages are displayed for exposed filters via ajax.
   */
  public function testExposedFilterErrorMessages(): void {
    $this->drupalGet('test_user_name');
    // Submit an invalid name, triggering validation errors.
    $name = $this->randomMachineName();
    $this->submitForm(['uid' => $name], 'Apply');
    $this->assertSession()->waitForElement('css', 'div[aria-label="Error message"]');
    $this->assertSession()->pageTextContainsOnce(sprintf('There are no users matching "%s"', $name));

    \Drupal::service('module_installer')->install(['inline_form_errors']);

    $this->drupalGet('test_user_name');
    // Submit an invalid name, triggering validation errors.
    $name = $this->randomMachineName();
    $this->submitForm(['uid' => $name], 'Apply');
    $this->assertSession()->waitForElement('css', 'div[aria-label="Error message"]');
    $this->assertSession()->pageTextContainsOnce(sprintf('There are no users matching "%s"', $name));
  }

}
