<?php

namespace Drupal\FunctionalTests\Installer;

use Drupal\Core\Serialization\Yaml;

/**
 * Tests installing a profile with non-English language and no locale module.
 *
 * @group Installer
 */
class InstallerNonEnglishProfileWithoutLocaleModuleTest extends InstallerTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The testing profile name.
   *
   * @var string
   */
  const PROFILE = 'testing_with_language_without_locale';

  /**
   * {@inheritdoc}
   */
  protected $profile = self::PROFILE;

  /**
   * {@inheritdoc}
   */
  protected function prepareEnvironment() {
    parent::prepareEnvironment();

    // Create a self::PROFILE testing profile that depends on the 'language'
    // module but not on 'locale' module. We set core_version_requirement to '*'
    // for the test so that it does not need to be updated between major
    // versions.
    $profile_info = [
      'type' => 'profile',
      'core_version_requirement' => '*',
      'name' => 'Test with language but without locale',
      'install' => ['language'],
    ];

    // File API functions are not available yet.
    $profile_dir = "{$this->root}/{$this->siteDirectory}/profiles/" . self::PROFILE;
    $profile_config_dir = "$profile_dir/config/install";
    mkdir($profile_config_dir, 0777, TRUE);
    $profile_info_file = $profile_dir . '/' . static::PROFILE . '.info.yml';
    file_put_contents($profile_info_file, Yaml::encode($profile_info));

    // Copy a non-English language config YAML to be installed with the profile.
    copy($this->root . '/core/profiles/testing_multilingual/config/install/language.entity.de.yml', $profile_config_dir . '/language.entity.de.yml');
  }

  /**
   * Tests installing a profile with non-English language and no locale module.
   */
  public function testNonEnglishProfileWithoutLocaleModule() {
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals('user/1');
    // Confirm that we are logged-in after installation.
    $this->assertSession()->pageTextContains($this->rootUser->getAccountName());

    // Verify that the confirmation message appears.
    require_once $this->root . '/core/includes/install.inc';
    $this->assertSession()->pageTextContains('Congratulations, you installed Drupal!');

    $this->assertFalse(\Drupal::service('module_handler')->moduleExists('locale'), 'The Locale module is not installed.');
    $this->assertTrue(\Drupal::service('module_handler')->moduleExists('language'), 'The Language module is installed.');
    $this->assertTrue(\Drupal::languageManager()->isMultilingual(), 'The language manager is multi-lingual.');
  }

}
