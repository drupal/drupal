<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Kernel;

use ColinODell\PsrTestLogger\TestLogger;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\fixture_manipulator\ActiveFixtureManipulator;
use Psr\Log\LogLevel;

/**
 * @covers \Drupal\package_manager\EventSubscriber\ChangeLogger
 * @group package_manager
 */
class ChangeLoggerTest extends PackageManagerKernelTestBase {

  /**
   * The logger to which change lists will be written.
   *
   * @var \ColinODell\PsrTestLogger\TestLogger
   */
  private TestLogger $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->logger = new TestLogger();
    parent::setUp();
  }

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    parent::register($container);
    $container->set('logger.channel.package_manager_change_log', $this->logger);
  }

  /**
   * Tests that the requested and applied changes are logged.
   */
  public function testChangeLogging(): void {
    $this->setReleaseMetadata([
      'semver_test' => __DIR__ . '/../../fixtures/release-history/semver_test.1.1.xml',
    ]);

    (new ActiveFixtureManipulator())
      ->addPackage([
        'name' => 'package/removed',
        'type' => 'library',
      ])
      ->commitChanges();

    $this->getStageFixtureManipulator()
      ->setCorePackageVersion('9.8.1')
      ->addPackage([
        'name' => 'drupal/semver_test',
        'type' => 'drupal-module',
        'version' => '8.1.1',
      ])
      ->removePackage('package/removed');

    $stage = $this->createStage();
    $stage->create();
    $stage->require([
      'drupal/semver_test:*',
      'drupal/core-recommended:^9.8.1',
    ]);
    // Nothing should be logged until post-apply.
    $stage->apply();
    $this->assertEmpty($this->logger->records);
    $stage->postApply();

    $this->assertTrue($this->logger->hasInfoRecords());
    $records = $this->logger->recordsByLevel[LogLevel::INFO];
    $this->assertCount(2, $records);
    // The first record should be of the requested changes.
    $expected_message = <<<END
Requested changes:
- Update drupal/core-recommended from 9.8.0 to ^9.8.1
- Install drupal/semver_test * (any version)
END;
    $this->assertSame($expected_message, (string) $records[0]['message']);

    // The second record should be of the actual changes.
    $expected_message = <<<END
Applied changes:
- Updated drupal/core from 9.8.0 to 9.8.1
- Updated drupal/core-dev from 9.8.0 to 9.8.1
- Updated drupal/core-recommended from 9.8.0 to 9.8.1
- Installed drupal/semver_test 8.1.1
- Uninstalled package/removed
END;
    $this->assertSame($expected_message, (string) $records[1]['message']);
  }

}
