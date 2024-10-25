<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Kernel;

use ColinODell\PsrTestLogger\TestLogger;
use Drupal\Component\FileSystem\FileSystem as DrupalFileSystem;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Site\Settings;
use Drupal\fixture_manipulator\StageFixtureManipulator;
use Drupal\KernelTests\KernelTestBase;
use Drupal\package_manager\Event\PreApplyEvent;
use Drupal\package_manager\Event\PreOperationStageEvent;
use Drupal\package_manager\Exception\StageEventException;
use Drupal\package_manager\FailureMarker;
use Drupal\package_manager\PathLocator;
use Drupal\package_manager\StatusCheckTrait;
use Drupal\package_manager\Validator\DiskSpaceValidator;
use Drupal\package_manager\StageBase;
use Drupal\Tests\package_manager\Traits\AssertPreconditionsTrait;
use Drupal\Tests\package_manager\Traits\ComposerStagerTestTrait;
use Drupal\Tests\package_manager\Traits\FixtureManipulatorTrait;
use Drupal\Tests\package_manager\Traits\FixtureUtilityTrait;
use Drupal\Tests\package_manager\Traits\ValidationTestTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use PhpTuf\ComposerStager\API\Core\BeginnerInterface;
use PhpTuf\ComposerStager\API\Core\CommitterInterface;
use PhpTuf\ComposerStager\API\Core\StagerInterface;
use PhpTuf\ComposerStager\API\Path\Factory\PathFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Base class for kernel tests of Package Manager's functionality.
 *
 * @internal
 */
abstract class PackageManagerKernelTestBase extends KernelTestBase {

  use AssertPreconditionsTrait;
  use ComposerStagerTestTrait;
  use FixtureManipulatorTrait;
  use FixtureUtilityTrait;
  use StatusCheckTrait;
  use ValidationTestTrait;

