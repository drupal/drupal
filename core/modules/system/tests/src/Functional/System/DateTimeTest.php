<?php

namespace Drupal\Tests\system\Functional\System;

use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Configure date and time settings. Test date formatting and time zone
 * handling, including daylight saving time.
 *
 * @group system
 */
class DateTimeTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'block',
    'node',
    'language',
    'field',
    'field_ui',
    'datetime',
    'options',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected function setUp() {
    parent::setUp();

    // Create admin user and log in admin user.
    $this->drupalLogin($this->drupalCreateUser([
      'administer site configuration',
      'administer content types',
      'administer nodes',
      'administer node fields',
      'administer node form display',
      'administer node display',
    ]));
    $this->drupalPlaceBlock('local_actions_block');
  }

  /**
   * Test time zones and DST handling.
   */
  public function testTimeZoneHandling() {
    // Setup date/time settings for Honolulu time.
    $config = $this->config('system.date')
      ->set('timezone.default', 'Pacific/Honolulu')
      ->set('timezone.user.configurable', 0)
      ->save();
    DateFormat::load('medium')
      ->setPattern('Y-m-d H:i:s O')
      ->save();

    // Create some nodes with different authored-on dates.
    $date1 = '2007-01-31 21:00:00 -1000';
    $date2 = '2007-07-31 21:00:00 -1000';
    $this->drupalCreateContentType(['type' => 'article']);
    $node1 = $this->drupalCreateNode(['created' => strtotime($date1), 'type' => 'article']);
    $node2 = $this->drupalCreateNode(['created' => strtotime($date2), 'type' => 'article']);

    // Confirm date format and time zone.
    $this->drupalGet('node/' . $node1->id());
    $this->assertText('2007-01-31 21:00:00 -1000', 'Date should be identical, with GMT offset of -10 hours.');
    $this->drupalGet('node/' . $node2->id());
    $this->assertText('2007-07-31 21:00:00 -1000', 'Date should be identical, with GMT offset of -10 hours.');

    // Set time zone to Los Angeles time.
    $config->set('timezone.default', 'America/Los_Angeles')->save();
    \Drupal::entityTypeManager()->getViewBuilder('node')->resetCache([$node1, $node2]);

    // Confirm date format and time zone.
    $this->drupalGet('node/' . $node1->id());
    $this->assertText('2007-01-31 23:00:00 -0800', 'Date should be two hours ahead, with GMT offset of -8 hours.');
    $this->drupalGet('node/' . $node2->id());
    $this->assertText('2007-08-01 00:00:00 -0700', 'Date should be three hours ahead, with GMT offset of -7 hours.');
  }

  /**
   * Test date format configuration.
   */
  public function testDateFormatConfiguration() {
    // Confirm 'no custom date formats available' message appears.
    $this->drupalGet('admin/config/regional/date-time');

    // Add custom date format.
    $this->clickLink(t('Add format'));
    $date_format_id = strtolower($this->randomMachineName(8));
    $name = ucwords($date_format_id);
    $date_format = 'd.m.Y - H:i';
    $edit = [
      'id' => $date_format_id,
      'label' => $name,
      'date_format_pattern' => $date_format,
    ];
    $this->drupalPostForm('admin/config/regional/date-time/formats/add', $edit, t('Add format'));
    $this->assertUrl(Url::fromRoute('entity.date_format.collection', [], ['absolute' => TRUE])->toString(), [], 'Correct page redirection.');
    $this->assertText(t('Custom date format added.'), 'Date format added confirmation message appears.');
    $this->assertText($name, 'Custom date format appears in the date format list.');
    $this->assertText(t('Delete'), 'Delete link for custom date format appears.');

    // Edit the custom date format and re-save without editing the format.
    $this->drupalGet('admin/config/regional/date-time');
    $this->clickLink(t('Edit'));
    $this->drupalPostForm(NULL, NULL, t('Save format'));
    $this->assertUrl('admin/config/regional/date-time', ['absolute' => TRUE], 'Correct page redirection.');
    $this->assertText(t('Custom date format updated.'), 'Custom date format successfully updated.');

    // Edit custom date format.
    $this->drupalGet('admin/config/regional/date-time');
    $this->clickLink(t('Edit'));
    $edit = [
      'date_format_pattern' => 'Y m',
    ];
    $this->drupalPostForm($this->getUrl(), $edit, t('Save format'));
    $this->assertUrl(Url::fromRoute('entity.date_format.collection', [], ['absolute' => TRUE])->toString(), [], 'Correct page redirection.');
    $this->assertText(t('Custom date format updated.'), 'Custom date format successfully updated.');

    // Delete custom date format.
    $this->clickLink(t('Delete'));
    $this->drupalPostForm('admin/config/regional/date-time/formats/manage/' . $date_format_id . '/delete', [], t('Delete'));
    $this->assertUrl(Url::fromRoute('entity.date_format.collection', [], ['absolute' => TRUE])->toString(), [], 'Correct page redirection.');
    $this->assertRaw(t('The date format %format has been deleted.', ['%format' => $name]), 'Custom date format removed.');

    // Make sure the date does not exist in config.
    $date_format = DateFormat::load($date_format_id);
    $this->assertNull($date_format);

    // Add a new date format with an existing format.
    $date_format_id = strtolower($this->randomMachineName(8));
    $name = ucwords($date_format_id);
    $date_format = 'Y';
    $edit = [
      'id' => $date_format_id,
      'label' => $name,
      'date_format_pattern' => $date_format,
    ];
    $this->drupalPostForm('admin/config/regional/date-time/formats/add', $edit, t('Add format'));
    $this->assertUrl(Url::fromRoute('entity.date_format.collection', [], ['absolute' => TRUE])->toString(), [], 'Correct page redirection.');
    $this->assertText(t('Custom date format added.'), 'Date format added confirmation message appears.');
    $this->assertText($name, 'Custom date format appears in the date format list.');
    $this->assertText(t('Delete'), 'Delete link for custom date format appears.');

    $date_format = DateFormat::create([
      'id' => 'xss_short',
      'label' => 'XSS format',
      'pattern' => '\<\s\c\r\i\p\t\>\a\l\e\r\t\(\'\X\S\S\'\)\;\<\/\s\c\r\i\p\t\>',
      ]);
    $date_format->save();

    $this->drupalGet(Url::fromRoute('entity.date_format.collection'));
    $this->assertEscaped("<script>alert('XSS');</script>", 'The date format was properly escaped');

    // Add a new date format with HTML in it.
    $date_format_id = strtolower($this->randomMachineName(8));
    $name = ucwords($date_format_id);
    $date_format = '& \<\e\m\>Y\<\/\e\m\>';
    $edit = [
      'id' => $date_format_id,
      'label' => $name,
      'date_format_pattern' => $date_format,
    ];
    $this->drupalPostForm('admin/config/regional/date-time/formats/add', $edit, t('Add format'));
    $this->assertUrl(Url::fromRoute('entity.date_format.collection', [], ['absolute' => TRUE])->toString(), [], 'Correct page redirection.');
    $this->assertText(t('Custom date format added.'), 'Date format added confirmation message appears.');
    $this->assertText($name, 'Custom date format appears in the date format list.');
    $this->assertEscaped('<em>' . date("Y") . '</em>');
  }

  /**
   * Test handling case with invalid data in selectors (like February, 31st).
   */
  public function testEnteringDateTimeViaSelectors() {

    $this->drupalCreateContentType(['type' => 'page_with_date', 'name' => 'Page with date']);

    $this->drupalGet('admin/structure/types/manage/page_with_date');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet('admin/structure/types/manage/page_with_date/fields/add-field');
    $edit = [
      'new_storage_type' => 'datetime',
      'label' => 'dt',
      'field_name' => 'dt',
    ];
    $this->drupalPostForm('admin/structure/types/manage/page_with_date/fields/add-field', $edit, t('Save and continue'));
    $this->assertText(t('These settings apply to the'), 'New datetime field created, now configuring');

    $this->drupalGet('admin/structure/types/manage/page_with_date/fields/node.page_with_date.field_dt/storage');
    $edit = [
      'settings[datetime_type]' => 'datetime',
      'cardinality' => 'number',
      'cardinality_number' => '1',
    ];
    $this->drupalPostForm('admin/structure/types/manage/page_with_date/fields/node.page_with_date.field_dt/storage', $edit, t('Save field settings'));

    $this->drupalGet('admin/structure/types/manage/page_with_date/fields');
    $this->assertText('field_dt', 'New field is in place');

    $this->drupalGet('admin/structure/types/manage/page_with_date/form-display');
    $edit = [
      'fields[field_dt][type]' => 'datetime_datelist',
      'fields[field_dt][region]' => 'content',
    ];
    $this->drupalPostForm('admin/structure/types/manage/page_with_date/form-display', $edit, t('Save'));
    $this->drupalLogout();

    // Now log in as a regular editor.
    $this->drupalLogin($this->drupalCreateUser(['create page_with_date content']));

    $this->drupalGet('node/add/page_with_date');
    $edit = [
      'title[0][value]' => 'sample doc',
      'field_dt[0][value][year]' => '2016',
      'field_dt[0][value][month]' => '2',
      'field_dt[0][value][day]' => '31',
      'field_dt[0][value][hour]' => '1',
      'field_dt[0][value][minute]' => '30',
    ];
    $this->drupalPostForm('node/add/page_with_date', $edit, t('Save'));
    $this->assertText(t('Selected combination of day and month is not valid.'), 'Inorrect date failed validation');

    $edit['field_dt[0][value][day]'] = '29';
    $this->drupalPostForm('node/add/page_with_date', $edit, t('Save'));
    $this->assertNoText(t('Selected combination of day and month is not valid.'), 'Correct date passed validation.');

    $this->drupalGet('node/1');
    $this->assertText(t('Mon, 02/29/2016 - 01:30'), 'Node successfully created with valid date.');
  }

}
