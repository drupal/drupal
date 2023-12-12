<?php

declare(strict_types=1);

namespace Drupal\Tests\views_ui\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the admin UI AJAX interactions.
 *
 * @group views_ui
 */
class AdminAjaxTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'views_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'views_ui_test_theme';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalLogin($this->createUser([
      'administer views',
    ]));
  }

  /**
   * Confirms that form_alter is triggered after AJAX rebuilds.
   */
  public function testAjaxRebuild() {
    \Drupal::service('theme_installer')->install(['views_ui_test_theme']);

    $this->config('system.theme')
      ->set('default', 'views_ui_test_theme')
      ->save();

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet('admin/structure/views/view/user_admin_people');
    $assert_session->pageTextContains('This is text added to the display tabs at the top');
    $assert_session->pageTextContains('This is text added to the display edit form');
    $page->clickLink('User: Name (Username)');
    $assert_session->waitForElementVisible('css', '.views-ui-dialog');
    $page->fillField('Label', 'New Title');
    $page->find('css', '.ui-dialog-buttonset button:contains("Apply")')->press();
    $assert_session->waitForElementRemoved('css', '.views-ui-dialog');
    $assert_session->pageTextContains('This is text added to the display tabs at the top');
    $assert_session->pageTextContains('This is text added to the display edit form');
  }

  /**
   * Tests body scroll.
   */
  public function testBodyScroll() {
    $this->drupalGet('admin/structure/views/view/user_admin_people');
    $page = $this->getSession()->getPage();
    foreach (['name[views.nothing]', 'name[views.dropbutton]'] as $field) {
      $page->find('css', '#views-add-field')->click();
      $this->assertSession()->assertWaitOnAjaxRequest();
      $page->checkField($field);
      $page->find('css', '.ui-dialog-buttonset')->pressButton('Add and configure fields');
      $this->assertSession()->assertWaitOnAjaxRequest();
      $this->assertJsCondition('document.documentElement.style.overflow === "hidden"');
      $page->find('css', '.ui-dialog-buttonset')->pressButton('Apply');
      $this->assertSession()->assertWaitOnAjaxRequest();

      // Check overflow.
      $this->assertJsCondition('document.documentElement.style.overflow !== "hidden"');
    }
  }

}
