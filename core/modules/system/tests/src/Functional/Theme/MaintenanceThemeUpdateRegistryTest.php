<?php

namespace Drupal\Tests\system\Functional\Theme;

use Drupal\Core\Serialization\Yaml;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\RequirementsPageTrait;

/**
 * Ensures theme update functions are registered for maintenance themes.
 *
 * @group Theme
 */
class MaintenanceThemeUpdateRegistryTest extends BrowserTestBase {
  use RequirementsPageTrait;

  /**
   * {@inheritdoc}
   */
  protected $profile = 'theme_test_profile';

  /**
   * {@inheritdoc}
   */
  protected function prepareSettings() {
    parent::prepareSettings();
    $this->writeSettings([
      'settings' => [
        'maintenance_theme' => (object) [
          'value' => 'test_theme_updates',
          'required' => TRUE,
        ],
      ],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEnvironment() {
    parent::prepareEnvironment();
    $info = [
      'type' => 'profile',
      'core_version_requirement' => '*',
      'name' => 'Theme test profile',
      'themes' => [
        'test_theme_updates',
      ],
    ];
    // Create an install profile that uses the test theme.
    $path = $this->siteDirectory . '/profiles/theme_test_profile';
    mkdir($path, 0777, TRUE);
    file_put_contents("$path/theme_test_profile.info.yml", Yaml::encode($info));

    // Create a system.theme.yml file for the profile so the test theme is used.
    $path = $this->siteDirectory . '/profiles/theme_test_profile/config/install';
    mkdir($path, 0777, TRUE);
    $theme_config = Yaml::decode(file_get_contents(\Drupal::moduleHandler()->getModule('system')->getPath() . '/config/install/system.theme.yml'));
    $theme_config['default'] = 'test_theme_updates';
    file_put_contents("$path/system.theme.yml", Yaml::encode($theme_config));
  }

  /**
   * Tests that after installing the profile there are no outstanding updates.
   */
  public function testMaintenanceThemeUpdateRegistration() {
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('update.php/selection');
    $this->updateRequirementsProblem();
    $this->drupalGet('update.php/selection');
    $this->assertSession()->pageTextContains('No pending updates.');
  }

}
