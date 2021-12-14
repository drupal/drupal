<?php

namespace Drupal\Tests\node\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the JavaScript updating of summaries on content type form.
 *
 * @group node
 */
class TestSettingSummariesContentType extends WebDriverTestBase {

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

    $admin_user = $this->drupalCreateUser(['administer content types']);
    $this->drupalLogin($admin_user);
    $this->drupalCreateContentType(['type' => 'test']);
  }

  /**
   * Tests a vertical tab 'Workflow' summary.
   */
  public function testWorkflowSummary() {
    $this->drupalGet('admin/structure/types/manage/test');
    $page = $this->getSession()->getPage();
    $page->find('css', 'a[href="#edit-workflow"]')->click();
    $this->assertSession()->waitForElementVisible('css', '[name="options[status]"]');
    $page->findField('options[status]')->uncheck();
    $page->findField('options[sticky]')->check();
    $page->findField('options[promote]')->check();
    $page->findField('options[revision]')->check();
    $locator = '[href="#edit-workflow"] .vertical-tabs__menu-item-summary';
    $page->waitFor(10, function () use ($page, $locator) {
      $summary = $page->find('css', $locator)->getText();
      return strpos('Not published', $summary) !== FALSE;
    });
    $summary = $page->find('css', $locator)->getText();
    $this->assertEquals('Not published, Promoted to front page, Sticky at top of lists, Create new revision', $summary);
  }

}
