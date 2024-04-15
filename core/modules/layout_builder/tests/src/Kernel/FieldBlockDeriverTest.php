<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;

/**
 * Tests field block plugin derivatives.
 *
 * @group layout_builder
 * @group legacy
 */
class FieldBlockDeriverTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'layout_builder',
    'layout_discovery',
  ];

  /**
   * Tests that field block derivers respect expose_all_field_blocks config.
   *
   * When expose_all_field_blocks is disabled (the default setting), only
   * bundles that have layout builder enabled will expose their fields as
   * field blocks.
   */
  public function testFieldBlockDerivers(): void {
    $plugins = $this->getBlockPluginIds();
    // Setting is disabled and entity_test bundles do not have layout builder
    // configured.
    $this->assertNotContains('field_block:user:user:name', $plugins);
    $this->assertNotContains('extra_field_block:user:user:member_for', $plugins);
    $this->assertNotContains('field_block:entity_test:entity_test:id', $plugins);

    // Enabling layout builder for entity_test adds field blocks for entity_test
    // bundles, but not for the user entity type.
    $display = LayoutBuilderEntityViewDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
      'status' => TRUE,
      'third_party_settings' => [
        'layout_builder' => [
          'enabled' => TRUE,
        ],
      ],
    ]);
    $display->save();
    $plugins = $this->getBlockPluginIds();
    $this->assertContains('field_block:entity_test:entity_test:id', $plugins);
    $this->assertNotContains('field_block:user:user:name', $plugins);
    $this->assertNotContains('extra_field_block:user:user:member_for', $plugins);

    // Exposing all field blocks adds them for the user entity type.
    \Drupal::service('module_installer')->install(['layout_builder_expose_all_field_blocks']);
    $plugins = $this->getBlockPluginIds();
    $this->assertContains('field_block:user:user:name', $plugins);
    $this->assertContains('extra_field_block:user:user:member_for', $plugins);
  }

  /**
   * Get an uncached list of block plugin IDs.
   *
   * @return array
   *   A list of block plugin IDs.
   */
  private function getBlockPluginIds(): array {
    return \array_keys(\Drupal::service('plugin.manager.block')->getDefinitions());
  }

}
