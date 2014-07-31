<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Drupal6FieldInstance.
 */

namespace Drupal\migrate_drupal\Tests\Dump;

/**
 * Database dump for testing entity display migration.
 */
class Drupal6FieldInstance extends Drupal6DumpBase {

  /**
   * {@inheritdoc}
   */
  public function load() {
    $this->createTable('content_node_field_instance', array(
      'description' => 'Table that contains field instance settings.',
      'fields' => array(
        'field_name' => array(
          'type' => 'varchar',
          'length' => 32,
          'not null' => TRUE,
          'default' => '',
        ),
        'type_name' => array(
          'type' => 'varchar',
          'length' => 32,
          'not null' => TRUE,
          'default' => '',
        ),
        'weight' => array(
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
        ),
        'label' => array(
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ),
        'widget_type' => array(
          'type' => 'varchar',
          'length' => 32,
          'not null' => TRUE,
          'default' => '',
        ),
        'widget_settings' => array(
          'type' => 'text',
          'size' => 'medium',
          'not null' => TRUE,
          'serialize' => TRUE,
        ),
        'display_settings' => array(
          'type' => 'text',
          'size' => 'medium',
          'not null' => TRUE,
          'serialize' => TRUE,
        ),
        'description' => array(
          'type' => 'text',
          'size' => 'medium',
          'not null' => TRUE,
        ),
        'widget_module' => array(
          'type' => 'varchar',
          'length' => 127,
          'not null' => TRUE,
          'default' => '',
        ),
        'widget_active' => array(
          'type' => 'int',
          'size' => 'tiny',
          'not null' => TRUE,
          'default' => 0,
        ),
      ),
      'primary key' => array('field_name', 'type_name'),
    ));

    $this->database->insert('content_node_field_instance')->fields(array(
      'field_name',
      'type_name',
      'weight',
      'label',
      'widget_type',
      'widget_settings',
      'display_settings',
      'description',
    ))
    ->values(array(
      'field_name' => 'field_test',
      'type_name' => 'story',
      'weight' => 1,
      'label' => 'Text Field',
      'widget_type' => 'text_textfield',
      'widget_settings' => serialize(array(
        'rows' => 5,
        'size' => '60',
        'default_value' => array(
          0 => array(
            'value' => 'text for default value',
            '_error_element' => 'default_value_widget][field_test][0][value',
          ),
        ),
        'default_value_php' => NULL,
      )),
      'display_settings' => serialize(array(
        'weight' => 1,
        'parent' => '',
        'label' => array(
          'format' => 'above',
        ),
        1 => array(
          'format' => 'default',
          'exclude' => 0,
        ),
        'teaser' => array(
          'format' => 'trimmed',
          'exclude' => 0,
        ),
        'full' => array(
          'format' => 'default',
          'exclude' => 0,
        ),
        4 => array(
          'format' => 'trimmed',
          'exclude' => 0,
        ),
        5 => array(
          'format' => 'default',
          'exclude' => 1,
        ),
      )),
      'description' => 'An example text field.',
    ))
    ->values(array(
      'field_name' => 'field_test',
      'type_name' => 'test_page',
      'weight' => 1,
      'label' => 'Text Field',
      'widget_type' => 'text_textfield',
      'widget_settings' => serialize(array(
        'rows' => 5,
        'size' => '60',
        'default_value' => array(
          0 => array(
            'value' => 'text for default value',
            '_error_element' => 'default_value_widget][field_test][0][value',
          ),
        ),
        'default_value_php' => NULL,
      )),
      'display_settings' => serialize(array(
        'weight' => 1,
        'parent' => '',
        'label' => array(
          'format' => 'above',
        ),
        'teaser' => array(
          'format' => 'trimmed',
          'exclude' => 0,
        ),
        'full' => array(
          'format' => 'default',
          'exclude' => 0,
        ),
        4 => array(
          'format' => 'trimmed',
          'exclude' => 0,
        ),
        5 => array(
          'format' => 'default',
          'exclude' => 1,
        ),
      )),
      'description' => 'An example textfield.',
    ))
    ->values(array(
      'field_name' => 'field_test_two',
      'type_name' => 'story',
      'weight' => 2,
      'label' => 'Integer Field',
      'widget_type' => 'number',
      'widget_settings' => 'a:2:{s:13:"default_value";a:1:{i:0;a:2:{s:5:"value";s:0:"";s:14:"_error_element";s:41:"default_value_widget][field_int][0][value";}}s:17:"default_value_php";N;}',
      'display_settings' => serialize(array(
        'weight' => 2,
        'parent' => '',
        'label' => array(
          'format' => 'above',
        ),
        'teaser' => array(
          'format' => 'unformatted',
          'exclude' => 0,
        ),
        'full' => array(
          'format' => 'us_0',
          'exclude' => 0,
        ),
        4 => array(
          'format' => 'unformatted',
          'exclude' => 0,
        ),
        5 => array(
          'format' => 'default',
          'exclude' => 1,
        ),
      )),
      'description' => 'An example integer field.',
    ))
    ->values(array(
      'field_name' => 'field_test_three',
      'type_name' => 'story',
      'weight' => 3,
      'label' => 'Decimal Field',
      'widget_type' => 'number',
      'widget_settings' => serialize(array(
        'default_value' => array(
          0 => array(
            'value' => '101',
            '_error_element' => 'default_value_widget][field_decimal][0][value',
          ),
        ),
        'default_value_php' => NULL,
      )),
      'display_settings' => serialize(array(
        'weight' => 3,
        'parent' => '',
        'label' => array(
          'format' => 'above',
        ),
        'teaser' => array(
          'format' => 'unformatted',
          'exclude' => 0,
        ),
        'full' => array(
          'format' => 'us_2',
          'exclude' => 0,
        ),
        4 => array(
          'format' => 'unformatted',
          'exclude' => 0,
        ),
        5 => array(
          'format' => 'default',
          'exclude' => 1,
        ),
      )),
      'description' => 'An example decimal field.',
    ))
    ->values(array(
      'field_name' => 'field_test_four',
      'type_name' => 'story',
      'weight' => 3,
      'label' => 'Float Field',
      'widget_type' => 'number',
      'widget_settings' => serialize(array(
        'default_value' => array(
          0 => array(
            'value' => '101',
            '_error_element' => 'default_value_widget][field_float][0][value',
          ),
        ),
        'default_value_php' => NULL,
      )),
      'display_settings' => serialize(array(
        'weight' => 3,
        'parent' => '',
        'label' => array(
          'format' => 'above',
        ),
        'teaser' => array(
          'format' => 'unformatted',
          'exclude' => 0,
        ),
        'full' => array(
          'format' => 'us_2',
          'exclude' => 0,
        ),
        4 => array(
          'format' => 'unformatted',
          'exclude' => 0,
        ),
        5 => array(
          'format' => 'default',
          'exclude' => 1,
        ),
      )),
      'description' => 'An example float field.',
    ))
    ->values(array(
      'field_name' => 'field_test_email',
      'type_name' => 'story',
      'weight' => 4,
      'label' => 'Email Field',
      'widget_type' => 'email_textfield',
      'widget_settings' => serialize(array(
        'size' => '60',
        'default_value' => array(
          0 => array(
            'email' => 'benjy@example.com',
          ),
        ),
        'default_value_php' => NULL,
      )),
      'display_settings' => serialize(array(
        'weight' => 4,
        'parent' => '',
        'label' => array(
          'format' => 'above',
        ),
        'teaser' => array(
          'format' => 'default',
          'exclude' => 0,
        ),
        'full' => array(
          'format' => 'default',
          'exclude' => 0,
        ),
        4 => array(
          'format' => 'default',
          'exclude' => 0,
        ),
        5 => array(
          'format' => 'default',
          'exclude' => 1,
        ),
      )),
      'description' => 'An example email field.',
    ))
    ->values(array(
      'field_name' => 'field_test_link',
      'type_name' => 'story',
      'weight' => 5,
      'label' => 'Link Field',
      'widget_type' => 'link',
      'widget_settings' => serialize(array(
        'default_value' => array(
          0 => array(
            'title' => 'default link title',
            'url' => 'http://drupal.org',
          ),
        ),
        'default_value_php' => NULL,
      )),
      'display_settings' => serialize(array(
        'weight' => 5,
        'parent' => '',
        'label' => array(
          'format' => 'above',
        ),
        'teaser' => array(
          'format' => 'default',
          'exclude' => 0,
        ),
        'full' => array(
          'format' => 'absolute',
          'exclude' => 0,
        ),
        4 => array(
          'format' => 'default',
          'exclude' => 0,
        ),
        5 => array(
          'format' => 'default',
          'exclude' => 1,
        ),
      )),
      'description' => 'An example link field.',
    ))
    ->values(array(
      'field_name' => 'field_test_filefield',
      'type_name' => 'story',
      'weight' => 7,
      'label' => 'File Field',
      'widget_type' => 'filefield_widget',
      'widget_settings' => serialize(array(
        'file_extensions' => 'txt pdf doc',
        'file_path' => 'images',
        'progress_indicator' => 'bar',
        'max_filesize_per_file' => '200K',
        'max_filesize_per_node' => '20M',
      )),
      'display_settings' => serialize(array(
        'weight' => 7,
        'parent' => '',
        'label' => array(
          'format' => 'above',
        ),
        'teaser' => array(
          'format' => 'default',
          'exclude' => 0,
        ),
        'full' => array(
          'format' => 'url_plain',
          'exclude' => 0,
        ),
        4 => array(
          'format' => 'default',
          'exclude' => 0,
        ),
        5 => array(
          'format' => 'default',
          'exclude' => 1,
        ),
      )),
      'description' => 'An example image field.',
    ))
    ->values(array(
      'field_name' => 'field_test_imagefield',
      'type_name' => 'story',
      'weight' => 8,
      'label' => 'Image Field',
      'widget_type' => 'imagefield_widget',
      'widget_settings' => serialize(array(
        'file_extensions' => 'png gif jpg jpeg',
        'file_path' => '',
        'progress_indicator' => 'bar',
        'max_filesize_per_file' => '',
        'max_filesize_per_node' => '',
        'max_resolution' => '0',
        'min_resolution' => '0',
        'alt' => 'Test alt',
        'custom_alt' => 0,
        'title' => 'Test title',
        'custom_title' => 0,
        'title_type' => 'textfield',
        'default_image' => NULL,
        'use_default_image' => 0,
      )),
      'display_settings' => serialize(array(
        'weight' => 8,
        'parent' => '',
        'label' => array(
          'format' => 'above',
        ),
        'teaser' => array(
          'format' => 'image_imagelink',
          'exclude' => 0,
        ),
        'full' => array(
          'format' => 'image_plain',
          'exclude' => 0,
        ),
        4 => array(
          'format' => 'default',
          'exclude' => 0,
        ),
        5 => array(
          'format' => 'default',
          'exclude' => 1,
        ),
      )),
      'description' => 'An example image field.',
    ))
    ->values(array(
      'field_name' => 'field_test_phone',
      'type_name' => 'story',
      'weight' => 9,
      'label' => 'Phone Field',
      'widget_type' => 'phone_textfield',
      'widget_settings' => serialize(array(
        'size' => '60',
        'default_value' => array(
          0 => array(
            'value' => '',
            '_error_element' => 'default_value_widget][field_phone][0][value',
          ),
        ),
        'default_value_php' => NULL,
      )),
      'display_settings' => serialize(array(
        'weight' => 9,
        'parent' => '',
        'label' => array(
          'format' => 'above',
        ),
        'teaser' => array(
          'format' => 'default',
          'exclude' => 0,
        ),
        'full' => array(
          'format' => 'default',
          'exclude' => 0,
        ),
        4 => array(
          'format' => 'default',
          'exclude' => 0,
        ),
        5 => array(
          'format' => 'default',
          'exclude' => 1,
        ),
      )),
      'description' => 'An example phone field.',
    ))
    ->values(array(
      'field_name' => 'field_test_date',
      'type_name' => 'story',
      'weight' => 10,
      'label' => 'Date Field',
      'widget_type' => 'date_select',
      'widget_settings' => serialize(array(
        'default_value' => 'blank',
        'default_value_code' => '',
        'default_value2' => 'same',
        'default_value_code2' => '',
        'input_format' => 'm/d/Y - H:i:s',
        'input_format_custom' => '',
        'increment' => '1',
        'text_parts' => array(),
        'year_range' => '-3:+3',
        'label_position' => 'above',
      )),
      'display_settings' => serialize(array(
        'weight' => 10,
        'parent' => '',
        'label' => array(
          'format' => 'above',
        ),
        'teaser' => array(
          'format' => 'default',
          'exclude' => 0,
        ),
        'full' => array(
          'format' => 'long',
          'exclude' => 0,
        ),
        4 => array(
          'format' => 'default',
          'exclude' => 0,
        ),
        5 => array(
          'format' => 'default',
          'exclude' => 1,
        ),
      )),
      'description' => 'An example date field.',
    ))
    ->values(array(
      'field_name' => 'field_test_datestamp',
      'type_name' => 'story',
      'weight' => 11,
      'label' => 'Date Stamp Field',
      'widget_type' => 'date_select',
      'widget_settings' => serialize(array(
        'default_value' => 'blank',
        'default_value_code' => '',
        'default_value2' => 'same',
        'default_value_code2' => '',
        'input_format' => 'm/d/Y - H:i:s',
        'input_format_custom' => '',
        'increment' => '1',
        'text_parts' => array(),
        'year_range' => '-3:+3',
        'label_position' => 'above',
      )),
      'display_settings' => serialize(array(
        'weight' => 11,
        'parent' => '',
        'label' => array(
          'format' => 'above',
        ),
        'teaser' => array(
          'format' => 'medium',
          'exclude' => 0,
        ),
        'full' => array(
          'format' => 'default',
          'exclude' => 0,
        ),
        4 => array(
          'format' => 'default',
          'exclude' => 0,
        ),
        5 => array(
          'format' => 'default',
          'exclude' => 1,
        ),
      )),
      'description' => 'An example date stamp field.',
    ))
    ->values(array(
      'field_name' => 'field_test_datetime',
      'type_name' => 'story',
      'weight' => 12,
      'label' => 'Datetime Field',
      'widget_type' => 'date_select',
      'widget_settings' => serialize(array(
        'default_value' => 'blank',
        'default_value_code' => '',
        'default_value2' => 'same',
        'default_value_code2' => '',
        'input_format' => 'm/d/Y - H:i:s',
        'input_format_custom' => '',
        'increment' => '1',
        'text_parts' => array(),
        'year_range' => '-3:+3',
        'label_position' => 'above',
      )),
      'display_settings' => serialize(array(
        'weight' => 12,
        'parent' => '',
        'label' => array(
          'format' => 'above',
        ),
        'teaser' => array(
          'format' => 'short',
          'exclude' => 0,
        ),
        'full' => array(
          'format' => 'default',
          'exclude' => 0,
        ),
        4 => array(
          'format' => 'default',
          'exclude' => 0,
        ),
        5 => array(
          'format' => 'default',
          'exclude' => 1,
        ),
      )),
      'description' => 'An example datetime field.',
    ))
    ->values(array(
      'field_name' => 'field_test_decimal_radio_buttons',
      'type_name' => 'story',
      'weight' => 13,
      'label' => 'Decimal Radio Buttons Field',
      'widget_type' => 'optionwidgets_buttons',
      'widget_settings' => 'a:2:{s:13:"default_value";a:1:{i:0;a:1:{s:5:"value";s:0:"";}}s:17:"default_value_php";N;}',
      'display_settings' => serialize(array(
        'weight' => 13,
        'parent' => '',
        'label' => array(
          'format' => 'above',
        ),
        'teaser' => array(
          'format' => 'unformatted',
          'exclude' => 0,
        ),
        'full' => array(
          'format' => 'us_0',
          'exclude' => 0,
        ),
        4 => array(
          'format' => 'unformatted',
          'exclude' => 0,
        ),
        5 => array(
          'format' => 'default',
          'exclude' => 1,
        ),
      )),
      'description' => 'An example decimal field using radio buttons.',
    ))
    ->values(array(
      'field_name' => 'field_test_float_single_checkbox',
      'type_name' => 'story',
      'weight' => 14,
      'label' => 'Float Single Checkbox Field',
      'widget_type' => 'optionwidgets_onoff',
      'widget_settings' => 'a:2:{s:13:"default_value";a:1:{i:0;a:1:{s:5:"value";N;}}s:17:"default_value_php";N;}',
      'display_settings' => serialize(array(
        'weight' => 14,
        'parent' => '',
        'label' => array(
          'format' => 'above',
        ),
        'teaser' => array(
          'format' => 'unformatted',
          'exclude' => 0,
        ),
        'full' => array(
          'format' => 'us_0',
          'exclude' => 0,
        ),
        4 => array(
          'format' => 'unformatted',
          'exclude' => 0,
        ),
        5 => array(
          'format' => 'default',
          'exclude' => 1,
        ),
      )),
      'description' => 'An example float field using a single on/off checkbox.',
    ))
    ->values(array(
      'field_name' => 'field_test_integer_selectlist',
      'type_name' => 'story',
      'weight' => 15,
      'label' => 'Integer Select List Field',
      'widget_type' => 'optionwidgets_select',
      'widget_settings' => 'a:2:{s:13:"default_value";a:1:{i:0;a:1:{s:5:"value";s:0:"";}}s:17:"default_value_php";N;}',
      'display_settings' => serialize(array(
        'weight' => 15,
        'parent' => '',
        'label' => array(
          'format' => 'above',
        ),
        'teaser' => array(
          'format' => 'unformatted',
          'exclude' => 0,
        ),
        'full' => array(
          'format' => 'us_0',
          'exclude' => 0,
        ),
        4 => array(
          'format' => 'unformatted',
          'exclude' => 0,
        ),
        5 => array(
          'format' => 'default',
          'exclude' => 1,
        ),
      )),
      'description' => 'An example integer field using a select list.',
    ))
    ->values(array(
      'field_name' => 'field_test_text_single_checkbox',
      'type_name' => 'story',
      'weight' => 16,
      'label' => 'Text Single Checkbox Field',
      'widget_type' => 'optionwidgets_onoff',
      'widget_settings' => 'a:2:{s:13:"default_value";a:1:{i:0;a:1:{s:5:"value";N;}}s:17:"default_value_php";N;}',
      'display_settings' => serialize(array(
        'weight' => 16,
        'parent' => '',
        'label' => array(
          'format' => 'above',
        ),
        'teaser' => array(
          'format' => 'trimmed',
          'exclude' => 0,
        ),
        'full' => array(
          'format' => 'default',
          'exclude' => 0,
        ),
        4 => array(
          'format' => 'trimmed',
          'exclude' => 0,
        ),
        5 => array(
          'format' => 'default',
          'exclude' => 1,
        ),
      )),
      'description' => 'An example text field using a single on/off checkbox.',
    ))
    ->execute();

    // Create the field table.
    $this->createTable('content_node_field', array(
      'description' => 'Table that contains field settings.',
      'fields' => array(
        'field_name' => array(
          'type' => 'varchar',
          'length' => 32,
          'not null' => TRUE,
          'default' => '',
        ),
        'type' => array(
          'type' => 'varchar',
          'length' => 127,
          'not null' => TRUE,
          'default' => '',
        ),
        'global_settings' => array(
          'type' => 'text',
          'size' => 'medium',
          'not null' => TRUE,
          'serialize' => TRUE,
        ),
        'required' => array(
          'type' => 'int',
          'size' => 'tiny',
          'not null' => TRUE,
          'default' => 0,
        ),
        'multiple' => array(
          'type' => 'int',
          'size' => 'tiny',
          'not null' => TRUE,
          'default' => 0,
        ),
        'db_storage' => array(
          'type' => 'int',
          'size' => 'tiny',
          'not null' => TRUE,
          'default' => 1,
        ),
        'module' => array(
          'type' => 'varchar',
          'length' => 127,
          'not null' => TRUE,
          'default' => '',
        ),
        'db_columns' => array(
          'type' => 'text',
          'size' => 'medium',
          'not null' => TRUE,
          'serialize' => TRUE,
        ),
        'active' => array(
          'type' => 'int',
          'size' => 'tiny',
          'not null' => TRUE,
          'default' => 0,
        ),
        'locked' => array(
          'type' => 'int',
          'size' => 'tiny',
          'not null' => TRUE,
          'default' => 0,
        ),
      ),
      'primary key' => array('field_name'),
    ));

    $this->database->insert('content_node_field')->fields(array(
      'field_name',
      'module',
      'type',
      'global_settings',
      'multiple',
      'db_storage',
      'db_columns',
      'active',
    ))
    ->values(array(
      'field_name' => 'field_test',
      'module' => 'text',
      'type' => 'text',
      'global_settings' => 'a:4:{s:15:"text_processing";s:1:"1";s:10:"max_length";s:0:"";s:14:"allowed_values";s:0:"";s:18:"allowed_values_php";s:0:"";}',
      'multiple' => 0,
      'db_storage' => 0,
      'db_columns' => serialize(array(
        'value' => array(
          'type' => 'text',
          'size' => 'big',
          'not null' => FALSE,
          'sortable' => TRUE,
          'views' => TRUE,
        ),
        'format' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ),
      )),
      'active' => 1,
    ))
    ->values(array(
      'field_name' => 'field_test_two',
      'module' => 'number',
      'type' => 'number_integer',
      'global_settings' => 'a:6:{s:6:"prefix";s:4:"pref";s:6:"suffix";s:3:"suf";s:3:"min";i:10;s:3:"max";i:100;s:14:"allowed_values";s:0:"";s:18:"allowed_values_php";s:0:"";}',
      'multiple' => 1,
      'db_storage' => 0,
      'db_columns' => 'a:2:{s:5:"value";a:5:{s:4:"type";s:4:"text";s:4:"size";s:3:"big";s:8:"not null";b:0;s:8:"sortable";b:1;s:5:"views";b:1;}s:6:"format";a:4:{s:4:"type";s:3:"int";s:8:"unsigned";b:1;s:8:"not null";b:0;s:5:"views";b:0;}}',
      'active' => 1,
    ))
    ->values(array(
      'field_name' => 'field_test_three',
      'module' => 'number',
      'type' => 'number_decimal',
      'global_settings' => '',
      'multiple' => 0,
      'db_storage' => 1,
      'db_columns' => serialize(array(
        'value' => array(
          'type' => 'numeric',
          'precision' => 10,
          'scale' => 2,
          'not null' => FALSE,
          'sortable' => TRUE,
          'views' => TRUE,
        ),
      )),
      'active' => 1,
    ))
    ->values(array(
      'field_name' => 'field_test_four',
      'module' => 'number',
      'type' => 'number_float',
      'global_settings' => serialize(array(
        'prefix' => 'id-',
        'suffix' => '',
        'min' => '100',
        'max' => '200',
        'allowed_values' => '',
        'allowed_values_php' => '',
       )),
      'multiple' => 0,
      'db_storage' => 1,
      'db_columns' => '',
      'active' => 1,
    ))
    ->values(array(
      'field_name' => 'field_test_email',
      'module' => 'email',
      'type' => 'email',
      'global_settings' => 'a:0:{}',
      'multiple' => 0,
      'db_storage' => 1,
      'db_columns' => 'a:0:{}',
      'active' => 1,
    ))
    ->values(array(
      'field_name' => 'field_test_link',
      'module' => 'link',
      'type' => 'link',
      'global_settings' => 'a:7:{s:10:"attributes";a:4:{s:6:"target";s:7:"default";s:3:"rel";s:8:"nofollow";s:5:"class";s:0:"";s:5:"title";s:10:"Link Title";}s:7:"display";a:1:{s:10:"url_cutoff";s:2:"80";}s:3:"url";i:0;s:5:"title";s:8:"required";s:11:"title_value";s:0:"";s:13:"enable_tokens";s:0:"";s:12:"validate_url";i:1;}',
      'multiple' => 0,
      'db_storage' => 1,
      'db_columns' => 'a:0:{}',
      'active' => 1,
    ))
    ->values(array(
      'field_name' => 'field_test_filefield',
      'module' => 'filefield',
      'type' => 'filefield',
      'global_settings' => 'a:3:{s:10:"list_field";s:1:"0";s:12:"list_default";i:1;s:17:"description_field";s:1:"1";}',
      'multiple' => 0,
      'db_storage' => 1,
      'db_columns' => 'a:0:{}',
      'active' => 1,
    ))
    ->values(array(
      'field_name' => 'field_test_imagefield',
      'module' => 'filefield',
      'type' => 'filefield',
      'global_settings' => 'a:3:{s:10:"list_field";s:1:"0";s:12:"list_default";i:1;s:17:"description_field";s:1:"0";}',
      'multiple' => 0,
      'db_storage' => 1,
      'db_columns' => 'a:0:{}',
      'active' => 1,
    ))
    ->values(array(
      'field_name' => 'field_test_phone',
      'module' => 'phone',
      'type' => 'au_phone',
      'global_settings' => 'a:1:{s:5:"value";a:3:{s:4:"type";s:7:"varchar";s:6:"length";i:255;s:8:"not null";b:0;}}',
      'multiple' => 0,
      'db_storage' => 1,
      'db_columns' => 'a:0:{}',
      'active' => 1,
    ))
    ->values(array(
      'field_name' => 'field_test_date',
      'module' => 'date',
      'type' => 'date',
      'global_settings' => 'a:0:{}',
      'multiple' => 0,
      'db_storage' => 1,
      'db_columns' => 'a:0:{}',
      'active' => 1,
    ))
    ->values(array(
      'field_name' => 'field_test_datestamp',
      'module' => 'date',
      'type' => 'datestamp',
      'global_settings' => 'a:0:{}',
      'multiple' => 0,
      'db_storage' => 1,
      'db_columns' => 'a:0:{}',
      'active' => 1,
    ))
    ->values(array(
      'field_name' => 'field_test_datetime',
      'module' => 'date',
      'type' => 'datetime',
      'global_settings' => 'a:0:{}',
      'multiple' => 0,
      'db_storage' => 1,
      'db_columns' => 'a:0:{}',
      'active' => 0,
    ))
    ->values(array(
      'field_name' => 'field_test_decimal_radio_buttons',
      'module' => 'number',
      'type' => 'number_decimal',
      'global_settings' => 'a:9:{s:6:"prefix";s:0:"";s:6:"suffix";s:0:"";s:3:"min";s:0:"";s:3:"max";s:0:"";s:14:"allowed_values";s:7:"1.2
2.1";s:18:"allowed_values_php";s:0:"";s:9:"precision";s:2:"10";s:5:"scale";s:1:"2";s:7:"decimal";s:1:".";}',
      'multiple' => 0,
      'db_storage' => 1,
      'db_columns' => 'a:0:{}',
      'active' => 1,
    ))
    ->values(array(
      'field_name' => 'field_test_float_single_checkbox',
      'module' => 'number',
      'type' => 'number_float',
      'global_settings' => 'a:6:{s:6:"prefix";s:0:"";s:6:"suffix";s:0:"";s:3:"min";s:0:"";s:3:"max";s:0:"";s:14:"allowed_values";s:11:"3.142
1.234";s:18:"allowed_values_php";s:0:"";}',
      'multiple' => 0,
      'db_storage' => 1,
      'db_columns' => 'a:0:{}',
      'active' => 1,
    ))
    ->values(array(
      'field_name' => 'field_test_integer_selectlist',
      'module' => 'number',
      'type' => 'number_integer',
      'global_settings' => 'a:6:{s:6:"prefix";s:0:"";s:6:"suffix";s:0:"";s:3:"min";s:0:"";s:3:"max";s:0:"";s:14:"allowed_values";s:19:"1234
2341
3412
4123";s:18:"allowed_values_php";s:0:"";}',
      'multiple' => 0,
      'db_storage' => 1,
      'db_columns' => serialize(array(
        'value' => array(
          'type' => 'int',
          'not null' => FALSE,
          'sortable' => TRUE,
        ),
      )),
      'active' => 1,
    ))
    ->values(array(
      'field_name' => 'field_test_text_single_checkbox',
      'module' => 'text',
      'type' => 'text',
      'global_settings' => 'a:4:{s:15:"text_processing";s:1:"0";s:10:"max_length";s:0:"";s:14:"allowed_values";s:13:"Hello
Goodbye";s:18:"allowed_values_php";s:0:"";}',
      'multiple' => 0,
      'db_storage' => 1,
      'db_columns' => 'a:0:{}',
      'active' => 1,
    ))
    ->execute();

