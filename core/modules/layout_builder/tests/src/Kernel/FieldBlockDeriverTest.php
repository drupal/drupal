<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\Kernel;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests field block plugin derivatives.
 */
#[Group('layout_builder')]
#[IgnoreDeprecations]
#[RunTestsInSeparateProcesses]
class FieldBlockDeriverTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'layout_builder',
    'layout_discovery',
  ];

  /**
   * Tests that field block derivers only expose fields for Layout Builder enabled bundles.
   *
   * Only bundles that have layout builder enabled will expose their fields as
   * field blocks.
   */
  public function testFieldBlockDerivers(): void {
    $plugins = $this->getBlockPluginIds();
    // Entity_test bundles do not have layout builder configured, so no field
    // blocks should be available for any entity types.
    $this->assertNotContains('field_block:user:user:name', $plugins);
    $this->assertNotContains('extra_field_block:user:user:test_extra_field', $plugins);
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
    $this->assertNotContains('extra_field_block:user:user:test_extra_field', $plugins);

    // Enabling layout builder for user adds field blocks for user.
    $display = LayoutBuilderEntityViewDisplay::create([
      'targetEntityType' => 'user',
      'bundle' => 'user',
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
    $this->assertContains('field_block:user:user:name', $plugins);
    $this->assertContains('extra_field_block:user:user:test_extra_field', $plugins);
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

  /**
   * Implements hook_entity_extra_field_info().
   */
  #[Hook('entity_extra_field_info')]
  public function entityExtraFieldInfo(): array {
    $fields['user']['user']['display']['test_extra_field'] = [
      'label' => 'Test extra field',
      'description' => 'Extra field used for testing field block discovery',
      'weight' => 5,
    ];
    return $fields;
  }

}
