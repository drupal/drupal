<?php

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
  public function testPreviewLinks() {
    $assertSession = $this->assertSession();
    $this->drupalGet('node/add/test');
    $this->submitForm([
      'title[0][value]' => 'Test node',
      'body[0][value]' => '<a href="#foo">Anchor link</a><a href="/foo">Normal link</a>',
    ], 'Preview');
    $this->clickLink('Anchor link');
    $assertSession->pageTextNotContains('Leave preview?');
    $this->clickLink('Normal link');
    $assertSession->pageTextContains('Leave preview?');
    $this->click('button:contains("Leave preview")');
    $this->assertStringEndsWith('/foo', $this->getUrl());
  }

}
