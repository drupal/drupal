<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Kernel;

use ColinODell\PsrTestLogger\TestLogger;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\package_manager\Event\PostRequireEvent;
use Drupal\package_manager\Event\PreApplyEvent;
use Drupal\package_manager\Event\PreRequireEvent;
use Drupal\package_manager\Event\SandboxEvent;
use Drupal\package_manager\Exception\SandboxEventException;
use Drupal\package_manager\PathLocator;
use Drupal\package_manager\StatusCheckTrait;
use Drupal\package_manager\ValidationResult;
use PhpTuf\ComposerStager\API\Core\BeginnerInterface;
use PhpTuf\ComposerStager\API\Core\CommitterInterface;
use PhpTuf\ComposerStager\API\Path\Factory\PathFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @covers \Drupal\package_manager\EventSubscriber\DirectWriteSubscriber
 * @covers \Drupal\package_manager\SandboxManagerBase::isDirectWrite
 * @covers \Drupal\package_manager\DirectWritePreconditionBypass
 *
 * @group package_manager
 */
class DirectWriteTest extends PackageManagerKernelTestBase implements EventSubscriberInterface {

  use StatusCheckTrait;
  use StringTranslationTrait;

  /**
   * Whether we are in maintenance mode before a require operation.
   *
   * @var bool|null
   *
   * @see ::onPreRequire()
   */
  private ?bool $preRequireMaintenanceMode = NULL;

