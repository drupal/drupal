<?php

namespace Drupal\Tests\update\Functional;

use Drupal\Core\DrupalKernel;

/**
 * Base test class for tests that test project upload functionality.
 */
abstract class UpdateUploaderTestBase extends UpdateTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Change the root path which Update Manager uses to install and update
    // projects to be inside the testing site directory. See
    // \Drupal\update\UpdateRootFactory::get() for equivalent changes to the
    // test child site.
    $request = \Drupal::request();
    $update_root = $this->container->get('update.root') . '/' . DrupalKernel::findSitePath($request);
    $this->container->get('update.root')->set($update_root);

    // Create the directories within the root path within which the Update
    // Manager will install projects.
    foreach (drupal_get_updaters() as $updater_info) {
      $updater = $updater_info['class'];
      $install_directory = $update_root . '/' . $updater::getRootDirectoryRelativePath();
      if (!is_dir($install_directory)) {
        mkdir($install_directory);
      }
    }
  }

}
