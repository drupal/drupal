<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Installer;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the installer calling OOP Tasks and Alters.
 */
#[Group('Installer')]
#[RunTestsInSeparateProcesses]
class TaskAdditionAndAlterInstallerTest extends InstallerTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'testing_install_tasks_interface';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests TaskInterface implementations.
   *
   * Also confirms that if both exist the procedural implementations do not
   * run.
   */
  #[IgnoreDeprecations]
  public function testInstallTasks(): void {
    $this->expectUserDeprecationMessage('Code in testing_install_tasks_interface.profile is deprecated in drupal:11.5.0 and must be converted before drupal:13.0.0. Use classes instead. See https://www.drupal.org/node/3619765');
    $this->assertSame('ran', \Drupal::keyValue('testing_install_tasks_interface')->get('task', 'skipped'));
    $this->assertSame('ran', \Drupal::keyValue('testing_install_tasks_interface')->get('alter', 'skipped'));
    $this->assertNull(\Drupal::keyValue('testing_install_tasks_interface')->get('procedural_task'));
    $this->assertNull(\Drupal::keyValue('testing_install_tasks_interface')->get('procedural_alter'));
  }

}
