<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Kernel;

use Drupal\package_manager\PathLocator;
use Drupal\package_manager\ValidationResult;

/**
 * @covers \Drupal\package_manager\Validator\StagedDBUpdateValidator
 * @group package_manager
 * @internal
 */
class StagedDBUpdateValidatorTest extends PackageManagerKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->container->get('theme_installer')->install(['stark']);
    $this->assertFalse($this->container->get('module_handler')->moduleExists('views'));
    $this->assertFalse($this->container->get('theme_handler')->themeExists('olivero'));

    // Ensure that all the extensions we're testing with have database update
    // files in the active directory.
    $active_dir = $this->container->get(PathLocator::class)->getProjectRoot();

    // System and Stark are installed, so they are used to test what happens
    // when database updates are detected in installed extensions. Views and
    // Olivero are not installed, so they are used to test what happens when
    // non-installed extensions have database updates.
    $extensions = [
      'core/modules/system',
      'core/themes/stark',
      'core/modules/views',
      'core/themes/olivero',
    ];
    foreach ($extensions as $extension_path) {
      $extension_path = $active_dir . '/' . $extension_path;
      mkdir($extension_path, 0777, TRUE);
      $extension_name = basename($extension_path);

      // Ensure each extension has a .install and a .post_update.php file with
      // an empty update function in it.
      foreach (['install', 'post_update.php'] as $suffix) {
        $function_name = match ($suffix) {
          'install' => $extension_name . '_update_1000',
          'post_update.php' => $extension_name . '_post_update_test',
        };
        file_put_contents("$extension_path/$extension_name.$suffix", "<?php\nfunction $function_name() {}");
      }
    }
  }

  /**
   * Data provider for ::testStagedDatabaseUpdates().
   *
   * @return array[]
   *   The test cases.
   */
  public static function providerStagedDatabaseUpdate(): array {
    $summary = t('Database updates have been detected in the following extensions.');

    return [
      'schema update in installed module' => [
        'core/modules/system',
        'install',
        [
          ValidationResult::createWarning([
            t('System'),
          ], $summary),
        ],
      ],
      'post-update in installed module' => [
        'core/modules/system',
        'post_update.php',
        [
          ValidationResult::createWarning([
            t('System'),
          ], $summary),
        ],
      ],
      'schema update in installed theme' => [
        'core/themes/stark',
        'install',
        [
          ValidationResult::createWarning([
            t('Stark'),
          ], $summary),
        ],
      ],
      'post-update in installed theme' => [
        'core/themes/stark',
        'post_update.php',
        [
          ValidationResult::createWarning([
            t('Stark'),
          ], $summary),
        ],
      ],
      // The validator should ignore changes in any extensions that aren't
      // installed.
      'schema update in non-installed module' => [
        'core/modules/views',
        'install',
        [],
      ],
      'post-update in non-installed module' => [
        'core/modules/views',
        'post_update.php',
        [],
      ],
      'schema update in non-installed theme' => [
        'core/themes/olivero',
        'install',
        [],
      ],
      'post-update in non-installed theme' => [
        'core/themes/olivero',
        'post_update.php',
        [],
      ],
    ];
  }

  /**
   * Tests validation of staged database updates.
   *
   * @param string $extension_dir
   *   The directory of the extension that should have database updates,
   *   relative to the stage directory.
   * @param string $file_extension
   *   The extension of the update file, without the leading period. Must be
   *   either `install` or `post_update.php`.
   * @param \Drupal\package_manager\ValidationResult[] $expected_results
   *   The expected validation results.
   *
   * @dataProvider providerStagedDatabaseUpdate
   */
  public function testStagedDatabaseUpdate(string $extension_dir, string $file_extension, array $expected_results): void {
    $extension_name = basename($extension_dir);
    $relative_file_path = $extension_dir . '/' . $extension_name . '.' . $file_extension;

    $stage = $this->createStage();
    $stage->create();
    // Nothing has been changed in the stage, so ensure the validator doesn't
    // detect any changes.
    $this->assertStatusCheckResults([], $stage);

    $staged_update_file = $stage->getStageDirectory() . '/' . $relative_file_path;
    $this->assertFileIsWritable($staged_update_file);

    // Now add a "real" update function -- either a schema update or a
    // post-update, depending on what $file_extension is -- and ensure that the
    // validator detects it.
    $update_function_name = match ($file_extension) {
      'install' => $extension_name . '_update_1001',
      'post_update.php' => $extension_name . '_post_update_' . $this->randomMachineName(),
    };
    file_put_contents($staged_update_file, "function $update_function_name() {}\n", FILE_APPEND);
    $this->assertStatusCheckResults($expected_results, $stage);

    // Add a bunch of functions which are named similarly to real schema update
    // and post-update functions, but not quite right, to ensure they are
    // ignored by the validator. Also throw an anonymous function in there to
    // ensure those are ignored as well.
    $code = <<<END
<?php
function {$extension_name}_update() { \$foo = function () {}; }
function {$extension_name}_update_string_123() {}
function {$extension_name}_update__123() {}
function ($extension_name}__post_update_test() {}
function ($extension_name}_post_update() {}
END;
    file_put_contents($staged_update_file, $code);
    $this->assertStatusCheckResults([], $stage);

    // If the update file is deleted from the stage, the validator should not
    // detect any database updates.
    unlink($staged_update_file);
    $this->assertStatusCheckResults([], $stage);

    // If the update file doesn't exist in the active directory, but does exist
    // in the stage with a legitimate schema update or post-update function, the
    // validator should detect it.
    $project_root = $this->container->get(PathLocator::class)
      ->getProjectRoot();
    unlink($project_root . '/' . $relative_file_path);
    file_put_contents($staged_update_file, "<?php\nfunction $update_function_name() {}");
    $this->assertStatusCheckResults($expected_results, $stage);
  }

  /**
   * Tests that the validator disregards unclaimed stages.
   */
  public function testUnclaimedStage(): void {
    $stage = $this->createStage();
    $stage->create();
    $this->assertStatusCheckResults([], $stage);
    // A new, unclaimed stage should be ignored by the validator.
    $this->assertStatusCheckResults([], $this->createStage());
  }

}
