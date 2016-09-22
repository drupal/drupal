<?php

namespace Drupal\Tests\views\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\simpletest\ContentTypeCreationTrait;
use Drupal\simpletest\NodeCreationTrait;
use Drupal\views\Tests\ViewTestData;

/**
 * Tests the click sorting AJAX functionality of Views exposed forms.
 *
 * @group views
 */
class PaginationAJAXTest extends JavascriptTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'views', 'views_test_config'];

  /**
   * @var array
   * Test Views to enable.
   */
  public static $testViews = ['test_content_ajax'];

  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    ViewTestData::createTestViews(self::class, ['views_test_config']);

    // Create a Content type and eleven test nodes.
    $this->createContentType(['type' => 'page']);
    for ($i = 1; $i <= 11; $i++) {
      $this->createNode(['title' => 'Node ' . $i . ' content', 'changed' => $i * 1000]);
    }

    // Create a user privileged enough to view content.
    $user = $this->drupalCreateUser([
      'administer site configuration',
      'access content',
      'access content overview',
    ]);
    $this->drupalLogin($user);
  }

  /**
   * Tests if pagination via AJAX works for the "Content" View.
   */
  public function testBasicPagination() {
    // Visit the content page.
    $this->drupalGet('test-content-ajax');

    $session_assert = $this->assertSession();

    $page = $this->getSession()->getPage();

    // Set the number of items displayed per page to 5 using the exposed pager.
    $page->selectFieldOption('edit-items-per-page', 5);
    $page->pressButton('Filter');
    $session_assert->assertWaitOnAjaxRequest();

    // Change 'Updated' sorting from descending to ascending.
    $page->clickLink('Updated');
    $session_assert->assertWaitOnAjaxRequest();

    // Use the pager by clicking on the links and test if we see the expected
    // number of rows on each page. For easy targeting the titles of the pager
    // links are used.
    /** @var \Behat\Mink\Element\NodeElement[] $rows */
    $rows = $page->findAll('css', 'tbody tr');
    $this->assertCount(5, $rows);
    $this->assertContains('Node 1 content', $rows[0]->getHtml());

    $this->clickLink('Go to page 2');
    $session_assert->assertWaitOnAjaxRequest();
    $rows = $page->findAll('css', 'tbody tr');
    $this->assertCount(5, $rows);
    $this->assertContains('Node 6 content', $rows[0]->getHtml());

    $this->clickLink('Go to page 3');
    $session_assert->assertWaitOnAjaxRequest();
    $rows = $page->findAll('css', 'tbody tr');
    $this->assertCount(1, $rows);
    $this->assertContains('Node 11 content', $rows[0]->getHtml());

    // Navigate back to the first page.
    $this->clickLink('Go to first page');
    $session_assert->assertWaitOnAjaxRequest();
    $rows = $page->findAll('css', 'tbody tr');
    $this->assertCount(5, $rows);
    $this->assertContains('Node 1 content', $rows[0]->getHtml());

    // Navigate using the 'next' link.
    $this->clickLink('Go to next page');
    $session_assert->assertWaitOnAjaxRequest();
    $rows = $page->findAll('css', 'tbody tr');
    $this->assertCount(5, $rows);
    $this->assertContains('Node 6 content', $rows[0]->getHtml());

    // Navigate using the 'last' link.
    $this->clickLink('Go to last page');
    $session_assert->assertWaitOnAjaxRequest();
    $rows = $page->findAll('css', 'tbody tr');
    $this->assertCount(1, $rows);
    $this->assertContains('Node 11 content', $rows[0]->getHtml());
  }

}
