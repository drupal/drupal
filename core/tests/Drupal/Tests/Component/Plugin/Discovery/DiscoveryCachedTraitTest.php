<?php

namespace Drupal\Tests\Component\Plugin\Discovery;

use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Component\Plugin\Discovery\DiscoveryCachedTrait
 * @uses \Drupal\Component\Plugin\Discovery\DiscoveryTrait
 * @group Plugin
 */
class DiscoveryCachedTraitTest extends UnitTestCase {

  /**
   * Data provider for testGetDefinition().
   *
   * @return array
   *   - Expected result from getDefinition().
   *   - Cached definitions to be placed into self::$definitions
   *   - Definitions to be returned by getDefinitions().
   *   - Plugin name to query for.
   */
  public function providerGetDefinition() {
    return [
      ['definition', [], ['plugin_name' => 'definition'], 'plugin_name'],
      ['definition', ['plugin_name' => 'definition'], [], 'plugin_name'],
      [NULL, ['plugin_name' => 'definition'], [], 'bad_plugin_name'],
    ];
  }

  /**
   * @covers ::getDefinition
   * @dataProvider providerGetDefinition
   */
  public function testGetDefinition($expected, $cached_definitions, $get_definitions, $plugin_id) {
    // Mock a DiscoveryCachedTrait.
    $trait = $this->getMockForTrait('Drupal\Component\Plugin\Discovery\DiscoveryCachedTrait');
    $reflection_definitions = new \ReflectionProperty($trait, 'definitions');
    $reflection_definitions->setAccessible(TRUE);
    // getDefinition() needs the ::$definitions property to be set in one of two
    // ways: 1) As existing cached data, or 2) as a side-effect of calling
    // getDefinitions().
    // If there are no cached definitions, then we have to fake the side-effect
    // of getDefinitions().
    if (count($cached_definitions) < 1) {
      $trait->expects($this->once())
        ->method('getDefinitions')
        // Use a callback method, so we can perform the side-effects.
        ->willReturnCallback(function() use ($reflection_definitions, $trait, $get_definitions) {
          $reflection_definitions->setValue($trait, $get_definitions);
          return $get_definitions;
        });
    }
    else {
      // Put $cached_definitions into our mocked ::$definitions.
      $reflection_definitions->setValue($trait, $cached_definitions);
    }
    // Call getDefinition(), with $exception_on_invalid always FALSE.
    $this->assertSame(
      $expected,
      $trait->getDefinition($plugin_id, FALSE)
    );
  }

}
