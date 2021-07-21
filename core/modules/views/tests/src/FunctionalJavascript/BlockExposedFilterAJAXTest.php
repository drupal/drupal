<?php

namespace Drupal\Tests\views\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\views\Tests\ViewTestData;

/**
 * Tests the exposed filter ajax functionality in a block.
 *
 * @group views
 */
class BlockExposedFilterAJAXTest extends WebDriverTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'views', 'block', 'views_test_config'];

  public static $testViews = ['test_block_exposed_ajax', 'test_block_exposed_ajax_with_page'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    ViewTestData::createTestViews(self::class, ['views_test_config']);
    $this->createContentType(['type' => 'page']);
    $this->createContentType(['type' => 'article']);
    $this->createNode(['title' => 'Page A']);
    $this->createNode(['title' => 'Page B']);
    $this->createNode(['title' => 'Article A', 'type' => 'article']);

    $this->drupalLogin($this->drupalCreateUser([
      'access content',
    ]));
  }

  /**
   * Tests if exposed filtering and reset works with a views block and ajax.
   */
  public function testExposedFilteringAndReset() {
    $node = $this->createNode();
    $block = $this->drupalPlaceBlock('views_block:test_block_exposed_ajax-block_1');
    $this->drupalGet($node->toUrl());

    $page = $this->getSession()->getPage();

    // Ensure that the Content we're testing for is present.
    $html = $page->getHtml();
    $this->assertStringContainsString('Page A', $html);
    $this->assertStringContainsString('Page B', $html);
    $this->assertStringContainsString('Article A', $html);

    // Filter by page type.
    $this->submitForm(['type' => 'page'], 'Apply');
    $this->assertSession()->waitForElementRemoved('xpath', '//*[text()="Article A"]');

    // Verify that only the page nodes are present.
    $html = $page->getHtml();
    $this->assertStringContainsString('Page A', $html);
    $this->assertStringContainsString('Page B', $html);
    $this->assertStringNotContainsString('Article A', $html);

    // Reset the form.
    $this->submitForm([], 'Reset');
    // Assert we are still on the node page.
    $html = $page->getHtml();
    // Repeat the original tests.
    $this->assertStringContainsString('Page A', $html);
    $this->assertStringContainsString('Page B', $html);
    $this->assertStringContainsString('Article A', $html);
    $this->assertSession()->addressEquals('node/' . $node->id());

    $block->delete();
    // Do the same test with a block that has a page display to test the user
    // is redirected to the page display.
    $this->drupalPlaceBlock('views_block:test_block_exposed_ajax_with_page-block_1');
    $this->drupalGet($node->toUrl());
    $this->submitForm(['type' => 'page'], 'Apply');
    $this->assertSession()->waitForElementRemoved('xpath', '//*[text()="Article A"]');
    $this->submitForm([], 'Reset');
    $this->assertSession()->addressEquals('some-path');
  }

  /**
   * Tests if exposed forms works when multiple instances of the same view
   * is present on the page.
   */
  public function testMultipleExposedFormsForTheSameView() {
    $this->drupalPlaceBlock('views_exposed_filter_block:test_block_exposed_ajax_with_page-page_2', ['region' => 'content', 'weight' => -100, 'id' => 'page-exposed-form']);
    $this->drupalPlaceBlock('views_block:test_block_exposed_ajax_with_page-block_1', ['id' => 'block-one-exposed-form', 'weight' => 0]);
    $this->drupalPlaceBlock('views_block:test_block_exposed_ajax_with_page-block_1', ['id' => 'block-two-exposed-form', 'weight' => 10]);

    $assert_session = $this->assertSession();

    // Go to the page and check that all 3 views are displaying correct
    // results.
    $this->drupalGet('some-other-path');

    $views = $this->getSession()->getPage()->findAll('css', '.views-element-container');
    $content = $views[0]->getHtml();
    $this->assertStringContainsString('Page A', $content);
    $this->assertStringContainsString('Page B', $content);
    $this->assertStringContainsString('Article A', $content);

    $content = $views[1]->getHtml();
    $this->assertStringContainsString('Page A', $content);
    $this->assertStringContainsString('Page B', $content);
    $this->assertStringContainsString('Article A', $content);

    $content = $views[2]->getHtml();
    $this->assertStringContainsString('Page A', $content);
    $this->assertStringContainsString('Page B', $content);
    $this->assertStringContainsString('Article A', $content);

    // Ensure that page view exposed form (displayed as block) does not
    // affect other two block views.
    // Find the form HTML ID.
    $element = $assert_session->elementExists('css', '#block-page-exposed-form .views-exposed-form');
    $form_id = $element->getAttribute('id');
    // Filter by article.
    $this->submitForm(['type' => 'article'], 'Apply', $form_id);
    $assert_session->waitForElementRemoved('xpath', '//div[@id="block-page-exposed-form"]/following::span[1][text()="Page A"]');

    // Verify that only page view has been filtered.
    $views = $this->getSession()->getPage()->findAll('css', '.views-element-container');
    $content = $views[0]->getHtml();
    $this->assertStringNotContainsString('Page A', $content);
    $this->assertStringNotContainsString('Page B', $content);
    $this->assertStringContainsString('Article A', $content);
    $content = $views[1]->getHtml();
    $this->assertStringContainsString('Page A', $content);
    $this->assertStringContainsString('Page B', $content);
    $this->assertStringContainsString('Article A', $content);
    $content = $views[2]->getHtml();
    $this->assertStringContainsString('Page A', $content);
    $this->assertStringContainsString('Page B', $content);
    $this->assertStringContainsString('Article A', $content);

    // Find the form HTML ID.
    $element = $assert_session->elementExists('css', '#block-page-exposed-form .views-exposed-form');
    $form_id = $element->getAttribute('id');
    // Filter by page.
    $this->submitForm(['type' => 'page'], 'Apply', $form_id);
    $assert_session->waitForElementRemoved('xpath', '//div[@id="block-page-exposed-form"]/following::span[1][text()="Article A"]');

    // Verify that only page view has been filtered.
    $views = $this->getSession()->getPage()->findAll('css', '.views-element-container');
    $content = $views[0]->getHtml();
    $this->assertStringContainsString('Page A', $content);
    $this->assertStringContainsString('Page B', $content);
    $this->assertStringNotContainsString('Article A', $content);
    $content = $views[1]->getHtml();
    $this->assertStringContainsString('Page A', $content);
    $this->assertStringContainsString('Page B', $content);
    $this->assertStringContainsString('Article A', $content);
    $content = $views[2]->getHtml();
    $this->assertStringContainsString('Page A', $content);
    $this->assertStringContainsString('Page B', $content);
    $this->assertStringContainsString('Article A', $content);

    // Find the form HTML ID.
    $element = $assert_session->elementExists('css', '#block-page-exposed-form .views-exposed-form');
    $form_id = $element->getAttribute('id');
    // Disable filter.
    $this->submitForm(['type' => 'All'], 'Apply', $form_id);
    $assert_session->waitForElement('xpath', '//div[@id="block-page-exposed-form"]/following::span[1][text()="Article A"]');

    // Ensure that the first block view exposed form does not affect the page
    // view and the other block view.
    // Find the form HTML ID.
    $element = $assert_session->elementExists('css', '#block-block-one-exposed-form .views-exposed-form');
    $form_id = $element->getAttribute('id');
    // Filter by article.
    $this->submitForm(['type' => 'article'], 'Apply', $form_id);
    $assert_session->waitForElementRemoved('xpath', '//div[@id="block-block-one-exposed-form"]//*[text()="Page A"]');

    // Verify that only the first block view has been filtered.
    $views = $this->getSession()->getPage()->findAll('css', '.views-element-container');
    $content = $views[0]->getHtml();
    $this->assertStringContainsString('Page A', $content);
    $this->assertStringContainsString('Page B', $content);
    $this->assertStringContainsString('Article A', $content);
    $content = $views[1]->getHtml();
    $this->assertStringNotContainsString('Page A', $content);
    $this->assertStringNotContainsString('Page B', $content);
    $this->assertStringContainsString('Article A', $content);
    $content = $views[2]->getHtml();
    $this->assertStringContainsString('Page A', $content);
    $this->assertStringContainsString('Page B', $content);
    $this->assertStringContainsString('Article A', $content);

    // Find the form HTML ID.
    $element = $assert_session->elementExists('css', '#block-block-one-exposed-form .views-exposed-form');
    $form_id = $element->getAttribute('id');
    // Filter by page.
    $this->submitForm(['type' => 'page'], 'Apply', $form_id);
    $assert_session->waitForElementRemoved('xpath', '//div[@id="block-block-one-exposed-form"]//*[text()="Article A"]');

    // Verify that only the first block view has been filtered.
    $views = $this->getSession()->getPage()->findAll('css', '.views-element-container');
    $content = $views[0]->getHtml();
    $this->assertStringContainsString('Page A', $content);
    $this->assertStringContainsString('Page B', $content);
    $this->assertStringContainsString('Article A', $content);
    $content = $views[1]->getHtml();
    $this->assertStringContainsString('Page A', $content);
    $this->assertStringContainsString('Page B', $content);
    $this->assertStringNotContainsString('Article A', $content);
    $content = $views[2]->getHtml();
    $this->assertStringContainsString('Page A', $content);
    $this->assertStringContainsString('Page B', $content);
    $this->assertStringContainsString('Article A', $content);

    // Find the form HTML ID.
    $element = $assert_session->elementExists('css', '#block-block-one-exposed-form .views-exposed-form');
    $form_id = $element->getAttribute('id');
    // Disable filter.
    $this->submitForm(['type' => 'All'], 'Apply', $form_id);
    $assert_session->waitForElement('xpath', '//div[@id="block-block-one-exposed-form"]//*[text()="Article A"]');

    // Ensure that the second block view exposed form does not affect the page
    // view and the other block view.
    // Find the form HTML ID.
    $element = $assert_session->elementExists('css', '#block-block-two-exposed-form .views-exposed-form');
    $form_id = $element->getAttribute('id');
    // Filter by article.
    $this->submitForm(['type' => 'article'], 'Apply', $form_id);
    $assert_session->waitForElementRemoved('xpath', '//div[@id="block-block-two-exposed-form"]//*[text()="Page A"]');

    // Verify that only the second block view has been filtered.
    $views = $this->getSession()->getPage()->findAll('css', '.views-element-container');
    $content = $views[0]->getHtml();
    $this->assertStringContainsString('Page A', $content);
    $this->assertStringContainsString('Page B', $content);
    $this->assertStringContainsString('Article A', $content);
    $content = $views[1]->getHtml();
    $this->assertStringContainsString('Page A', $content);
    $this->assertStringContainsString('Page B', $content);
    $this->assertStringContainsString('Article A', $content);
    $content = $views[2]->getHtml();
    $this->assertStringNotContainsString('Page A', $content);
    $this->assertStringNotContainsString('Page B', $content);
    $this->assertStringContainsString('Article A', $content);

    // Find the form HTML ID.
    $element = $assert_session->elementExists('css', '#block-block-two-exposed-form .views-exposed-form');
    $form_id = $element->getAttribute('id');
    // Filter by page.
    $this->submitForm(['type' => 'page'], 'Apply', $form_id);
    $assert_session->waitForElementRemoved('xpath', '//div[@id="block-block-two-exposed-form"]//*[text()="Article A"]');

    // Verify that only the second block view has been filtered.
    $views = $this->getSession()->getPage()->findAll('css', '.views-element-container');
    $content = $views[0]->getHtml();
    $this->assertStringContainsString('Page A', $content);
    $this->assertStringContainsString('Page B', $content);
    $this->assertStringContainsString('Article A', $content);
    $content = $views[1]->getHtml();
    $this->assertStringContainsString('Page A', $content);
    $this->assertStringContainsString('Page B', $content);
    $this->assertStringContainsString('Article A', $content);
    $content = $views[2]->getHtml();
    $this->assertStringContainsString('Page A', $content);
    $this->assertStringContainsString('Page B', $content);
    $this->assertStringNotContainsString('Article A', $content);

    // Find the form HTML ID.
    $element = $assert_session->elementExists('css', '#block-block-two-exposed-form .views-exposed-form');
    $form_id = $element->getAttribute('id');
    // Disable filter.
    $this->submitForm(['type' => 'All'], 'Apply', $form_id);
    $assert_session->waitForElement('xpath', '//div[@id="block-block-two-exposed-form"]//*[text()="Article A"]');

    // Ensure that the all forms works when used one by one.
    // Find the form HTML ID.
    $element = $assert_session->elementExists('css', '#block-page-exposed-form .views-exposed-form');
    $form_id = $element->getAttribute('id');
    // Filter by article.
    $this->submitForm(['type' => 'article'], 'Apply', $form_id);
    $assert_session->waitForElementRemoved('xpath', '//div[@id="block-page-exposed-form"]/following::span[1][text()="Page A"]');

    // Find the form HTML ID.
    $element = $assert_session->elementExists('css', '#block-block-one-exposed-form .views-exposed-form');
    $form_id = $element->getAttribute('id');
    // Filter by page.
    $this->submitForm(['type' => 'page'], 'Apply', $form_id);
    $assert_session->waitForElementRemoved('xpath', '//div[@id="block-block-one-exposed-form"]//*[text()="Page A"]');

    // Find the form HTML ID.
    $element = $assert_session->elementExists('css', '#block-block-two-exposed-form .views-exposed-form');
    $form_id = $element->getAttribute('id');
    // Filter by page.
    $this->submitForm(['type' => 'article'], 'Apply', $form_id);
    $assert_session->waitForElementRemoved('xpath', '//div[@id="block-block-two-exposed-form"]//*[text()="Page A"]');

    // Verify that all views has been filtered.
    $views = $this->getSession()->getPage()->findAll('css', '.views-element-container');
    $content = $views[0]->getHtml();
    $this->assertStringNotContainsString('Page A', $content);
    $this->assertStringNotContainsString('Page B', $content);
    $this->assertStringContainsString('Article A', $content);
    $content = $views[1]->getHtml();
    $this->assertStringContainsString('Page A', $content);
    $this->assertStringContainsString('Page B', $content);
    $this->assertStringNotContainsString('Article A', $content);
    $content = $views[2]->getHtml();
    $this->assertStringNotContainsString('Page A', $content);
    $this->assertStringNotContainsString('Page B', $content);
    $this->assertStringContainsString('Article A', $content);

    // Find the form HTML ID.
    $element = $assert_session->elementExists('css', '#block-block-two-exposed-form .views-exposed-form');
    $form_id = $element->getAttribute('id');
    // Disable filter.
    $this->submitForm(['type' => 'All'], 'Apply', $form_id);
    $assert_session->waitForElement('xpath', '//div[@id="block-block-two-exposed-form"]//*[text()="Page A"]');

    // Verify that all views has been filtered one more time.
    $views = $this->getSession()->getPage()->findAll('css', '.views-element-container');
    $content = $views[0]->getHtml();
    $this->assertStringNotContainsString('Page A', $content);
    $this->assertStringNotContainsString('Page B', $content);
    $this->assertStringContainsString('Article A', $content);
    $content = $views[1]->getHtml();
    $this->assertStringContainsString('Page A', $content);
    $this->assertStringContainsString('Page B', $content);
    $this->assertStringNotContainsString('Article A', $content);
    $content = $views[2]->getHtml();
    $this->assertStringContainsString('Page A', $content);
    $this->assertStringContainsString('Page B', $content);
    $this->assertStringContainsString('Article A', $content);
  }

}
