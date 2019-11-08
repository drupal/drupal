<?php

namespace Drupal\FunctionalJavascriptTests\Ajax;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the throbber.
 *
 * @group Ajax
 */
class ThrobberTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'views',
    'views_ui',
    'views_ui_test_field',
    'hold_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $admin_user = $this->drupalCreateUser([
      'administer views',
    ]);
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests theming throbber element.
   */
  public function testThemingThrobberElement() {
    $session = $this->getSession();
    $web_assert = $this->assertSession();
    $page = $session->getPage();

    $custom_ajax_progress_indicator_fullscreen = <<<JS
      Drupal.theme.ajaxProgressIndicatorFullscreen = function () {
        return '<div class="custom-ajax-progress-fullscreen"></div>';
      };
JS;
    $custom_ajax_progress_throbber = <<<JS
      Drupal.theme.ajaxProgressThrobber = function (message) {
        return '<div class="custom-ajax-progress-throbber"></div>';
      };
JS;
    $custom_ajax_progress_message = <<<JS
      Drupal.theme.ajaxProgressMessage = function (message) {
        return '<div class="custom-ajax-progress-message">Hold door!</div>';
      };
JS;

    $this->drupalGet('admin/structure/views/view/content');
    $web_assert->assertNoElementAfterWait('css', '.ajax-progress-fullscreen');

    // Test theming fullscreen throbber.
    $session->executeScript($custom_ajax_progress_indicator_fullscreen);
    hold_test_response(TRUE);
    $page->clickLink('Content: Published (grouped)');
    $this->assertNotNull($web_assert->waitForElement('css', '.custom-ajax-progress-fullscreen'), 'Custom ajaxProgressIndicatorFullscreen.');
    hold_test_response(FALSE);
    $web_assert->assertNoElementAfterWait('css', '.custom-ajax-progress-fullscreen');

    // Test theming throbber message.
    $web_assert->waitForElementVisible('css', '[data-drupal-selector="edit-options-group-info-add-group"]');
    $session->executeScript($custom_ajax_progress_message);
    hold_test_response(TRUE);
    $page->pressButton('Add another item');
    $this->assertNotNull($web_assert->waitForElement('css', '.ajax-progress-throbber .custom-ajax-progress-message'), 'Custom ajaxProgressMessage.');
    hold_test_response(FALSE);
    $web_assert->assertNoElementAfterWait('css', '.ajax-progress-throbber');

    // Test theming throbber.
    $web_assert->waitForElementVisible('css', '[data-drupal-selector="edit-options-group-info-group-items-3-title"]');
    $session->executeScript($custom_ajax_progress_throbber);
    hold_test_response(TRUE);
    $page->pressButton('Add another item');
    $this->assertNotNull($web_assert->waitForElement('css', '.custom-ajax-progress-throbber'), 'Custom ajaxProgressThrobber.');
    hold_test_response(FALSE);
    $web_assert->assertNoElementAfterWait('css', '.custom-ajax-progress-throbber');
  }

}
