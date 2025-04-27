<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Kernel;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\package_manager\ComposerInspector;
use Drupal\package_manager\Event\PreApplyEvent;
use Drupal\package_manager\Event\PreCreateEvent;
use Drupal\package_manager\Event\PreRequireEvent;
use Drupal\package_manager\Exception\SandboxException;
use Drupal\package_manager\InstalledPackagesList;
use Drupal\package_manager\PathLocator;
use Drupal\package_manager\Validator\LockFileValidator;
use Drupal\package_manager\ValidationResult;
use Drupal\package_manager_bypass\NoOpStager;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\package_manager\Validator\LockFileValidator
 * @group package_manager
 * @internal
 */
class LockFileValidatorTest extends PackageManagerKernelTestBase {

  use StringTranslationTrait;

  /**
   * The path of the active directory in the test project.
   *
   * @var string
   */
  private $activeDir;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->activeDir = $this->container->get(PathLocator::class)
      ->getProjectRoot();
  }

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    parent::register($container);

    // Temporarily mock the Composer inspector to prevent it from complaining
    // over the lack of a lock file if it's invoked by other validators.
    $inspector = $this->prophesize(ComposerInspector::class);
    $arguments = Argument::cetera();
    $inspector->getConfig('allow-plugins', $arguments)->willReturn('[]');
    $inspector->getConfig('secure-http', $arguments)->willReturn('true');
    $inspector->getConfig('disable-tls', $arguments)->willReturn('false');
    $inspector->getConfig('extra', $arguments)->willReturn('{}');
    $inspector->getConfig('minimum-stability', $arguments)->willReturn('stable');
    $inspector->getInstalledPackagesList($arguments)->willReturn(new InstalledPackagesList());
    $inspector->getAllowPluginsConfig($arguments)->willReturn([]);
    $inspector->validate($arguments);
    $inspector->getRootPackageInfo($arguments)->willReturn([]);
    $container->set(ComposerInspector::class, $inspector->reveal());
  }

  /**
   * Tests that if no active lock file exists, a stage cannot be created.
   *
   * @covers ::storeHash
   */
  public function testCreateWithNoLock(): void {
    unlink($this->activeDir . '/composer.lock');
    $project_root = $this->container->get(PathLocator::class)->getProjectRoot();
    $lock_file_path = $project_root . DIRECTORY_SEPARATOR . 'composer.lock';
    $no_lock = ValidationResult::createError([
      $this->t('The active lock file (@file) does not exist.', ['@file' => $lock_file_path]),
    ]);
    $stage = $this->assertResults([$no_lock], PreCreateEvent::class);
    // The stage was not created successfully, so the status check should be
    // clear.
    $this->assertStatusCheckResults([], $stage);
  }

  /**
   * Tests that if an active lock file exists, a stage can be created.
   *
   * @covers ::storeHash
   * @covers ::deleteHash
   */
  public function testCreateWithLock(): void {
    $this->assertResults([]);

    // Change the lock file to ensure the stored hash of the previous version
    // has been deleted.
    file_put_contents($this->activeDir . '/composer.lock', '{"changed": true}');
    $this->assertResults([]);
  }

  /**
   * Tests validation when the lock file has changed.
   *
   * @dataProvider providerValidateStageEvents
   */
  public function testLockFileChanged(string $event_class): void {
    // Add a listener with an extremely high priority to the same event that
    // should raise the validation error. Because the validator uses the default
    // priority of 0, this listener changes lock file before the validator
    // runs.
    $this->addEventTestListener(function () {
      $lock = json_decode(file_get_contents($this->activeDir . '/composer.lock'), TRUE, flags: JSON_THROW_ON_ERROR);
      $lock['extra']['key'] = 'value';
      file_put_contents($this->activeDir . '/composer.lock', json_encode($lock, JSON_THROW_ON_ERROR));
    }, $event_class);
    $result = ValidationResult::createError([
      $this->t('Unexpected changes were detected in the active lock file (@file), which indicates that other Composer operations were performed since this Package Manager operation started. This can put the code base into an unreliable state and therefore is not allowed.',
       ['@file' => $this->activeDir . '/composer.lock']),
    ], $this->t('Problem detected in lock file during stage operations.'));
    $stage = $this->assertResults([$result], $event_class);
    // A status check should agree that there is an error here.
    $this->assertStatusCheckResults([$result], $stage);
  }

  /**
   * Tests validation when the lock file is deleted.
   *
   * @dataProvider providerValidateStageEvents
   */
  public function testLockFileDeleted(string $event_class): void {
    // Add a listener with an extremely high priority to the same event that
    // should raise the validation error. Because the validator uses the default
    // priority of 0, this listener deletes lock file before the validator
    // runs.
    $this->addEventTestListener(function () {
      unlink($this->activeDir . '/composer.lock');
    }, $event_class);
    $result = ValidationResult::createError([
      $this->t('The active lock file (@file) does not exist.', [
        '@file' => $this->activeDir . '/composer.lock',
      ]),
    ], $this->t('Problem detected in lock file during stage operations.'));
    $stage = $this->assertResults([$result], $event_class);
    // A status check should agree that there is an error here.
    $this->assertStatusCheckResults([$result], $stage);
  }

  /**
   * Tests exception when a stored hash of the active lock file is unavailable.
   *
   * @dataProvider providerValidateStageEvents
   */
  public function testNoStoredHash(string $event_class): void {
    $reflector = new \ReflectionClassConstant(LockFileValidator::class, 'KEY');
    $key = $reflector->getValue();

    // Add a listener with an extremely high priority to the same event that
    // should throw an exception. Because the validator uses the default
    // priority of 0, this listener deletes stored hash before the validator
    // runs.
    $this->addEventTestListener(function () use ($key) {
      $this->container->get('keyvalue')
        ->get('package_manager')
        ->delete($key);
    }, $event_class);

    $stage = $this->createStage();
    $stage->create();
    try {
      $stage->require(['drupal/core:9.8.1']);
      $stage->apply();
    }
    catch (SandboxException $e) {
      $this->assertSame(\LogicException::class, $e->getPrevious()::class);
      $this->assertSame('Stored hash key deleted.', $e->getMessage());
    }
  }

  /**
   * Tests validation when the staged and active lock files are identical.
   */
  public function testApplyWithNoChange(): void {
    // Leave the staged lock file alone.
    NoOpStager::setLockFileShouldChange(FALSE);

    $result = ValidationResult::createError([
      $this->t('There appear to be no pending Composer operations because the active lock file (<PROJECT_ROOT>/composer.lock) and the staged lock file (<STAGE_DIR>/composer.lock) are identical.'),
    ], $this->t('Problem detected in lock file during stage operations.'));
    $stage = $this->assertResults([$result], PreApplyEvent::class);
    // A status check shouldn't produce raise any errors, because it's only
    // during pre-apply that we care if there are any pending Composer
    // operations.
    $this->assertStatusCheckResults([], $stage);
  }

  /**
   * Tests StatusCheckEvent when the stage is available.
   */
  public function testStatusCheckAvailableStage():void {
    $this->assertStatusCheckResults([]);
  }

  /**
   * Data provider for test methods that validate the stage directory.
   *
   * @return string[][]
   *   The test cases.
   */
  public static function providerValidateStageEvents(): array {
    return [
      'pre-require' => [
        PreRequireEvent::class,
      ],
      'pre-apply' => [
        PreApplyEvent::class,
      ],
    ];
  }

}
