<?php

declare(strict_types=1);

namespace Drupal\BuildTests\QuickStart;

use Drupal\BuildTests\Framework\BuildTestBase;
use Symfony\Component\Process\PhpExecutableFinder;

/**
 * Helper methods for using the quickstart feature of Drupal.
 */
abstract class QuickStartTestBase extends BuildTestBase {

  /**
   * User name of the admin account generated during install.
   *
   * @var string
   */
  protected $adminUsername;

  /**
   * Password of the admin account generated during install.
   *
   * @var string
   */
  protected $adminPassword;

  /**
   * Install a Drupal site using the quick start feature.
   *
   * @param string $profile
   *   Drupal profile to install.
   * @param string $working_dir
   *   (optional) A working directory relative to the workspace, within which to
   *   execute the command. Defaults to the workspace directory.
   */
  public function installQuickStart($profile, $working_dir = NULL) {
    $php_finder = new PhpExecutableFinder();
    $install_process = $this->executeCommand($php_finder->find() . ' ./core/scripts/drupal install ' . $profile, $working_dir);
    $this->assertCommandOutputContains('Username:');
    preg_match('/Username: (.+)\vPassword: (.+)/', $install_process->getOutput(), $matches);
    $this->assertNotEmpty($this->adminUsername = $matches[1]);
    $this->assertNotEmpty($this->adminPassword = $matches[2]);
  }

  /**
   * Helper that uses Drupal's user/login form to log in.
   *
   * @param string $username
   *   Username.
   * @param string $password
   *   Password.
   * @param string $working_dir
   *   (optional) A working directory within which to login. Defaults to the
   *   workspace directory.
   */
  public function formLogin($username, $password, $working_dir = NULL) {
    $this->visit('/user/login', $working_dir);
    $assert = $this->getMink()->assertSession();
    $assert->statusCodeEquals(200);
    $assert->fieldExists('edit-name')->setValue($username);
    $assert->fieldExists('edit-pass')->setValue($password);
    $session = $this->getMink()->getSession();
    $session->getPage()->findButton('Log in')->submit();
  }

}
