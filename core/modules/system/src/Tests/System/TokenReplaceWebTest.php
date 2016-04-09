<?php

namespace Drupal\system\Tests\System;

use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\simpletest\WebTestBase;
use Drupal\system\Tests\Cache\AssertPageCacheContextsAndTagsTrait;

/**
 * Tests the token system integration.
 *
 * @group system
 */
class TokenReplaceWebTest extends WebTestBase {

  use AssertPageCacheContextsAndTagsTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['token_test', 'filter', 'node'];

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

    $this->drupalGet('token-test-without-bubleable-metadata/' . $node->id());
    $this->assertText("Tokens: {$node->id()} {$account->id()}");
    $this->assertCacheTags(['node:1', 'rendered', 'user:2']);
    $this->assertCacheContexts(['languages:language_interface', 'theme', 'url.query_args:' . MainContentViewSubscriber::WRAPPER_FORMAT, 'user']);
  }

}
