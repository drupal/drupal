<?php

namespace Drupal\Tests\system\Functional\System;

use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;

/**
 * Tests the token system integration.
 *
 * @group system
 */
class TokenReplaceWebTest extends BrowserTestBase {

  use AssertPageCacheContextsAndTagsTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['token_test', 'filter', 'node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests a token replacement on an actual website.
   */
  public function testTokens() {
    $node = $this->drupalCreateNode();
    $account = $this->drupalCreateUser();
    $this->drupalLogin($account);

    $this->drupalGet('token-test/' . $node->id());
    $this->assertText("Tokens: {$node->id()} {$account->id()}");
    $this->assertCacheTags(['node:1', 'rendered', 'user:2']);
    $this->assertCacheContexts(['languages:language_interface', 'theme', 'url.query_args:' . MainContentViewSubscriber::WRAPPER_FORMAT, 'user']);

    $this->drupalGet('token-test-without-bubbleable-metadata/' . $node->id());
    $this->assertText("Tokens: {$node->id()} {$account->id()}");
    $this->assertCacheTags(['node:1', 'rendered', 'user:2']);
    $this->assertCacheContexts(['languages:language_interface', 'theme', 'url.query_args:' . MainContentViewSubscriber::WRAPPER_FORMAT, 'user']);
  }

}
