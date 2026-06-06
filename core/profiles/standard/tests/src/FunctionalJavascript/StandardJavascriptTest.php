<?php

declare(strict_types=1);

namespace Drupal\Tests\standard\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\Entity\Node;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Standard installation profile JavaScript expectations.
 */
#[Group('standard')]
#[RunTestsInSeparateProcesses]
class StandardJavascriptTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * Tests BigPipe accelerates particular Standard installation profile routes.
   */
  public function testBigPipe(): void {
    // Standard profile does not include a content type by default.
    $this->drupalCreateContentType(['type' => 'test_content', 'name' => 'Test Content']);

    $this->drupalLogin($this->drupalCreateUser([
      'access content',
    ]));

    $node = Node::create(['type' => 'test_content'])
      ->setTitle($this->randomMachineName())
      ->setPromoted(TRUE)
      ->setPublished();
    $node->save();

    // Front page: Five placeholders.
    $this->drupalGet('');
    $this->assertBigPipePlaceholderReplacementCount(5);

    // Front page with warm render caches: Zero placeholders.
    $this->drupalGet('');
    $this->assertBigPipePlaceholderReplacementCount(0);

    // Node page: Four placeholders.
    $this->drupalGet($node->toUrl());
    $this->assertBigPipePlaceholderReplacementCount(4);

    // Node page second request: Zero placeholders.
    $this->drupalGet($node->toUrl());
    $this->assertBigPipePlaceholderReplacementCount(0);
  }

  /**
   * Asserts the number of BigPipe placeholders that are replaced on the page.
   *
   * @param int $expected_count
   *   The expected number of BigPipe placeholders.
   */
  protected function assertBigPipePlaceholderReplacementCount($expected_count): void {
    $web_assert = $this->assertSession();
    if ($expected_count > 0) {
      $web_assert->waitForElement('css', 'script[data-big-pipe-event="stop"]');
    }
    $page = $this->getSession()->getPage();
    // Settings are removed as soon as they are processed.
    if ($expected_count === 0) {
      $this->assertArrayNotHasKey('bigPipePlaceholderIds', $this->getDrupalSettings());
    }
    else {
      $this->assertCount(0, $this->getDrupalSettings()['bigPipePlaceholderIds']);
    }
    $this->assertCount($expected_count, $page->findAll('css', 'script[data-big-pipe-replacement-for-placeholder-with-id]'));
  }

}
