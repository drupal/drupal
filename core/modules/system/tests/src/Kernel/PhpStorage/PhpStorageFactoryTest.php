<?php

namespace Drupal\Tests\system\Kernel\PhpStorage;

use Drupal\Component\PhpStorage\MTimeProtectedFileStorage;
use Drupal\Core\PhpStorage\PhpStorageFactory;
use Drupal\Core\Site\Settings;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\system\PhpStorage\MockPhpStorage;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the PHP storage factory.
 *
 * @group PhpStorage
 * @see \Drupal\Core\PhpStorage\PhpStorageFactory
 */
class PhpStorageFactoryTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Empty the PHP storage settings, as KernelTestBase sets it by default.
    $settings = Settings::getAll();
    unset($settings['php_storage']);
    new Settings($settings);
  }

  /**
   * Tests the get() method with no settings.
   */
  public function testGetNoSettings() {
    $php = PhpStorageFactory::get('test');
    // This should be the default class used.
    $this->assertTrue($php instanceof MTimeProtectedFileStorage, 'An MTimeProtectedFileStorage instance was returned with no settings.');
  }

  /**
   * Tests the get() method using the 'default' settings.
   */
  public function testGetDefault() {
    $this->setSettings();
    $php = PhpStorageFactory::get('test');
    $this->assertTrue($php instanceof MockPhpStorage, 'A FileReadOnlyStorage instance was returned with default settings.');
  }

  /**
   * Tests the get() method with overridden settings.
   */
  public function testGetOverride() {
    $this->setSettings('test');
    $php = PhpStorageFactory::get('test');
    // The FileReadOnlyStorage should be used from settings.
    $this->assertTrue($php instanceof MockPhpStorage, 'A MockPhpStorage instance was returned from overridden settings.');

    // Test that the name is used for the bin when it is NULL.
    $this->setSettings('test', array('bin' => NULL));
    $php = PhpStorageFactory::get('test');
    $this->assertTrue($php instanceof MockPhpStorage, 'An MockPhpStorage instance was returned from overridden settings.');
    $this->assertSame('test', $php->getConfigurationValue('bin'), 'Name value was used for bin.');

    // Test that a default directory is set if it's empty.
    $this->setSettings('test', array('directory' => NULL));
    $php = PhpStorageFactory::get('test');
    $this->assertTrue($php instanceof MockPhpStorage, 'An MockPhpStorage instance was returned from overridden settings.');
    $this->assertSame(PublicStream::basePath() . '/php', $php->getConfigurationValue('directory'), 'Default file directory was used.');

    // Test that a default storage class is set if it's empty.
    $this->setSettings('test', array('class' => NULL));
    $php = PhpStorageFactory::get('test');
    $this->assertTrue($php instanceof MTimeProtectedFileStorage, 'An MTimeProtectedFileStorage instance was returned from overridden settings with no class.');

    // Test that a default secret is not returned if it's set in the override.
    $this->setSettings('test');
    $php = PhpStorageFactory::get('test');
    $this->assertNotEquals('mock hash salt', $php->getConfigurationValue('secret'), 'The default secret is not used if a secret is set in the overridden settings.');

    // Test that a default secret is set if it's empty.
    $this->setSettings('test', array('secret' => NULL));
    $php = PhpStorageFactory::get('test');
    $this->assertSame('mock hash salt', $php->getConfigurationValue('secret'), 'The default secret is used if one is not set in the overridden settings.');
  }

  /**
   * Sets the Settings() singleton.
   *
   * @param string $name
   *   The storage bin name to set.
   * @param array $configuration
   *   An array of configuration to set. Will be merged with default values.
   */
  protected function setSettings($name = 'default', array $configuration = array()) {
    $settings['php_storage'][$name] = $configuration + array(
      'class' => 'Drupal\system\PhpStorage\MockPhpStorage',
      'directory' => 'tmp://',
      'secret' => $this->randomString(),
      'bin' => 'test',
    );
    $settings['hash_salt'] = 'mock hash salt';
    new Settings($settings);
  }

}
