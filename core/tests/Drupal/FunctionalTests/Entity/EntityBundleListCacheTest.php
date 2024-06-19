<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Entity;

use Drupal\Core\Url;
use Drupal\entity_test\Entity\EntityTestBundle;
use Drupal\entity_test\Entity\EntityTestWithBundle;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;

/**
 * Tests that bundle tags are invalidated when entities change.
 *
 * @group Entity
 */
class EntityBundleListCacheTest extends BrowserTestBase {

  use AssertPageCacheContextsAndTagsTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['cache_test', 'entity_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    EntityTestBundle::create([
      'id' => 'bundle_a',
      'label' => 'Bundle A',
    ])->save();
    EntityTestBundle::create([
      'id' => 'bundle_b',
      'label' => 'Bundle B',
    ])->save();
  }

  /**
   * Tests that tags are invalidated when an entity with that bundle changes.
   */
  public function testBundleListingCache(): void {
    // Access to lists of test entities with each bundle.
    $bundle_a_url = Url::fromRoute('cache_test_list.bundle_tags', ['entity_type_id' => 'entity_test_with_bundle', 'bundle' => 'bundle_a']);
    $bundle_b_url = Url::fromRoute('cache_test_list.bundle_tags', ['entity_type_id' => 'entity_test_with_bundle', 'bundle' => 'bundle_b']);
    $this->drupalGet($bundle_a_url);
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'MISS');
    $this->assertCacheTags(['rendered', 'entity_test_with_bundle_list:bundle_a']);

    $this->drupalGet($bundle_a_url);
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'HIT');
    $this->assertCacheTags(['rendered', 'entity_test_with_bundle_list:bundle_a']);
    $this->drupalGet($bundle_b_url);
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'MISS');
    $this->assertCacheTags(['rendered', 'entity_test_with_bundle_list:bundle_b']);
    $this->drupalGet($bundle_b_url);
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'HIT');
    $entity1 = EntityTestWithBundle::create(['type' => 'bundle_a', 'name' => 'entity1']);
    $entity1->save();
    // Check that tags are invalidated after creating an entity of the current
    // bundle.
    $this->drupalGet($bundle_a_url);
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'MISS');
    $this->drupalGet($bundle_a_url);
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'HIT');
    // Check that tags are not invalidated after creating an entity of a
    // different bundle than the current in the request.
    $this->drupalGet($bundle_b_url);
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'HIT');
  }

}
