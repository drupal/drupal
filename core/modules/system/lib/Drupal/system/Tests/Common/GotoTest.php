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
  public static function getInfo() {
    return array(
      'name' => 'Redirect functionality',
      'description' => 'Tests the drupal_goto() and hook_drupal_goto_alter() functionality.',
      'group' => 'Common',
    );
  }

  function setUp() {
    parent::setUp('common_test');
  }

  /**
   * Test drupal_goto().
   */
  function testDrupalGoto() {
    $this->drupalGet('common-test/drupal_goto/redirect');
    $headers = $this->drupalGetHeaders(TRUE);
    list(, $status) = explode(' ', $headers[0][':status'], 3);
    $this->assertEqual($status, 302, t('Expected response code was sent.'));
    $this->assertText('drupal_goto', t('Drupal goto redirect succeeded.'));
    $this->assertEqual($this->getUrl(), url('common-test/drupal_goto', array('absolute' => TRUE)), t('Drupal goto redirected to expected URL.'));

    $this->drupalGet('common-test/drupal_goto/redirect_advanced');
    $headers = $this->drupalGetHeaders(TRUE);
    list(, $status) = explode(' ', $headers[0][':status'], 3);
    $this->assertEqual($status, 301, t('Expected response code was sent.'));
    $this->assertText('drupal_goto', t('Drupal goto redirect succeeded.'));
    $this->assertEqual($this->getUrl(), url('common-test/drupal_goto', array('query' => array('foo' => '123'), 'absolute' => TRUE)), t('Drupal goto redirected to expected URL.'));

    // Test that drupal_goto() respects ?destination=xxx. Use an complicated URL
    // to test that the path is encoded and decoded properly.
    $destination = 'common-test/drupal_goto/destination?foo=%2525&bar=123';
    $this->drupalGet('common-test/drupal_goto/redirect', array('query' => array('destination' => $destination)));
    $this->assertText('drupal_goto', t('Drupal goto redirect with destination succeeded.'));
    $this->assertEqual($this->getUrl(), url('common-test/drupal_goto/destination', array('query' => array('foo' => '%25', 'bar' => '123'), 'absolute' => TRUE)), t('Drupal goto redirected to given query string destination.'));
  }

  /**
   * Test hook_drupal_goto_alter().
   */
  function testDrupalGotoAlter() {
    $this->drupalGet('common-test/drupal_goto/redirect_fail');

    $this->assertNoText(t("Drupal goto failed to stop program"), t("Drupal goto stopped program."));
    $this->assertNoText('drupal_goto_fail', t("Drupal goto redirect failed."));
  }

  /**
   * Test drupal_get_destination().
   */
  function testDrupalGetDestination() {
    $query = $this->randomName(10);

    // Verify that a 'destination' query string is used as destination.
    $this->drupalGet('common-test/destination', array('query' => array('destination' => $query)));
    $this->assertText('The destination: ' . $query, t('The given query string destination is determined as destination.'));

    // Verify that the current path is used as destination.
    $this->drupalGet('common-test/destination', array('query' => array($query => NULL)));
    $url = 'common-test/destination?' . $query;
    $this->assertText('The destination: ' . $url, t('The current path is determined as destination.'));
  }
}