  /**
   * Whether we are in maintenance mode after a require operation.
   *
   * @var bool|null
   *
   * @see ::onPostRequire()
   */
  private ?bool $postRequireMaintenanceMode = NULL;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      // The pre-require and post-require listeners need to run after
      // \Drupal\package_manager\EventSubscriber\DirectWriteSubscriber.
      PreRequireEvent::class => ['onPreRequire', -10001],
      PostRequireEvent::class => ['onPostRequire', 9999],
      PreApplyEvent::class => 'assertNotDirectWrite',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->container->get(EventDispatcherInterface::class)
      ->addSubscriber($this);
  }

  /**
   * Event listener that asserts the sandbox manager isn't in direct-write mode.
   *
   * @param \Drupal\package_manager\Event\SandboxEvent $event
   *   The event being handled.
   */
  public function assertNotDirectWrite(SandboxEvent $event): void {
    $this->assertFalse($event->sandboxManager->isDirectWrite());
  }

  /**
   * Event listener that records the maintenance mode flag on pre-require.
   */
  public function onPreRequire(): void {
    $this->preRequireMaintenanceMode = (bool) $this->container->get(StateInterface::class)
      ->get('system.maintenance_mode');
  }

  /**
   * Event listener that records the maintenance mode flag on post-require.
   */
  public function onPostRequire(): void {
    $this->postRequireMaintenanceMode = (bool) $this->container->get(StateInterface::class)
      ->get('system.maintenance_mode');
  }

  /**
   * Tests that direct-write does not work if it is globally disabled.
   */
  public function testSiteSandboxedIfDirectWriteGloballyDisabled(): void {
    // Even if we use a sandbox manager that supports direct write, it should
    // not be enabled.
    $sandbox_manager = $this->createStage(TestDirectWriteSandboxManager::class);
    $logger = new TestLogger();
    $sandbox_manager->setLogger($logger);
    $this->assertFalse($sandbox_manager->isDirectWrite());
    $sandbox_manager->create();
    $this->assertTrue($sandbox_manager->sandboxDirectoryExists());
    $this->assertNotSame(
      $this->container->get(PathLocator::class)->getProjectRoot(),
      $sandbox_manager->getSandboxDirectory(),
    );
    $this->assertFalse($logger->hasRecords('info'));
  }

  /**
   * Tests direct-write mode when globally enabled.
   */
  public function testSiteNotSandboxedIfDirectWriteGloballyEnabled(): void {
    $mock_beginner = $this->createMock(BeginnerInterface::class);
    $mock_beginner->expects($this->never())
      ->method('begin')
      ->withAnyParameters();
    $this->container->set(BeginnerInterface::class, $mock_beginner);

    $mock_committer = $this->createMock(CommitterInterface::class);
    $mock_committer->expects($this->never())
      ->method('commit')
      ->withAnyParameters();
    $this->container->set(CommitterInterface::class, $mock_committer);

    $this->setSetting('package_manager_allow_direct_write', TRUE);

    $sandbox_manager = $this->createStage(TestDirectWriteSandboxManager::class);
    $logger = new TestLogger();
    $sandbox_manager->setLogger($logger);
    $this->assertTrue($sandbox_manager->isDirectWrite());

    // A status check should flag a warning about running in direct-write mode.
    $expected_results = [
      ValidationResult::createWarning([
        $this->t('Direct-write mode is enabled, which means that changes will be made without sandboxing them first. This can be risky and is not recommended for production environments. For safety, your site will be put into maintenance mode while dependencies are updated.'),
      ]),
    ];
    $actual_results = $this->runStatusCheck($sandbox_manager);
    $this->assertValidationResultsEqual($expected_results, $actual_results);

    $sandbox_manager->create();
    // In direct-write mode, the active and sandbox directories are the same.
    $this->assertTrue($sandbox_manager->sandboxDirectoryExists());
    $this->assertSame(
      $this->container->get(PathLocator::class)->getProjectRoot(),
      $sandbox_manager->getSandboxDirectory(),
    );

    // Do a require operation so we can assert that we are kicked into, and out
    // of, maintenance mode.
    $sandbox_manager->require(['ext-json:*']);
    $this->assertTrue($this->preRequireMaintenanceMode);
    $this->assertFalse($this->postRequireMaintenanceMode);

    $sandbox_manager->apply();
    $sandbox_manager->postApply();
    // Destroying the sandbox should not populate the clean-up queue.
    $sandbox_manager->destroy();
    /** @var \Drupal\Core\Queue\QueueInterface $queue */
    $queue = $this->container->get(QueueFactory::class)
      ->get('package_manager_cleanup');
    $this->assertSame(0, $queue->numberOfItems());

    $records = $logger->recordsByLevel['info'];
    $this->assertCount(2, $records);
    $this->assertSame('Direct-write is enabled. Skipping sandboxing.', (string) $records[0]['message']);
    $this->assertSame('Direct-write is enabled. Changes have been made to the running code base.', (string) $records[1]['message']);

    // A sandbox manager that doesn't support direct-write should not be
    // influenced by the setting.
    $this->assertFalse($this->createStage()->isDirectWrite());
  }

  /**
   * Tests that pre-require errors prevent maintenance mode during direct-write.
   */
  public function testMaintenanceModeNotEnteredIfErrorOnPreRequire(): void {
    $this->setSetting('package_manager_allow_direct_write', TRUE);
    // Sanity check: we shouldn't be in maintenance mode to begin with.
    $state = $this->container->get(StateInterface::class);
    $this->assertEmpty($state->get('system.maintenance_mode'));

    // Set up an event subscriber which will flag an error.
    $this->container->get(EventDispatcherInterface::class)
      ->addListener(PreRequireEvent::class, function (PreRequireEvent $event): void {
        $event->addError([
          $this->t('Maintenance mode should not happen.'),
        ]);
      });

    $sandbox_manager = $this->createStage(TestDirectWriteSandboxManager::class);
    $sandbox_manager->create();
    try {
      $sandbox_manager->require(['ext-json:*']);
      $this->fail('Expected an exception to be thrown on pre-require.');
    }
    catch (SandboxEventException $e) {
      $this->assertSame("Maintenance mode should not happen.\n", $e->getMessage());
      // We should never have entered maintenance mode.
      $this->assertFalse($this->preRequireMaintenanceMode);
      // Sanity check: the post-require event should never have been dispatched.
      $this->assertNull($this->postRequireMaintenanceMode);
    }
  }

  /**
   * Tests that the sandbox's direct-write status is part of its locking info.
   */
  public function testDirectWriteFlagIsLocked(): void {
    $this->setSetting('package_manager_allow_direct_write', TRUE);
    $sandbox_manager = $this->createStage(TestDirectWriteSandboxManager::class);
    $this->assertTrue($sandbox_manager->isDirectWrite());
    $sandbox_manager->create();
    $this->setSetting('package_manager_allow_direct_write', FALSE);
    $this->assertTrue($sandbox_manager->isDirectWrite());
    // Only once the sandbox is destroyed should the sandbox manager reflect the
    // changed setting.
    $sandbox_manager->destroy();
    $this->assertFalse($sandbox_manager->isDirectWrite());
  }

  /**
   * Tests that direct-write bypasses certain Composer Stager preconditions.
   *
   * @param class-string $service_class
   *   The class name of the precondition service.
   *
   * @testWith ["PhpTuf\\ComposerStager\\API\\Precondition\\Service\\ActiveAndStagingDirsAreDifferentInterface"]
   *   ["PhpTuf\\ComposerStager\\API\\Precondition\\Service\\RsyncIsAvailableInterface"]
   */
  public function testPreconditionBypass(string $service_class): void {
    // Set up conditions where the active and sandbox directories are the same,
    // and the path to rsync isn't valid.
    $path = $this->container->get(PathFactoryInterface::class)
      ->create('/the/absolute/apex');
    $this->config('package_manager.settings')
      ->set('executables.rsync', "C:\Not Rsync.exe")
      ->save();

    /** @var \PhpTuf\ComposerStager\API\Precondition\Service\PreconditionInterface $precondition */
    $precondition = $this->container->get($service_class);
    // The precondition should be unfulfilled.
    $this->assertFalse($precondition->isFulfilled($path, $path));

    // Initializing a sandbox manager with direct-write support should bypass
    // the precondition.
    $this->setSetting('package_manager_allow_direct_write', TRUE);
    $sandbox_manager = $this->createStage(TestDirectWriteSandboxManager::class);
    $sandbox_manager->create();
    $this->assertTrue($sandbox_manager->isDirectWrite());

    // The precondition should be fulfilled, and clear that it's because we're
    // in direct-write mode.
    $this->assertTrue($precondition->isFulfilled($path, $path));
    $this->assertSame('This precondition has been skipped because it is not needed in direct-write mode.', (string) $precondition->getStatusMessage($path, $path));
  }

}
