<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\File;

use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests File System Requirements.
 */
#[Group('File')]
#[RunTestsInSeparateProcesses]
class FileSystemRequirementsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->setInstallProfile('standard');
  }

  /**
   * Tests if settings are set, there are not warnings.
   */
  public function testSettingsExist(): void {
    $this->setSetting('file_temp_path', $this->randomMachineName());
    $requirements = $this->checkSystemRequirements();
    $this->assertArrayNotHasKey('temp_directory', $requirements);
  }

  /**
   * Checks system runtime requirements.
   *
   * @return array
   *   An array of system requirements.
   */
  protected function checkSystemRequirements() {
    // This loadInclude() is to ensure that the install API is available.
    // Since we're loading an include of type 'install', this will also
    // include core/includes/install.inc for us, which is where
    // drupal_verify_install_file() is currently defined.
    // @todo Remove this once the function lives in a better place.
    // @see https://www.drupal.org/project/drupal/issues/3526388
    $this->container->get('module_handler')->loadInclude('system', 'install');
    return \Drupal::moduleHandler()->invoke('system', 'runtime_requirements');
  }

}
