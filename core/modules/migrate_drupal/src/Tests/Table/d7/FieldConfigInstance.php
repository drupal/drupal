<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\FieldConfigInstance.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the field_config_instance table.
 */
class FieldConfigInstance extends DrupalDumpBase {

  public function load() {
    $this->createTable("field_config_instance", array(
      'primary key' => array(
        'id',
      ),
      'fields' => array(
        'id' => array(
          'type' => 'serial',
          'not null' => TRUE,
          'length' => '11',
        ),
        'field_id' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
        ),
        'field_name' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '32',
          'default' => '',
        ),
        'entity_type' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '32',
          'default' => '',
        ),
        'bundle' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '128',
          'default' => '',
        ),
        'data' => array(
          'type' => 'blob',
          'not null' => TRUE,
          'length' => 100,
        ),
        'deleted' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '4',
          'default' => '0',
        ),
      ),
    ));
    $this->database->insert("field_config_instance")->fields(array(
      'id',
      'field_id',
      'field_name',
      'entity_type',
      'bundle',
      'data',
      'deleted',
    ))
    ->values(array(
      'id' => '1',
      'field_id' => '1',
      'field_name' => 'comment_body',
      'entity_type' => 'comment',
      'bundle' => 'comment_node_page',
      'data' => 'a:6:{s:5:"label";s:7:"Comment";s:8:"settings";a:2:{s:15:"text_processing";i:1;s:18:"user_register_form";b:0;}s:8:"required";b:1;s:7:"display";a:1:{s:7:"default";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:12:"text_default";s:6:"weight";i:0;s:8:"settings";a:0:{}s:6:"module";s:4:"text";}}s:6:"widget";a:4:{s:4:"type";s:13:"text_textarea";s:8:"settings";a:1:{s:4:"rows";i:5;}s:6:"weight";i:0;s:6:"module";s:4:"text";}s:11:"description";s:0:"";}',
      'deleted' => '0',
    ))->values(array(
      'id' => '2',
      'field_id' => '2',
      'field_name' => 'body',
      'entity_type' => 'node',
      'bundle' => 'page',
      'data' => 'a:6:{s:5:"label";s:4:"Body";s:6:"widget";a:4:{s:4:"type";s:26:"text_textarea_with_summary";s:8:"settings";a:2:{s:4:"rows";i:20;s:12:"summary_rows";i:5;}s:6:"weight";i:-4;s:6:"module";s:4:"text";}s:8:"settings";a:3:{s:15:"display_summary";b:1;s:15:"text_processing";i:1;s:18:"user_register_form";b:0;}s:7:"display";a:2:{s:7:"default";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:12:"text_default";s:8:"settings";a:0:{}s:6:"module";s:4:"text";s:6:"weight";i:0;}s:6:"teaser";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:23:"text_summary_or_trimmed";s:8:"settings";a:1:{s:11:"trim_length";i:600;}s:6:"module";s:4:"text";s:6:"weight";i:0;}}s:8:"required";b:0;s:11:"description";s:0:"";}',
      'deleted' => '0',
    ))->values(array(
      'id' => '3',
      'field_id' => '1',
      'field_name' => 'comment_body',
      'entity_type' => 'comment',
      'bundle' => 'comment_node_article',
      'data' => 'a:6:{s:5:"label";s:7:"Comment";s:8:"settings";a:2:{s:15:"text_processing";i:1;s:18:"user_register_form";b:0;}s:8:"required";b:1;s:7:"display";a:1:{s:7:"default";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:12:"text_default";s:6:"weight";i:0;s:8:"settings";a:0:{}s:6:"module";s:4:"text";}}s:6:"widget";a:4:{s:4:"type";s:13:"text_textarea";s:8:"settings";a:1:{s:4:"rows";i:5;}s:6:"weight";i:0;s:6:"module";s:4:"text";}s:11:"description";s:0:"";}',
      'deleted' => '0',
    ))->values(array(
      'id' => '4',
      'field_id' => '2',
      'field_name' => 'body',
      'entity_type' => 'node',
      'bundle' => 'article',
      'data' => 'a:6:{s:5:"label";s:4:"Body";s:6:"widget";a:4:{s:4:"type";s:26:"text_textarea_with_summary";s:8:"settings";a:2:{s:4:"rows";i:20;s:12:"summary_rows";i:5;}s:6:"weight";i:-4;s:6:"module";s:4:"text";}s:8:"settings";a:3:{s:15:"display_summary";b:1;s:15:"text_processing";i:1;s:18:"user_register_form";b:0;}s:7:"display";a:2:{s:7:"default";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:12:"text_default";s:8:"settings";a:0:{}s:6:"module";s:4:"text";s:6:"weight";i:0;}s:6:"teaser";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:23:"text_summary_or_trimmed";s:8:"settings";a:1:{s:11:"trim_length";i:600;}s:6:"module";s:4:"text";s:6:"weight";i:0;}}s:8:"required";b:0;s:11:"description";s:0:"";}',
      'deleted' => '0',
    ))->values(array(
      'id' => '5',
      'field_id' => '3',
      'field_name' => 'field_tags',
      'entity_type' => 'node',
      'bundle' => 'article',
      'data' => 'a:6:{s:5:"label";s:4:"Tags";s:11:"description";s:63:"Enter a comma-separated list of words to describe your content.";s:6:"widget";a:4:{s:4:"type";s:21:"taxonomy_autocomplete";s:6:"weight";i:-4;s:8:"settings";a:2:{s:4:"size";i:60;s:17:"autocomplete_path";s:21:"taxonomy/autocomplete";}s:6:"module";s:8:"taxonomy";}s:7:"display";a:2:{s:7:"default";a:5:{s:4:"type";s:28:"taxonomy_term_reference_link";s:6:"weight";i:10;s:5:"label";s:5:"above";s:8:"settings";a:0:{}s:6:"module";s:8:"taxonomy";}s:6:"teaser";a:5:{s:4:"type";s:28:"taxonomy_term_reference_link";s:6:"weight";i:10;s:5:"label";s:5:"above";s:8:"settings";a:0:{}s:6:"module";s:8:"taxonomy";}}s:8:"settings";a:1:{s:18:"user_register_form";b:0;}s:8:"required";b:0;}',
      'deleted' => '0',
    ))->values(array(
      'id' => '6',
      'field_id' => '4',
      'field_name' => 'field_image',
      'entity_type' => 'node',
      'bundle' => 'article',
      'data' => 'a:6:{s:5:"label";s:5:"Image";s:11:"description";s:40:"Upload an image to go with this article.";s:8:"required";b:0;s:8:"settings";a:9:{s:14:"file_directory";s:11:"field/image";s:15:"file_extensions";s:16:"png gif jpg jpeg";s:12:"max_filesize";s:0:"";s:14:"max_resolution";s:0:"";s:14:"min_resolution";s:0:"";s:9:"alt_field";b:1;s:11:"title_field";s:0:"";s:13:"default_image";i:0;s:18:"user_register_form";b:0;}s:6:"widget";a:4:{s:4:"type";s:11:"image_image";s:8:"settings";a:2:{s:18:"progress_indicator";s:8:"throbber";s:19:"preview_image_style";s:9:"thumbnail";}s:6:"weight";i:-1;s:6:"module";s:5:"image";}s:7:"display";a:2:{s:7:"default";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:5:"image";s:8:"settings";a:2:{s:11:"image_style";s:5:"large";s:10:"image_link";s:0:"";}s:6:"weight";i:-1;s:6:"module";s:5:"image";}s:6:"teaser";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:5:"image";s:8:"settings";a:2:{s:11:"image_style";s:6:"medium";s:10:"image_link";s:7:"content";}s:6:"weight";i:-1;s:6:"module";s:5:"image";}}}',
      'deleted' => '0',
    ))->values(array(
      'id' => '7',
      'field_id' => '1',
      'field_name' => 'comment_body',
      'entity_type' => 'comment',
      'bundle' => 'comment_node_blog',
      'data' => 'a:6:{s:5:"label";s:7:"Comment";s:8:"settings";a:2:{s:15:"text_processing";i:1;s:18:"user_register_form";b:0;}s:8:"required";b:1;s:7:"display";a:1:{s:7:"default";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:12:"text_default";s:6:"weight";i:0;s:8:"settings";a:0:{}s:6:"module";s:4:"text";}}s:6:"widget";a:4:{s:4:"type";s:13:"text_textarea";s:8:"settings";a:1:{s:4:"rows";i:5;}s:6:"weight";i:0;s:6:"module";s:4:"text";}s:11:"description";s:0:"";}',
      'deleted' => '0',
    ))->values(array(
      'id' => '8',
      'field_id' => '2',
      'field_name' => 'body',
      'entity_type' => 'node',
      'bundle' => 'blog',
      'data' => 'a:6:{s:5:"label";s:4:"Body";s:6:"widget";a:4:{s:4:"type";s:26:"text_textarea_with_summary";s:8:"settings";a:2:{s:4:"rows";i:20;s:12:"summary_rows";i:5;}s:6:"weight";i:-4;s:6:"module";s:4:"text";}s:8:"settings";a:3:{s:15:"display_summary";b:1;s:15:"text_processing";i:1;s:18:"user_register_form";b:0;}s:7:"display";a:2:{s:7:"default";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:12:"text_default";s:8:"settings";a:0:{}s:6:"module";s:4:"text";s:6:"weight";i:0;}s:6:"teaser";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:23:"text_summary_or_trimmed";s:8:"settings";a:1:{s:11:"trim_length";i:600;}s:6:"module";s:4:"text";s:6:"weight";i:0;}}s:8:"required";b:0;s:11:"description";s:0:"";}',
      'deleted' => '0',
    ))->values(array(
      'id' => '9',
      'field_id' => '1',
      'field_name' => 'comment_body',
      'entity_type' => 'comment',
      'bundle' => 'comment_node_book',
      'data' => 'a:6:{s:5:"label";s:7:"Comment";s:8:"settings";a:2:{s:15:"text_processing";i:1;s:18:"user_register_form";b:0;}s:8:"required";b:1;s:7:"display";a:1:{s:7:"default";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:12:"text_default";s:6:"weight";i:0;s:8:"settings";a:0:{}s:6:"module";s:4:"text";}}s:6:"widget";a:4:{s:4:"type";s:13:"text_textarea";s:8:"settings";a:1:{s:4:"rows";i:5;}s:6:"weight";i:0;s:6:"module";s:4:"text";}s:11:"description";s:0:"";}',
      'deleted' => '0',
    ))->values(array(
      'id' => '10',
      'field_id' => '2',
      'field_name' => 'body',
      'entity_type' => 'node',
      'bundle' => 'book',
      'data' => 'a:6:{s:5:"label";s:4:"Body";s:6:"widget";a:4:{s:4:"type";s:26:"text_textarea_with_summary";s:8:"settings";a:2:{s:4:"rows";i:20;s:12:"summary_rows";i:5;}s:6:"weight";i:-4;s:6:"module";s:4:"text";}s:8:"settings";a:3:{s:15:"display_summary";b:1;s:15:"text_processing";i:1;s:18:"user_register_form";b:0;}s:7:"display";a:2:{s:7:"default";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:12:"text_default";s:8:"settings";a:0:{}s:6:"module";s:4:"text";s:6:"weight";i:0;}s:6:"teaser";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:23:"text_summary_or_trimmed";s:8:"settings";a:1:{s:11:"trim_length";i:600;}s:6:"module";s:4:"text";s:6:"weight";i:0;}}s:8:"required";b:0;s:11:"description";s:0:"";}',
      'deleted' => '0',
    ))->values(array(
      'id' => '11',
      'field_id' => '5',
      'field_name' => 'taxonomy_forums',
      'entity_type' => 'node',
      'bundle' => 'forum',
      'data' => 'a:6:{s:5:"label";s:6:"Forums";s:8:"required";b:1;s:6:"widget";a:4:{s:4:"type";s:14:"options_select";s:8:"settings";a:0:{}s:6:"weight";i:0;s:6:"module";s:7:"options";}s:7:"display";a:2:{s:7:"default";a:5:{s:4:"type";s:28:"taxonomy_term_reference_link";s:6:"weight";i:10;s:5:"label";s:5:"above";s:8:"settings";a:0:{}s:6:"module";s:8:"taxonomy";}s:6:"teaser";a:5:{s:4:"type";s:28:"taxonomy_term_reference_link";s:6:"weight";i:10;s:5:"label";s:5:"above";s:8:"settings";a:0:{}s:6:"module";s:8:"taxonomy";}}s:8:"settings";a:1:{s:18:"user_register_form";b:0;}s:11:"description";s:0:"";}',
      'deleted' => '0',
    ))->values(array(
      'id' => '12',
      'field_id' => '1',
      'field_name' => 'comment_body',
      'entity_type' => 'comment',
      'bundle' => 'comment_node_forum',
      'data' => 'a:6:{s:5:"label";s:7:"Comment";s:8:"settings";a:2:{s:15:"text_processing";i:1;s:18:"user_register_form";b:0;}s:8:"required";b:1;s:7:"display";a:1:{s:7:"default";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:12:"text_default";s:6:"weight";i:0;s:8:"settings";a:0:{}s:6:"module";s:4:"text";}}s:6:"widget";a:4:{s:4:"type";s:13:"text_textarea";s:8:"settings";a:1:{s:4:"rows";i:5;}s:6:"weight";i:0;s:6:"module";s:4:"text";}s:11:"description";s:0:"";}',
      'deleted' => '0',
    ))->values(array(
      'id' => '13',
      'field_id' => '2',
      'field_name' => 'body',
      'entity_type' => 'node',
      'bundle' => 'forum',
      'data' => 'a:6:{s:5:"label";s:4:"Body";s:6:"widget";a:4:{s:4:"type";s:26:"text_textarea_with_summary";s:8:"settings";a:2:{s:4:"rows";i:20;s:12:"summary_rows";i:5;}s:6:"weight";i:1;s:6:"module";s:4:"text";}s:8:"settings";a:3:{s:15:"display_summary";b:1;s:15:"text_processing";i:1;s:18:"user_register_form";b:0;}s:7:"display";a:2:{s:7:"default";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:12:"text_default";s:8:"settings";a:0:{}s:6:"module";s:4:"text";s:6:"weight";i:11;}s:6:"teaser";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:23:"text_summary_or_trimmed";s:8:"settings";a:1:{s:11:"trim_length";i:600;}s:6:"module";s:4:"text";s:6:"weight";i:11;}}s:8:"required";b:0;s:11:"description";s:0:"";}',
      'deleted' => '0',
    ))->values(array(
      'id' => '14',
      'field_id' => '1',
      'field_name' => 'comment_body',
      'entity_type' => 'comment',
      'bundle' => 'comment_node_test_content_type',
      'data' => 'a:6:{s:5:"label";s:7:"Comment";s:8:"settings";a:2:{s:15:"text_processing";i:1;s:18:"user_register_form";b:0;}s:8:"required";b:1;s:7:"display";a:1:{s:7:"default";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:12:"text_default";s:6:"weight";i:0;s:8:"settings";a:0:{}s:6:"module";s:4:"text";}}s:6:"widget";a:4:{s:4:"type";s:13:"text_textarea";s:8:"settings";a:1:{s:4:"rows";i:5;}s:6:"weight";s:1:"0";s:6:"module";s:4:"text";}s:11:"description";s:0:"";}',
      'deleted' => '0',
    ))->values(array(
      'id' => '16',
      'field_id' => '6',
      'field_name' => 'field_boolean',
      'entity_type' => 'node',
      'bundle' => 'test_content_type',
      'data' => 'a:7:{s:5:"label";s:7:"Boolean";s:6:"widget";a:5:{s:6:"weight";s:1:"1";s:4:"type";s:13:"options_onoff";s:6:"module";s:7:"options";s:6:"active";i:1;s:8:"settings";a:1:{s:13:"display_label";i:1;}}s:8:"settings";a:1:{s:18:"user_register_form";b:0;}s:7:"display";a:1:{s:7:"default";a:5:{s:5:"label";s:5:"above";s:4:"type";s:12:"list_default";s:6:"weight";s:1:"0";s:8:"settings";a:0:{}s:6:"module";s:4:"list";}}s:8:"required";i:0;s:11:"description";s:19:"Some helpful text. ";s:13:"default_value";a:1:{i:0;a:1:{s:5:"value";i:0;}}}',
      'deleted' => '0',
    ))->values(array(
      'id' => '17',
      'field_id' => '7',
      'field_name' => 'field_email',
      'entity_type' => 'node',
      'bundle' => 'test_content_type',
      'data' => 'a:7:{s:5:"label";s:5:"Email";s:6:"widget";a:5:{s:6:"weight";s:1:"4";s:4:"type";s:15:"email_textfield";s:6:"module";s:5:"email";s:6:"active";i:1;s:8:"settings";a:1:{s:4:"size";s:2:"60";}}s:8:"settings";a:1:{s:18:"user_register_form";b:0;}s:7:"display";a:1:{s:7:"default";a:5:{s:5:"label";s:5:"above";s:4:"type";s:13:"email_default";s:6:"weight";s:1:"1";s:8:"settings";a:0:{}s:6:"module";s:5:"email";}}s:8:"required";i:0;s:11:"description";s:20:"The email help text.";s:13:"default_value";a:1:{i:0;a:1:{s:5:"email";s:19:"default@example.com";}}}',
      'deleted' => '0',
    ))->values(array(
      'id' => '18',
      'field_id' => '8',
      'field_name' => 'field_phone',
      'entity_type' => 'node',
      'bundle' => 'test_content_type',
      'data' => 'a:7:{s:5:"label";s:5:"Phone";s:6:"widget";a:5:{s:6:"weight";s:1:"6";s:4:"type";s:15:"phone_textfield";s:6:"module";s:5:"phone";s:6:"active";i:0;s:8:"settings";a:0:{}}s:8:"settings";a:6:{s:18:"phone_country_code";i:1;s:26:"phone_default_country_code";s:1:"1";s:20:"phone_int_max_length";i:15;s:18:"ca_phone_separator";s:1:"-";s:20:"ca_phone_parentheses";i:1;s:18:"user_register_form";b:0;}s:7:"display";a:1:{s:7:"default";a:5:{s:5:"label";s:5:"above";s:4:"type";s:5:"phone";s:6:"weight";s:1:"2";s:8:"settings";a:0:{}s:6:"module";s:5:"phone";}}s:8:"required";i:1;s:11:"description";s:0:"";s:13:"default_value";N;}',
      'deleted' => '0',
    ))->values(array(
      'id' => '19',
      'field_id' => '9',
      'field_name' => 'field_date',
      'entity_type' => 'node',
      'bundle' => 'test_content_type',
      'data' => 'a:6:{s:5:"label";s:4:"Date";s:6:"widget";a:5:{s:6:"weight";s:1:"2";s:4:"type";s:11:"date_select";s:6:"module";s:4:"date";s:6:"active";i:1;s:8:"settings";a:6:{s:12:"input_format";s:13:"m/d/Y - H:i:s";s:19:"input_format_custom";s:0:"";s:10:"year_range";s:5:"-3:+3";s:9:"increment";s:2:"15";s:14:"label_position";s:5:"above";s:10:"text_parts";a:0:{}}}s:8:"settings";a:5:{s:13:"default_value";s:3:"now";s:18:"default_value_code";s:0:"";s:14:"default_value2";s:4:"same";s:19:"default_value_code2";s:0:"";s:18:"user_register_form";b:0;}s:7:"display";a:1:{s:7:"default";a:5:{s:5:"label";s:5:"above";s:4:"type";s:12:"date_default";s:6:"weight";s:1:"3";s:8:"settings";a:5:{s:11:"format_type";s:4:"long";s:15:"multiple_number";s:0:"";s:13:"multiple_from";s:0:"";s:11:"multiple_to";s:0:"";s:6:"fromto";s:4:"both";}s:6:"module";s:4:"date";}}s:8:"required";i:0;s:11:"description";s:0:"";}',
      'deleted' => '0',
    ))->values(array(
      'id' => '20',
      'field_id' => '10',
      'field_name' => 'field_date_with_end_time',
      'entity_type' => 'node',
      'bundle' => 'test_content_type',
      'data' => 'a:6:{s:5:"label";s:18:"Date With End Time";s:6:"widget";a:5:{s:6:"weight";s:1:"3";s:4:"type";s:9:"date_text";s:6:"module";s:4:"date";s:6:"active";i:1;s:8:"settings";a:6:{s:12:"input_format";s:13:"m/d/Y - H:i:s";s:19:"input_format_custom";s:0:"";s:10:"year_range";s:5:"-3:+3";s:9:"increment";i:15;s:14:"label_position";s:5:"above";s:10:"text_parts";a:0:{}}}s:8:"settings";a:5:{s:13:"default_value";s:3:"now";s:18:"default_value_code";s:0:"";s:14:"default_value2";s:4:"same";s:19:"default_value_code2";s:0:"";s:18:"user_register_form";b:0;}s:7:"display";a:1:{s:7:"default";a:5:{s:5:"label";s:5:"above";s:4:"type";s:12:"date_default";s:6:"weight";s:1:"4";s:8:"settings";a:5:{s:11:"format_type";s:4:"long";s:15:"multiple_number";s:0:"";s:13:"multiple_from";s:0:"";s:11:"multiple_to";s:0:"";s:6:"fromto";s:4:"both";}s:6:"module";s:4:"date";}}s:8:"required";i:0;s:11:"description";s:0:"";}',
      'deleted' => '0',
    ))->values(array(
      'id' => '21',
      'field_id' => '11',
      'field_name' => 'field_file',
      'entity_type' => 'node',
      'bundle' => 'test_content_type',
      'data' => 'a:6:{s:5:"label";s:4:"File";s:6:"widget";a:5:{s:6:"weight";s:1:"5";s:4:"type";s:12:"file_generic";s:6:"module";s:4:"file";s:6:"active";i:1;s:8:"settings";a:1:{s:18:"progress_indicator";s:8:"throbber";}}s:8:"settings";a:5:{s:14:"file_directory";s:0:"";s:15:"file_extensions";s:15:"txt pdf ods odf";s:12:"max_filesize";s:5:"10 MB";s:17:"description_field";i:1;s:18:"user_register_form";b:0;}s:7:"display";a:1:{s:7:"default";a:5:{s:5:"label";s:5:"above";s:4:"type";s:12:"file_default";s:6:"weight";s:1:"5";s:8:"settings";a:0:{}s:6:"module";s:4:"file";}}s:8:"required";i:0;s:11:"description";s:0:"";}',
      'deleted' => '0',
    ))->values(array(
      'id' => '22',
      'field_id' => '12',
      'field_name' => 'field_float',
      'entity_type' => 'node',
      'bundle' => 'test_content_type',
      'data' => 'a:7:{s:5:"label";s:5:"Float";s:6:"widget";a:5:{s:6:"weight";s:1:"7";s:4:"type";s:6:"number";s:6:"module";s:6:"number";s:6:"active";i:0;s:8:"settings";a:0:{}}s:8:"settings";a:5:{s:3:"min";s:6:"-3.756";s:3:"max";s:5:"18.56";s:6:"prefix";s:12:"Prefix value";s:6:"suffix";s:12:"Suffix value";s:18:"user_register_form";b:0;}s:7:"display";a:1:{s:7:"default";a:5:{s:5:"label";s:5:"above";s:4:"type";s:14:"number_decimal";s:6:"weight";s:1:"6";s:8:"settings";a:4:{s:18:"thousand_separator";s:1:" ";s:17:"decimal_separator";s:1:".";s:5:"scale";i:2;s:13:"prefix_suffix";b:1;}s:6:"module";s:6:"number";}}s:8:"required";i:0;s:11:"description";s:22:"Some floaty help text.";s:13:"default_value";a:1:{i:0;a:1:{s:5:"value";s:3:"1.2";}}}',
      'deleted' => '0',
    ))->values(array(
      'id' => '23',
      'field_id' => '13',
      'field_name' => 'field_images',
      'entity_type' => 'node',
      'bundle' => 'test_content_type',
      'data' => 'a:6:{s:5:"label";s:6:"Images";s:6:"widget";a:5:{s:6:"weight";s:1:"8";s:4:"type";s:11:"image_image";s:6:"module";s:5:"image";s:6:"active";i:1;s:8:"settings";a:2:{s:18:"progress_indicator";s:8:"throbber";s:19:"preview_image_style";s:9:"thumbnail";}}s:8:"settings";a:9:{s:14:"file_directory";s:0:"";s:15:"file_extensions";s:16:"png gif jpg jpeg";s:12:"max_filesize";s:5:"15 MB";s:14:"max_resolution";s:9:"1000x1000";s:14:"min_resolution";s:3:"1x1";s:9:"alt_field";i:1;s:11:"title_field";i:1;s:13:"default_image";i:0;s:18:"user_register_form";b:0;}s:7:"display";a:1:{s:7:"default";a:5:{s:5:"label";s:5:"above";s:4:"type";s:5:"image";s:6:"weight";s:1:"7";s:8:"settings";a:2:{s:11:"image_style";s:0:"";s:10:"image_link";s:0:"";}s:6:"module";s:5:"image";}}s:8:"required";i:1;s:11:"description";s:0:"";}',
      'deleted' => '0',
    ))->values(array(
      'id' => '24',
      'field_id' => '14',
      'field_name' => 'field_integer',
      'entity_type' => 'node',
      'bundle' => 'test_content_type',
      'data' => 'a:7:{s:5:"label";s:7:"Integer";s:6:"widget";a:5:{s:6:"weight";s:1:"9";s:4:"type";s:6:"number";s:6:"module";s:6:"number";s:6:"active";i:0;s:8:"settings";a:0:{}}s:8:"settings";a:5:{s:3:"min";s:1:"1";s:3:"max";s:2:"25";s:6:"prefix";s:3:"abc";s:6:"suffix";s:3:"xyz";s:18:"user_register_form";b:0;}s:7:"display";a:1:{s:7:"default";a:5:{s:5:"label";s:5:"above";s:4:"type";s:14:"number_integer";s:6:"weight";s:1:"8";s:8:"settings";a:4:{s:18:"thousand_separator";s:1:" ";s:17:"decimal_separator";s:1:".";s:5:"scale";i:0;s:13:"prefix_suffix";b:1;}s:6:"module";s:6:"number";}}s:8:"required";i:1;s:11:"description";s:0:"";s:13:"default_value";N;}',
      'deleted' => '0',
    ))->values(array(
      'id' => '25',
      'field_id' => '15',
      'field_name' => 'field_link',
      'entity_type' => 'node',
      'bundle' => 'test_content_type',
      'data' => 'a:7:{s:5:"label";s:4:"Link";s:6:"widget";a:5:{s:6:"weight";s:2:"10";s:4:"type";s:10:"link_field";s:6:"module";s:4:"link";s:6:"active";i:0;s:8:"settings";a:0:{}}s:8:"settings";a:12:{s:12:"absolute_url";i:1;s:12:"validate_url";i:1;s:3:"url";i:0;s:5:"title";s:8:"optional";s:11:"title_value";s:19:"Unused Static Title";s:27:"title_label_use_field_label";i:0;s:15:"title_maxlength";s:3:"128";s:7:"display";a:1:{s:10:"url_cutoff";s:2:"81";}s:10:"attributes";a:6:{s:6:"target";s:6:"_blank";s:3:"rel";s:8:"nofollow";s:18:"configurable_class";i:0;s:5:"class";s:7:"classes";s:18:"configurable_title";i:1;s:5:"title";s:0:"";}s:10:"rel_remove";s:19:"rel_remove_external";s:13:"enable_tokens";i:1;s:18:"user_register_form";b:0;}s:7:"display";a:1:{s:7:"default";a:5:{s:5:"label";s:5:"above";s:4:"type";s:12:"link_default";s:6:"weight";s:1:"9";s:8:"settings";a:0:{}s:6:"module";s:4:"link";}}s:8:"required";i:0;s:11:"description";s:0:"";s:13:"default_value";N;}',
      'deleted' => '0',
    ))->values(array(
      'id' => '26',
      'field_id' => '16',
      'field_name' => 'field_text_list',
      'entity_type' => 'node',
      'bundle' => 'test_content_type',
      'data' => 'a:7:{s:5:"label";s:9:"Text List";s:6:"widget";a:5:{s:6:"weight";s:2:"11";s:4:"type";s:14:"options_select";s:6:"module";s:7:"options";s:6:"active";i:1;s:8:"settings";a:0:{}}s:8:"settings";a:1:{s:18:"user_register_form";b:0;}s:7:"display";a:1:{s:7:"default";a:5:{s:5:"label";s:5:"above";s:4:"type";s:12:"list_default";s:6:"weight";s:2:"10";s:8:"settings";a:0:{}s:6:"module";s:4:"list";}}s:8:"required";i:0;s:11:"description";s:0:"";s:13:"default_value";N;}',
      'deleted' => '0',
    ))->values(array(
      'id' => '27',
      'field_id' => '17',
      'field_name' => 'field_integer_list',
      'entity_type' => 'node',
      'bundle' => 'test_content_type',
      'data' => 'a:7:{s:5:"label";s:12:"Integer List";s:6:"widget";a:5:{s:6:"weight";s:2:"12";s:4:"type";s:15:"options_buttons";s:6:"module";s:7:"options";s:6:"active";i:1;s:8:"settings";a:0:{}}s:8:"settings";a:1:{s:18:"user_register_form";b:0;}s:7:"display";a:1:{s:7:"default";a:5:{s:5:"label";s:5:"above";s:4:"type";s:12:"list_default";s:6:"weight";s:2:"11";s:8:"settings";a:0:{}s:6:"module";s:4:"list";}}s:8:"required";i:0;s:11:"description";s:0:"";s:13:"default_value";N;}',
      'deleted' => '0',
    ))->values(array(
      'id' => '28',
      'field_id' => '18',
      'field_name' => 'field_long_text',
      'entity_type' => 'node',
      'bundle' => 'test_content_type',
      'data' => 'a:7:{s:5:"label";s:9:"Long text";s:6:"widget";a:5:{s:6:"weight";s:2:"13";s:4:"type";s:26:"text_textarea_with_summary";s:6:"module";s:4:"text";s:6:"active";i:1;s:8:"settings";a:2:{s:4:"rows";s:2:"19";s:12:"summary_rows";i:5;}}s:8:"settings";a:3:{s:15:"text_processing";s:1:"1";s:15:"display_summary";i:0;s:18:"user_register_form";b:0;}s:7:"display";a:1:{s:7:"default";a:5:{s:5:"label";s:5:"above";s:4:"type";s:12:"text_default";s:6:"weight";s:2:"12";s:8:"settings";a:0:{}s:6:"module";s:4:"text";}}s:8:"required";i:0;s:11:"description";s:0:"";s:13:"default_value";N;}',
      'deleted' => '0',
    ))->values(array(
      'id' => '30',
      'field_id' => '20',
      'field_name' => 'field_term_reference',
      'entity_type' => 'node',
      'bundle' => 'test_content_type',
      'data' => 'a:7:{s:5:"label";s:14:"Term Reference";s:6:"widget";a:5:{s:6:"weight";s:2:"14";s:4:"type";s:21:"taxonomy_autocomplete";s:6:"module";s:8:"taxonomy";s:6:"active";i:0;s:8:"settings";a:2:{s:4:"size";i:60;s:17:"autocomplete_path";s:21:"taxonomy/autocomplete";}}s:8:"settings";a:1:{s:18:"user_register_form";b:0;}s:7:"display";a:1:{s:7:"default";a:4:{s:5:"label";s:5:"above";s:4:"type";s:6:"hidden";s:6:"weight";s:2:"13";s:8:"settings";a:0:{}}}s:8:"required";i:0;s:11:"description";s:0:"";s:13:"default_value";N;}',
      'deleted' => '0',
    ))->values(array(
      'id' => '31',
      'field_id' => '21',
      'field_name' => 'field_text',
      'entity_type' => 'node',
      'bundle' => 'test_content_type',
      'data' => 'a:7:{s:5:"label";s:4:"Text";s:6:"widget";a:5:{s:6:"weight";s:2:"15";s:4:"type";s:14:"text_textfield";s:6:"module";s:4:"text";s:6:"active";i:1;s:8:"settings";a:1:{s:4:"size";s:2:"55";}}s:8:"settings";a:2:{s:15:"text_processing";s:1:"0";s:18:"user_register_form";b:0;}s:7:"display";a:1:{s:7:"default";a:4:{s:5:"label";s:5:"above";s:4:"type";s:6:"hidden";s:6:"weight";s:2:"14";s:8:"settings";a:0:{}}}s:8:"required";i:0;s:11:"description";s:0:"";s:13:"default_value";N;}',
      'deleted' => '0',
    ))->values(array(
      'id' => '32',
      'field_id' => '14',
      'field_name' => 'field_integer',
      'entity_type' => 'comment',
      'bundle' => 'comment_node_test_content_type',
      'data' => 'a:7:{s:5:"label";s:7:"Integer";s:6:"widget";a:5:{s:6:"weight";s:1:"2";s:4:"type";s:6:"number";s:6:"module";s:6:"number";s:6:"active";i:0;s:8:"settings";a:0:{}}s:8:"settings";a:5:{s:3:"min";s:0:"";s:3:"max";s:0:"";s:6:"prefix";s:0:"";s:6:"suffix";s:0:"";s:18:"user_register_form";b:0;}s:7:"display";a:1:{s:7:"default";a:5:{s:5:"label";s:5:"above";s:4:"type";s:14:"number_integer";s:8:"settings";a:4:{s:18:"thousand_separator";s:1:" ";s:17:"decimal_separator";s:1:".";s:5:"scale";i:0;s:13:"prefix_suffix";b:1;}s:6:"module";s:6:"number";s:6:"weight";i:1;}}s:8:"required";i:0;s:11:"description";s:0:"";s:13:"default_value";N;}',
      'deleted' => '0',
    ))->values(array(
      'id' => '33',
      'field_id' => '11',
      'field_name' => 'field_file',
      'entity_type' => 'user',
      'bundle' => 'user',
      'data' => 'a:6:{s:5:"label";s:4:"File";s:6:"widget";a:5:{s:6:"weight";s:1:"8";s:4:"type";s:12:"file_generic";s:6:"module";s:4:"file";s:6:"active";i:1;s:8:"settings";a:1:{s:18:"progress_indicator";s:8:"throbber";}}s:8:"settings";a:5:{s:14:"file_directory";s:0:"";s:15:"file_extensions";s:3:"txt";s:12:"max_filesize";s:0:"";s:17:"description_field";i:0;s:18:"user_register_form";i:0;}s:7:"display";a:1:{s:7:"default";a:5:{s:5:"label";s:5:"above";s:4:"type";s:12:"file_default";s:8:"settings";a:0:{}s:6:"module";s:4:"file";s:6:"weight";i:0;}}s:8:"required";i:0;s:11:"description";s:0:"";}',
      'deleted' => '0',
    ))->execute();
  }

}
#edb455536fc9c336da2b6caaa2e5b52f
