<?php

namespace Drupal\Tests\system\Functional\Common;

use Drupal\Component\Serialization\Json;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;

/**
 * Performs integration tests on drupal_render().
 *
 * @group Common
 */
class RenderWebTest extends BrowserTestBase {

  use AssertPageCacheContextsAndTagsTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['common_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Asserts the cache context for the wrapper format is always present.
   */
  public function testWrapperFormatCacheContext() {
    $this->drupalGet('common-test/type-link-active-class');
    $this->assertStringStartsWith("<!DOCTYPE html>\n<html", $this->getSession()->getPage()->getContent());
    $this->assertIdentical('text/html; charset=UTF-8', $this->drupalGetHeader('Content-Type'));
    $this->assertTitle('Test active link class | Drupal');
    $this->assertCacheContext('url.query_args:' . MainContentViewSubscriber::WRAPPER_FORMAT);

    $this->drupalGet('common-test/type-link-active-class', ['query' => [MainContentViewSubscriber::WRAPPER_FORMAT => 'json']]);
    $this->assertIdentical('application/json', $this->drupalGetHeader('Content-Type'));
    $json = Json::decode($this->getSession()->getPage()->getContent());
    $this->assertEqual(['content', 'title'], array_keys($json));
    $this->assertIdentical('Test active link class', $json['title']);
    $this->assertCacheContext('url.query_args:' . MainContentViewSubscriber::WRAPPER_FORMAT);
  }

}
