<?php

namespace Drupal\Tests\inline_form_errors\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\Entity\NodeType;

/**
 * Tests Inline Form Errors compatibility with Quick Edit.
 *
 * @group inline_form_errors
 */
class FormErrorHandlerQuickEditTest extends WebDriverTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'quickedit',
    'node',
    'inline_form_errors',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * An editor user with permissions to access the in-place editor.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $editorUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a page node type for testing.
    NodeType::create(['type' => 'page', 'name' => 'page'])->save();

    // Create a user with the permission to use in-place editing.
    $permissions = [
      'access content',
      'create page content',
      'edit any page content',
      'access contextual links',
      'access in-place editing',
    ];
    $this->editorUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->editorUser);
  }

  /**
   * Tests that the inline form errors are not visible for Quick Edit forms.
   */
  public function testDisabledInlineFormErrors() {
    $session = $this->getSession();
    $web_assert = $this->assertSession();

    // Create a page node.
    $node = $this->drupalCreateNode();

    // Visit the node page.
    $this->drupalGet('node/' . $node->id());

    // Wait until the quick edit link is available.
    $web_assert->waitForElement('css', '.quickedit > a');

    // Activate the quick editing mode.
    $session->executeScript("jQuery('article.node').find('.quickedit > a').click()");

    $web_assert->waitForElement('css', '.quickedit-toolbar');

    // Clear the title field. Trigger a 'keyup' to be able to save the changes.
    $session->executeScript("jQuery('.field--name-title').text('').trigger('keyup')");

    // Try to save the changes.
    $save_button = $web_assert->waitForElement('css', '.action-save.quickedit-button');
    $save_button->click();

    // Wait until the form submission is complete.
    $web_assert->assertWaitOnAjaxRequest();

    // Assert that no error summary from Inline Form Errors is shown.
    $web_assert->elementTextNotContains('css', '.quickedit-validation-errors', '1 error has been found');

    // Assert that the required title error is shown.
    $web_assert->elementTextContains('css', '.quickedit-validation-errors', 'Title field is required.');
  }

}
