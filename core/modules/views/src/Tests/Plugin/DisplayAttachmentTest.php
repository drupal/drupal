<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Plugin\DisplayAttachmentTest.
 */

namespace Drupal\views\Tests\Plugin;

/**
 * Tests the attachment display plugin.
 *
 * @see \Drupal\views\Plugin\views\display\Attachment
 */
class DisplayAttachmentTest extends PluginTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_display_attachment');

  public static function getInfo() {
    return array(
      'name' => 'Display: Attachment plugin',
      'description' => 'Tests the attachment display plugin.',
      'group' => 'Views Plugins',
    );
  }

  protected function setUp() {
    parent::setUp();

    $this->enableViewsTestModule();
  }


  /**
   * Tests the attachment plugin.
   */
  protected function testAttachment() {
    $this->drupalGet('test-display-attachment');

    $result = $this->xpath('//div[contains(@class, "view-content")]');
    $this->assertEqual(count($result), 2, 'Both actual view and the attachment is rendered.');

    $result = $this->xpath('//div[contains(@class, "attachment-after")]');
    $this->assertEqual(count($result), 0, 'The attachment is not rendered after the actual view.');

    $result = $this->xpath('//div[contains(@class, "attachment-before")]');
    $this->assertEqual(count($result), 1, 'The attachment is rendered before the actual view.');
  }

}
