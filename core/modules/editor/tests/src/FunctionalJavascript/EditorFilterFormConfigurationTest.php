<?php

namespace Drupal\Tests\editor\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;

/**
 * Tests Text Editor's integration with the Text Format configuration form.
 *
 * @group editor
 */
class EditorFilterFormConfigurationTest extends JavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['ckeditor'];

  /**
   * Tests switching between editors.
   */
  public function testSwitchingEditors() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->createUser(['administer filters']));

    // Configure a format to use CKEditor.
    $this->drupalGet('admin/config/content/formats');
    $assert_session->pageTextNotContains('CKEditor');
    $this->clickLink('Configure');

    $page->selectFieldOption('editor[editor]', 'ckeditor');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->elementExists('css', '#ckeditor-button-configuration');

    $page->pressButton('Save configuration');
    $assert_session->pageTextContains('The text format Plain text has been updated.');
    $assert_session->pageTextContains('CKEditor');

    // Switch between no editor and CKEditor to ensure the originally configured
    // editor is not lost.
    $this->clickLink('Configure');
    $assert_session->elementExists('css', '#ckeditor-button-configuration');

    $page->selectFieldOption('editor[editor]', '_none');
    $assert_session->assertWaitOnAjaxRequest();
    $this->htmlOutput($page->getContent());
    $assert_session->elementNotExists('css', '#ckeditor-button-configuration');

    $page->selectFieldOption('editor[editor]', 'ckeditor');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->elementExists('css', '#ckeditor-button-configuration');

    $page->pressButton('Save configuration');
    $assert_session->pageTextContains('The text format Plain text has been updated.');
    $assert_session->pageTextContains('CKEditor');
  }

}
