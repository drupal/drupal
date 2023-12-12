<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\FunctionalJavascript;

/**
 * Tests for CKEditor 5 to ensure correct styling in off-canvas.
 *
 * @group ckeditor5
 * @internal
 */
class CKEditor5OffCanvasTest extends CKEditor5TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'ckeditor5',
    'ckeditor5_test',
  ];

  /**
   * Tests if CKEditor is properly styled inside an off-canvas dialog.
   */
  public function testOffCanvasStyles() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->addNewTextFormat($page, $assert_session);

    $this->drupalGet('/ckeditor5_test/off_canvas');

    // The "Add Node" link triggers an off-canvas dialog with an add node form
    // that includes CKEditor.
    $page->clickLink('Add Node');
    $assert_session->waitForElementVisible('css', '#drupal-off-canvas-wrapper');
    $assert_session->assertWaitOnAjaxRequest();

    $styles = $assert_session->elementExists('css', 'style#ckeditor5-off-canvas-reset');
    $this->assertStringContainsString('#drupal-off-canvas-wrapper [data-drupal-ck-style-fence]', $styles->getHtml());

    $assert_session->elementExists('css', '.ck');

    $ckeditor_toolbar_bg_color = $this->getSession()->evaluateScript('window.getComputedStyle(document.querySelector(\'.ck.ck-toolbar\')).backgroundColor');
    $this->assertEquals('rgb(255, 255, 255)', $ckeditor_toolbar_bg_color, 'Toolbar background-color should be unaffected by off-canvas');
    // Editable area should be visible.
    $assert_session->elementExists('css', '.ck .ck-content');
    $ckeditor_editable_bg_color = $this->getSession()->evaluateScript('window.getComputedStyle(document.querySelector(\'.ck.ck-content\')).backgroundColor');
    $this->assertEquals('rgb(255, 255, 255)', $ckeditor_editable_bg_color, 'Content background-color should be unaffected by off-canvas');
  }

}
