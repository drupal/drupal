<?php

namespace Drupal\Tests\views\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\simpletest\ContentTypeCreationTrait;
use Drupal\simpletest\NodeCreationTrait;

/**
 * Tests the basic AJAX functionality of Views exposed forms.
 *
 * @group views
 */
class ExposedFilterAJAXTest extends JavascriptTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'views'];

  /**
   * Tests if exposed filtering via AJAX works for the "Content" View.
   */
  public function testExposedFiltering() {
    // Enable AJAX on the /admin/content View.
    \Drupal::configFactory()->getEditable('views.view.content')
      ->set('display.default.display_options.use_ajax', TRUE)
      ->save();

    // Create a Content type and two test nodes.
    $this->createContentType(['type' => 'page']);
    $this->createNode(['title' => 'Page One']);
    $this->createNode(['title' => 'Page Two']);

    // Create a user privileged enough to use exposed filters and view content.
    $user = $this->drupalCreateUser([
      'administer site configuration',
      'access content',
      'access content overview',
      'edit any page content',
    ]);
    $this->drupalLogin($user);

    // Visit the View page.
    $this->drupalGet('admin/content');

    $session = $this->getSession();

    // Ensure that the Content we're testing for is present.
    $html = $session->getPage()->getHtml();
    $this->assertContains('Page One', $html);
    $this->assertContains('Page Two', $html);

    // Search for "Page One".
    $this->submitForm(['title' => 'Page One'], t('Filter'));
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Verify that only the "Page One" Node is present.
    $html = $session->getPage()->getHtml();
    $this->assertContains('Page One', $html);
    $this->assertNotContains('Page Two', $html);

    // Search for "Page Two".
    $this->submitForm(['title' => 'Page Two'], t('Filter'));
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Verify that only the "Page Two" Node is present.
    $html = $session->getPage()->getHtml();
    $this->assertContains('Page Two', $html);
    $this->assertNotContains('Page One', $html);

    // Submit bulk actions form to ensure that the previous AJAX submit does not
    // break it.
    $this->submitForm([
      'action' => 'node_make_sticky_action',
      'node_bulk_form[0]' => TRUE,
    ], t('Apply to selected items'));

    // Verify that the action was performed.
    $this->assertSession()->pageTextContains('Make content sticky was applied to 1 item.');

    // Reset the form.
    $this->submitForm([], t('Reset'));
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->assertSession()->pageTextContains('Page One');
    $this->assertSession()->pageTextContains('Page Two');
    $this->assertFalse($session->getPage()->hasButton('Reset'));
  }

}
