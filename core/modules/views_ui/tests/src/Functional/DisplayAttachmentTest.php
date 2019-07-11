<?php

namespace Drupal\Tests\views_ui\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\views\Views;

/**
 * Tests the UI for the attachment display plugin.
 *
 * @group views_ui
 * @see \Drupal\views\Plugin\views\display\Attachment
 */
class DisplayAttachmentTest extends UITestBase {

  /**
   * Views used by this test.
   *
   * @var array
   * .
   */
  public static $testViews = ['test_attachment_ui'];

  /**
   * Tests the attachment UI.
   */
  public function testAttachmentUI() {
    $this->drupalGet('admin/structure/views/view/test_attachment_ui/edit/attachment_1');
    $this->assertText(t('Not defined'), 'The right text appears if there is no attachment selection yet.');

    $attachment_display_url = 'admin/structure/views/nojs/display/test_attachment_ui/attachment_1/displays';
    $this->drupalGet($attachment_display_url);
    // Display labels should be escaped.
    $this->assertEscaped('<em>Page</em>');

    foreach (['default', 'page-1'] as $display_id) {
      $this->assertNoFieldChecked("edit-displays-$display_id", new FormattableMarkup('Make sure the @display_id can be marked as attached', ['@display_id' => $display_id]));
    }

    // Save the attachments and test the value on the view.
    $this->drupalPostForm($attachment_display_url, ['displays[page_1]' => 1], t('Apply'));
    // Options summary should be escaped.
    $this->assertEscaped('<em>Page</em>');
    $this->assertNoRaw('<em>Page</em>');
    $result = $this->xpath('//a[@id = :id]', [':id' => 'views-attachment-1-displays']);
    $this->assertEqual($result[0]->getAttribute('title'), t('Page'));
    $this->drupalPostForm(NULL, [], t('Save'));

    $view = Views::getView('test_attachment_ui');
    $view->initDisplay();
    $this->assertEqual(array_keys(array_filter($view->displayHandlers->get('attachment_1')->getOption('displays'))), ['page_1'], 'The attached displays got saved as expected');

    $this->drupalPostForm($attachment_display_url, ['displays[default]' => 1, 'displays[page_1]' => 1], t('Apply'));
    $result = $this->xpath('//a[@id = :id]', [':id' => 'views-attachment-1-displays']);
    $this->assertEqual($result[0]->getAttribute('title'), t('Multiple displays'));
    $this->drupalPostForm(NULL, [], t('Save'));

    $view = Views::getView('test_attachment_ui');
    $view->initDisplay();
    $this->assertEqual(array_keys($view->displayHandlers->get('attachment_1')->getOption('displays')), ['default', 'page_1'], 'The attached displays got saved as expected');
  }

  /**
   * Tests the attachment working after the attached page was deleted.
   */
  public function testRemoveAttachedDisplay() {
    // Create a view.
    $view = $this->randomView();
    $path_prefix = 'admin/structure/views/view/' . $view['id'] . '/edit';
    $attachment_display_url = 'admin/structure/views/nojs/display/' . $view['id'] . '/attachment_1/displays';

    // Open the Page display and create the attachment display.
    $this->drupalGet($path_prefix . '/page_1');
    $this->drupalPostForm(NULL, [], 'Add Attachment');
    $this->assertText(t('Not defined'), 'The right text appears if there is no attachment selection yet.');

    // Attach the Attachment to the Page display.
    $this->drupalPostForm($attachment_display_url, ['displays[page_1]' => 1], t('Apply'));
    $this->drupalPostForm(NULL, [], t('Save'));

    // Open the Page display and mark it as deleted.
    $this->drupalGet($path_prefix . '/page_1');
    $this->assertFieldById('edit-displays-settings-settings-content-tab-content-details-top-actions-delete', 'Delete Page', 'Make sure there is a delete button on the page display.');
    $this->drupalPostForm($path_prefix . '/page_1', [], 'Delete Page');

    // Open the attachment display and save it.
    $this->drupalGet($path_prefix . '/attachment_1');
    $this->drupalPostForm(NULL, [], t('Save'));

    // Check that there is no warning for the removed page display.
    $this->assertNoText("Plugin ID &#039;page_1&#039; was not found.");

    // Check that the attachment is no longer linked to the removed display.
    $this->assertText(t('Not defined'), 'The right text appears if there is no attachment selection yet.');

  }

}
