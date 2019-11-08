<?php

namespace Drupal\FunctionalTests\Datetime;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the functionality of TimestampAgoFormatter core field formatter.
 *
 * @group field
 */
class TimestampAgoFormatterTest extends BrowserTestBase {

  /**
   * An array of display options to pass to entity_get_display().
   *
   * @var array
   */
  protected $displayOptions;

  /**
   * A field storage to use in this test class.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorage;

  /**
   * The field used in this test class.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['entity_test', 'field_ui'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $web_user = $this->drupalCreateUser([
      'access administration pages',
      'view test entity',
      'administer entity_test content',
      'administer entity_test fields',
      'administer entity_test display',
      'administer entity_test form display',
      'view the administration theme',
    ]);
    $this->drupalLogin($web_user);

    $field_name = 'field_timestamp';
    $type = 'timestamp';
    $widget_type = 'datetime_timestamp';
    $formatter_type = 'timestamp_ago';

    $this->fieldStorage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => $type,
    ]);
    $this->fieldStorage->save();
    $this->field = FieldConfig::create([
      'field_storage' => $this->fieldStorage,
      'bundle' => 'entity_test',
      'required' => TRUE,
    ]);
    $this->field->save();

    EntityFormDisplay::load('entity_test.entity_test.default')
      ->setComponent($field_name, ['type' => $widget_type])
      ->save();

    $this->displayOptions = [
      'type' => $formatter_type,
      'label' => 'hidden',
    ];

    EntityViewDisplay::create([
      'targetEntityType' => $this->field->getTargetEntityTypeId(),
      'bundle' => $this->field->getTargetBundle(),
      'mode' => 'full',
      'status' => TRUE,
    ])->setComponent($field_name, $this->displayOptions)
      ->save();
  }

  /**
   * Tests the formatter settings.
   */
  public function testSettings() {
    $this->drupalGet('entity_test/structure/entity_test/display');

    $edit = [
      'fields[field_timestamp][region]' => 'content',
      'fields[field_timestamp][type]' => 'timestamp_ago',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    $this->drupalPostForm(NULL, [], 'field_timestamp_settings_edit');
    $edit = [
      'fields[field_timestamp][settings_edit_form][settings][future_format]' => 'ends in @interval',
      'fields[field_timestamp][settings_edit_form][settings][past_format]' => 'started @interval ago',
      'fields[field_timestamp][settings_edit_form][settings][granularity]' => 1,
    ];
    $this->drupalPostForm(NULL, $edit, 'Update');
    $this->drupalPostForm(NULL, [], 'Save');

    $this->assertSession()->pageTextContains('ends in 1 year');
    $this->assertSession()->pageTextContains('started 1 year ago');
  }

}
