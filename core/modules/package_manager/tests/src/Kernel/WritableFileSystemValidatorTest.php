<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Kernel;

use Drupal\package_manager\Event\PreApplyEvent;
use Drupal\package_manager\Event\PreCreateEvent;
use Drupal\package_manager\PathLocator;
use Drupal\package_manager\ValidationResult;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Unit tests the file system permissions validator.
 *
 * This validator is tested functionally in Automatic Updates' build tests,
 * since those give us control over the file system permissions.
 *
 * @see \Drupal\Tests\auto_updates\Build\CoreUpdateTest::assertReadOnlyFileSystemError()
 *
 * @covers \Drupal\package_manager\Validator\WritableFileSystemValidator
 * @group package_manager
 * @internal
 */
class WritableFileSystemValidatorTest extends PackageManagerKernelTestBase {

  /**
   * Data provider for testWritable().
   *
   * @return mixed[][]
   *   The test cases.
   */
  public static function providerWritable(): array {
    // @see \Drupal\Tests\package_manager\Traits\ValidationTestTrait::resolvePlaceholdersInArrayValuesWithRealPaths()
    $drupal_root_error = t('The Drupal directory "<PROJECT_ROOT>/web" is not writable.');
    $vendor_error = t('The vendor directory "<VENDOR_DIR>" is not writable.');
    $project_root_error = t('The project root directory "<PROJECT_ROOT>" is not writable.');
    $summary = t('The file system is not writable.');
    $writable_permission = 0777;
    $non_writable_permission = 0550;

    return [
      'root and vendor are writable, nested web root' => [
        $writable_permission,
        $writable_permission,
        $writable_permission,
        'web',
        [],
      ],
      'root writable, vendor not writable, nested web root' => [
        $writable_permission,
        $writable_permission,
        $non_writable_permission,
        'web',
        [
          ValidationResult::createError([$vendor_error], $summary),
        ],
      ],
      'root not writable, vendor writable, nested web root' => [
        $non_writable_permission,
        $non_writable_permission,
        $writable_permission,
        'web',
        [
          ValidationResult::createError([$drupal_root_error, $project_root_error], $summary),
        ],
      ],
      'nothing writable, nested web root' => [
        $non_writable_permission,
        $non_writable_permission,
        $non_writable_permission,
        'web',
        [
          ValidationResult::createError([$drupal_root_error, $project_root_error, $vendor_error], $summary),
        ],
      ],
      'root and vendor are writable, non-nested web root' => [
        $writable_permission,
        $writable_permission,
        $writable_permission,
        '',
        [],
      ],
      'root writable, vendor not writable, non-nested web root' => [
        $writable_permission,
        $writable_permission,
        $non_writable_permission,
        '',
        [
          ValidationResult::createError([$vendor_error], $summary),
        ],
      ],
      'root not writable, vendor writable, non-nested web root' => [
        $non_writable_permission,
        $non_writable_permission,
        $writable_permission,
        '',
        [
          ValidationResult::createError([$project_root_error], $summary),
        ],
      ],
      'nothing writable, non-nested web root' => [
        $non_writable_permission,
        $non_writable_permission,
        $non_writable_permission,
        '',
        [
          ValidationResult::createError([$project_root_error, $vendor_error], $summary),
        ],
      ],
    ];
  }

  /**
   * Tests the file system permissions validator.
   *
   * @param int $root_permissions
   *   The file permissions for the root folder.
   * @param int $webroot_permissions
   *   The file permissions for the web root folder.
   * @param int $vendor_permissions
   *   The file permissions for the vendor folder.
   * @param string $webroot_relative_directory
   *   The web root path, relative to the project root, or an empty string if
   *   the web root and project root are the same.
   * @param \Drupal\package_manager\ValidationResult[] $expected_results
   *   The expected validation results.
   *
   * @dataProvider providerWritable
   */
  public function testWritable(int $root_permissions, int $webroot_permissions, int $vendor_permissions, string $webroot_relative_directory, array $expected_results): void {
    $this->setUpPermissions($root_permissions, $webroot_permissions, $vendor_permissions, $webroot_relative_directory);

    $this->assertStatusCheckResults($expected_results);
    $this->assertResults($expected_results, PreCreateEvent::class);
  }

