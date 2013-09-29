<?php

/**
 * @file
 * Tests for devel module.
 */

namespace Drupal\devel\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\devel\DevelMailLog;

/**
 * Test devel mail functionality.
 */
class DevelMailTest extends WebTestBase {
  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('devel');
  protected $profile = 'testing';

  public static function getInfo() {
    return array(
      'name' => 'Devel Mail interface',
      'description' => 'Test sending mails with debug interface',
      'group' => 'Devel',
    );
  }

  function setUp() {
    parent::setUp();
  }

  /**
   * Test mail logging functionality.
   */
  function testMail() {
    $message = array();
    $message['to'] = 'drupal@example.com';
    $message['subject'] = 'Test mail';
    $message['headers'] = array(
      'From' => 'postmaster@example.com',
      'X-stupid' => 'dumb',
    );
    $message['body'] = "I am the body of this message";
    $d = new DevelMailLog();

    $filename = $d->getFileName($message);
    $content = $d->composeMessage($message);
    $expected_filename = $d->getOutputDirectory() . '/drupal@example.com-Test_mail-' . date('y-m-d_his') . '.mail.txt';
    $this->assertEqual($filename, $expected_filename);
    $content = str_replace("\r", '', $content);
    $this->assertEqual($content, 'From: postmaster@example.com
X-stupid: dumb
To: drupal@example.com
Test mail
I am the body of this message');
  }
}
