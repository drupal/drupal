<?php

declare(strict_types=1);

namespace Drupal\Tests\node\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests that outlines of node meta values are displayed in summaries and tabs.
 *
 * @group node
 */
class CollapsedSummariesTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType([
      'type' => 'page',
      'name' => 'Basic page',
    ]);

    $this->drupalCreateNode([
      'title' => $this->randomMachineName(),
      'type' => 'page',
    ]);

    $this->drupalLogin($this->createUser(['edit any page content']));
  }

  /**
   * Confirm that summaries are provided for node meta at all widths.
   */
  public function testSummaries() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // At a wider width, vertical tabs are used for the meta section of the node
    // form.
    $this->getSession()->resizeWindow(1200, 1200);
    $this->drupalGet('node/1/edit');

    $assert_session->waitForText("New revision");
    $summary = $assert_session->waitForElement('css', '.vertical-tabs__menu-item-summary');
    $this->assertNotNull($summary);
    $this->assertTrue($summary->isVisible());
    $this->assertEquals('New revision', $summary->getText());
    $page->uncheckField('revision');
    $assert_session->waitForText('No revision');
    $this->assertEquals('No revision', $summary->getText());

    // At a narrower width, details are used for the meta section of the node
    // form.
    $this->getSession()->resizeWindow(600, 1200);
    $this->drupalGet('node/1/edit');

    $summary = $assert_session->waitForElement('css', 'span.summary');
    $this->assertNotNull($summary);
    $this->assertTrue($summary->isVisible());
    $page->uncheckField('revision');
    $assert_session->waitForText('No revision');
    $this->assertEquals('(No revision)', $summary->getText());
  }

}