  /**
   * Tests the file system permissions validator during pre-apply.
   *
   * @param int $root_permissions
   *   The file permissions for the root folder.
   * @param int $webroot_permissions
   *   The file permissions for the web root folder.
   * @param int $vendor_permissions
   *   The file permissions for the vendor folder.
   * @param string $webroot_relative_directory
   *   The web root path, relative to the project root, or an empty string if
   *   the web root and project root are the same.
   * @param \Drupal\package_manager\ValidationResult[] $expected_results
   *   The expected validation results.
   *
   * @dataProvider providerWritable
   */
  public function testWritableDuringPreApply(int $root_permissions, int $webroot_permissions, int $vendor_permissions, string $webroot_relative_directory, array $expected_results): void {
    $this->addEventTestListener(
      function () use ($webroot_permissions, $root_permissions, $vendor_permissions, $webroot_relative_directory): void {
        $this->setUpPermissions($root_permissions, $webroot_permissions, $vendor_permissions, $webroot_relative_directory);

        // During pre-apply we don't care whether the staging root is writable.
        /** @var \Drupal\package_manager_bypass\MockPathLocator $path_locator */
        $path_locator = $this->container->get(PathLocator::class);
        $this->assertTrue(chmod($path_locator->getStagingRoot(), 0550));
      },
    );

    $this->assertResults($expected_results, PreApplyEvent::class);
  }

  /**
   * Sets the permissions of the test project's directories.
   *
   * @param int $root_permissions
   *   The permissions for the project root.
   * @param int $web_root_permissions
   *   The permissions for the web root.
   * @param int $vendor_permissions
   *   The permissions for the vendor directory.
   * @param string $relative_web_root
   *   The web root path, relative to the project root, or an empty string if
   *   the web root and project root are the same.
   */
  private function setUpPermissions(int $root_permissions, int $web_root_permissions, int $vendor_permissions, string $relative_web_root): void {
    /** @var \Drupal\package_manager_bypass\MockPathLocator $path_locator */
    $path_locator = $this->container->get(PathLocator::class);

    $project_root = $web_root = $path_locator->getProjectRoot();
    $vendor_dir = $path_locator->getVendorDirectory();
    // Create the web root directory, if necessary.
    if (!empty($relative_web_root)) {
      $web_root .= '/' . $relative_web_root;
      mkdir($web_root);
    }
    $path_locator->setPaths($project_root, $vendor_dir, $relative_web_root, $path_locator->getStagingRoot());

    // We need to set the vendor directory and web root permissions first
    // because they may be located inside the project root.
    $this->assertTrue(chmod($vendor_dir, $vendor_permissions));
    if ($project_root !== $web_root) {
      $this->assertTrue(chmod($web_root, $web_root_permissions));
    }
    $this->assertTrue(chmod($project_root, $root_permissions));
  }

  /**
   * Data provider for ::testStagingRootPermissions().
   *
   * @return mixed[][]
   *   The test cases.
   */
  public static function providerStagingRootPermissions(): array {
    $writable_permission = 0777;
    $non_writable_permission = 0550;
    $summary = t('The file system is not writable.');
    return [
      'writable stage root exists' => [
        $writable_permission,
        [],
        FALSE,
      ],
      'write-protected stage root exists' => [
        $non_writable_permission,
        [
          ValidationResult::createError([t('The stage root directory "<STAGE_ROOT>" is not writable.')], $summary),
        ],
        FALSE,
      ],
      'stage root directory does not exist, parent directory not writable' => [
        $non_writable_permission,
        [
          ValidationResult::createError([t('The stage root directory will not able to be created at "<STAGE_ROOT_PARENT>".')], $summary),
        ],
        TRUE,
      ],
    ];
  }

  /**
   * Tests that the stage root's permissions are validated.
   *
   * @param int $permissions
   *   The file permissions to apply to the stage root directory, or its parent
   *   directory, depending on the value of $delete_staging_root.
   * @param array $expected_results
   *   The expected validation results.
   * @param bool $delete_staging_root
   *   Whether the stage root directory will exist at all.
   *
   * @dataProvider providerStagingRootPermissions
   */
  public function testStagingRootPermissions(int $permissions, array $expected_results, bool $delete_staging_root): void {
    $dir = $this->container->get(PathLocator::class)
      ->getStagingRoot();

    if ($delete_staging_root) {
      $fs = new Filesystem();
      $fs->remove($dir);
      $dir = dirname($dir);
    }
    $this->assertTrue(chmod($dir, $permissions));
    $this->assertStatusCheckResults($expected_results);
    $this->assertResults($expected_results, PreCreateEvent::class);
  }

}
