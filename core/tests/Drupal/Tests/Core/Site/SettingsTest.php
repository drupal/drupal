<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Site\SettingsTest.
 */

namespace Drupal\Tests\Core\Site;

use Drupal\Core\Site\Settings;
use Drupal\Tests\UnitTestCase;

/**
 * Tests read-only settings.
 *
 * @group Drupal
 *
 * @coversDefaultClass \Drupal\Core\Site\Settings
 */
class SettingsTest extends UnitTestCase {

  /**
   * Simple settings array to test against.
   *
   * @var array
   */
  protected $config = array();

  /**
   * The class under test.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected $settings;

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => '\Drupal\Core\Site\Settings unit test',
      'description' => '',
      'group' => 'Common',
    );
  }

  /**
   * @covers ::__construct
   */
  public function setUp(){
    $this->config = array(
      'one' => '1',
      'two' => '2',
      'hash_salt' => $this->randomName(),
    );
    $this->settings = new Settings($this->config);
  }

  /**
   * @covers ::get
   */
  public function testGet() {
    // Test stored settings.
    $this->assertEquals($this->config['one'], Settings::get('one'), 'The correct setting was not returned.');
    $this->assertEquals($this->config['two'], Settings::get('two'), 'The correct setting was not returned.');

    // Test setting that isn't stored with default.
    $this->assertEquals('3', Settings::get('three', '3'), 'Default value for a setting not properly returned.');
    $this->assertNull(Settings::get('four'), 'Non-null value returned for a setting that should not exist.');
  }

  /**
   * @covers ::getAll
   */
  public function testGetAll() {
    $this->assertEquals($this->config, Settings::getAll());
  }

  /**
   * @covers ::getInstance
   */
  public function testGetInstance() {
    $singleton = $this->settings->getInstance();
    $this->assertEquals($singleton, $this->settings);
  }

  /**
   * Tests Settings::getHashSalt();
   *
   * @covers ::getHashSalt
   */
  public function testGetHashSalt() {
    $this->assertSame($this->config['hash_salt'], $this->settings->getHashSalt());
  }

  /**
   * Tests Settings::getHashSalt() with no hash salt value.
   *
   * @covers ::getHashSalt
   *
   * @dataProvider providerTestGetHashSaltEmpty
   *
   * @expectedException \RuntimeException
   */
  public function testGetHashSaltEmpty(array $config) {
    // Re-create settings with no 'hash_salt' key.
    $settings = new Settings($config);
    $settings->getHashSalt();
  }

  /**
   * Data provider for testGetHashSaltEmpty.
   *
   * @return array
   */
  public function providerTestGetHashSaltEmpty() {
   return array(
     array(array()),
     array(array('hash_salt' => '')),
     array(array('hash_salt' => NULL)),
   );
  }

}
