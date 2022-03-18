<?php

declare(strict_types = 1);

namespace Drupal\Tests\ckeditor5\Unit;

use Drupal\ckeditor5\Plugin\Editor\CKEditor5;
use Drupal\Tests\ckeditor5\Traits\PrivateMethodUnitTestTrait;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\ckeditor5\Plugin\Editor\CKEditor5
 * @group ckeditor5
 * @internal
 */
class CKEditor5Test extends UnitTestCase {

  use PrivateMethodUnitTestTrait;

  /**
   * Simulated CKEditor5::buildConfigurationForm() form structure.
   *
   * @var array
   */
  protected const SIMULATED_FORM_STRUCTURE = [
    'toolbar' => [
      'available' => [],
      'items' => [],
    ],
    'available_items_description' => [],
    'active_items_description' => [],
    'plugin_settings' => [],
    'plugins' => [
      'providerA_plugin1' => [],
      'providerB_plugin2' => [
        'foo' => [],
        'bar' => [],
      ],
    ],
  ];

  /**
   * @covers \Drupal\ckeditor5\Plugin\Editor\CKEditor5::mapViolationPropertyPathsToFormNames
   * @dataProvider providerPathsToFormNames
   */
  public function testPathsToFormNames(string $property_path, string $expected_form_item_name, bool $expect_exception = FALSE): void {
    $mapMethod = self::getMethod(CKEditor5::class, 'mapViolationPropertyPathsToFormNames');
    if ($expect_exception) {
      $this->expectExceptionMessage('assert($shifted === \'settings\')');
    }

    $form_item_name = $mapMethod->invokeArgs(NULL, [$property_path, static::SIMULATED_FORM_STRUCTURE]);

    if (!$expect_exception) {
      $this->assertSame($expected_form_item_name, $form_item_name);
    }
  }

  /**
   * Data provider for testing mapViolationPropertyPathsToFormNames.
   *
   * @return array[]
   *   An array with the property path and expected form item name.
   */
  public function providerPathsToFormNames(): array {
    return [
      'validation error targeting toolbar items' => [
        'settings.toolbar.items',
        'settings][toolbar][items',
      ],
      'validation error targeting a specific toolbar item' => [
        'settings.toolbar.items.6',
        'settings][toolbar][items',
      ],
      'validation error targeting a simple plugin form' => [
        'settings.plugins.providerA_plugin1',
        'settings][plugins][providerA_plugin1',
      ],
      'validation error targeting a simple plugin form, with deep config schema detail' => [
        'settings.plugins.providerA_plugin1.foo.bar.baz',
        'settings][plugins][providerA_plugin1',
      ],
      'validation error targeting a complex plugin form' => [
        'settings.plugins.providerB_plugin2',
        'settings][plugins][providerB_plugin2',
      ],
      'validation error targeting a complex plugin form, with deep config schema detail' => [
        'settings.plugins.providerB_plugin2.foo.bar.baz',
        'settings][plugins][providerB_plugin2][foo',
      ],
      'unrealistic example one — should trigger exception' => [
        'bad.bad.worst',
        'I DO NOT EXIST',
        TRUE,
      ],
      'unrealistic example two — should trigger exception' => [
        'one.two.three.four',
        'one][two][three][four',
        TRUE,
      ],
    ];
  }

}
