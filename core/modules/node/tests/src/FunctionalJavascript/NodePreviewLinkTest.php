<?php

declare(strict_types=1);

namespace Drupal\Tests\node\FunctionalJavascript;

use Drupal\filter\Entity\FilterFormat;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the JavaScript prevention of navigation away from node previews.
 *
 * @group node
 */
class NodePreviewLinkTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'filter'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $filtered_html_format = FilterFormat::create([
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
    ]);
    $filtered_html_format->save();

    $this->drupalCreateContentType(['type' => 'test']);

    $user = $this->drupalCreateUser([
      'access content',
      'edit own test content',
      'create test content',
      $filtered_html_format->getPermissionName(),
    ]);
    $this->drupalLogin($user);
  }

  /**
   * Tests the behavior of clicking preview links.
   */
  public function testPreviewLinks(): void {
    $assertSession = $this->assertSession();
    $this->drupalGet('node/add/test');
    $this->submitForm([
      'title[0][value]' => 'Test anchor link',
      'body[0][value]' => '<a href="#foo">Anchor link</a>',
    ], 'Preview');
    $this->clickLink('Anchor link');
    $assertSession->pageTextNotContains('Leave preview?');
    $this->drupalGet('node/add/test');
    $this->submitForm([
      'title[0][value]' => 'Test normal link',
      'body[0][value]' => '<a href="/foo">Normal link</a>',
    ], 'Preview');
    $this->clickLink('Normal link');
    $assertSession->pageTextContains('Leave preview?');
    $this->click('button:contains("Leave preview")');
    $this->assertStringEndsWith('/foo', $this->getUrl());
    $this->drupalGet('node/add/test');
    $this->submitForm([
      'title[0][value]' => 'Test child element link',
      'body[0][value]' => '<a href="/foo" class="preview-child-element"><span>Child element link</span></a>',
    ], 'Preview');
    $this->getSession()->getPage()->find('css', '.preview-child-element span')->click();
    $assertSession->pageTextContains('Leave preview?');
    $this->click('button:contains("Leave preview")');
    $this->assertStringEndsWith('/foo', $this->getUrl());
  }

}
