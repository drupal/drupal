<?php

/**
 * @file
 * Definition of \Drupal\config\Tests\ConfigLocaleOverride.
 */

namespace Drupal\config\Tests;

use Drupal\Core\Config\ConfigException;
use Drupal\Core\Language\Language;
use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests locale config override.
 */
class ConfigLocaleOverride extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('locale', 'config_test', 'user', 'language', 'system');

  public static function getInfo() {
    return array(
      'name' => 'Locale override',
      'description' => 'Confirm that locale overrides work',
      'group' => 'Configuration',
    );
  }

  public function setUp() {
    parent::setUp();
    config_install_default_config('module', 'config_test');
  }

  /*
   * Tests basic locale override.
   */
  function testConfigLocaleOverride() {
    $name = 'config_test.system';
    // The default language is en so the config key should be localised.
    $config = config($name);
    $this->assertIdentical($config->get('foo'), 'en bar');

    // Ensure that we get the expected value when we use system_config.
    config_context_enter('config.context.free');
    $config_admin = config('config_test.system');
    $this->assertIdentical($config_admin->get('foo'), 'bar');

    // Leave the non override context.
    config_context_leave();
    $config = config($name);
    $this->assertIdentical($config->get('foo'), 'en bar');
  }

  /*
   * Tests locale override based on user's preferred language.
   */
  function testConfigLocaleUserOverride() {
    $this->installSchema('system', 'variable');
    $this->installSchema('language', 'language');
    language_save(new Language(array(
      'name' => 'French',
      'langcode' => 'fr',
    )));
    language_save(new Language(array(
      'name' => 'English',
      'langcode' => 'en',
    )));
    language_save(new Language(array(
      'name' => 'German',
      'langcode' => 'de',
    )));

    $this->installSchema('user', 'users');
    $account = entity_create('user', array(
      'name' => 'French user',
      'mail' => 'test@example.com',
      'created' => REQUEST_TIME,
      'status' => 1,
      'preferred_langcode' => 'fr',
    ));
    $config_factory = drupal_container()->get('config.factory');

    $user_config_context = config_context_enter("Drupal\\user\\UserConfigContext");
    $user_config_context->setAccount($account);
    $config = config('config_test.system');
    $this->assertIdentical($config->get('foo'), 'fr bar');

    // Ensure that we get the expected value when we leave the user context.
    $config_factory->leaveContext();
    $config = config('config_test.system');
    $this->assertIdentical($config->get('foo'), 'en bar');

    $account = entity_create('user', array(
      'name' => 'German user',
      'mail' => 'test@example.com',
      'created' => REQUEST_TIME,
      'status' => 1,
      'preferred_langcode' => 'de',
    ));

    $config_factory->enterContext($user_config_context->setAccount($account));
    // Should not have to re-initialise config object to get new overrides as
    // the new context will have a different uuid.
    $config = config('config_test.system');
    $this->assertIdentical($config->get('foo'), 'de bar');

    // Enter an english context on top of the german context.
    $account = entity_create('user', array(
      'name' => 'English user',
      'mail' => 'test@example.com',
      'created' => REQUEST_TIME,
      'status' => 1,
      'preferred_langcode' => 'en',
    ));
    // Create a new user config context to stack on top of the existign one.
    $en_user_config_context = config_context_enter("Drupal\\user\\UserConfigContext");
    $en_user_config_context->setAccount($account);
    $config = config('config_test.system');
    $this->assertIdentical($config->get('foo'), 'en bar');

    // Ensure that we get the expected value when we leave the english user
    // context.
    $config_factory->leaveContext();
    $config = config('config_test.system');
    $this->assertIdentical($config->get('foo'), 'de bar');

    // Ensure that we get the expected value when we leave the german user
    // context.
    $config_factory->leaveContext();
    $config = config('config_test.system');
    $this->assertIdentical($config->get('foo'), 'en bar');

    // Ensure that we cannot leave the default context.
    $config_factory->leaveContext();
    $config = config('config_test.system');
    $this->assertIdentical($config->get('foo'), 'en bar');
  }

  /*
   * Tests config_context_enter() invalid context name handling.
   */
  function testInvalidContextName() {
    $message = 'Expected ConfigException was thrown for an invalid context_name argument.';
    try {
      config_context_enter('invalid.config.context');
      $this->fail($message);
    }
    catch (ConfigException $e) {
      $this->pass($message);
    }
  }
}
