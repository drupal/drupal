<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Functional;

use Drupal\package_manager\ComposerInspector;
use Drupal\package_manager_test_validation\TestExecutableFinder;
use PhpTuf\ComposerStager\API\Finder\Service\ExecutableFinderInterface;

/**
 * Tests that Package Manager shows the Composer version on the status report.
 *
 * @group package_manager
 * @internal
 */
class ComposerRequirementTest extends PackageManagerTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'package_manager',
    'package_manager_test_validation',
  ];

  /**
   * Tests that Composer version and path are listed on the status report.
   */
  public function testComposerInfoShown(): void {
    // Ensure we can locate the Composer executable.
    /** @var \PhpTuf\ComposerStager\API\Finder\Service\ExecutableFinderInterface $executable_finder */
    $executable_finder = $this->container->get(ExecutableFinderInterface::class);
    $composer_path = $executable_finder->find('composer');
    $composer_version = $this->container->get(ComposerInspector::class)->getVersion();

    // Ensure the status report shows Composer's version and path.
    $account = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($account);
    $this->drupalGet('/admin/reports/status');
    $assert_session = $this->assertSession();
    $assert_session->pageTextContains('Composer version');
    $assert_session->responseContains("$composer_version (<code>$composer_path</code>)");

    // If the Composer can't be found, we should see the error message that gets
    // throw by the executable finder.
    TestExecutableFinder::throwFor('composer');
    $this->getSession()->reload();
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains('Composer was not found. The error message was: composer is not a thing');
  }

}
