<?php

declare(strict_types=1);

namespace Drupal\Tests\locale\Unit;

use Drupal\Core\DependencyInjection\Container;
use Drupal\locale\Locale;
use Drupal\locale\LocaleConfigManager;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\locale\Locale
 * @group Cache
 */
class LocaleTest extends UnitTestCase {

  /**
   * Tests deprecation of config() method.
   *
   * @covers ::config
   * @group legacy
   */
  public function testConfig(): void {
    $config_manager = $this->prophesize(LocaleConfigManager::class);
    $container = $this->prophesize(Container::class);
    $container->get('locale.config_manager')
      ->willReturn($config_manager->reveal());
    \Drupal::setContainer($container->reveal());

    $this->expectDeprecation('The Drupal\locale\Locale is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. There is no replacement. See https://www.drupal.org/node/3437110');
    $this->expectDeprecation('Drupal\locale\Locale::config() is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use \Drupal::service(\'locale.config_manager\') instead. See https://www.drupal.org/node/3437110');

    $this->assertInstanceOf(LocaleConfigManager::class, Locale::config());
  }

}
