<?php

namespace Drupal\Tests\tour\Kernel;

use Drupal\tour\TourTipPluginInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;

/**
 * @coversDefaultClass \Drupal\tour\TourTipPluginInterface
 * @group tour
 * @group legacy
 */
class TourTipLegacyTest extends TestCase {
  use ExpectDeprecationTrait;

  public function testPluginHelperDeprecation(): void {
    $this->expectDeprecation('The Drupal\tour\TourTipPluginInterface is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Implement Drupal\tour\TipPluginInterface instead. See https://www.drupal.org/node/3340701');
    $plugin = $this->createMock(TourTipPluginInterface::class);
    $this->assertInstanceOf(TourTipPluginInterface::class, $plugin);
  }

}
