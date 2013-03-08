<?php

/**
 * @file
 * Contains \Drupal\config\Tests\ConfigLocaleOverride.
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

  /**
   * Tests basic locale override.
   */
  function testConfigLocaleOverride() {
    $name = 'config_test.system';
    // The default language is en so the config key should be localised.
    $config = config($name);
    $this->assertIdentical($config->get('foo'), 'en bar');
    $this->assertIdentical($config->get('404'), 'herp');

    // Ensure that we get the expected value when we avoid overrides.
    config_context_enter('config.context.free');
    $config_admin = config($name);
    $this->assertIdentical($config_admin->get('foo'), 'bar');
    $this->assertIdentical($config_admin->get('404'), 'herp');

    // Leave the non override context.
    config_context_leave();
    $config = config($name);
    $this->assertIdentical($config->get('foo'), 'en bar');
    $this->assertIdentical($config->get('404'), 'herp');
  }

  /**
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

    $user_config_context = config_context_enter('Drupal\user\UserConfigContext');
    $user_config_context->setAccount($account);
    $config = config('config_test.system');
    $this->assertIdentical($config->get('foo'), 'fr bar');
    // Ensure the non-overriden value is still the same.
    $this->assertIdentical($config->get('404'), 'herp');

    // Ensure that we get the expected value when we leave the user context. The
    // locale overrides contain an English override too, so although we are not
    // in a user based language override context, the English language override
    // applies due to the negotiated language for the page.
    config_context_leave();
    $config = config('config_test.system');
    $this->assertIdentical($config->get('foo'), 'en bar');

    $account = entity_create('user', array(
      'name' => 'German user',
      'mail' => 'test@example.com',
      'created' => REQUEST_TIME,
      'status' => 1,
      'preferred_langcode' => 'de',
    ));

    $config_factory = drupal_container()->get('config.factory');
    $config_factory->enterContext($user_config_context->setAccount($account));
    // Should not have to re-initialize the configuration object to get new
    // overrides as the new context will have a different uuid.
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
    $en_user_config_context = config_context_enter('Drupal\user\UserConfigContext');
    $en_user_config_context->setAccount($account);
    $config = config('config_test.system');
    $this->assertIdentical($config->get('foo'), 'en bar');

    // Ensure that we get the expected value when we leave the english user
    // context.
    config_context_leave();
    $config = config('config_test.system');
    $this->assertIdentical($config->get('foo'), 'de bar');

    // Ensure that we get the expected value when we leave the german user
    // context.
    config_context_leave();
    $config = config('config_test.system');
    $this->assertIdentical($config->get('foo'), 'en bar');

    // Ensure that we cannot leave the default context.
    config_context_leave();
    $config = config('config_test.system');
    $this->assertIdentical($config->get('foo'), 'en bar');
  }

  /**
   * Tests locale override in combination with global overrides.
   */
  function testConfigLocaleUserAndGlobalOverride() {
    global $conf;

    // Globally override value for the keys in config_test.system. Although we
    // override the foo key, there are also language overrides, which trump
    // global overrides so the 'foo' key override will never surface.
    $conf['config_test.system']['foo'] = 'global bar';
    $conf['config_test.system']['404'] = 'global herp';

    $this->installSchema('system', 'variable');
    $this->installSchema('language', 'language');
    language_save(new Language(array(
      'name' => 'French',
      'langcode' => 'fr',
    )));

    $this->installSchema('user', 'users');
    $account = entity_create('user', array(
      'name' => 'French user',
      'mail' => 'test@example.com',
      'created' => REQUEST_TIME,
      'status' => 1,
      'preferred_langcode' => 'fr',
    ));

    $user_config_context = config_context_enter('Drupal\user\UserConfigContext');
    $user_config_context->setAccount($account);
    $config = config('config_test.system');
    $this->assertIdentical($config->get('foo'), 'fr bar');
    // Ensure the value overriden from global $conf works.
    $this->assertIdentical($config->get('404'), 'global herp');

    // Ensure that we get the expected value when we leave the user context. The
    // locale overrides contain an English override too, so although we are not
    // in a user based language override context, the English language override
    // applies due to the negotiated language for the page.
    config_context_leave();
    $config = config('config_test.system');
    $this->assertIdentical($config->get('foo'), 'en bar');
    // Global override should still apply.
    $this->assertIdentical($config->get('404'), 'global herp');

    // Ensure that we cannot leave the default context.
    config_context_leave();
    $config = config('config_test.system');
    $this->assertIdentical($config->get('foo'), 'en bar');
    // Global override should still apply.
    $this->assertIdentical($config->get('404'), 'global herp');

    // Ensure that we get the expected value when we avoid overrides.
    config_context_enter('config.context.free');
    $config_admin = config('config_test.system');
    // Language override should not apply anymore.
    $this->assertIdentical($config_admin->get('foo'), 'bar');
    // Global override should not apply.
    $this->assertIdentical($config_admin->get('404'), 'herp');
    config_context_leave();
  }

  /**
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
