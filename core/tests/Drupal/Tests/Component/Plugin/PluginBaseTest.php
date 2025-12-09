<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Plugin;

use Drupal\Component\Plugin\PluginBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests Drupal\Component\Plugin\PluginBase.
 */
#[CoversClass(PluginBase::class)]
#[Group('Plugin')]
class PluginBaseTest extends TestCase {

  /**
   * Tests get plugin id.
   *
   * @legacy-covers ::getPluginId
   */
  #[DataProvider('providerTestGetPluginId')]
  public function testGetPluginId($plugin_id, $expected): void {
    $plugin_base = new StubPluginBase(
      [],
      $plugin_id,
      [],
    );

    $this->assertEquals($expected, $plugin_base->getPluginId());
  }

  /**
   * Returns test data for testGetPluginId().
   *
   * @return array
   *   An array of test cases, where each item contains a plugin ID and
   *   the expected plugin ID result.
   */
  public static function providerTestGetPluginId(): array {
    return [
      ['base_id', 'base_id'],
      ['base_id:derivative', 'base_id:derivative'],
    ];
  }

  /**
   * Tests get base id.
   *
   * @coves ::getBaseId
   */
  #[DataProvider('providerTestGetBaseId')]
  public function testGetBaseId($plugin_id, $expected): void {
    $plugin_base = new StubPluginBase(
      [],
      $plugin_id,
      [],
    );

    $this->assertEquals($expected, $plugin_base->getBaseId());
  }

  /**
   * Returns test data for testGetBaseId().
   *
   * @return array
   *   An array of test cases, where each item contains a plugin ID and
   *   the expected base ID result.
   */
  public static function providerTestGetBaseId(): array {
    return [
      ['base_id', 'base_id'],
      ['base_id:derivative', 'base_id'],
    ];
  }

  /**
   * Tests get derivative id.
   *
   * @legacy-covers ::getDerivativeId
   */
  #[DataProvider('providerTestGetDerivativeId')]
  public function testGetDerivativeId($plugin_id = NULL, $expected = NULL): void {
    $plugin_base = new StubPluginBase(
      [],
      $plugin_id,
      [],
    );

    $this->assertEquals($expected, $plugin_base->getDerivativeId());
  }

  /**
   * Returns test data for testGetDerivativeId().
   *
   * @return array
   *   An array of test cases, where each item contains a plugin ID and
   *   the expected derivative ID result.
   */
  public static function providerTestGetDerivativeId(): array {
    return [
      ['base_id', NULL],
      ['base_id:derivative', 'derivative'],
    ];
  }

  /**
   * Tests get plugin definition.
   *
   * @legacy-covers ::getPluginDefinition
   */
  public function testGetPluginDefinition(): void {
    $plugin_base = new StubPluginBase(
      [],
      'plugin_id',
      ['value', ['key' => 'value']],
    );

    $this->assertEquals(['value', ['key' => 'value']], $plugin_base->getPluginDefinition());
  }

}
