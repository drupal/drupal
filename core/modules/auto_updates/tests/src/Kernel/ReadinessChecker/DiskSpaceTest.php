<?php

namespace Drupal\Tests\auto_updates\Kernel\ReadinessChecker;

use Composer\Autoload\ClassLoader;
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
    // No disk space issues.
    $checker = $this->container->get('auto_updates.disk_space_checker');
    $result = $checker->getResult();
    $this->assertNull($result);

    // Out of space.
    /** @var \Composer\Autoload\ClassLoader  $class_loader */
    $class_loader = $this->container->get('class_loader');
    $app_root = $this->container->getParameter('app.root');
    $checker = $this->replaceCheckerService(new TestDiskSpace($app_root, $class_loader));
    $result = $checker->getResult();
    $messages = $result->getErrorMessages();
    $this->assertCount(1, $messages);
    $this->assertStringMatchesFormat('Logical disk "%s" has insufficient space. There must be at least %s MB free.', (string) $messages[0]);

    // Out of space not the same logical disk.
    $checker = $this->replaceCheckerService(new TestDiskSpaceNonSameDisk($app_root, $class_loader));
    $result = $checker->getResult();
    $messages = $result->getErrorMessages();
    $this->assertCount(2, $messages);
    $this->assertStringMatchesFormat('Drupal root filesystem "%s" has insufficient space. There must be at least %s MB free.', (string) $messages[0]);
    $this->assertStringMatchesFormat('Vendor filesystem "%s" has insufficient space. There must be at least %s MB free.', (string) $messages[1]);

    // Web root and vendor path are invalid.
    $checker = $this->replaceCheckerService(new TestDiskSpaceInvalidVendor('if_there_was_ever_a_folder_with_this_path_this_test_would_fail', $class_loader));
    $result = $checker->getResult();
    $messages = $result->getErrorMessages();
    $this->assertCount(1, $messages);
    $this->assertEquals('Free disk space cannot be determined because the web root and vendor directories could not be located.', (string) $messages[0]);
  }

  /**
   * Replaces the disk space checker service in the container.
   *
   * The checker must be set in the container because the '_serviceId' property
   * must be set on the object for
   * \Drupal\auto_updates\ReadinessChecker\ReadinessCheckerManager to work.
   *
   * @param \Drupal\auto_updates\ReadinessChecker\DiskSpace $disk_space_checker
   *   The disk space checker service to set in the container.
   *
   * @return \Drupal\auto_updates\ReadinessChecker\DiskSpace
   *   The new disk space checker returned from the container.
   */
  protected function replaceCheckerService(DiskSpace $disk_space_checker) {
    $this->container->set('auto_updates.disk_space_checker', $disk_space_checker);
    return $this->container->get('auto_updates.disk_space_checker');
  }

}

/**
 * Test checker with the free disk space minimum set to a 1000 terabytes.
 */
class TestDiskSpace extends DiskSpace {

  /**
   * {@inheritdoc}
   */
  const MINIMUM_DISK_SPACE = 1000000000000000;

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
  public function __construct(string $app_root, ClassLoader $class_loader) {
    parent::__construct($app_root, $class_loader);
    $this->vendorDir = 'if_there_was_ever_a_folder_with_this_path_this_test_would_fail';
  }

}
