<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Kernel;

use Drupal\package_manager\Exception\SandboxEventException;
use Drupal\package_manager\PathLocator;
use Drupal\package_manager\ValidationResult;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @covers \Drupal\package_manager\Validator\DuplicateInfoFileValidator
 * @group package_manager
 * @internal
 */
class DuplicateInfoFileValidatorTest extends PackageManagerKernelTestBase {

  /**
   * Data provider for testDuplicateInfoFilesInStage.
   *
   * @return mixed[][]
   *   The test cases.
   */
  public static function providerDuplicateInfoFilesInStage(): array {
    return [
      'Duplicate info.yml files in stage' => [
        [
          '/module.info.yml',
        ],
        [
          '/module.info.yml',
          '/modules/module.info.yml',
        ],
        [
          ValidationResult::createError([
            t('The stage directory has 2 instances of module.info.yml as compared to 1 in the active directory. This likely indicates that a duplicate extension was installed.'),
          ]),
        ],
      ],
      // Duplicate files in stage but having different extension which we don't
      // care about.
      'Duplicate info files in stage' => [
        [
          '/my_file.info',
        ],
        [
          '/my_file.info',
          '/modules/my_file.info',
        ],
        [],
      ],
      'Duplicate info.yml files in stage with one file in tests/fixtures folder' => [
        [
          '/tests/fixtures/module.info.yml',
        ],
        [
          '/tests/fixtures/module.info.yml',
          '/modules/module.info.yml',
        ],
        [],
      ],
      'Duplicate info.yml files in stage with one file in tests/modules folder' => [
        [
          '/tests/modules/module.info.yml',
        ],
        [
          '/tests/modules/module.info.yml',
          '/modules/module.info.yml',
        ],
        [],
      ],
      'Duplicate info.yml files in stage with one file in tests/themes folder' => [
        [
          '/tests/themes/theme.info.yml',
        ],
        [
          '/tests/themes/theme.info.yml',
          '/themes/theme.info.yml',
        ],
        [],
      ],
      'Duplicate info.yml files in stage with one file in tests/profiles folder' => [
        [
          '/tests/profiles/profile.info.yml',
        ],
        [
          '/tests/profiles/profile.info.yml',
          '/profiles/profile.info.yml',
        ],
        [],
      ],
      'Duplicate info.yml files in stage not present in active' => [
        [],
        [
          '/module.info.yml',
          '/modules/module.info.yml',
        ],
        [
          ValidationResult::createError([
            t('The stage directory has 2 instances of module.info.yml. This likely indicates that a duplicate extension was installed.'),
          ]),
        ],
      ],
      'Duplicate info.yml files in active' => [
        [
          '/module.info.yml',
          '/modules/module.info.yml',
        ],
        [
          '/module.info.yml',
        ],
        [],
      ],
      'Same number of info.yml files in active and stage' => [
        [
          '/module.info.yml',
          '/modules/module.info.yml',
        ],
        [
          '/module.info.yml',
          '/modules/module.info.yml',
        ],
        [],
      ],
      'Multiple duplicate info.yml files in stage' => [
        [
          '/modules/module1/module1.info.yml',
          '/modules/module2/module2.info.yml',
        ],
        [
          '/modules/module1/module1.info.yml',
          '/modules/module2/module2.info.yml',
          '/modules/foo/module1.info.yml',
          '/modules/bar/module2.info.yml',
          '/modules/baz/module2.info.yml',
        ],
        [
          ValidationResult::createError([
            t('The stage directory has 3 instances of module2.info.yml as compared to 1 in the active directory. This likely indicates that a duplicate extension was installed.'),
          ]),
          ValidationResult::createError([
            t('The stage directory has 2 instances of module1.info.yml as compared to 1 in the active directory. This likely indicates that a duplicate extension was installed.'),
          ]),
        ],
      ],
      'Multiple duplicate info.yml files in stage not present in active' => [
        [],
        [
          '/modules/module1/module1.info.yml',
          '/modules/module2/module2.info.yml',
          '/modules/foo/module1.info.yml',
          '/modules/bar/module2.info.yml',
          '/modules/baz/module2.info.yml',
        ],
        [
          ValidationResult::createError([
            t('The stage directory has 3 instances of module2.info.yml. This likely indicates that a duplicate extension was installed.'),
          ]),
          ValidationResult::createError([
            t('The stage directory has 2 instances of module1.info.yml. This likely indicates that a duplicate extension was installed.'),
          ]),
        ],
      ],
      'Multiple duplicate info.yml files in stage with one info.yml file not present in active' => [
        [
          '/modules/module1/module1.info.yml',
        ],
        [
          '/modules/module1/module1.info.yml',
          '/modules/module2/module2.info.yml',
          '/modules/foo/module1.info.yml',
          '/modules/bar/module2.info.yml',
          '/modules/baz/module2.info.yml',
        ],
        [
          ValidationResult::createError([
            t('The stage directory has 3 instances of module2.info.yml. This likely indicates that a duplicate extension was installed.'),
          ]),
          ValidationResult::createError([
            t('The stage directory has 2 instances of module1.info.yml as compared to 1 in the active directory. This likely indicates that a duplicate extension was installed.'),
          ]),
        ],
      ],
    ];
  }

  /**
   * Tests that duplicate info.yml in stage raise an error.
   *
   * @param string[] $active_info_files
   *   An array of info.yml files in active directory.
   * @param string[] $stage_info_files
   *   An array of info.yml files in stage directory.
   * @param \Drupal\package_manager\ValidationResult[] $expected_results
   *   An array of expected results.
   *
   * @dataProvider providerDuplicateInfoFilesInStage
   */
  public function testDuplicateInfoFilesInStage(array $active_info_files, array $stage_info_files, array $expected_results): void {
    $stage = $this->createStage();
    $stage->create();
    $stage->require(['composer/semver:^3']);

    $active_dir = $this->container->get(PathLocator::class)->getProjectRoot();
    $stage_dir = $stage->getSandboxDirectory();
    foreach ($active_info_files as $active_info_file) {
      $this->createFileAtPath($active_dir, $active_info_file);
    }
    foreach ($stage_info_files as $stage_info_file) {
      $this->createFileAtPath($stage_dir, $stage_info_file);
    }
    try {
      $stage->apply();
      $this->assertEmpty($expected_results);
    }
    catch (SandboxEventException $e) {
      $this->assertNotEmpty($expected_results);
      $this->assertValidationResultsEqual($expected_results, $e->event->getResults());
    }
  }

  /**
   * Creates the file at the root directory.
   *
   * @param string $root_directory
   *   The base directory in which the file will be created.
   * @param string $file_path
   *   The path of the file to create.
   */
  private function createFileAtPath(string $root_directory, string $file_path): void {
    $parts = explode(DIRECTORY_SEPARATOR, $file_path);
    $filename = array_pop($parts);
    $file_dir = str_replace($filename, '', $file_path);
    $fs = new Filesystem();
    if (!file_exists($file_dir)) {
      $fs->mkdir($root_directory . $file_dir);
    }
    file_put_contents($root_directory . $file_path, "name: SOME MODULE\ntype: module\n");
  }

}
