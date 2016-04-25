<?php

namespace Drupal\system\Tests\System;

use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;

/**
 * Configure date and time settings. Test date formatting and time zone
 * handling, including daylight saving time.
 *
 * @group system
 */
class DateTimeTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['block', 'node', 'language'];

  protected function setUp() {
    parent::setUp();

    // Create admin user and log in admin user.
    $this->drupalLogin ($this->drupalCreateUser(array('administer site configuration')));
    $this->drupalPlaceBlock('local_actions_block');
  }

  /**
   * Test time zones and DST handling.
   */
  function testTimeZoneHandling() {
    // Setup date/time settings for Honolulu time.
    $config = $this->config('system.date')
      ->set('timezone.default', 'Pacific/Honolulu')
      ->set('timezone.user.configurable', 0)
      ->save();
    entity_load('date_format', 'medium')
      ->setPattern('Y-m-d H:i:s O')
      ->save();

    // Create some nodes with different authored-on dates.
    $date1 = '2007-01-31 21:00:00 -1000';
    $date2 = '2007-07-31 21:00:00 -1000';
    $this->drupalCreateContentType(array('type' => 'article'));
    $node1 = $this->drupalCreateNode(array('created' => strtotime($date1), 'type' => 'article'));
    $node2 = $this->drupalCreateNode(array('created' => strtotime($date2), 'type' => 'article'));

    // Confirm date format and time zone.
    $this->drupalGet('node/' . $node1->id());
    $this->assertText('2007-01-31 21:00:00 -1000', 'Date should be identical, with GMT offset of -10 hours.');
    $this->drupalGet('node/' . $node2->id());
    $this->assertText('2007-07-31 21:00:00 -1000', 'Date should be identical, with GMT offset of -10 hours.');

    // Set time zone to Los Angeles time.
    $config->set('timezone.default', 'America/Los_Angeles')->save();
    \Drupal::entityManager()->getViewBuilder('node')->resetCache(array($node1, $node2));

    // Confirm date format and time zone.
    $this->drupalGet('node/' . $node1->id());
    $this->assertText('2007-01-31 23:00:00 -0800', 'Date should be two hours ahead, with GMT offset of -8 hours.');
    $this->drupalGet('node/' . $node2->id());
    $this->assertText('2007-08-01 00:00:00 -0700', 'Date should be three hours ahead, with GMT offset of -7 hours.');
  }

  /**
   * Test date format configuration.
   */
  function testDateFormatConfiguration() {
    // Confirm 'no custom date formats available' message appears.
    $this->drupalGet('admin/config/regional/date-time');

    // Add custom date format.
    $this->clickLink(t('Add format'));
    $date_format_id = strtolower($this->randomMachineName(8));
    $name = ucwords($date_format_id);
    $date_format = 'd.m.Y - H:i';
    $edit = array(
      'id' => $date_format_id,
      'label' => $name,
      'date_format_pattern' => $date_format,
    );
    $this->drupalPostForm('admin/config/regional/date-time/formats/add', $edit, t('Add format'));
    $this->assertUrl(\Drupal::url('entity.date_format.collection', [], ['absolute' => TRUE]), [], 'Correct page redirection.');
    $this->assertText(t('Custom date format added.'), 'Date format added confirmation message appears.');
    $this->assertText($name, 'Custom date format appears in the date format list.');
    $this->assertText(t('Delete'), 'Delete link for custom date format appears.');

    // Edit the custom date format and re-save without editing the format.
    $this->drupalGet('admin/config/regional/date-time');
    $this->clickLink(t('Edit'));
    $this->drupalPostForm(NULL, NULL, t('Save format'));
    $this->assertUrl('admin/config/regional/date-time', array('absolute' => TRUE), 'Correct page redirection.');
    $this->assertText(t('Custom date format updated.'), 'Custom date format successfully updated.');

    // Edit custom date format.
    $this->drupalGet('admin/config/regional/date-time');
    $this->clickLink(t('Edit'));
    $edit = array(
      'date_format_pattern' => 'Y m',
    );
    $this->drupalPostForm($this->getUrl(), $edit, t('Save format'));
    $this->assertUrl(\Drupal::url('entity.date_format.collection', [], ['absolute' => TRUE]), [], 'Correct page redirection.');
    $this->assertText(t('Custom date format updated.'), 'Custom date format successfully updated.');

    // Delete custom date format.
    $this->clickLink(t('Delete'));
    $this->drupalPostForm('admin/config/regional/date-time/formats/manage/' . $date_format_id . '/delete', array(), t('Delete'));
    $this->assertUrl(\Drupal::url('entity.date_format.collection', [], ['absolute' => TRUE]), [], 'Correct page redirection.');
    $this->assertRaw(t('The date format %format has been deleted.', array('%format' => $name)), 'Custom date format removed.');

    // Make sure the date does not exist in config.
    $date_format = entity_load('date_format', $date_format_id);
    $this->assertFalse($date_format);

    // Add a new date format with an existing format.
    $date_format_id = strtolower($this->randomMachineName(8));
    $name = ucwords($date_format_id);
    $date_format = 'Y';
    $edit = array(
      'id' => $date_format_id,
      'label' => $name,
      'date_format_pattern' => $date_format,
    );
    $this->drupalPostForm('admin/config/regional/date-time/formats/add', $edit, t('Add format'));
    $this->assertUrl(\Drupal::url('entity.date_format.collection', [], ['absolute' => TRUE]), [], 'Correct page redirection.');
    $this->assertText(t('Custom date format added.'), 'Date format added confirmation message appears.');
    $this->assertText($name, 'Custom date format appears in the date format list.');
    $this->assertText(t('Delete'), 'Delete link for custom date format appears.');

    $date_format = DateFormat::create(array(
      'id' => 'xss_short',
      'label' => 'XSS format',
      'pattern' => '\<\s\c\r\i\p\t\>\a\l\e\r\t\(\'\X\S\S\'\)\;\<\/\s\c\r\i\p\t\>',
      ));
    $date_format->save();

    $this->drupalGet(Url::fromRoute('entity.date_format.collection'));
    $this->assertEscaped("<script>alert('XSS');</script>", 'The date format was properly escaped');

    // Add a new date format with HTML in it.
    $date_format_id = strtolower($this->randomMachineName(8));
    $name = ucwords($date_format_id);
    $date_format = '& \<\e\m\>Y\<\/\e\m\>';
    $edit = array(
      'id' => $date_format_id,
      'label' => $name,
      'date_format_pattern' => $date_format,
    );
    $this->drupalPostForm('admin/config/regional/date-time/formats/add', $edit, t('Add format'));
    $this->assertUrl(\Drupal::url('entity.date_format.collection', [], ['absolute' => TRUE]), [], 'Correct page redirection.');
    $this->assertText(t('Custom date format added.'), 'Date format added confirmation message appears.');
    $this->assertText($name, 'Custom date format appears in the date format list.');
    $this->assertEscaped('<em>' . date("Y") . '</em>');
  }

}
