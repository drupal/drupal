<?php

declare(strict_types=1);

namespace Drupal\Tests\shortcut\Kernel;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\KernelTests\KernelTestBase;
use Drupal\shortcut\Entity\Shortcut;
use Drupal\shortcut\Entity\ShortcutSet;
use Drupal\shortcut\ShortcutSetInterface;
use Drupal\Tests\system\Traits\CacheTestTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\UserInterface;

/**
 * Tests the Shortcut entity's cache tags.
 *
 * @group shortcut
 */
class ShortcutCacheTagsTest extends KernelTestBase {
  use UserCreationTrait;
  use CacheTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'toolbar',
    'shortcut',
    'link',
  ];

  /**
   * User with permission to administer shortcuts.
   */
  protected UserInterface $adminUser;

  /**
   * The default shortcut set.
   */
  protected ShortcutSetInterface $shortcutSet;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('shortcut');
    $this->installEntitySchema('shortcut_set');

    $this->adminUser = $this->createUser([
      'access toolbar',
      'access shortcuts',
      'administer site configuration',
      'administer shortcuts',
      'administer themes',
    ]);

    $this->shortcutSet = ShortcutSet::create([
      'id' => 'default',
      'label' => 'Default',
    ]);
    $this->shortcutSet->save();

  }

  /**
   * Creates a shortcut entity in the default shortcut set.
   */
  protected function createShortcutEntity(): Shortcut {
    // Create a "Llama" shortcut.
    $shortcut = Shortcut::create([
      'shortcut_set' => $this->shortcutSet->id(),
      'title' => 'Llama',
      'weight' => 0,
      'link' => [['uri' => 'internal:/admin']],
    ]);
    $shortcut->save();

    return $shortcut;
  }

  /**
   * Tests that when creating a shortcut, the shortcut set tag is invalidated.
   */
  public function testEntityCreation(): void {
    $cache_bin = $this->getDefaultVariationCache();

    // Create a cache entry that is tagged with a shortcut set cache tag.
    $cache_tags = ['config:shortcut.set.' . $this->shortcutSet->id()];

    $cacheability = new CacheableMetadata();
    $cacheability->addCacheTags($cache_tags);
    $cache_bin->set(['foo'], 'bar', $cacheability, $cacheability);

    // Verify a cache hit.
    $this->verifyDefaultCache(['foo'], $cache_tags, $cacheability);

    // Now create a shortcut entity in that shortcut set.
    $this->createShortcutEntity();

    // Verify a cache miss.
    $this->assertFalse($cache_bin->get(['foo'], $cacheability), 'Creating a new shortcut invalidates the cache tag of the shortcut set.');
  }

}
