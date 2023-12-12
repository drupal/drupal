<?php

declare(strict_types=1);

namespace Drupal\FunctionalJavascriptTests\Theme;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests Claro's Views Bulk Operations form.
 *
 * @group claro
 */
class ClaroViewsBulkOperationsTest extends WebDriverTestBase {
  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'views'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'claro';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

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
  }

  /**
   * Tests the dynamic Bulk Operations form.
   */
  public function testBulkOperationsUi() {
    $this->drupalGet('admin/content');

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $no_items_selected = 'No items selected';
    $one_item_selected = '1 item selected';
    $two_items_selected = '2 items selected';
    $vbo_available_message = 'Bulk actions are now available';
    $this->assertNotNull($assert_session->waitForElementVisible('css', ".js-views-bulk-actions-status:contains(\"$no_items_selected\")"));
    $select_all = $page->find('css', '.select-all > input');

    $page->checkField('node_bulk_form[0]');
    $this->assertNotNull($assert_session->waitForElementVisible('css', ".js-views-bulk-actions-status:contains(\"$one_item_selected\")"));

    // When the bulk operations controls are first activated, this should be
    // relayed to screen readers.
    $this->assertNotNull($assert_session->waitForElement('css', "#drupal-live-announce:contains(\"$vbo_available_message\")"));
    $this->assertFalse($select_all->isChecked());

    $page->checkField('node_bulk_form[1]');
    $this->assertNotNull($assert_session->waitForElementVisible('css', ".js-views-bulk-actions-status:contains(\"$two_items_selected\")"));
    $this->assertNotNull($assert_session->waitForElement('css', "#drupal-live-announce:contains(\"$two_items_selected\")"));
    $assert_session->pageTextNotContains($vbo_available_message);
    $this->assertTrue($select_all->isChecked());

    $page->uncheckField('node_bulk_form[0]');
    $this->assertNotNull($assert_session->waitForElementVisible('css', ".js-views-bulk-actions-status:contains(\"$one_item_selected\")"));
    $this->assertNotNull($assert_session->waitForElement('css', "#drupal-live-announce:contains(\"$one_item_selected\")"));
    $assert_session->pageTextNotContains($vbo_available_message);
    $this->assertFalse($select_all->isChecked());

    $page->uncheckField('node_bulk_form[1]');
    $this->assertNotNull($assert_session->waitForElementVisible('css', ".js-views-bulk-actions-status:contains(\"$no_items_selected\")"));
    $this->assertNotNull($assert_session->waitForElement('css', "#drupal-live-announce:contains(\"$no_items_selected\")"));
    $assert_session->pageTextNotContains($vbo_available_message);
    $this->assertFalse($select_all->isChecked());

    $select_all->check();
    $this->assertNotNull($assert_session->waitForElementVisible('css', ".js-views-bulk-actions-status:contains(\"$two_items_selected\")"));
    $this->assertNotNull($assert_session->waitForElement('css', "#drupal-live-announce:contains(\"$vbo_available_message\")"));
    $this->assertNotNull($assert_session->waitForElement('css', "#drupal-live-announce:contains(\"$two_items_selected\")"));

    $select_all->uncheck();
    $this->assertNotNull($assert_session->waitForElementVisible('css', ".js-views-bulk-actions-status:contains(\"$no_items_selected\")"));
    $this->assertNotNull($assert_session->waitForElement('css', "#drupal-live-announce:contains(\"$no_items_selected\")"));
    $assert_session->pageTextNotContains($vbo_available_message);
  }

}
