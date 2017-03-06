<?php

namespace Drupal\Tests\config\Functional;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\InstallStorage;
use Drupal\Tests\BrowserTestBase;
use Drupal\Core\Config\FileStorage;
use Drupal\system\Entity\Action;
use Drupal\tour\Entity\Tour;

/**
 * Tests installation and removal of configuration objects in install, disable
 * and uninstall functionality.
 *
 * @group config
 */
class ConfigInstallProfileOverrideTest extends BrowserTestBase {

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'testing_config_overrides';


  /**
   * Tests install profile config changes.
   */
  public function testInstallProfileConfigOverwrite() {
    $config_name = 'system.cron';
    // The expected configuration from the system module.
    $expected_original_data = [
      'threshold' => [
        'requirements_warning' => 172800,
        'requirements_error' => 1209600,
      ],
      'logging' => 1,
    ];
    // The expected active configuration altered by the install profile.
    $expected_profile_data = [
      'threshold' => [
        'requirements_warning' => 259200,
        'requirements_error' => 1209600,
      ],
      'logging' => 1,
    ];
    $expected_profile_data['_core']['default_config_hash'] = Crypt::hashBase64(serialize($expected_profile_data));

    // Verify that the original data matches. We have to read the module config
    // file directly, because the install profile default system.cron.yml
    // configuration file was used to create the active configuration.
    $config_dir = drupal_get_path('module', 'system') . '/' . InstallStorage::CONFIG_INSTALL_DIRECTORY;
    $this->assertTrue(is_dir($config_dir));
    $source_storage = new FileStorage($config_dir);
    $data = $source_storage->read($config_name);
    $this->assertIdentical($data, $expected_original_data);

    // Verify that active configuration matches the expected data, which was
    // created from the testing install profile's system.cron.yml file.
    $config = $this->config($config_name);
    $this->assertIdentical($config->get(), $expected_profile_data);

    // Ensure that the configuration entity has the expected dependencies and
    // overrides.
    $action = Action::load('user_block_user_action');
    $this->assertEqual($action->label(), 'Overridden block the selected user(s)');
    $action = Action::load('user_cancel_user_action');
    $this->assertEqual($action->label(), 'Cancel the selected user account(s)', 'Default configuration that is not overridden is not affected.');

    // Ensure that optional configuration can be overridden.
    $tour = Tour::load('language');
    $this->assertEqual(count($tour->getTips()), 1, 'Optional configuration can be overridden. The language tour only has one tip');
    $tour = Tour::load('language-add');
    $this->assertEqual(count($tour->getTips()), 3, 'Optional configuration that is not overridden is not affected.');

    // Ensure that optional configuration from a profile is created if
    // dependencies are met.
    $this->assertEqual(Tour::load('testing_config_overrides')->label(), 'Config override test');

    // Ensure that optional configuration from a profile is not created if
    // dependencies are not met. Cannot use the entity system since the entity
    // type does not exist.
    $optional_dir = drupal_get_path('module', 'testing_config_overrides') . '/' . InstallStorage::CONFIG_OPTIONAL_DIRECTORY;
    $optional_storage = new FileStorage($optional_dir);
    foreach (['config_test.dynamic.dotted.default', 'config_test.dynamic.override', 'config_test.dynamic.override_unmet'] as $id) {
      $this->assertTrue(\Drupal::config($id)->isNew(), "The config_test entity $id contained in the profile's optional directory does not exist.");
      // Make that we don't get false positives from the assertion above.
      $this->assertTrue($optional_storage->exists($id), "The config_test entity $id does exist in the profile's optional directory.");
    }

    // Install the config_test module and ensure that the override from the
    // install profile is used. Optional configuration can override
    // configuration in a modules config/install directory.
    $this->container->get('module_installer')->install(['config_test']);
    $this->rebuildContainer();
    $config_test_storage = \Drupal::entityManager()->getStorage('config_test');
    $this->assertEqual($config_test_storage->load('dotted.default')->label(), 'Default install profile override', 'The config_test entity is overridden by the profile optional configuration.');
    // Test that override of optional configuration does work.
    $this->assertEqual($config_test_storage->load('override')->label(), 'Override', 'The optional config_test entity is overridden by the profile optional configuration.');
    // Test that override of optional configuration which introduces an unmet
    // dependency does not get created.
    $this->assertNull($config_test_storage->load('override_unmet'), 'The optional config_test entity with unmet dependencies is not created.');
    $this->assertNull($config_test_storage->load('completely_new'), 'The completely new optional config_test entity with unmet dependencies is not created.');

    // Installing dblog creates the optional configuration.
    $this->container->get('module_installer')->install(['dblog']);
    $this->rebuildContainer();
    $this->assertEqual($config_test_storage->load('override_unmet')->label(), 'Override', 'The optional config_test entity is overridden by the profile optional configuration and is installed when its dependencies are met.');
    $this->assertEqual($config_test_storage->load('completely_new')->label(), 'Completely new optional configuration', 'The optional config_test entity is provided by the profile optional configuration and is installed when its dependencies are met.');
  }

}
