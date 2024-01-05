<?php

declare(strict_types=1);

namespace Drupal\Tests\tour\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\tour\TipPluginBase;

/**
 * @coversDefaultClass \Drupal\tour\TipPluginBase
 *
 * @group tour
 */
class TipPluginBaseTest extends UnitTestCase {

  /**
   * @covers ::getLocation
   */
  public function testGetLocationAssertion() {
    $base_plugin = $this->getMockForAbstractClass(TipPluginBase::class, [], '', FALSE);

    $base_plugin->set('position', 'right');
    $this->assertSame('right', $base_plugin->getLocation());

    $base_plugin->set('position', 'not_valid');
    $this->expectException(\AssertionError::class);
    $this->expectExceptionMessage('not_valid is not a valid Tour Tip position value');
    $base_plugin->getLocation();
  }

}
