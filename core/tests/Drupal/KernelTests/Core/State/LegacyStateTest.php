<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\State;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the legacy state deprecations.
 *
 * @group system
 * @group legacy
 * @coversDefaultClass \Drupal\Core\State\State
 */
class LegacyStateTest extends KernelTestBase {

  /**
   * @covers ::get
   * @covers ::set
   */
  public function testDeprecatedState(): void {
    $state = $this->container->get('state');
    $this->expectDeprecation('The \'system.css_js_query_string\' state is deprecated in drupal:10.2.0. Use \Drupal\Core\Asset\AssetQueryStringInterface::get() and ::reset() instead. See https://www.drupal.org/node/3358337.');
    $state->set('system.css_js_query_string', 'foo');
    $this->expectDeprecation('The \'system.css_js_query_string\' state is deprecated in drupal:10.2.0. Use \Drupal\Core\Asset\AssetQueryStringInterface::get() and ::reset() instead. See https://www.drupal.org/node/3358337.');
    $this->assertEquals('foo', $state->get('system.css_js_query_string'));
    $this->assertEquals('foo', \Drupal::service('asset.query_string')->get());
  }

}
