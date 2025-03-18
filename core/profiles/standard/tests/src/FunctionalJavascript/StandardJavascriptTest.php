<?php

declare(strict_types=1);

namespace Drupal\Tests\standard\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\Entity\Node;

/**
 * Tests Standard installation profile JavaScript expectations.
 *
 * @group standard
 */
class StandardJavascriptTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * Tests BigPipe accelerates particular Standard installation profile routes.
   */
  public function testBigPipe(): void {
    $this->drupalLogin($this->drupalCreateUser([
      'access content',
      'post comments',
      'skip comment approval',
    ]));

    $node = Node::create(['type' => 'article'])
      ->setTitle($this->randomMachineName())
      ->setPromoted(TRUE)
      ->setPublished();
    $node->save();

    // Front page: Five placeholders.
    $this->drupalGet('');
    $this->assertBigPipePlaceholderReplacementCount(5);

    // Node page: Six placeholders:
    $this->drupalGet($node->toUrl());
    $this->assertBigPipePlaceholderReplacementCount(6);
  }

  /**
   * Asserts the number of BigPipe placeholders that are replaced on the page.
   *
   * @param int $expected_count
   *   The expected number of BigPipe placeholders.
   */
  protected function assertBigPipePlaceholderReplacementCount($expected_count): void {
    $web_assert = $this->assertSession();
    $web_assert->waitForElement('css', 'script[data-big-pipe-event="stop"]');
    $page = $this->getSession()->getPage();
    // Settings are removed as soon as they are processed.
    $this->assertCount(0, $this->getDrupalSettings()['bigPipePlaceholderIds']);
    $this->assertCount($expected_count, $page->findAll('css', 'script[data-big-pipe-replacement-for-placeholder-with-id]'));
  }

}
