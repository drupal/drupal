<?php

declare(strict_types=1);

namespace Drupal\Tests\views_ui\Functional;

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
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the attachment UI.
   */
  public function testAttachmentUI(): void {
    $this->drupalGet('admin/structure/views/view/test_attachment_ui/edit/attachment_1');
    $this->assertSession()->pageTextContains('Not defined');

    $attachment_display_url = 'admin/structure/views/nojs/display/test_attachment_ui/attachment_1/displays';
    $this->drupalGet($attachment_display_url);
    // Display labels should be escaped.
    $this->assertSession()->assertEscaped('<em>Page</em>');

    $this->assertSession()->checkboxNotChecked("edit-displays-default");
    $this->assertSession()->checkboxNotChecked("edit-displays-page-1");

    // Save the attachments and test the value on the view.
    $this->drupalGet($attachment_display_url);
    $this->submitForm(['displays[page_1]' => 1], 'Apply');
    // Options summary should be escaped.
    $this->assertSession()->assertEscaped('<em>Page</em>');
    $this->assertSession()->responseNotContains('<em>Page</em>');
    $this->assertSession()->elementAttributeContains('xpath', '//a[@id = "views-attachment-1-displays"]', 'title', 'Page');
    $this->submitForm([], 'Save');

    $view = Views::getView('test_attachment_ui');
    $view->initDisplay();
    $this->assertEquals(['page_1'], array_keys(array_filter($view->displayHandlers->get('attachment_1')->getOption('displays'))), 'The attached displays got saved as expected');

    $this->drupalGet($attachment_display_url);
    $this->submitForm([
      'displays[default]' => 1,
      'displays[page_1]' => 1,
    ], 'Apply');
    $this->assertSession()->elementAttributeContains('xpath', '//a[@id = "views-attachment-1-displays"]', 'title', 'Multiple displays');
    $this->submitForm([], 'Save');

    $view = Views::getView('test_attachment_ui');
    $view->initDisplay();
    $this->assertEquals(['default', 'page_1'], array_keys($view->displayHandlers->get('attachment_1')->getOption('displays')), 'The attached displays got saved as expected');
  }

  /**
   * Tests the attachment working after the attached page was deleted.
   */
  public function testRemoveAttachedDisplay(): void {
    // Create a view.
    $view = $this->randomView();
    $path_prefix = 'admin/structure/views/view/' . $view['id'] . '/edit';
    $attachment_display_url = 'admin/structure/views/nojs/display/' . $view['id'] . '/attachment_1/displays';

    // Open the Page display and create the attachment display.
    $this->drupalGet($path_prefix . '/page_1');
    $this->submitForm([], 'Add Attachment');
    $this->assertSession()->pageTextContains('Not defined');

    // Attach the Attachment to the Page display.
    $this->drupalGet($attachment_display_url);
    $this->submitForm(['displays[page_1]' => 1], 'Apply');
    $this->submitForm([], 'Save');

    // Open the Page display and mark it as deleted.
    $this->drupalGet($path_prefix . '/page_1');
    $this->assertSession()->buttonExists('edit-displays-settings-settings-content-tab-content-details-top-actions-delete');
    $this->drupalGet($path_prefix . '/page_1');
    $this->submitForm([], 'Delete Page');

    // Open the attachment display and save it.
    $this->drupalGet($path_prefix . '/attachment_1');
    $this->submitForm([], 'Save');

    // Check that there is no warning for the removed page display.
    $this->assertSession()->pageTextNotContains("Plugin ID 'page_1' was not found.");

    // Check that the attachment is no longer linked to the removed display.
    $this->assertSession()->pageTextContains('Not defined');

  }

  /**
   * Tests the attachment after changing machine name.
   */
  public function testAttachmentOnAttachedMachineNameChange(): void {

    $view = $this->randomView();
    $path_prefix = 'admin/structure/views/view/' . $view['id'] . '/edit';
    $attachment_display_url = 'admin/structure/views/nojs/display/' . $view['id'] . '/attachment_1/displays';

    // Open the Page display and create the attachment display.
    $this->drupalGet($path_prefix . '/page_1');
    $this->submitForm([], 'Add Attachment');
    $this->assertSession()->pageTextContains('Not defined');

    // Attach the Attachment to the Default and   Page display.
    $this->drupalGet($attachment_display_url);
    $this->submitForm(['displays[default]' => 1, 'displays[page_1]' => 1], 'Apply');
    $this->submitForm([], 'Save');

    // Change the machine name of the page.
    $this->drupalGet('admin/structure/views/nojs/display/' . $view['id'] . '/page_1/display_id');
    $this->submitForm(['display_id' => 'page_new_id'], 'Apply');
    $this->submitForm([], 'Save');

    // Check that the attachment is still attached to the page.
    $this->drupalGet($attachment_display_url);
    $this->assertSession()->checkboxChecked("edit-displays-default");
    $this->assertSession()->checkboxChecked("edit-displays-page-new-id");

  }

}
