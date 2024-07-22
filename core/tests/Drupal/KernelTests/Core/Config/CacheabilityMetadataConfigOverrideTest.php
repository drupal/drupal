<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Config;

use Drupal\config_override_test\Cache\PirateDayCacheContext;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests if configuration overrides correctly affect cacheability metadata.
 *
 * @group config
 */
class CacheabilityMetadataConfigOverrideTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'block_content',
    'config',
    'config_override_test',
    'field',
    'path_alias',
    'system',
    'text',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->container->get('theme_installer')->install(['stark']);
    $this->installEntitySchema('block_content');
    $this->installConfig([
      'block_content',
      'config_override_test',
    ]);
  }

  /**
   * Tests if config overrides correctly set cacheability metadata.
   */
  public function testConfigOverride(): void {
    // It's pirate day today!
    $GLOBALS['it_is_pirate_day'] = TRUE;

    $config_factory = $this->container->get('config.factory');
    $config = $config_factory->get('system.theme');

    // Check that we are using the Pirate theme.
    $theme = $config->get('default');
    $this->assertEquals('pirate', $theme);

    // Check that the cacheability metadata is correct.
    $this->assertEquals(['pirate_day'], $config->getCacheContexts());
    $this->assertEquals(['config:system.theme', 'pirate-day-tag'], $config->getCacheTags());
    $this->assertEquals(PirateDayCacheContext::PIRATE_DAY_MAX_AGE, $config->getCacheMaxAge());
  }

  /**
   * Tests if config overrides set cacheability metadata on config entities.
   */
  public function testConfigEntityOverride(): void {
    // It's pirate day today!
    $GLOBALS['it_is_pirate_day'] = TRUE;

    // Load the User login block and check that its cacheability metadata is
    // overridden correctly. This verifies that the metadata is correctly
    // applied to config entities.
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');
    $block = $entity_type_manager->getStorage('block')->load('call_to_action');

    // Check that our call to action message is appealing to filibusters.
    $this->assertEquals('Draw yer cutlasses!', $block->label());

    // Check that the cacheability metadata is correct.
    $this->assertEquals(['pirate_day'], $block->getCacheContexts());
    $this->assertEquals(['config:block.block.call_to_action', 'pirate-day-tag'], $block->getCacheTags());
    $this->assertEquals(PirateDayCacheContext::PIRATE_DAY_MAX_AGE, $block->getCacheMaxAge());

    // Check that duplicating a config entity does not have the original config
    // entity's cache tag.
    $this->assertEquals(['config:block.block.', 'pirate-day-tag'], $block->createDuplicate()->getCacheTags());

    // Check that renaming a config entity does not have the original config
    // entity's cache tag.
    $block->set('id', 'call_to_looting')->save();
    $this->assertEquals(['pirate_day'], $block->getCacheContexts());
    $this->assertEquals(['config:block.block.call_to_looting', 'pirate-day-tag'], $block->getCacheTags());
    $this->assertEquals(PirateDayCacheContext::PIRATE_DAY_MAX_AGE, $block->getCacheMaxAge());
  }

}
