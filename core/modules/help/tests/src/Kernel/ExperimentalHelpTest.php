<?php

declare(strict_types=1);

namespace Drupal\Tests\help\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\HttpKernelUiHelperTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\UserInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Verifies help for experimental modules.
 */
#[Group('help')]
#[RunTestsInSeparateProcesses]
class ExperimentalHelpTest extends KernelTestBase {

  use HttpKernelUiHelperTrait;
  use UserCreationTrait;

  /**
   * Modules to install.
   *
   * The experimental_module_test module implements hook_help() and is in the
   * Core (Experimental) package.
   *
   * @var array
   */
  protected static $modules = [
    'help',
    'experimental_module_test',
    'help_page_test',
    'user',
    'system',
  ];

  /**
   * The admin user.
   */
  protected UserInterface $adminUser;

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
   * Verifies that a warning message is displayed for experimental modules.
   */
  public function testExperimentalHelp(): void {
    $this->drupalGet('admin/help/experimental_module_test');
    $this->assertSession()->statusMessageContains('This module is experimental.', 'warning');

    // Regular modules should not display the message.
    $this->drupalGet('admin/help/help_page_test');
    $this->assertSession()->statusMessageNotContains('This module is experimental.');

    // Ensure the actual help page is displayed to avoid a false positive.
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('online documentation for the Help Page Test module');
  }

}
