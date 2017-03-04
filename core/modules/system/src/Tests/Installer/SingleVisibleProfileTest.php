<?php

namespace Drupal\system\Tests\Installer;

use Drupal\Core\Serialization\Yaml;
use Drupal\simpletest\InstallerTestBase;

/**
 * Tests distribution profile support.
 *
 * @group Installer
 */
class SingleVisibleProfileTest extends InstallerTestBase {

  /**
   * The installation profile to install.
   *
   * Not needed when only one is visible.
   *
   * @var string
   */
  protected $profile = NULL;

  /**
   * The install profile info.
   *
   * @var array
   */
  protected $info;

  protected function setUp() {
    $this->info = [
      'type' => 'profile',
      'core' => \Drupal::CORE_COMPATIBILITY,
      'name' => 'Override standard',
      'hidden' => TRUE,
    ];
    // File API functions are not available yet.
    $path = $this->siteDirectory . '/profiles/standard';
    mkdir($path, 0777, TRUE);
    file_put_contents("$path/standard.info.yml", Yaml::encode($this->info));

    parent::setUp();
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpProfile() {
    // This step is skipped, because there is only one visible profile.
  }

  /**
   * Confirms that the installation succeeded.
   */
  public function testInstalled() {
    $this->assertUrl('user/1');
    $this->assertResponse(200);
    // Confirm that we are logged-in after installation.
    $this->assertText($this->rootUser->getUsername());
    // Confirm that the minimal profile was installed.
    $this->assertEqual(drupal_get_profile(), 'minimal');
  }

}
