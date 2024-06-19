<?php

declare(strict_types=1);

namespace Drupal\Tests\toolbar\FunctionalJavascript;

use Drupal\Component\Serialization\Json;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the sessionStorage state set by the toolbar.
 *
 * @group toolbar
 */
class ToolbarStoredStateTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['toolbar', 'node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  public function testToolbarStoredState(): void {
    $admin_user = $this->drupalCreateUser([
      'access toolbar',
      'administer site configuration',
      'access content overview',
    ]);
    $this->drupalLogin($admin_user);
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet('<front>');
    $this->assertNotEmpty($this->assertSession()->waitForElement('css', 'body.toolbar-horizontal'));
    $this->assertNotEmpty($this->assertSession()->waitForElementVisible('css', '.toolbar-tray'));
    $this->assertSession()->waitForElementRemoved('css', '.toolbar-loading');

    $page->clickLink('toolbar-item-user');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '#toolbar-item-user.is-active'));

    // Expected state values with the user tray open with horizontal
    // orientation.
    $expected = [
      'orientation' => 'horizontal',
      'hasActiveTab' => TRUE,
      'activeTabId' => 'toolbar-item-user',
      'activeTray' => 'toolbar-item-user-tray',
      'isOriented' => TRUE,
      'isFixed' => TRUE,
    ];
    $toolbar_stored_state = JSON::decode(
      $this->getSession()->evaluateScript("sessionStorage.getItem('Drupal.toolbar.toolbarState')")
    );

    // The userButtonMinWidth property will differ depending on the length of
    // the test-generated username, so it is checked differently and the value
    // is copied to the expected value array.
    $this->assertNotNull($toolbar_stored_state['userButtonMinWidth']);
    $this->assertIsNumeric($toolbar_stored_state['userButtonMinWidth']);
    $this->assertGreaterThan(60, $toolbar_stored_state['userButtonMinWidth']);
    $expected['userButtonMinWidth'] = $toolbar_stored_state['userButtonMinWidth'];

    $this->assertSame($expected, $toolbar_stored_state);

    $page->clickLink('toolbar-item-user');
    $assert_session->assertNoElementAfterWait('css', '#toolbar-item-user.is-active');

    // Update expected state values to reflect no tray being open.
    $expected['hasActiveTab'] = FALSE;
    $expected['activeTabId'] = NULL;
    unset($expected['activeTray']);
    $toolbar_stored_state = JSON::decode(
      $this->getSession()->evaluateScript("sessionStorage.getItem('Drupal.toolbar.toolbarState')")
    );
    $this->assertSame($expected, $toolbar_stored_state);

    $page->clickLink('toolbar-item-administration');
    $orientation_toggle = $assert_session->waitForElementVisible('css', '[title="Vertical orientation"]');
    $orientation_toggle->click();
    $assert_session->waitForElementVisible('css', 'body.toolbar-vertical');

    // Update expected state values to reflect the administration tray being
    // open with vertical orientation.
    $expected['orientation'] = 'vertical';
    $expected['hasActiveTab'] = TRUE;
    $expected['activeTabId'] = 'toolbar-item-administration';
    $expected['activeTray'] = 'toolbar-item-administration-tray';
    $toolbar_stored_state = JSON::decode(
      $this->getSession()->evaluateScript("sessionStorage.getItem('Drupal.toolbar.toolbarState')")
    );
    $this->assertSame($expected, $toolbar_stored_state);

    $this->getSession()->resizeWindow(600, 600);

    // Update expected state values to reflect the viewport being at a width
    // that is narrow enough that the toolbar isn't fixed.
    $expected['isFixed'] = FALSE;
    $toolbar_stored_state = JSON::decode(
      $this->getSession()->evaluateScript("sessionStorage.getItem('Drupal.toolbar.toolbarState')")
    );
    $this->assertSame($expected, $toolbar_stored_state);
  }

}
