<?php

declare(strict_types=1);

namespace Drupal\Tests\help\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\HttpKernelUiHelperTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Verify no help is displayed for modules not providing any help.
 */
#[Group('help')]
#[RunTestsInSeparateProcesses]
class NoHelpTest extends KernelTestBase {

  use HttpKernelUiHelperTrait;
  use UserCreationTrait;

  /**
   * Modules to install.
   *
   * Use one of the test modules that do not implement hook_help().
   *
   * @var array
   */
  protected static $modules = ['help', 'menu_test', 'user', 'system'];

  /**
   * The user who will be created.
   *
   * @var \Drupal\user\Entity\User|false
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->adminUser = $this->createUser(['access help pages']);
    $this->setCurrentUser($this->adminUser);
  }

  /**
   * Ensures modules not implementing help do not appear on admin/help.
   */
  public function testMainPageNoHelp(): void {
    $this->drupalGet('admin/help');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Module overviews are provided by modules');
    $this->assertFalse(\Drupal::moduleHandler()->hasImplementations('help', 'menu_test'), 'The menu_test module does not implement hook_help');
    // Make sure the test module menu_test does not display a help link on
    // admin/help.
    $this->assertSession()->pageTextNotContains(\Drupal::service('extension.list.module')->getName('menu_test'));

    // Ensure that the module overview help page for a module that does not
    // implement hook_help() results in a 404.
    $this->drupalGet('admin/help/menu_test');
    $this->assertSession()->statusCodeEquals(404);
  }

}
