<?php

namespace Drupal\Tests\views\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\views\Tests\ViewTestData;

/**
 * Tests the click sorting AJAX functionality of Views exposed forms.
 *
 * @group views
 */
class ClickSortingAJAXTest extends WebDriverTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'views', 'views_test_config'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  public static $testViews = ['test_content_ajax'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    ViewTestData::createTestViews(self::class, ['views_test_config']);

    // Create a Content type and two test nodes.
    $this->createContentType(['type' => 'page']);
    $this->createNode(['title' => 'Page A', 'changed' => REQUEST_TIME]);
    $this->createNode(['title' => 'Page B', 'changed' => REQUEST_TIME + 1000]);

    // Create a user privileged enough to view content.
    $user = $this->drupalCreateUser([
      'administer site configuration',
      'access content',
      'access content overview',
    ]);
    $this->drupalLogin($user);
  }

  /**
   * Tests if sorting via AJAX works for the "Content" View.
   */
  public function testClickSorting() {
    // Visit the content page.
    $this->drupalGet('test-content-ajax');

    $session_assert = $this->assertSession();

    $page = $this->getSession()->getPage();

    // Ensure that the Content we're testing for is in the right order, default
    // sorting is by changed timestamp so the last created node should be first.
    /** @var \Behat\Mink\Element\NodeElement[] $rows */
    $rows = $page->findAll('css', 'tbody tr');
    $this->assertCount(2, $rows);
    $this->assertStringContainsString('Page B', $rows[0]->getHtml());
    $this->assertStringContainsString('Page A', $rows[1]->getHtml());

    // Now sort by title and check if the order changed.
    $page->clickLink('Title');
    $session_assert->assertWaitOnAjaxRequest();
    $rows = $page->findAll('css', 'tbody tr');
    $this->assertCount(2, $rows);
    $this->assertStringContainsString('Page A', $rows[0]->getHtml());
    $this->assertStringContainsString('Page B', $rows[1]->getHtml());
  }

}