  /**
   * The mocked HTTP client that returns metadata about available updates.
   *
   * We need to preserve this as a class property so that we can re-inject it
   * into the container when a rebuild is triggered by module installation.
   *
   * @var \GuzzleHttp\Client
   *
   * @see ::register()
   */
  private $client;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'fixture_manipulator',
    'package_manager',
    'package_manager_bypass',
    'system',
    'update',
    'update_test',
  ];

  /**
   * The service IDs of any validators to disable.
   *
   * @var string[]
   */
  protected $disableValidators = [];

  /**
   * The test root directory, if any, created by ::createTestProject().
   *
   * @var string|null
   *
   * @see ::createTestProject()
   * @see ::tearDown()
   */
  protected ?string $testProjectRoot = NULL;

  /**
   * The Symfony filesystem class.
   *
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  private Filesystem $fileSystem;

  /**
   * A logger that will fail the test if Package Manager logs any errors.
   *
   * @var \ColinODell\PsrTestLogger\TestLogger
   *
   * @see ::tearDown()
   */
  protected TestLogger $failureLogger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig('package_manager');

    $this->fileSystem = new Filesystem();
    $this->createTestProject();

    // The Update module's default configuration must be installed for our
    // fake release metadata to be fetched, and the System module's to ensure
    // the site has a name.
    $this->installConfig(['system', 'update']);

    // Make the update system think that all of System's post-update functions
    // have run.
    $this->registerPostUpdateFunctions();

    // Ensure we can fail the test if any warnings, or worse, are logged by
    // Package Manager.
    // @see ::tearDown()
    $this->failureLogger = new TestLogger();
    $this->container->get('logger.channel.package_manager')
      ->addLogger($this->failureLogger);
  }

  /**
   * {@inheritdoc}
   */
  protected function enableModules(array $modules): void {
    parent::enableModules($modules);
    $this->registerPostUpdateFunctions();
  }

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    parent::register($container);

    // If we previously set up a mock HTTP client in ::setReleaseMetadata(),
    // re-inject it into the container.
    if ($this->client) {
      $container->set('http_client', $this->client);
    }

    // When the test project is used, the disk space validator is replaced with
    // a mock. When staged changes are applied, the container is rebuilt, which
    // destroys the mocked service and can cause unexpected side effects. The
    // 'persist' tag prevents the mock from being destroyed during a container
    // rebuild.
    // @see ::createTestProject()
    $container->getDefinition(DiskSpaceValidator::class)->addTag('persist');

    // Ensure that our failure logger will survive container rebuilds.
    $container->getDefinition('logger.channel.package_manager')
      ->addTag('persist');

    array_walk($this->disableValidators, $container->removeDefinition(...));
  }

  /**
   * Creates a stage object for testing purposes.
   *
   * @return \Drupal\Tests\package_manager\Kernel\TestStage
   *   A stage object, with test-only modifications.
   */
  protected function createStage(): TestStage {
    return new TestStage(
      $this->container->get(PathLocator::class),
      $this->container->get(BeginnerInterface::class),
      $this->container->get(StagerInterface::class),
      $this->container->get(CommitterInterface::class),
      $this->container->get(QueueFactory::class),
      $this->container->get('event_dispatcher'),
      $this->container->get('tempstore.shared'),
      $this->container->get('datetime.time'),
      $this->container->get(PathFactoryInterface::class),
      $this->container->get(FailureMarker::class)
    );
  }

  /**
   * Asserts validation results are returned from a stage life cycle event.
   *
   * @param \Drupal\package_manager\ValidationResult[] $expected_results
   *   The expected validation results.
   * @param string|null $event_class
   *   (optional) The class of the event which should return the results. Must
   *   be passed if $expected_results is not empty.
   *
   * @return \Drupal\package_manager\StageBase
   *   The stage that was used to collect the validation results.
   */
  protected function assertResults(array $expected_results, ?string $event_class = NULL): StageBase {
    $stage = $this->createStage();

    try {
      $stage->create();
      $stage->require(['drupal/core:9.8.1']);
      $stage->apply();
      $stage->postApply();
      $stage->destroy();

      // If we did not get an exception, ensure we didn't expect any results.
      $this->assertValidationResultsEqual([], $expected_results);
    }
    catch (StageEventException $e) {
      $this->assertNotEmpty($expected_results);
      $this->assertInstanceOf($event_class, $e->event);
      $this->assertExpectedResultsFromException($expected_results, $e);
    }
    return $stage;
  }

  /**
   * Asserts validation results are returned from the status check event.
   *
   * @param \Drupal\package_manager\ValidationResult[] $expected_results
   *   The expected validation results.
   * @param \Drupal\Tests\package_manager\Kernel\TestStage|null $stage
   *   (optional) The test stage to use to create the status check event. If
   *   none is provided a new stage will be created.
   */
  protected function assertStatusCheckResults(array $expected_results, ?StageBase $stage = NULL): void {
    $actual_results = $this->runStatusCheck($stage ?? $this->createStage(), $this->container->get('event_dispatcher'));
    $this->assertValidationResultsEqual($expected_results, $actual_results);
  }

  /**
   * Marks all pending post-update functions as completed.
   *
   * Since kernel tests don't normally install modules and register their
   * updates, this method makes sure that we are testing from a clean, fully
   * up-to-date state.
   */
  protected function registerPostUpdateFunctions(): void {
    static $updates = [];
    $updates = array_merge($updates, $this->container->get('update.post_update_registry')
      ->getPendingUpdateFunctions());

    $this->container->get('keyvalue')
      ->get('post_update')
      ->set('existing_updates', $updates);
  }

  /**
   * Creates a test project.
   *
   * This will create a temporary uniques root directory and then creates two
   * directories in it:
   * 'active', which is the active directory containing a fake Drupal code base,
   * and 'stage', which is the root directory used to stage changes. The path
   * locator service will also be mocked so that it points to the test project.
   *
   * @param string|null $source_dir
   *   (optional) The path of a directory which should be copied into the
   *   test project and used as the active directory.
   */
  protected function createTestProject(?string $source_dir = NULL): void {
    static $called;
    if (isset($called)) {
      throw new \LogicException('Only one test project should be created per kernel test method!');
    }
    else {
      $called = TRUE;
    }

    $this->testProjectRoot = DrupalFileSystem::getOsTemporaryDirectory() . DIRECTORY_SEPARATOR . 'package_manager_testing_root' . $this->databasePrefix;
    if (is_dir($this->testProjectRoot)) {
      $this->fileSystem->remove($this->testProjectRoot);
    }
    $this->fileSystem->mkdir($this->testProjectRoot);

    // Create the active directory and copy its contents from a fixture.
    $active_dir = $this->testProjectRoot . DIRECTORY_SEPARATOR . 'active';
    $this->assertTrue(mkdir($active_dir));
    static::copyFixtureFilesTo($source_dir ?? __DIR__ . '/../../fixtures/fake_site', $active_dir);

    // Removing 'vfs://root/' from site path set in
    // \Drupal\KernelTests\KernelTestBase::setUpFilesystem as we don't use vfs.
    $test_site_path = str_replace('vfs://root/', '', $this->siteDirectory);

    // Copy directory structure from vfs site directory to our site directory.
    $this->fileSystem->mirror($this->siteDirectory, $active_dir . DIRECTORY_SEPARATOR . $test_site_path);

    // Override siteDirectory to point to root/active/... instead of root/... .
    $this->siteDirectory = $active_dir . DIRECTORY_SEPARATOR . $test_site_path;

    // Override KernelTestBase::setUpFilesystem's Settings object.
    $settings = Settings::getInstance() ? Settings::getAll() : [];
    $settings['file_public_path'] = $this->siteDirectory . '/files';
    $settings['config_sync_directory'] = $this->siteDirectory . '/files/config/sync';
    new Settings($settings);

    // Create a stage root directory alongside the active directory.
    $staging_root = $this->testProjectRoot . DIRECTORY_SEPARATOR . 'stage';
    $this->assertTrue(mkdir($staging_root));

    // Ensure the path locator points to the test project. We assume that is its
    // own web root and the vendor directory is at its top level.
    /** @var \Drupal\package_manager_bypass\MockPathLocator $path_locator */
    $path_locator = $this->container->get(PathLocator::class);
    $path_locator->setPaths($active_dir, $active_dir . '/vendor', '', $staging_root);

    // This validator will persist through container rebuilds.
    // @see ::register()
    $validator = new TestDiskSpaceValidator($path_locator);
    // By default, the validator should report that the root, vendor, and
    // temporary directories have basically infinite free space.
    $validator->freeSpace = [
      $path_locator->getProjectRoot() => PHP_INT_MAX,
      $path_locator->getVendorDirectory() => PHP_INT_MAX,
      $validator->temporaryDirectory() => PHP_INT_MAX,
    ];
    $this->container->set(DiskSpaceValidator::class, $validator);
  }

  /**
   * Sets the current (running) version of core, as known to the Update module.
   *
   * @todo Remove this function with use of the trait from the Update module in
   *   https://drupal.org/i/3348234.
   *
   * @param string $version
   *   The current version of core.
   */
  protected function setCoreVersion(string $version): void {
    $this->config('update_test.settings')
      ->set('system_info.#all.version', $version)
      ->save();
  }

  /**
   * Sets the release metadata file to use when fetching available updates.
   *
   * @param string[] $files
   *   The paths of the XML metadata files to use, keyed by project name.
   */
  protected function setReleaseMetadata(array $files): void {
    $responses = [];

    foreach ($files as $project => $file) {
      $metadata = Utils::tryFopen($file, 'r');
      $responses["/release-history/$project/current"] = new Response(200, [], Utils::streamFor($metadata));
    }
    $callable = function (RequestInterface $request) use ($responses): Response {
      return $responses[$request->getUri()->getPath()] ?? new Response(404);
    };

    // The mock handler's queue consist of same callable as many times as the
    // number of requests we expect to be made for update XML because it will
    // retrieve one item off the queue for each request.
    // @see \GuzzleHttp\Handler\MockHandler::__invoke()
    $handler = new MockHandler(array_fill(0, 100, $callable));
    $this->client = new Client([
      'handler' => HandlerStack::create($handler),
    ]);
    $this->container->set('http_client', $this->client);
  }

  /**
   * Adds an event listener on an event for testing purposes.
   *
   * @param callable $listener
   *   The listener to add.
   * @param string $event_class
   *   (optional) The event to listen to. Defaults to PreApplyEvent.
   * @param int $priority
   *   (optional) The priority. Defaults to PHP_INT_MAX.
   */
  protected function addEventTestListener(callable $listener, string $event_class = PreApplyEvent::class, int $priority = PHP_INT_MAX): void {
    $this->container->get('event_dispatcher')
      ->addListener($event_class, $listener, $priority);
  }

  /**
   * Asserts event propagation is stopped by a certain event subscriber.
   *
   * @param string $event_class
   *   The event during which propagation is expected to stop.
   * @param callable $expected_propagation_stopper
   *   The event subscriber (which subscribes to the given event class) which is
   *   expected to stop propagation. This event subscriber must have been
   *   registered by one of the installed Drupal module.
   */
  protected function assertEventPropagationStopped(string $event_class, callable $expected_propagation_stopper): void {
    $priority = $this->container->get('event_dispatcher')->getListenerPriority($event_class, $expected_propagation_stopper);
    // Ensure the event subscriber was actually a listener for the event.
    $this->assertIsInt($priority);
    // Add a listener with a priority that is 1 less than priority of the
    // event subscriber. This listener would be called after
    // $expected_propagation_stopper if the event propagation was not stopped
    // and cause the test to fail.
    $this->addEventTestListener(function () use ($event_class): void {
      $this->fail('Event propagation should have been stopped during ' . $event_class . '.');
    }, $event_class, $priority - 1);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    // Delete the test project root, which contains the active directory and
    // the stage directory. First, make it writable in case any permissions were
    // changed during the test.
    if ($this->testProjectRoot) {
      $this->fileSystem->chmod($this->testProjectRoot, 0777, 0000, TRUE);
      $this->fileSystem->remove($this->testProjectRoot);
    }

    StageFixtureManipulator::handleTearDown();

    // Ensure no warnings (or worse) were logged by Package Manager.
    $this->assertFalse($this->failureLogger->hasRecords(RfcLogLevel::EMERGENCY), 'Package Manager logged emergencies.');
    $this->assertFalse($this->failureLogger->hasRecords(RfcLogLevel::ALERT), 'Package Manager logged alerts.');
    $this->assertFalse($this->failureLogger->hasRecords(RfcLogLevel::CRITICAL), 'Package Manager logged critical errors.');
    $this->assertFalse($this->failureLogger->hasRecords(RfcLogLevel::ERROR), 'Package Manager logged errors.');
    $this->assertFalse($this->failureLogger->hasRecords(RfcLogLevel::WARNING), 'Package Manager logged warnings.');
    parent::tearDown();
  }

  /**
   * Asserts that a StageEventException has a particular set of results.
   *
   * @param array $expected_results
   *   The expected results.
   * @param \Drupal\package_manager\Exception\StageEventException $exception
   *   The exception.
   */
  protected function assertExpectedResultsFromException(array $expected_results, StageEventException $exception): void {
    $event = $exception->event;
    $this->assertInstanceOf(PreOperationStageEvent::class, $event);

    $stage = $event->stage;
    $stage_dir = $stage->stageDirectoryExists() ? $stage->getStageDirectory() : NULL;
    $this->assertValidationResultsEqual($expected_results, $event->getResults(), NULL, $stage_dir);
  }

}

