<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Common\GotoTest.
 */

namespace Drupal\system\Tests\Common;

use Drupal\simpletest\WebTestBase;

/**
 * Tests drupal_goto() and hook_drupal_goto_alter().
 */
class GotoTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('common_test');

  public static function getInfo() {
    return array(
      'name' => 'Redirect functionality',
      'description' => 'Tests the drupal_goto() and hook_drupal_goto_alter() functionality.',
      'group' => 'Common',
    );
  }

  /**
   * Tests drupal_goto().
   */
  function testDrupalGoto() {
    $this->drupalGet('common-test/drupal_goto/redirect');
    $headers = $this->drupalGetHeaders(TRUE);
    list(, $status) = explode(' ', $headers[0][':status'], 3);
    $this->assertEqual($status, 302, 'Expected response code was sent.');
    $this->assertText('drupal_goto', 'Drupal goto redirect succeeded.');
    $this->assertEqual($this->getUrl(), url('common-test/drupal_goto', array('absolute' => TRUE)), 'Drupal goto redirected to expected URL.');

    $this->drupalGet('common-test/drupal_goto/redirect_advanced');
    $headers = $this->drupalGetHeaders(TRUE);
    list(, $status) = explode(' ', $headers[0][':status'], 3);
    $this->assertEqual($status, 301, 'Expected response code was sent.');
    $this->assertText('drupal_goto', 'Drupal goto redirect succeeded.');
    $this->assertEqual($this->getUrl(), url('common-test/drupal_goto', array('query' => array('foo' => '123'), 'absolute' => TRUE)), 'Drupal goto redirected to expected URL.');

    // Test that drupal_goto() respects ?destination=xxx. Use a complicated URL
    // to test that the path is encoded and decoded properly.
    $destination = 'common-test/drupal_goto/destination?foo=%2525&bar=123';
    $this->drupalGet('common-test/drupal_goto/redirect', array('query' => array('destination' => $destination)));
    $this->assertText('drupal_goto', 'Drupal goto redirect with destination succeeded.');
    $this->assertEqual($this->getUrl(), url('common-test/drupal_goto/destination', array('query' => array('foo' => '%25', 'bar' => '123'), 'absolute' => TRUE)), 'Drupal goto redirected to given query string destination.');

    // Test that drupal_goto() respects ?destination=xxx with an absolute URL
    // that points to this Drupal installation.
    $destination = url('common-test/drupal_goto/alt', array('absolute' => TRUE));
    $this->drupalGet('common-test/drupal_goto/redirect', array('query' => array('destination' => $destination)));
    $this->assertText('drupal_goto_alt', 'Drupal goto redirect with absolute URL destination that points to this Drupal installation succeeded.');
    $this->assertEqual($this->getUrl(), url('common-test/drupal_goto/alt', array('absolute' => TRUE)), 'Drupal goto redirected to given query string destination with absolute URL that points to this Drupal installation.');

    // Test that drupal_goto() fails to respect ?destination=xxx with an absolute URL
    // that does not point to this Drupal installation.
    $destination = 'http://pagedoesnotexist';
    $this->drupalGet('common-test/drupal_goto/redirect', array('query' => array('destination' => $destination)));
    $this->assertText('drupal_goto', 'Drupal goto fails to redirect with absolute URL destination that does not point to this Drupal installation.');
    $this->assertNotEqual($this->getUrl(), $destination, 'Drupal goto failed to redirect to given query string destination with absolute URL that does not point to this Drupal installation.');
  }

  /**
   * Tests hook_drupal_goto_alter().
   */
  function testDrupalGotoAlter() {
    $this->drupalGet('common-test/drupal_goto/redirect_fail');

    $this->assertNoText(t("Drupal goto failed to stop program"), 'Drupal goto stopped program.');
    $this->assertNoText('drupal_goto_fail', 'Drupal goto redirect failed.');
  }

  /**
   * Tests drupal_get_destination().
   */
  function testDrupalGetDestination() {
    $query = $this->randomName(10);

    // Verify that a 'destination' query string is used as destination.
    $this->drupalGet('common-test/destination', array('query' => array('destination' => $query)));
    $this->assertText('The destination: ' . $query, 'The given query string destination is determined as destination.');

    // Verify that the current path is used as destination.
    $this->drupalGet('common-test/destination', array('query' => array($query => NULL)));
    $url = 'common-test/destination?' . $query;
    $this->assertText('The destination: ' . $url, 'The current path is determined as destination.');
  }
}
