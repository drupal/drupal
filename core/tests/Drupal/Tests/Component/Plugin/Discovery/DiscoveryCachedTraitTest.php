<?php

/**
 * @file
 * Contains \Drupal\Tests\Component\Plugin\Discovery\DiscoveryCachedTraitTest.
 */

namespace Drupal\Tests\Component\Plugin\Discovery;

use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass Drupal\Component\Plugin\Discovery\DiscoveryCachedTrait
 * @uses Drupal\Component\Plugin\Discovery\DiscoveryTrait
 * @group Plugin
 */
class DiscoveryCachedTraitTest extends UnitTestCase {

  // Temporary storage to mock a side-effect.
  protected $trait;
  protected $definitions_ref;
  protected $get_definitions;


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
    return array(
      ['definition', [], ['plugin_name' => 'definition'], 'plugin_name'],
      ['definition', ['plugin_name' => 'definition'], [], 'plugin_name'],
      [NULL, ['plugin_name' => 'definition'], [], 'bad_plugin_name'],
    );
  }

  /**
   * @covers ::getDefinition
   * @dataProvider providerGetDefinition
   */
  public function testGetDefinition($expected, $cached_definitions, $get_definitions, $plugin_id) {
    // Mock a DiscoveryCachedTrait.
    $trait = $this->getMockForTrait('Drupal\Component\Plugin\Discovery\DiscoveryCachedTrait');
    $definitions_ref = new \ReflectionProperty($trait, 'definitions');
    $definitions_ref->setAccessible(TRUE);
    // getDefinition() needs the ::$definitions property to be set in one of two
    // ways: 1) As existing cached data, or 2) as a side-effect of calling
    // getDefinitions().
    // If there are no cached definitions, then we have to fake the side-effect
    // of getDefinitions().
    if (count($cached_definitions) < 1) {
      $this->trait = $trait;
      $this->definitions_ref = $definitions_ref;
      $this->get_definitions = $get_definitions;
      // Use a callback method, so we can perform the side-effects.
      $trait->expects($this->once())
        ->method('getDefinitions')
        ->willReturnCallback(array($this, 'getDefinitionsCallback'));
    }
    else {
      // Put $cached_definitions into our mocked ::$definitions.
      $definitions_ref->setValue($trait, $cached_definitions);
    }
    // Call getDefinition(), with $exception_on_invalid always FALSE.
    $this->assertSame(
      $expected,
      $trait->getDefinition($plugin_id, FALSE)
    );
  }

  /**
   * Callback method so we can mock the side-effects of getDefinitions().
   *
   * @see testGetDefinition
   */
  public function getDefinitionsCallback() {
    $this->definitions_ref->setValue(
      $this->trait,
      $this->get_definitions
    );
    return $this->get_definitions;
  }

}