/**
 * Defines a stage specifically for testing purposes.
 */
class TestStage extends StageBase {

  /**
   * {@inheritdoc}
   */
  protected string $type = 'package_manager:test';

  /**
   * Implements the magic __sleep() method.
   *
   * TRICKY: without this, any failed ::assertStatusCheckResults()
   * will fail, because PHPUnit will want to serialize all arguments in the call
   * stack.
   *
   * @see https://www.drupal.org/project/auto_updates/issues/3312619#comment-14801308
   */
  public function __sleep(): array {
    return [];
  }

}

/**
 * A test version of the disk space validator to bypass system-level functions.
 */
class TestDiskSpaceValidator extends DiskSpaceValidator {

  /**
   * Whether the root and vendor directories are on the same logical disk.
   *
   * @var bool
   */
  public $sharedDisk = TRUE;

  /**
   * The amount of free space, keyed by path.
   *
   * @var float[]
   */
  public $freeSpace = [];

  /**
   * {@inheritdoc}
   */
  protected function stat(string $path): array {
    return [
      'dev' => $this->sharedDisk ? 'disk' : uniqid(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function freeSpace(string $path): float {
    return $this->freeSpace[$path];
  }

  /**
   * {@inheritdoc}
   */
  public function temporaryDirectory(): string {
    return 'temp';
  }

}
