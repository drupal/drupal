<?php

namespace Drupal\Tests\node\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests JavaScript functionality specific to delete operations.
 *
 * @group node
 */
class NodeDeleteConfirmTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalCreateContentType([
      'name' => 'Article',
      'type' => 'article',
    ]);
    $admin_user = $this->drupalCreateUser([
      'access content',
      'access content overview',
      'administer content types',
      'edit any article content',
      'delete any article content',
    ]);
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests that the node delete operation opens in a modal.
   */
  public function testNodeDelete() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // Test the delete operation on an item in admin/content .
    $node = $this->createNode([
      'type' => 'article',
      'title' => 'Delete article from content list',
    ]);
    $node->save();

    $this->drupalGet('admin/content');

    $page->find('css', '.dropbutton-toggle button')->click();
    $page->clickLink('Delete');

    // Asserts a dialog opens with the expected text.
    $this->assertEquals('Are you sure you want to delete the content item Delete article from content list?', $assert_session->waitForElement('css', '.ui-dialog-title')->getText());
    $page->find('css', '.ui-dialog-buttonset')->pressButton('Delete');

    $assert_session->waitForText('The Article Delete article from content list has been deleted.');
    // Assert that the node is deleted in above operation.
    $this->drupalGet('/admin/content');
    $assert_session->waitForText('There are no content items yet.');

    $node = $this->createNode([
      'type' => 'article',
      'title' => 'Delete article from entity form',
    ]);
    $node->save();

    // Tests the delete modal on its entity form.
    $this->drupalGet('node/2/edit');
    $this->clickLink('Delete');
    $this->assertEquals('Are you sure you want to delete the content item Delete article from entity form?', $assert_session->waitForElement('css', '.ui-dialog-title')->getText());

    $page->find('css', '.ui-dialog-buttonset')->pressButton('Delete');
    $this->assertSession()->pageTextContains('The Article Delete article from entity form has been deleted.');

    // Assert that the node is deleted in above operation.
    $this->drupalGet('/admin/content');
    $assert_session->waitForText('There are no content items yet.');
  }

  /**
   * Tests that the node type delete operation opens in a modal.
   */
  public function testNodeTypeDelete() {
    $page = $this->getSession()->getPage();

    // Delete node type using link on the content type list.
    $this->drupalGet('admin/structure/types');
    $this->assertSession()->waitForText('Article');
    $page->find('css', '.dropbutton-toggle button')->click();
    $this->clickLink('Delete');
    $this->assertEquals('Are you sure you want to delete the content type Article?', $this->assertSession()->waitForElement('css', '.ui-dialog-title')->getText());
    $page->find('css', '.ui-dialog-buttonset')->pressButton('Delete');
    $this->assertSession()->pageTextContains('The content type Article has been deleted.');

    $this->drupalCreateContentType([
      'name' => 'Article',
      'type' => 'article',
    ]);

    // Delete node type using link on the edit content type form.
    $this->drupalGet('admin/structure/types/manage/article');
    $this->clickLink('Delete');
    $this->assertEquals('Are you sure you want to delete the content type Article?', $this->assertSession()->waitForElement('css', '.ui-dialog-title')->getText());
    $page->find('css', '.ui-dialog-buttonset')->pressButton('Delete');
    $this->assertSession()->pageTextContains('The content type Article has been deleted.');
  }

}
