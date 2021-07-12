<?php

namespace Drupal\Tests\Component\Plugin;

use Drupal\Component\Plugin\Definition\PluginDefinitionInterface;
use Drupal\Component\Plugin\PluginBase;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;

/**
 * @group Plugin
 * @coversDefaultClass \Drupal\Component\Plugin\PluginInspectionTrait
 */
class PluginInspectionTraitTest extends TestCase {

  use ExpectDeprecationTrait;

  /**
   * @covers ::getPluginDefinition
   * @group legacy
   *
   * @dataProvider providerTestDeprecated
   */
  public function testDeprecated($plugin_definition, $deprecation_message) {
    $this->expectDeprecation($deprecation_message);
    $this->getMockForAbstractClass(PluginBase::class, [
      [],
      'plugin_id',
      $plugin_definition,
    ]);
  }

  /**
   * Provides data for testDeprecated.
   */
  public function providerTestDeprecated() {
    $message = 'This is a deprecation message for PluginInspectionTraitTest.';
    $plugin_definition = $this->getMockBuilder(PluginDefinitionInterface::class)
      ->getMock();
    $plugin_definition->deprecationMessage = $message;

    $definition_with_additional = $this->getMockBuilder(PluginDefinitionInterface::class)
      ->addMethods(['get'])
      ->getMockForAbstractClass();
    $definition_with_additional->additional = ['deprecation_message' => $message];
    $definition_with_additional->method('get')->willReturn(['deprecation_message' => $message]);

    return [
      'definition is an array' => [
        ['value', ['key' => 'value'], 'deprecation_message' => $message],
        $message,
      ],
      'definition is an object' => [
        $plugin_definition,
        $message,
      ],
      'definition is an object with additional' => [
        $definition_with_additional,
        $message,
      ],
    ];
  }

}
