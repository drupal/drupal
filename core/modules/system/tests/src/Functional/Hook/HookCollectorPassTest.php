<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Hook;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests services in .module files.
 */
#[Group('Hook')]
#[RunTestsInSeparateProcesses]
class HookCollectorPassTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['container_initialize'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests installing a module with a Drupal container call outside functions.
   *
   * If this is removed then it needs to be moved to a test that installs modules through
   * admin/modules.
   */
  public function testContainerOutsideFunction(): void {
    $settings['settings']['rebuild_access'] = (object) [
      'value' => TRUE,
      'required' => TRUE,
    ];

    // This simulates installing the module and running a cache rebuild in a
    // separate request.
    $this->writeSettings($settings);
    $this->rebuildAll();
    $this->drupalGet(Url::fromUri('base:core/rebuild.php'));
    $this->assertSession()->pageTextNotContains('ContainerNotInitializedException');
    // Successful response from rebuild.php should redirect to the front page.
    $this->assertSession()->addressEquals('/');

    // If this file is removed then this test needs to be updated to trigger
    // the container rebuild error from https://www.drupal.org/i/3505049
    $config_module_file = $this->root . '/core/modules/system/tests/modules/container_initialize/container_initialize.module';
    $this->assertFileExists($config_module_file, 'This test depends on a container call in a .module file');
    // Confirm that the file still has a bare container call.
    $bare_container = "declare(strict_types=1);

\Drupal::getContainer()->getParameter('site.path');
";
    $file_content = file_get_contents($config_module_file);
    $this->assertStringContainsString($bare_container, $file_content, 'container_initialize.module container test feature is missing.');
  }

}
