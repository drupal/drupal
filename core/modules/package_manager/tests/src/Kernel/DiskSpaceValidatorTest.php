<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Kernel;

use Drupal\package_manager\Event\PreApplyEvent;
use Drupal\package_manager\Event\PreCreateEvent;
use Drupal\package_manager\ValidationResult;
use Drupal\Component\Utility\Bytes;
use Drupal\package_manager\Validator\DiskSpaceValidator;

/**
 * @covers \Drupal\package_manager\Validator\DiskSpaceValidator
 * @group package_manager
 * @internal
 */
class DiskSpaceValidatorTest extends PackageManagerKernelTestBase {

  /**
   * Data provider for testDiskSpaceValidation().
   *
   * @return mixed[][]
   *   The test cases.
   */
  public static function providerDiskSpaceValidation(): array {
    // @see \Drupal\Tests\package_manager\Traits\ValidationTestTrait::resolvePlaceholdersInArrayValuesWithRealPaths()
    $root = '<PROJECT_ROOT>';
    $vendor = '<VENDOR_DIR>';

    $root_insufficient = t('Drupal root filesystem "<PROJECT_ROOT>" has insufficient space. There must be at least 1024 megabytes free.');
    $vendor_insufficient = t('Vendor filesystem "<VENDOR_DIR>" has insufficient space. There must be at least 1024 megabytes free.');
    $temp_insufficient = t('Directory "temp" has insufficient space. There must be at least 1024 megabytes free.');
    $summary = t("There is not enough disk space to create a stage directory.");

    return [
      'shared, vendor and temp sufficient, root insufficient' => [
        TRUE,
        [
          $root => '1M',
          $vendor => '2G',
          'temp' => '4G',
        ],
        [
          ValidationResult::createError([$root_insufficient]),
        ],
      ],
      'shared, root and vendor insufficient, temp sufficient' => [
        TRUE,
        [
          $root => '1M',
          $vendor => '2M',
          'temp' => '2G',
        ],
        [
          ValidationResult::createError([$root_insufficient]),
        ],
      ],
      'shared, vendor and root sufficient, temp insufficient' => [
        TRUE,
        [
          $root => '2G',
          $vendor => '4G',
          'temp' => '1M',
        ],
        [
          ValidationResult::createError([$temp_insufficient]),
        ],
      ],
      'shared, root and temp insufficient, vendor sufficient' => [
        TRUE,
        [
          $root => '1M',
          $vendor => '2G',
          'temp' => '2M',
        ],
        [
          ValidationResult::createError([
            $root_insufficient,
            $temp_insufficient,
          ], $summary),
        ],
      ],
      'not shared, root insufficient, vendor and temp sufficient' => [
        FALSE,
        [
          $root => '5M',
          $vendor => '1G',
          'temp' => '4G',
        ],
        [
          ValidationResult::createError([$root_insufficient]),
        ],
      ],
      'not shared, vendor insufficient, root and temp sufficient' => [
        FALSE,
        [
          $root => '2G',
          $vendor => '10M',
          'temp' => '4G',
        ],
        [
          ValidationResult::createError([$vendor_insufficient]),
        ],
      ],
      'not shared, root and vendor sufficient, temp insufficient' => [
        FALSE,
        [
          $root => '1G',
          $vendor => '2G',
          'temp' => '3M',
        ],
        [
          ValidationResult::createError([$temp_insufficient]),
        ],
      ],
      'not shared, root and vendor insufficient, temp sufficient' => [
        FALSE,
        [
          $root => '500M',
          $vendor => '75M',
          'temp' => '2G',
        ],
        [
          ValidationResult::createError([
            $root_insufficient,
            $vendor_insufficient,
          ], $summary),
        ],
      ],
    ];
  }

  /**
   * Tests disk space validation.
   *
   * @param bool $shared_disk
   *   Whether the root and vendor directories are on the same logical disk.
   * @param array $free_space
   *   The free space that should be reported for various paths. The keys
   *   are the paths, and the values are the free space that should be reported,
   *   in a format that can be parsed by
   *   \Drupal\Component\Utility\Bytes::toNumber().
   * @param \Drupal\package_manager\ValidationResult[] $expected_results
   *   The expected validation results.
   *
   * @dataProvider providerDiskSpaceValidation
   */
  public function testDiskSpaceValidation(bool $shared_disk, array $free_space, array $expected_results): void {
    $free_space = array_flip($this->resolvePlaceholdersInArrayValuesWithRealPaths(array_flip($free_space)));

    /** @var \Drupal\Tests\package_manager\Kernel\TestDiskSpaceValidator $validator */
    $validator = $this->container->get(DiskSpaceValidator::class);
    $validator->sharedDisk = $shared_disk;
    $validator->freeSpace = array_map([Bytes::class, 'toNumber'], $free_space);

    $this->assertStatusCheckResults($expected_results);
    $this->assertResults($expected_results, PreCreateEvent::class);
  }

  /**
   * Tests disk space validation during pre-apply.
   *
   * @param bool $shared_disk
   *   Whether the root and vendor directories are on the same logical disk.
   * @param array $free_space
   *   The free space that should be reported for various paths. The keys
   *   are the paths, and the values are the free space that should be reported,
   *   in a format that can be parsed by
   *   \Drupal\Component\Utility\Bytes::toNumber().
   * @param \Drupal\package_manager\ValidationResult[] $expected_results
   *   The expected validation results.
   *
   * @dataProvider providerDiskSpaceValidation
   */
  public function testDiskSpaceValidationDuringPreApply(bool $shared_disk, array $free_space, array $expected_results): void {
    $free_space = array_flip($this->resolvePlaceholdersInArrayValuesWithRealPaths(array_flip($free_space)));

    $this->addEventTestListener(function () use ($shared_disk, $free_space): void {
      /** @var \Drupal\Tests\package_manager\Kernel\TestDiskSpaceValidator $validator */
      $validator = $this->container->get(DiskSpaceValidator::class);
      $validator->sharedDisk = $shared_disk;
      $validator->freeSpace = array_map([Bytes::class, 'toNumber'], $free_space);
    });

    $this->assertResults($expected_results, PreApplyEvent::class);
  }

}
