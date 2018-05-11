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
  public static $modules = ['node', 'views'];

  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    $this->enableViewsTestModule();

    $admin_user = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests the attachment plugin.
   */
  public function testAttachment() {
    $this->drupalGet('test-display-attachment');

    $result = $this->xpath('//div[contains(@class, "view-content")]');
    $this->assertEqual(count($result), 2, 'Both actual view and the attachment is rendered.');

    $result = $this->xpath('//div[contains(@class, "attachment-after")]');
    $this->assertEqual(count($result), 0, 'The attachment is not rendered after the actual view.');

    $result = $this->xpath('//div[contains(@class, "attachment-before")]');
    $this->assertEqual(count($result), 1, 'The attachment is rendered before the actual view.');
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
    $this->assertTrue(in_array('attachment_1', $attached_displays), 'The attachment_1 display is attached to the page display.');
    $this->assertTrue(in_array('attachment_2', $attached_displays), 'The attachment_2 display is attached to the page display.');

    // Check that the attachments are output on the page display.
    $this->drupalGet('test-attached-disabled');

    $result = $this->xpath('//div[contains(@class, "view-content")]');
    $this->assertEqual(count($result), 3, 'The page view and the attachments are rendered.');

    $result = $this->xpath('//div[contains(@class, "attachment-before")]');
    $this->assertEqual(count($result), 1, 'The attachment is rendered before the page view.');

    $result = $this->xpath('//div[contains(@class, "attachment-after")]');
    $this->assertEqual(count($result), 1, 'The attachment is rendered after the page view.');

    // Disable the attachment_1 display.
    $view->displayHandlers->get('attachment_1')->setOption('enabled', FALSE);
    $view->save();

    // Test that the before attachment is not displayed.
    $this->drupalGet('/test-attached-disabled');
    $result = $this->xpath('//div[contains(@class, "view-content")]');
    $this->assertEqual(count($result), 2, 'The page view and only one attachment are rendered.');

    $result = $this->xpath('//div[contains(@class, "attachment-before")]');
    $this->assertEqual(count($result), 0, 'The attachment_1 is not rendered.');

    // Disable the attachment_2 display.
    $view->displayHandlers->get('attachment_2')->setOption('enabled', FALSE);
    $view->save();

    // Test that the after attachment is not displayed.
    $this->drupalGet('/test-attached-disabled');
    $result = $this->xpath('//div[contains(@class, "view-content")]');
    $this->assertEqual(count($result), 1, 'The page view is rendered without attachments.');

    $result = $this->xpath('//div[contains(@class, "attachment-after")]');
    $this->assertEqual(count($result), 0, 'The attachment_2 is not rendered.');
  }

}
