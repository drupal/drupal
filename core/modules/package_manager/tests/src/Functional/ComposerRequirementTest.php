<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Functional;

use Drupal\package_manager\ComposerInspector;
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
  protected static $modules = ['package_manager'];

  /**
   * Tests that Composer version and path are listed on the status report.
   */
  public function testComposerInfoShown(): void {
    $config = $this->config('package_manager.settings');

    // Ensure we can locate the Composer executable.
    /** @var \PhpTuf\ComposerStager\API\Finder\Service\ExecutableFinderInterface $executable_finder */
    $executable_finder = $this->container->get(ExecutableFinderInterface::class);
    $composer_path = $executable_finder->find('composer');
    $composer_version = $this->container->get(ComposerInspector::class)->getVersion();

    // With a valid path to Composer, ensure the status report shows its version
    // number and path.
    $config->set('executables.composer', $composer_path)->save();
    $account = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($account);
    $this->drupalGet('/admin/reports/status');
    $assert_session = $this->assertSession();
    $assert_session->pageTextContains('Composer version');
    $assert_session->responseContains("$composer_version (<code>$composer_path</code>)");

    // If the path to Composer is invalid, we should see the error message
    // that gets raised when we try to get its version.
    $config->set('executables.composer', '/path/to/composer')->save();
    $this->getSession()->reload();
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains('Composer was not found. The error message was: Failed to run process: The command "\'/path/to/composer\' \'--format=json\'" failed.');
  }

}
