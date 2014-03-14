<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Installer\DistributionProfileTest.
 */

namespace Drupal\system\Tests\Installer;

use Drupal\simpletest\InstallerTestBase;
use Symfony\Component\Yaml\Yaml;

/**
 * Tests the installer translation detection.
 */
class DistributionProfileTest extends InstallerTestBase {

  /**
   * The distribution profile info.
   *
   * @var array
   */
  protected $info;

  public static function getInfo() {
    return array(
      'name' => 'Distribution installation profile test',
      'description' => 'Tests distribution profile support.',
      'group' => 'Installer',
    );
  }

  protected function setUp() {
    $this->info = array(
      'type' => 'profile',
      'core' => \Drupal::CORE_COMPATIBILITY,
      'name' => 'Distribution profile',
      'distribution' => array(
        'name' => 'My Distribution',
        'install' => array(
          'theme' => 'bartik',
        ),
      ),
    );
    // File API functions are not available yet.
    $path = $this->siteDirectory . '/profiles/mydistro';
    mkdir($path, 0777, TRUE);
    file_put_contents("$path/mydistro.info.yml", Yaml::dump($this->info, PHP_INT_MAX, 2));
    file_put_contents("$path/mydistro.profile", "<?php\n");

    parent::setUp();
  }

  /**
   * Overrides InstallerTest::setUpLanguage().
   */
  protected function setUpLanguage() {
    // Verify that the distribution name appears.
    $this->assertRaw($this->info['distribution']['name']);
    // Verify that the requested theme is used.
    $this->assertRaw($this->info['distribution']['install']['theme']);
    // Verify that the "Choose profile" step does not appear.
    $this->assertNoText('profile');

    parent::setUpLanguage();
  }

  /**
   * Overrides InstallerTest::setUpProfile().
   */
  protected function setUpProfile() {
    // This step is skipped, because there is a distribution profile.
  }

  /**
   * Confirms that the installation succeeded.
   */
  public function testInstalled() {
    $this->assertUrl('user/1');
    $this->assertResponse(200);
    // Confirm that we are logged-in after installation.
    $this->assertText($this->root_user->getUsername());
  }

}
