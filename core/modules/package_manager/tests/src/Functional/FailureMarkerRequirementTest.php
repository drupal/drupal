<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\package_manager\FailureMarker;
use Drupal\package_manager\PathLocator;
use Drupal\package_manager\StageBase;
use Drupal\Tests\package_manager\Traits\AssertPreconditionsTrait;

/**
 * Tests that Package Manager's requirements check for the failure marker.
 *
 * @group package_manager
 * @internal
 */
class FailureMarkerRequirementTest extends PackageManagerTestBase {
  use StringTranslationTrait;

  use AssertPreconditionsTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'package_manager',
    'package_manager_bypass',
  ];

  /**
   * Tests that error is shown if failure marker already exists.
   */
  public function testFailureMarkerExists(): void {
    $account = $this->drupalCreateUser([
      'administer site configuration',
    ]);
    $this->drupalLogin($account);

    $fake_project_root = $this->root . DIRECTORY_SEPARATOR . $this->publicFilesDirectory;
    $this->container->get(PathLocator::class)
      ->setPaths($fake_project_root, NULL, NULL, NULL);

    $failure_marker = $this->container->get(FailureMarker::class);
    $message = $this->t('Package Manager is here to wreck your day.');
    $stage = new class() extends StageBase {

      public function __construct() {}

      /**
       * {@inheritdoc}
       */
      protected string $type = 'test';
    };
    $failure_marker->write($stage, $message);
    $path = $failure_marker->getPath();
    $this->assertFileExists($path);
    $this->assertStringStartsWith($fake_project_root, $path);

    $this->drupalGet('/admin/reports/status');
    $assert_session = $this->assertSession();
    $assert_session->pageTextContains('Failed Package Manager update detected');
    $assert_session->pageTextContains($message->render());
  }

}
