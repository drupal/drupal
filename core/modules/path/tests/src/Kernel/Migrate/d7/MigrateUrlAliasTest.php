<?php

namespace Drupal\Tests\path\Kernel\Migrate\d7;

/**
 * Tests URL alias migration.
 *
 * @group path
 */
class MigrateUrlAliasTest extends MigrateUrlAliasTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'content_translation',
    'migrate_drupal_multilingual',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->executeMigrations([
      'd7_node_translation',
      'd7_url_alias',
    ]);
  }

  /**
   * Test the URL alias migration with translated nodes.
   */
  public function testUrlAliasWithTranslatedNodes() {
    $alias_storage = $this->container->get('path.alias_storage');

    // Alias for the 'The thing about Deep Space 9' node in English.
    $path = $alias_storage->load(['alias' => '/deep-space-9']);
    $this->assertSame('/node/2', $path['source']);
    $this->assertSame('en', $path['langcode']);

    // Alias for the 'The thing about Deep Space 9' Icelandic translation,
    // which should now point to node/2 instead of node/3.
    $path = $alias_storage->load(['alias' => '/deep-space-9-is']);
    $this->assertSame('/node/2', $path['source']);
    $this->assertSame('is', $path['langcode']);

    // Alias for the 'The thing about Firefly' node in Icelandic.
    $path = $alias_storage->load(['alias' => '/firefly-is']);
    $this->assertSame('/node/4', $path['source']);
    $this->assertSame('is', $path['langcode']);

    // Alias for the 'The thing about Firefly' English translation,
    // which should now point to node/4 instead of node/5.
    $path = $alias_storage->load(['alias' => '/firefly']);
    $this->assertSame('/node/4', $path['source']);
    $this->assertSame('en', $path['langcode']);
  }

}
