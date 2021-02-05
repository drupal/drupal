<?php

namespace Drupal\Tests\auto_updates\Kernel\ReadinessChecker;

use Drupal\auto_updates\ReadinessChecker\DiskSpace;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests disk space readiness checking.
 *
 * @group auto_updates
 */
class DiskSpaceTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['auto_updates'];

  /**
   * Tests the functionality of disk space readiness checks.
   */
  public function testDiskSpace():void {
    /** @var \Composer\Autoload\ClassLoader  $class_loader */
    $class_loader = $this->container->get('class_loader');
    $app_root = $this->container->getParameter('app.root');
    // No disk space issues.
    $checker = new DiskSpace($app_root, $class_loader);
    // Readiness checkers are services and will always have the public property
    // '_serviceId'.
    $checker->_serviceId = 'auto_updates.disk_space_checker';
    $result = $checker->getResult();
    $this->assertNull($result);

    // Out of space.
    $checker = new TestDiskSpace($app_root, $class_loader);
    $checker->_serviceId = 'auto_updates.disk_space_checker';
    $result = $checker->getResult();
    $messages = $result->getErrorMessages();
    $this->assertCount(1, $messages);
    $this->assertStringMatchesFormat('Logical disk "%s" has insufficient space. There must be at least %s megabytes free.', (string) $messages[0]);

    // Out of space not the same logical disk.
    $checker = new TestDiskSpaceNonSameDisk($app_root, $class_loader);
    $checker->_serviceId = 'auto_updates.disk_space_checker';
    $result = $checker->getResult();
    $messages = $result->getErrorMessages();
    $this->assertCount(2, $messages);
    $this->assertStringMatchesFormat('Drupal root filesystem "%s" has insufficient space. There must be at least %s megabytes free.', (string) $messages[0]);
    $this->assertStringMatchesFormat('Vendor filesystem "%s" has insufficient space. There must be at least %s megabytes free.', (string) $messages[1]);

    // Web root and vendor path are invalid.
    $checker = new TestDiskSpaceInvalidVendor('if_there_was_ever_a_folder_with_this_path_this_test_would_fail', $class_loader);
    $checker->_serviceId = 'auto_updates.disk_space_checker';
    $result = $checker->getResult();
    $messages = $result->getErrorMessages();
    $this->assertCount(1, $messages);
    $this->assertEquals('Free disk space cannot be determined because the web root and vendor directories could not be located.', (string) $messages[0]);
  }

}

/**
 * Test checker with the free disk space minimum set to a very high number.
 */
class TestDiskSpace extends DiskSpace {

  /**
   * {@inheritdoc}
   */
  const MINIMUM_DISK_SPACE = 99999999999999999999999999999999999999999999999999;

}

/**
 * A test checker that overrides TestDiskSpace to fake different logical disks.
 */
class TestDiskSpaceNonSameDisk extends TestDiskSpace {

  /**
   * {@inheritdoc}
   */
  protected function areSameLogicalDisk(string $root, string $vendor): bool {
    return FALSE;
  }

}
/**
 * A test checker that overrides TestDiskSpace to fake an invalid vendor path.
 */
class TestDiskSpaceInvalidVendor extends TestDiskSpace {

  /**
   * {@inheritdoc}
   */
  protected $vendorDir = 'if_there_was_ever_a_folder_with_this_path_this_test_would_fail';

}
