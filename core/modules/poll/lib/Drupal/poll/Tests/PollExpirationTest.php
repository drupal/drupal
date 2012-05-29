<?php

/**
 * @file
 * Definition of Drupal\poll\Tests\PollExpirationTest.
 */

namespace Drupal\poll\Tests;

class PollExpirationTest extends PollTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Poll expiration',
      'description' => 'Test the poll auto-expiration logic.',
      'group' => 'Poll',
    );
  }

  function testAutoExpire() {
    // Set up a poll.
    $title = $this->randomName();
    $choices = $this->_generateChoices(2);
    $poll_nid = $this->pollCreate($title, $choices, FALSE);
    $this->assertTrue($poll_nid, t('Poll for auto-expire test created.'));

    // Visit the poll edit page and verify that by default, expiration
    // is set to unlimited.
    $this->drupalGet("node/$poll_nid/edit");
    $this->assertField('runtime', t('Poll expiration setting found.'));
    $elements = $this->xpath('//select[@id="edit-runtime"]/option[@selected="selected"]');
    $this->assertTrue(isset($elements[0]['value']) && $elements[0]['value'] == 0, t('Poll expiration set to unlimited.'));

    // Set the expiration to one week.
    $edit = array();
    $poll_expiration = 604800; // One week.
    $edit['runtime'] = $poll_expiration;
    $this->drupalPost(NULL, $edit, t('Save'));
    $this->assertRaw(t('Poll %title has been updated.', array('%title' => $title)), t('Poll expiration settings saved.'));

    // Make sure that the changed expiration settings is kept.
    $this->drupalGet("node/$poll_nid/edit");
    $elements = $this->xpath('//select[@id="edit-runtime"]/option[@selected="selected"]');
    $this->assertTrue(isset($elements[0]['value']) && $elements[0]['value'] == $poll_expiration, t('Poll expiration set to unlimited.'));

    // Force a cron run. Since the expiration date has not yet been reached,
    // the poll should remain active.
    drupal_cron_run();
    $this->drupalGet("node/$poll_nid/edit");
    $elements = $this->xpath('//input[@id="edit-active-1"]');
    $this->assertTrue(isset($elements[0]) && !empty($elements[0]['checked']), t('Poll is still active.'));

    // Test expiration. Since REQUEST_TIME is a constant and we don't
    // want to keep SimpleTest waiting until the moment of expiration arrives,
    // we forcibly change the expiration date in the database.
    $created = db_query('SELECT created FROM {node} WHERE nid = :nid', array(':nid' => $poll_nid))->fetchField();
    db_update('node')
      ->fields(array('created' => $created - ($poll_expiration * 1.01)))
      ->condition('nid', $poll_nid)
      ->execute();

    // Run cron and verify that the poll is now marked as "closed".
    drupal_cron_run();
    $this->drupalGet("node/$poll_nid/edit");
    $elements = $this->xpath('//input[@id="edit-active-0"]');
    $this->assertTrue(isset($elements[0]) && !empty($elements[0]['checked']), t('Poll has expired.'));
  }
}
