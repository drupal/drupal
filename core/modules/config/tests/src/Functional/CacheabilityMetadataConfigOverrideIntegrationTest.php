<?php

namespace Drupal\Tests\config\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;

/**
 * Tests if configuration overrides correctly affect cacheability metadata.
 *
 * @group config
 */
class CacheabilityMetadataConfigOverrideIntegrationTest extends BrowserTestBase {

  use AssertPageCacheContextsAndTagsTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block_test',
    'config_override_integration_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // @todo If our block does not contain any content then the cache context
    //   is not bubbling up and the test fails. Remove this line once the cache
    //   contexts are properly set. See https://www.drupal.org/node/2529980.
    \Drupal::state()->set('block_test.content', 'Needs to have some content');

    $this->drupalLogin($this->drupalCreateUser());
  }

  /**
   * Tests if config overrides correctly set cacheability metadata.
   */
  public function testConfigOverride() {
    // Check the default (disabled) state of the cache context. The block label
    // should not be overridden.
    $this->drupalGet('<front>');
    $this->assertSession()->pageTextNotContains('Overridden block label');

    // Both the cache context and tag should be present.
    $this->assertCacheContext('config_override_integration_test');
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'config_override_integration_test_tag');

    // Flip the state of the cache context. The block label should now be
    // overridden.
    \Drupal::state()->set('config_override_integration_test.enabled', TRUE);
    $this->drupalGet('<front>');
    $this->assertSession()->pageTextContains('Overridden block label');

    // Both the cache context and tag should still be present.
    $this->assertCacheContext('config_override_integration_test');
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'config_override_integration_test_tag');
  }

}
