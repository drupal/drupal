<?php

declare(strict_types=1);

namespace Drupal\Tests\node\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the JavaScript updating of summaries on content type form.
 *
 * @group node
 */
class SettingSummariesContentTypeTest extends WebDriverTestBase {

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
  public function testWorkflowSummary(): void {
    $this->drupalGet('admin/structure/types/manage/test');
    $page = $this->getSession()->getPage();
    $page->find('css', 'a[href="#edit-workflow"]')->click();
    $this->assertSession()->waitForElementVisible('css', '[name="options[status]"]');
    $page->findField('options[status]')->uncheck();
    $page->findField('options[sticky]')->check();
    $page->findField('options[promote]')->check();
    $page->findField('options[revision]')->check();
    $summary = 'Not published, Promoted to front page, Sticky at top of lists, Create new revision';
    $locator = '[href="#edit-workflow"] .vertical-tabs__menu-item-summary:contains("' . $summary . '")';
    $this->assertNotEmpty($this->assertSession()->waitForElement('css', $locator));
  }

}
