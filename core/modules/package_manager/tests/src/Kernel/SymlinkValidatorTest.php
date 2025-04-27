<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Kernel;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\package_manager\Event\PreCreateEvent;
use Drupal\package_manager\Exception\SandboxEventException;
use Drupal\package_manager\PathLocator;
use Drupal\package_manager\ValidationResult;
use PhpTuf\ComposerStager\API\Environment\Service\EnvironmentInterface;
use Prophecy\Argument;

/**
 * @covers \Drupal\package_manager\Validator\SymlinkValidator
 * @group package_manager
 * @internal
 */
class SymlinkValidatorTest extends PackageManagerKernelTestBase {

  use StringTranslationTrait;

  /**
   * Tests that relative symlinks within the same package are supported.
   */
  public function testSymlinksWithinSamePackage(): void {
    $project_root = $this->container->get(PathLocator::class)
      ->getProjectRoot();

    $drush_dir = $project_root . '/vendor/drush/drush';
    mkdir($drush_dir . '/docs', 0777, TRUE);
    touch($drush_dir . '/drush_logo-black.png');
    // Relative symlinks must be made from their actual directory to be
    // correctly evaluated.
    chdir($drush_dir . '/docs');
    symlink('../drush_logo-black.png', 'drush_logo-black.png');

    // Switch back to the Drupal root to ensure that the check isn't affected
    // by which directory we happen to be in.
    chdir($this->getDrupalRoot());
    $this->assertStatusCheckResults([]);
  }

  /**
   * Tests that hard links are not supported.
   */
  public function testHardLinks(): void {
    $project_root = $this->container->get(PathLocator::class)
      ->getProjectRoot();

    link($project_root . '/composer.json', $project_root . '/composer.link');
    $result = ValidationResult::createError([
      $this->t('The %which directory at %dir contains hard links, which is not supported. The first one is %file.', [
        '%which' => 'active',
        '%dir' => $project_root,
        '%file' => $project_root . '/composer.json',
      ]),
    ]);
    $this->assertStatusCheckResults([$result]);
  }

  /**
   * Tests that symlinks with absolute paths are not supported.
   */
  public function testAbsoluteSymlinks(): void {
    $project_root = $this->container->get(PathLocator::class)
      ->getProjectRoot();

    symlink($project_root . '/composer.json', $project_root . '/composer.link');
    $result = ValidationResult::createError([
      $this->t('The %which directory at %dir contains absolute links, which is not supported. The first one is %file.', [
        '%which' => 'active',
        '%dir' => $project_root,
        '%file' => $project_root . '/composer.link',
      ]),
    ]);
    $this->assertStatusCheckResults([$result]);
  }

  /**
   * Tests that relative symlinks cannot point outside the project root.
   */
  public function testSymlinkPointingOutsideProjectRoot(): void {
    $project_root = $this->container->get(PathLocator::class)
      ->getProjectRoot();

    $parent_dir = dirname($project_root);
    touch($parent_dir . '/hello.txt');
    // Relative symlinks must be made from their actual directory to be
    // correctly evaluated.
    chdir($project_root);
    symlink('../hello.txt', 'fail.txt');
    $result = ValidationResult::createError([
      $this->t('The %which directory at %dir contains links that point outside the codebase, which is not supported. The first one is %file.', [
        '%which' => 'active',
        '%dir' => $project_root,
        '%file' => $project_root . '/fail.txt',
      ]),
    ]);
    $this->assertStatusCheckResults([$result]);
    $this->assertResults([$result], PreCreateEvent::class);
  }

  /**
   * Tests that relative symlinks cannot point outside the stage directory.
   */
  public function testSymlinkPointingOutsideStageDirectory(): void {
    // The same check should apply to symlinks in the stage directory that
    // point outside of it.
    $stage = $this->createStage();
    $stage->create();
    $stage->require(['ext-json:*']);

    $stage_dir = $stage->getSandboxDirectory();
    $parent_dir = dirname($stage_dir);
    touch($parent_dir . '/hello.txt');
    // Relative symlinks must be made from their actual directory to be
    // correctly evaluated.
    chdir($stage_dir);
    symlink('../hello.txt', 'fail.txt');

    $result = ValidationResult::createError([
      $this->t('The %which directory at %dir contains links that point outside the codebase, which is not supported. The first one is %file.', [
        '%which' => 'staging',
        '%dir' => $stage_dir,
        '%file' => $stage_dir . '/fail.txt',
      ]),
    ]);
    try {
      $stage->apply();
      $this->fail('Expected an exception, but none was thrown.');
    }
    catch (SandboxEventException $e) {
      $this->assertExpectedResultsFromException([$result], $e);
    }
  }

  /**
   * Tests what happens when there is a symlink to a directory.
   */
  public function testSymlinkToDirectory(): void {
    $project_root = $this->container->get(PathLocator::class)
      ->getProjectRoot();

    mkdir($project_root . '/modules/custom');
    // Relative symlinks must be made from their actual directory to be
    // correctly evaluated.
    chdir($project_root . '/modules/custom');
    symlink('../example', 'example_module');

    // Switch back to the Drupal root to ensure that the check isn't affected
    // by which directory we happen to be in.
    chdir($this->getDrupalRoot());
    $this->assertStatusCheckResults([]);
  }

  /**
   * Tests that symlinks are not supported on Windows, even if they're safe.
   */
  public function testSymlinksNotAllowedOnWindows(): void {
    $environment = $this->prophesize(EnvironmentInterface::class);
    $environment->isWindows()->willReturn(TRUE);
    $environment->setTimeLimit(Argument::type('int'))->willReturn(TRUE);
    $this->container->set(EnvironmentInterface::class, $environment->reveal());

    $project_root = $this->container->get(PathLocator::class)
      ->getProjectRoot();
    // Relative symlinks must be made from their actual directory to be
    // correctly evaluated.
    chdir($project_root);
    symlink('composer.json', 'composer.link');

    $result = ValidationResult::createError([
      $this->t('The %which directory at %dir contains links, which is not supported on Windows. The first one is %file.', [
        '%which' => 'active',
        '%dir' => $project_root,
        '%file' => $project_root . '/composer.link',
      ]),
    ]);
    $this->assertStatusCheckResults([$result]);
  }

  /**
   * Tests that unsupported links are excluded if they're under excluded paths.
   *
   * @depends testAbsoluteSymlinks
   *
   * @covers \Drupal\package_manager\PathExcluder\GitExcluder
   * @covers \Drupal\package_manager\PathExcluder\NodeModulesExcluder
   */
  public function testUnsupportedLinkUnderExcludedPath(): void {
    $project_root = $this->container->get(PathLocator::class)
      ->getProjectRoot();

    // Create absolute symlinks (which are not supported by Composer Stager) in
    // both `node_modules`, which is a regular directory, and `.git`, which is a
    // hidden directory.
    mkdir($project_root . '/node_modules');
    symlink($project_root . '/composer.json', $project_root . '/node_modules/composer.link');
    symlink($project_root . '/composer.json', $project_root . '/.git/composer.link');

    $this->assertStatusCheckResults([]);
  }

}
