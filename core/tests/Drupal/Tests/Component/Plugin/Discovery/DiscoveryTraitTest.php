<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Plugin\Discovery;

use Drupal\Component\Plugin\Discovery\DiscoveryTrait;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests Drupal\Component\Plugin\Discovery\DiscoveryTrait.
 */
#[CoversTrait(DiscoveryTrait::class)]
#[Group('Plugin')]
#[UsesClass(PluginNotFoundException::class)]
class DiscoveryTraitTest extends TestCase {

  /**
   * Data provider for testDoGetDefinition().
   *
   * @return array
   *   - Expected plugin definition.
   *   - Plugin definition array, to pass to doGetDefinition().
   *   - Plugin ID to get, passed to doGetDefinition().
   */
  public static function providerDoGetDefinition(): array {
    return [
      ['definition', ['plugin_name' => 'definition'], 'plugin_name'],
      [NULL, ['plugin_name' => 'definition'], 'bad_plugin_name'],
    ];
  }

  /**
   * Tests do get definition.
   */
  #[DataProvider('providerDoGetDefinition')]
  public function testDoGetDefinition($expected, $definitions, $plugin_id): void {
    $trait = new DiscoveryTraitMockableClass();
    // Un-protect the method using reflection.
    $method_ref = new \ReflectionMethod($trait, 'doGetDefinition');
    // Call doGetDefinition, with $exception_on_invalid always FALSE.
    $this->assertSame(
      $expected,
      $method_ref->invoke($trait, $definitions, $plugin_id, FALSE)
    );
  }

  /**
   * Data provider for testDoGetDefinitionException()
   *
   * @return array
   *   - Expected plugin definition.
   *   - Plugin definition array, to pass to doGetDefinition().
   *   - Plugin ID to get, passed to doGetDefinition().
   */
  public static function providerDoGetDefinitionException(): array {
    return [
      [FALSE, ['plugin_name' => 'definition'], 'bad_plugin_name'],
    ];
  }

  /**
   * Tests do get definition exception.
   */
  #[DataProvider('providerDoGetDefinitionException')]
  public function testDoGetDefinitionException($expected, $definitions, $plugin_id): void {
    $trait = new DiscoveryTraitMockableClass();
    // Un-protect the method using reflection.
    $method_ref = new \ReflectionMethod($trait, 'doGetDefinition');
    // Call doGetDefinition, with $exception_on_invalid always TRUE.
    $this->expectException(PluginNotFoundException::class);
    $method_ref->invoke($trait, $definitions, $plugin_id, TRUE);
  }

  /**
   * Tests get definition.
   */
  #[DataProvider('providerDoGetDefinition')]
  public function testGetDefinition($expected, $definitions, $plugin_id): void {
    // Since getDefinition is a wrapper around doGetDefinition(), we can re-use
    // its data provider. We just have to tell abstract method getDefinitions()
    // to use the $definitions array.
    $trait = $this->getMockBuilder(DiscoveryTraitMockableClass::class)
      ->onlyMethods(['getDefinitions'])
      ->getMock();
    $trait->expects($this->once())
      ->method('getDefinitions')
      ->willReturn($definitions);
    // Call getDefinition(), with $exception_on_invalid always FALSE.
    $this->assertSame(
      $expected,
      $trait->getDefinition($plugin_id, FALSE)
    );
  }

  /**
   * Tests get definition exception.
   */
  #[DataProvider('providerDoGetDefinitionException')]
  public function testGetDefinitionException($expected, $definitions, $plugin_id): void {
    // Since getDefinition is a wrapper around doGetDefinition(), we can re-use
    // its data provider. We just have to tell abstract method getDefinitions()
    // to use the $definitions array.
    $trait = $this->getMockBuilder(DiscoveryTraitMockableClass::class)
      ->onlyMethods(['getDefinitions'])
      ->getMock();
    $trait->expects($this->once())
      ->method('getDefinitions')
      ->willReturn($definitions);
    // Call getDefinition(), with $exception_on_invalid always TRUE.
    $this->expectException(PluginNotFoundException::class);
    $trait->getDefinition($plugin_id, TRUE);
  }

  /**
   * Data provider for testHasDefinition().
   *
   * @return array
   *   - Expected TRUE or FALSE.
   *   - Plugin ID to look for.
   */
  public static function providerHasDefinition(): array {
    return [
      [TRUE, 'valid'],
      [FALSE, 'not_valid'],
    ];
  }

  /**
   * Tests has definition.
   */
  #[DataProvider('providerHasDefinition')]
  public function testHasDefinition($expected, $plugin_id): void {
    $trait = $this->getMockBuilder(DiscoveryTraitMockableClass::class)
      ->onlyMethods(['getDefinition'])
      ->getMock();
    // Set up our mocked getDefinition() to return TRUE for 'valid' and FALSE
    // for 'not_valid'.
    $trait->expects($this->once())
      ->method('getDefinition')
      ->willReturnMap([
        ['valid', FALSE, TRUE],
        ['not_valid', FALSE, FALSE],
      ]);
    // Call hasDefinition().
    $this->assertSame(
      $expected,
      $trait->hasDefinition($plugin_id)
    );
  }

}

/**
 * A class using the DiscoveryTrait for mocking purposes.
 */
class DiscoveryTraitMockableClass {

  use DiscoveryTrait;

  public function getDefinitions(): array {
    return [];
  }

}