    $this->createTable('content_field_test', array(
        'description' => 'Table for field_test',
        'fields' => array(
          'vid' => array(
            'description' => 'The primary identifier for this version.',
            'type' => 'serial',
            'unsigned' => TRUE,
            'not null' => TRUE,
          ),
          'nid' => array(
            'description' => 'The {node} this version belongs to.',
            'type' => 'int',
            'unsigned' => TRUE,
            'not null' => TRUE,
            'default' => 0,
          ),
          'field_test_value' => array(
            'description' => 'Test field value.',
            'type' => 'varchar',
            'length' => 255,
            'not null' => TRUE,
            'default' => '',
          ),
          'field_test_format' => array(
            'type' => 'int',
            'unsigned' => TRUE,
            'not null' => TRUE,
            'default' => 0,
          ),
        ),
        'primary key' => array('vid'),
      ));
    $this->database->insert('content_field_test')->fields(array(
        'vid',
        'nid',
        'field_test_value',
        'field_test_format',
      ))
      ->values(array(
          'vid' => 1,
          'nid' => 1,
          'field_test_value' => 'This is a shared text field',
          'field_test_format' => 1,
        ))
      ->execute();


    $this->createTable('content_field_test_two', array(
      'description' => 'Table for field_test_two',
      'fields' => array(
        'vid' => array(
          'description' => 'The primary identifier for this version.',
          'type' => 'serial',
          'unsigned' => TRUE,
          'not null' => TRUE,
        ),
        'nid' => array(
          'description' => 'The {node} this version belongs to.',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ),
        'field_test_two_value' => array(
          'description' => 'Test field column.',
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ),
        'delta' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ),
        'field_test_two_format' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ),
      ),
      'primary key' => array('vid', 'delta'),
    ));
    $this->database->insert('content_field_test_two')->fields(array(
      'vid',
      'nid',
      'field_test_two_value',
      'delta',
      'field_test_two_format',
    ))
    ->values(array(
      'vid' => 1,
      'nid' => 1,
      'field_test_two_value' => 10,
      'delta' => 0,
      'field_test_two_format' => 1,
    ))
    ->values(array(
      'vid' => 1,
      'nid' => 1,
      'field_test_two_value' => 20,
      'delta' => 1,
      'field_test_two_format' => 1,
    ))
    ->execute();
    $this->setModuleVersion('content', '6001');

  }

}
