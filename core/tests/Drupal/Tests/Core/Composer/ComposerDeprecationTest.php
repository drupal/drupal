<?php

namespace Drupal\Tests\Core\Composer;

use Composer\Config;
use Composer\Composer as ComposerClass;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Installer\PackageEvent;
use Composer\IO\IOInterface;
use Composer\Package\Package;
use Composer\Script\Event;
use Drupal\Core\Composer\Composer;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the deprecations in the Drupal\Core\Composer\Composer class.
 *
 * @group Composer
 * @coversDefaultClass \Drupal\Core\Composer\Composer
 */
class ComposerDeprecationTest extends UnitTestCase {

  /**
   * @covers ::ensureHtaccess
   * @group legacy
   */
  public function testEnsureHtaccess() {
    $event = $this->prophesize(Event::class);

    $composer = $this->prophesize(ComposerClass::class);
    $event->getComposer()->willReturn($composer->reveal());

    $config = $this->prophesize(Config::class);
    $composer->getConfig()->willReturn($config->reveal());

    $this->expectDeprecation('Unsilenced deprecation: Calling Drupal\Core\Composer\Composer::ensureHtaccess from composer.json is deprecated in drupal:9.5.0 and is removed from drupal:10.0.0. Any "scripts" section mentioning this in composer.json can be removed and replaced with the drupal/core-vendor-hardening Composer plugin, as needed. See https://www.drupal.org/node/3260624');
    Composer::ensureHtaccess($event->reveal());
  }

  /**
   * @covers ::vendorTestCodeCleanup
   * @group legacy
   */
  public function testVendorTestCodeCleanup() {
    $event = $this->prophesize(PackageEvent::class);

    $composer = $this->prophesize(ComposerClass::class);
    $event->getComposer()->willReturn($composer->reveal());

    $config = $this->prophesize(Config::class);
    $composer->getConfig()->willReturn($config->reveal());

    $operation = $this->prophesize(UpdateOperation::class);
    $event->getOperation()->willReturn($operation->reveal());

    $package = $this->prophesize(Package::class);
    $operation->getTargetPackage()->willReturn($package->reveal());

    $package->getName()->willReturn('foo');
    $package->getPrettyName()->willReturn('foo');

    $io = $this->prophesize(IOInterface::class);
    $event->getIO()->willReturn($io->reveal());

    $this->expectDeprecation('Unsilenced deprecation: Calling Drupal\Core\Composer\Composer::vendorTestCodeCleanup from composer.json is deprecated in drupal:9.5.0 and is removed from drupal:10.0.0. Any "scripts" section mentioning this in composer.json can be removed and replaced with the drupal/core-vendor-hardening Composer plugin, as needed. See https://www.drupal.org/node/3260624');
    Composer::vendorTestCodeCleanup($event->reveal());
  }

  /**
   * @covers ::removeTimeout
   * @group legacy
   */
  public function testRemoveTimeout() {
    $this->expectDeprecation('Unsilenced deprecation: Calling Drupal\Core\Composer\Composer::removeTimeout from composer.json is deprecated in drupal:9.5.0 and is removed from drupal:10.0.0. There is no replacement. See https://www.drupal.org/node/3260624');
    Composer::removeTimeout();
  }

}
