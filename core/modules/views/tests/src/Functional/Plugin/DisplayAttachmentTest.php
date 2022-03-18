<?php

namespace Drupal\Tests\views\Functional\Plugin;

use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\views\Views;

/**
 * Tests the attachment display plugin.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\display\Attachment
 */
class DisplayAttachmentTest extends ViewTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_display_attachment', 'test_attached_disabled'];

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'views'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  protected function setUp($import_test_views = TRUE, $modules = ['views_test_config']): void {
    parent::setUp($import_test_views, $modules);

    $this->enableViewsTestModule();

    $admin_user = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests the attachment plugin.
   */
  public function testAttachment() {
    $this->drupalGet('test-display-attachment');
    // Verify that both actual view and the attachment are rendered.
    $this->assertSession()->elementsCount('xpath', '//div[contains(@class, "view-content")]', 2);
    // Verify that the attachment is not rendered after the actual view.
    $this->assertSession()->elementNotExists('xpath', '//div[contains(@class, "attachment-after")]');
    // Verify that the attachment is rendered before the actual view.
    $this->assertSession()->elementsCount('xpath', '//div[contains(@class, "attachment-before")]', 1);
  }

  /**
   * Tests that nothing is output when the attachment displays are disabled.
   */
  public function testDisabledAttachments() {
    $this->drupalCreateContentType(['type' => 'page']);
    $this->drupalCreateNode();

    // Ensure that the feed_1 display is attached to the page_1 display.
    $view = Views::getView('test_attached_disabled');
    $view->setDisplay('page_1');
    $attached_displays = $view->display_handler->getAttachedDisplays();
    $this->assertContains('attachment_1', $attached_displays, 'The attachment_1 display is attached to the page display.');
    $this->assertContains('attachment_2', $attached_displays, 'The attachment_2 display is attached to the page display.');

    // Check that the attachments are output on the page display.
    $this->drupalGet('test-attached-disabled');
    // Verify that the page view and the attachments are rendered.
    $this->assertSession()->elementsCount('xpath', '//div[contains(@class, "view-content")]', 3);
    // Verify that the attachment is rendered before the page view.
    $this->assertSession()->elementsCount('xpath', '//div[contains(@class, "attachment-before")]', 1);
    // Verify that the attachment is rendered after the page view.
    $this->assertSession()->elementsCount('xpath', '//div[contains(@class, "attachment-after")]', 1);

    // Disable the attachment_1 display.
    $view->displayHandlers->get('attachment_1')->setOption('enabled', FALSE);
    $view->save();

    // Test that the before attachment is not displayed.
    $this->drupalGet('/test-attached-disabled');
    // Verify that the page view and only one attachment are rendered.
    $this->assertSession()->elementsCount('xpath', '//div[contains(@class, "view-content")]', 2);
    // Verify that the attachment_1 is not rendered.
    $this->assertSession()->elementNotExists('xpath', '//div[contains(@class, "attachment-before")]');

    // Disable the attachment_2 display.
    $view->displayHandlers->get('attachment_2')->setOption('enabled', FALSE);
    $view->save();

    // Test that the after attachment is not displayed.
    $this->drupalGet('/test-attached-disabled');
    // Verify that the page view is rendered without attachments.
    $this->assertSession()->elementsCount('xpath', '//div[contains(@class, "view-content")]', 1);
    // Verify that the attachment_2 is not rendered.
    $this->assertSession()->elementNotExists('xpath', '//div[contains(@class, "attachment-after")]');
  }

}
