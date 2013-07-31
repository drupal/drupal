<?php

/**
 * @file
 * Contains \Drupal\Tests\Component\Utility\SettingsTest.php
 */

namespace Drupal\Tests\Component\Utility;

use Drupal\Component\Utility\Settings;
use Drupal\Tests\UnitTestCase;

/**
 * Tests read only settings.
 *
 * @see \Drupal\Component\Utility\Settings
 */
class SettingsTest extends UnitTestCase {

  /**
   * Simple settings array to test against.
   *
   * @var array
   */
  protected $config = array();

  /**
   * The settings object to test.
   *
   * @var \Drupal\Component\Utility\Settings
   */
  protected $settings;

  public static function getInfo() {
    return array(
      'name' => 'Read-only settings test',
      'description' => 'Confirm that \Drupal\Component\Utility\Settings is working.',
      'group' => 'Common',
    );
  }

  /**
   * Setup a basic configuration array.
   */
  public function setUp(){
    $this->config = array(
      'one' => '1',
      'two' => '2',
    );

    $this->settings = new Settings($this->config);
  }

  /**
   * Tests Settings::get().
   */
  public function testGet() {
    // Test stored settings.
    $this->assertEquals($this->config['one'], $this->settings->get('one'), 'The correect setting was not returned.');
    $this->assertEquals($this->config['two'], $this->settings->get('two'), 'The correct setting was not returned.');

    // Test setting that isn't stored with default.
    $this->assertEquals('3', $this->settings->get('three', '3'), 'Default value for a setting not properly returned.');
    $this->assertNull($this->settings->get('four'), 'Non-null value returned for a setting that should not exist.');
  }

  /**
   * Test Settings::getAll().
   */
  public function testGetAll() {
    $this->assertEquals($this->config, $this->settings->getAll());
  }

  /**
   * Tests Settings::getSingleton().
   */
  public function testGetSingleton() {
    $singleton = $this->settings->getSingleton();
    $this->assertEquals($singleton, $this->settings);
  }

}
