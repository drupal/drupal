<?php

declare(strict_types=1);

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
class PaginationAJAXTest extends WebDriverTestBase {

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

  /**
   * @var array
   * Test Views to enable.
   */
  public static $testViews = ['test_content_ajax'];

  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    ViewTestData::createTestViews(self::class, ['views_test_config']);

    // Create a Content type and eleven test nodes.
    $this->createContentType(['type' => 'page']);
    for ($i = 1; $i <= 11; $i++) {
      $fields = [
        'title' => $i > 6 ? 'Node ' . $i . ' content' : 'Node ' . $i . ' content default_value',
        'changed' => $i * 1000,
      ];
      $this->createNode($fields);
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

    $settings = $this->getDrupalSettings();

    // Make sure that the view_path is set correctly.
    $expected_view_path = '/test-content-ajax';
    $this->assertEquals($expected_view_path, current($settings['views']['ajaxViews'])['view_path']);

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
    $this->assertStringContainsString('Node 1 content default_value', $rows[0]->getHtml());

    $this->clickLink('Go to page 2');
    $session_assert->assertWaitOnAjaxRequest();
    $rows = $page->findAll('css', 'tbody tr');
    $this->assertCount(5, $rows);
    $this->assertStringContainsString('Node 6 content default_value', $rows[0]->getHtml());
    $link = $page->findLink('Go to page 3');
    $this->assertNoDuplicateAssetsOnPage();

    // Test that no unwanted parameters are added to the URL.
    $this->assertEquals('?status=All&type=All&title=&langcode=All&items_per_page=5&order=changed&sort=asc&page=2', $link->getAttribute('href'));

    $this->clickLink('Go to page 3');
    $session_assert->assertWaitOnAjaxRequest();
    $rows = $page->findAll('css', 'tbody tr');
    $this->assertCount(1, $rows);
    $this->assertStringContainsString('Node 11 content', $rows[0]->getHtml());

    // Navigate back to the first page.
    $this->clickLink('Go to first page');
    $session_assert->assertWaitOnAjaxRequest();
    $rows = $page->findAll('css', 'tbody tr');
    $this->assertCount(5, $rows);
    $this->assertStringContainsString('Node 1 content default_value', $rows[0]->getHtml());

    // Navigate using the 'next' link.
    $this->clickLink('Go to next page');
    $session_assert->assertWaitOnAjaxRequest();
    $rows = $page->findAll('css', 'tbody tr');
    $this->assertCount(5, $rows);
    $this->assertStringContainsString('Node 6 content default_value', $rows[0]->getHtml());

    // Navigate using the 'last' link.
    $this->clickLink('Go to last page');
    $session_assert->assertWaitOnAjaxRequest();
    $rows = $page->findAll('css', 'tbody tr');
    $this->assertCount(1, $rows);
    $this->assertStringContainsString('Node 11 content', $rows[0]->getHtml());

    // Make sure the AJAX calls don't change the view_path.
    $settings = $this->getDrupalSettings();
    $this->assertEquals($expected_view_path, current($settings['views']['ajaxViews'])['view_path']);
  }

  /**
   * Tests if pagination via AJAX works for the filter with default value.
   */
  public function testDefaultFilterPagination() {
    // Add default value to the title filter.
    $view = \Drupal::configFactory()->getEditable('views.view.test_content_ajax');
    $display = $view->get('display');
    $display['default']['display_options']['filters']['title']['value'] = 'default_value';
    $view->set('display', $display);
    $view->save();

    // Visit the content page.
    $this->drupalGet('test-content-ajax');

    $session_assert = $this->assertSession();

    $page = $this->getSession()->getPage();

    $settings = $this->getDrupalSettings();

    // Make sure that the view_path is set correctly.
    $expected_view_path = '/test-content-ajax';
    $this->assertEquals($expected_view_path, current($settings['views']['ajaxViews'])['view_path']);

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
    $this->assertStringContainsString('Node 1 content default_value', $rows[0]->getHtml());

    $this->clickLink('Go to page 2');
    $session_assert->assertWaitOnAjaxRequest();
    $rows = $page->findAll('css', 'tbody tr');
    $this->assertCount(1, $rows);
    $this->assertStringContainsString('Node 6 content default_value', $rows[0]->getHtml());
    $link = $page->findLink('Go to page 1');
    $this->assertNoDuplicateAssetsOnPage();

    // Test that no unwanted parameters are added to the URL.
    $this->assertEquals('?status=All&type=All&title=default_value&langcode=All&items_per_page=5&order=changed&sort=asc&page=0', $link->getAttribute('href'));

    // Set the title filter to empty string using the exposed pager.
    $page->fillField('title', '');
    $page->pressButton('Filter');
    $session_assert->assertWaitOnAjaxRequest();
    $rows = $page->findAll('css', 'tbody tr');
    $this->assertCount(5, $rows);
    $this->assertStringContainsString('Node 11 content', $rows[0]->getHtml());

    // Navigate to the second page.
    $this->clickLink('Go to page 2');
    $session_assert->assertWaitOnAjaxRequest();
    $rows = $page->findAll('css', 'tbody tr');
    $this->assertCount(5, $rows);
    $this->assertStringContainsString('Node 6 content default_value', $rows[0]->getHtml());
    $link = $page->findLink('Go to page 1');
    $this->assertNoDuplicateAssetsOnPage();

    // Test that no unwanted parameters are added to the URL.
    $this->assertEquals('?status=All&type=All&title=&langcode=All&items_per_page=5&page=0', $link->getAttribute('href'));

    // Navigate back to the first page.
    $this->clickLink('Go to first page');
    $session_assert->assertWaitOnAjaxRequest();
    $rows = $page->findAll('css', 'tbody tr');
    $this->assertCount(5, $rows);
    $this->assertStringContainsString('Node 11 content', $rows[0]->getHtml());

    // Navigate using the 'next' link.
    $this->clickLink('Go to next page');
    $session_assert->assertWaitOnAjaxRequest();
    $rows = $page->findAll('css', 'tbody tr');
    $this->assertCount(5, $rows);
    $this->assertStringContainsString('Node 6 content default_value', $rows[0]->getHtml());

    // Navigate using the 'last' link.
    $this->clickLink('Go to last page');
    $session_assert->assertWaitOnAjaxRequest();
    $rows = $page->findAll('css', 'tbody tr');
    $this->assertCount(1, $rows);
    $this->assertStringContainsString('Node 1 content default_value', $rows[0]->getHtml());

    // Make sure the AJAX calls don't change the view_path.
    $settings = $this->getDrupalSettings();
    $this->assertEquals($expected_view_path, current($settings['views']['ajaxViews'])['view_path']);
  }

  /**
   * Assert that assets are not loaded twice on a page.
   *
   * @internal
   */
  protected function assertNoDuplicateAssetsOnPage(): void {
    /** @var \Behat\Mink\Element\NodeElement[] $scripts */
    $scripts = $this->getSession()->getPage()->findAll('xpath', '//script');
    $script_src = [];
    foreach ($scripts as $script) {
      $this->assertNotContains($script->getAttribute('src'), $script_src);
      $script_src[] = $script->getAttribute('src');
    }
  }

}
