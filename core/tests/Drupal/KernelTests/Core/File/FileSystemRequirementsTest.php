<?php

namespace Drupal\KernelTests\Core\File;

use Drupal\KernelTests\KernelTestBase;

/**
 * @group File
 */
class FileSystemRequirementsTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
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
  public function testSettingsExist() {
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
    module_load_install('system');
    return system_requirements('runtime');
  }

}
