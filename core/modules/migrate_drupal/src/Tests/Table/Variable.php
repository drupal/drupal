<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Variable.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see cores/scripts/dump-database-d6.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table;

use Drupal\migrate_drupal\Tests\Dump\Drupal6DumpBase;

/**
 * Generated file to represent the variable table.
 */
class Variable extends Drupal6DumpBase {

  public function load() {
    $this->createTable("variable", array(
      'primary key' => array(
        'name',
      ),
      'fields' => array(
        'name' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '128',
          'default' => '',
        ),
        'value' => array(
          'type' => 'text',
          'not null' => TRUE,
          'length' => 100,
        ),
      ),
    ));
    $this->database->insert("variable")->fields(array(
      'name',
      'value',
    ))
    ->values(array(
      'name' => 'actions_max_stack',
      'value' => 'i:35;',
    ))->values(array(
      'name' => 'admin_compact_mode',
      'value' => 'b:0;',
    ))->values(array(
      'name' => 'aggregator_allowed_html_tags',
      'value' => 's:70:"<a> <b> <br /> <dd> <dl> <dt> <em> <i> <li> <ol> <p> <strong> <u> <ul>";',
    ))->values(array(
      'name' => 'aggregator_clear',
      'value' => 's:7:"9676800";',
    ))->values(array(
      'name' => 'aggregator_fetcher',
      'value' => 's:10:"aggregator";',
    ))->values(array(
      'name' => 'aggregator_parser',
      'value' => 's:10:"aggregator";',
    ))->values(array(
      'name' => 'aggregator_processors',
      'value' => 'a:1:{i:0;s:10:"aggregator";}',
    ))->values(array(
      'name' => 'aggregator_summary_items',
      'value' => 's:1:"3";',
    ))->values(array(
      'name' => 'aggregator_teaser_length',
      'value' => 's:3:"600";',
    ))->values(array(
      'name' => 'allowed_html_1',
      'value' => 's:61:"<a> <em> <strong> <cite> <code> <ul> <ol> <li> <dl> <dt> <dd>";',
    ))->values(array(
      'name' => 'allow_insecure_uploads',
      'value' => 'i:1;',
    ))->values(array(
      'name' => 'anonymous',
      'value' => 's:5:"Guest";',
    ))->values(array(
      'name' => 'book_allowed_types',
      'value' => 'a:1:{i:0;s:4:"book";}',
    ))->values(array(
      'name' => 'book_block_mode',
      'value' => 's:9:"all pages";',
    ))->values(array(
      'name' => 'book_child_type',
      'value' => 's:4:"book";',
    ))->values(array(
      'name' => 'cache',
      'value' => 'i:1;',
    ))->values(array(
      'name' => 'cache_lifetime',
      'value' => 'i:0;',
    ))->values(array(
      'name' => 'comment_anonymous_article',
      'value' => 'i:0;',
    ))->values(array(
      'name' => 'comment_anonymous_page',
      'value' => 'i:0;',
    ))->values(array(
      'name' => 'comment_anonymous_story',
      'value' => 'i:1;',
    ))->values(array(
      'name' => 'comment_article',
      'value' => 's:1:"2";',
    ))->values(array(
      'name' => 'comment_controls_article',
      'value' => 's:1:"3";',
    ))->values(array(
      'name' => 'comment_controls_page',
      'value' => 's:1:"3";',
    ))->values(array(
      'name' => 'comment_controls_story',
      'value' => 's:1:"3";',
    ))->values(array(
      'name' => 'comment_default_mode_article',
      'value' => 's:1:"4";',
    ))->values(array(
      'name' => 'comment_default_mode_page',
      'value' => 's:1:"4";',
    ))->values(array(
      'name' => 'comment_default_mode_story',
      'value' => 's:1:"2";',
    ))->values(array(
      'name' => 'comment_default_order_article',
      'value' => 's:1:"1";',
    ))->values(array(
      'name' => 'comment_default_order_page',
      'value' => 's:1:"1";',
    ))->values(array(
      'name' => 'comment_default_order_story',
      'value' => 's:1:"1";',
    ))->values(array(
      'name' => 'comment_default_per_page_article',
      'value' => 's:2:"50";',
    ))->values(array(
      'name' => 'comment_default_per_page_page',
      'value' => 's:2:"50";',
    ))->values(array(
      'name' => 'comment_default_per_page_story',
      'value' => 's:2:"70";',
    ))->values(array(
      'name' => 'comment_form_location_article',
      'value' => 's:1:"0";',
    ))->values(array(
      'name' => 'comment_form_location_page',
      'value' => 's:1:"0";',
    ))->values(array(
      'name' => 'comment_form_location_story',
      'value' => 's:1:"0";',
    ))->values(array(
      'name' => 'comment_page',
      'value' => 's:1:"0";',
    ))->values(array(
      'name' => 'comment_preview_article',
      'value' => 's:1:"1";',
    ))->values(array(
      'name' => 'comment_preview_page',
      'value' => 's:1:"1";',
    ))->values(array(
      'name' => 'comment_preview_story',
      'value' => 's:1:"0";',
    ))->values(array(
      'name' => 'comment_story',
      'value' => 's:1:"2";',
    ))->values(array(
      'name' => 'comment_subject_field_article',
      'value' => 's:1:"1";',
    ))->values(array(
      'name' => 'comment_subject_field_page',
      'value' => 's:1:"1";',
    ))->values(array(
      'name' => 'comment_subject_field_story',
      'value' => 's:1:"0";',
    ))->values(array(
      'name' => 'contact_default_status',
      'value' => 'i:1;',
    ))->values(array(
      'name' => 'contact_hourly_threshold',
      'value' => 'i:3;',
    ))->values(array(
      'name' => 'content_extra_weights_story',
      'value' => 'a:9:{s:5:"title";s:2:"-5";s:10:"body_field";s:2:"-2";s:20:"revision_information";s:2:"19";s:6:"author";s:2:"18";s:7:"options";s:2:"20";s:16:"comment_settings";s:2:"22";s:4:"menu";s:2:"-3";s:8:"taxonomy";s:2:"-4";s:11:"attachments";s:2:"21";}',
    ))->values(array(
      'name' => 'content_extra_weights_test_page',
      'value' => 'a:8:{s:5:"title";s:2:"37";s:10:"body_field";s:2:"38";s:20:"revision_information";s:2:"40";s:6:"author";s:2:"39";s:7:"options";s:2:"41";s:16:"comment_settings";s:2:"42";s:4:"menu";s:2:"36";s:11:"attachments";s:2:"43";}',
    ))->values(array(
      'name' => 'content_schema_version',
      'value' => 's:4:"6010";',
    ))->values(array(
      'name' => 'cron_threshold_error',
      'value' => 'i:1209600;',
    ))->values(array(
      'name' => 'cron_threshold_warning',
      'value' => 'i:172800;',
    ))->values(array(
      'name' => 'css_js_query_string',
      'value' => 's:20:"AkMTxRZndiw700000000";',
    ))->values(array(
      'name' => 'date:story:4:field_test_datestamp_fromto',
      'value' => 's:4:"both";',
    ))->values(array(
      'name' => 'date:story:4:field_test_datestamp_multiple_from',
      'value' => 's:0:"";',
    ))->values(array(
      'name' => 'date:story:4:field_test_datestamp_multiple_number',
      'value' => 's:0:"";',
    ))->values(array(
      'name' => 'date:story:4:field_test_datestamp_multiple_to',
      'value' => 's:0:"";',
    ))->values(array(
      'name' => 'date:story:4:field_test_datestamp_show_repeat_rule',
      'value' => 's:4:"show";',
    ))->values(array(
      'name' => 'date:story:4:field_test_datetime_fromto',
      'value' => 's:4:"both";',
    ))->values(array(
      'name' => 'date:story:4:field_test_datetime_multiple_from',
      'value' => 's:0:"";',
    ))->values(array(
      'name' => 'date:story:4:field_test_datetime_multiple_number',
      'value' => 's:0:"";',
    ))->values(array(
      'name' => 'date:story:4:field_test_datetime_multiple_to',
      'value' => 's:0:"";',
    ))->values(array(
      'name' => 'date:story:4:field_test_datetime_show_repeat_rule',
      'value' => 's:4:"show";',
    ))->values(array(
      'name' => 'date:story:4:field_test_date_fromto',
      'value' => 's:4:"both";',
    ))->values(array(
      'name' => 'date:story:4:field_test_date_multiple_from',
      'value' => 's:0:"";',
    ))->values(array(
      'name' => 'date:story:4:field_test_date_multiple_number',
      'value' => 's:0:"";',
    ))->values(array(
      'name' => 'date:story:4:field_test_date_multiple_to',
      'value' => 's:0:"";',
    ))->values(array(
      'name' => 'date:story:4:field_test_date_show_repeat_rule',
      'value' => 's:4:"show";',
    ))->values(array(
      'name' => 'date:story:5:field_test_datestamp_fromto',
      'value' => 's:4:"both";',
    ))->values(array(
      'name' => 'date:story:5:field_test_datestamp_multiple_from',
      'value' => 's:0:"";',
    ))->values(array(
      'name' => 'date:story:5:field_test_datestamp_multiple_number',
      'value' => 's:0:"";',
    ))->values(array(
      'name' => 'date:story:5:field_test_datestamp_multiple_to',
      'value' => 's:0:"";',
    ))->values(array(
      'name' => 'date:story:5:field_test_datestamp_show_repeat_rule',
      'value' => 's:4:"show";',
    ))->values(array(
      'name' => 'date:story:5:field_test_datetime_fromto',
      'value' => 's:4:"both";',
    ))->values(array(
      'name' => 'date:story:5:field_test_datetime_multiple_from',
      'value' => 's:0:"";',
    ))->values(array(
      'name' => 'date:story:5:field_test_datetime_multiple_number',
      'value' => 's:0:"";',
    ))->values(array(
      'name' => 'date:story:5:field_test_datetime_multiple_to',
      'value' => 's:0:"";',
    ))->values(array(
      'name' => 'date:story:5:field_test_datetime_show_repeat_rule',
      'value' => 's:4:"show";',
    ))->values(array(
      'name' => 'date:story:5:field_test_date_fromto',
      'value' => 's:4:"both";',
    ))->values(array(
      'name' => 'date:story:5:field_test_date_multiple_from',
      'value' => 's:0:"";',
    ))->values(array(
      'name' => 'date:story:5:field_test_date_multiple_number',
      'value' => 's:0:"";',
    ))->values(array(
      'name' => 'date:story:5:field_test_date_multiple_to',
      'value' => 's:0:"";',
    ))->values(array(
      'name' => 'date:story:5:field_test_date_show_repeat_rule',
      'value' => 's:4:"show";',
    ))->values(array(
      'name' => 'date:story:full:field_test_datestamp_fromto',
      'value' => 's:4:"both";',
    ))->values(array(
      'name' => 'date:story:full:field_test_datestamp_multiple_from',
      'value' => 's:0:"";',
    ))->values(array(
      'name' => 'date:story:full:field_test_datestamp_multiple_number',
      'value' => 's:0:"";',
    ))->values(array(
      'name' => 'date:story:full:field_test_datestamp_multiple_to',
      'value' => 's:0:"";',
    ))->values(array(
      'name' => 'date:story:full:field_test_datestamp_show_repeat_rule',
      'value' => 's:4:"show";',
    ))->values(array(
      'name' => 'date:story:full:field_test_datetime_fromto',
      'value' => 's:4:"both";',
    ))->values(array(
      'name' => 'date:story:full:field_test_datetime_multiple_from',
      'value' => 's:0:"";',
    ))->values(array(
      'name' => 'date:story:full:field_test_datetime_multiple_number',
      'value' => 's:0:"";',
    ))->values(array(
      'name' => 'date:story:full:field_test_datetime_multiple_to',
      'value' => 's:0:"";',
    ))->values(array(
      'name' => 'date:story:full:field_test_datetime_show_repeat_rule',
      'value' => 's:4:"show";',
    ))->values(array(
      'name' => 'date:story:full:field_test_date_fromto',
      'value' => 's:4:"both";',
    ))->values(array(
      'name' => 'date:story:full:field_test_date_multiple_from',
      'value' => 's:0:"";',
    ))->values(array(
      'name' => 'date:story:full:field_test_date_multiple_number',
      'value' => 's:0:"";',
    ))->values(array(
      'name' => 'date:story:full:field_test_date_multiple_to',
      'value' => 's:0:"";',
    ))->values(array(
      'name' => 'date:story:full:field_test_date_show_repeat_rule',
      'value' => 's:4:"show";',
    ))->values(array(
      'name' => 'date:story:teaser:field_test_datestamp_fromto',
      'value' => 's:4:"both";',
    ))->values(array(
      'name' => 'date:story:teaser:field_test_datestamp_multiple_from',
      'value' => 's:0:"";',
    ))->values(array(
      'name' => 'date:story:teaser:field_test_datestamp_multiple_number',
      'value' => 's:0:"";',
    ))->values(array(
      'name' => 'date:story:teaser:field_test_datestamp_multiple_to',
      'value' => 's:0:"";',
    ))->values(array(
      'name' => 'date:story:teaser:field_test_datestamp_show_repeat_rule',
      'value' => 's:4:"show";',
    ))->values(array(
      'name' => 'date:story:teaser:field_test_datetime_fromto',
      'value' => 's:4:"both";',
    ))->values(array(
      'name' => 'date:story:teaser:field_test_datetime_multiple_from',
      'value' => 's:0:"";',
    ))->values(array(
      'name' => 'date:story:teaser:field_test_datetime_multiple_number',
      'value' => 's:0:"";',
    ))->values(array(
      'name' => 'date:story:teaser:field_test_datetime_multiple_to',
      'value' => 's:0:"";',
    ))->values(array(
      'name' => 'date:story:teaser:field_test_datetime_show_repeat_rule',
      'value' => 's:4:"show";',
    ))->values(array(
      'name' => 'date:story:teaser:field_test_date_fromto',
      'value' => 's:4:"both";',
    ))->values(array(
      'name' => 'date:story:teaser:field_test_date_multiple_from',
      'value' => 's:0:"";',
    ))->values(array(
      'name' => 'date:story:teaser:field_test_date_multiple_number',
      'value' => 's:0:"";',
    ))->values(array(
      'name' => 'date:story:teaser:field_test_date_multiple_to',
      'value' => 's:0:"";',
    ))->values(array(
      'name' => 'date:story:teaser:field_test_date_show_repeat_rule',
      'value' => 's:4:"show";',
    ))->values(array(
      'name' => 'date_api_version',
      'value' => 's:3:"5.2";',
    ))->values(array(
      'name' => 'date_format_long',
      'value' => 's:24:"\L\O\N\G l, F j, Y - H:i";',
    ))->values(array(
      'name' => 'date_format_medium',
      'value' => 's:27:"\M\E\D\I\U\M D, m/d/Y - H:i";',
    ))->values(array(
      'name' => 'date_format_short',
      'value' => 's:22:"\S\H\O\R\T m/d/Y - H:i";',
    ))->values(array(
      'name' => 'dblog_row_limit',
      'value' => 'i:1000;',
    ))->values(array(
      'name' => 'drupal_http_request_fails',
      'value' => 'b:0;',
    ))->values(array(
      'name' => 'drupal_private_key',
      'value' => 's:43:"6bTz0JLHTM1R1c7VtbZtbio47JygBoNuGuzS5G0JYWs";',
    ))->values(array(
      'name' => 'error_level',
      'value' => 'i:1;',
    ))->values(array(
      'name' => 'event_nodeapi_event',
      'value' => 's:3:"all";',
    ))->values(array(
      'name' => 'feed_default_items',
      'value' => 'i:10;',
    ))->values(array(
      'name' => 'feed_item_length',
      'value' => 's:5:"title";',
    ))->values(array(
      'name' => 'file_description_length',
      'value' => 'i:128;',
    ))->values(array(
      'name' => 'file_description_type',
      'value' => 's:9:"textfield";',
    ))->values(array(
      'name' => 'file_directory_path',
      'value' => 's:29:"core/modules/simpletest/files";',
    ))->values(array(
      'name' => 'file_directory_temp',
      'value' => 's:10:"files/temp";',
    ))->values(array(
      'name' => 'file_downloads',
      'value' => 's:1:"1";',
    ))->values(array(
      'name' => 'file_icon_directory',
      'value' => 's:25:"sites/default/files/icons";',
    ))->values(array(
      'name' => 'filter_allowed_protocols',
      'value' => 'a:13:{i:0;s:4:"http";i:1;s:5:"https";i:2;s:3:"ftp";i:3;s:4:"news";i:4;s:4:"nntp";i:5;s:3:"tel";i:6;s:6:"telnet";i:7;s:6:"mailto";i:8;s:3:"irc";i:9;s:3:"ssh";i:10;s:4:"sftp";i:11;s:6:"webcal";i:12;s:4:"rtsp";}',
    ))->values(array(
      'name' => 'filter_html_1',
      'value' => 'i:1;',
    ))->values(array(
      'name' => 'filter_html_help_1',
      'value' => 'i:1;',
    ))->values(array(
      'name' => 'filter_html_nofollow_1',
      'value' => 'i:0;',
    ))->values(array(
      'name' => 'filter_url_length_1',
      'value' => 's:2:"72";',
    ))->values(array(
      'name' => 'form_build_id_article',
      'value' => 's:48:"form-uujdfNoFjx9PnvEkSoFnanxF1waDx_AikUQLRTGGdvQ";',
    ))->values(array(
      'name' => 'forum_block_num_0',
      'value' => 's:1:"5";',
    ))->values(array(
      'name' => 'forum_block_num_1',
      'value' => 's:1:"5";',
    ))->values(array(
      'name' => 'forum_hot_topic',
      'value' => 's:2:"15";',
    ))->values(array(
      'name' => 'forum_nav_vocabulary',
      'value' => 's:1:"1";',
    ))->values(array(
      'name' => 'forum_order',
      'value' => 's:1:"1";',
    ))->values(array(
      'name' => 'forum_per_page',
      'value' => 's:2:"25";',
    ))->values(array(
      'name' => 'image_jpeg_quality',
      'value' => 'i:75;',
    ))->values(array(
      'name' => 'image_toolkit',
      'value' => 's:2:"gd";',
    ))->values(array(
      'name' => 'javascript_parsed',
      'value' => 'a:0:{}',
    ))->values(array(
      'name' => 'locale_cache_strings',
      'value' => 'i:1;',
    ))->values(array(
      'name' => 'locale_js_directory',
      'value' => 's:9:"languages";',
    ))->values(array(
      'name' => 'menu_expanded',
      'value' => 'a:1:{i:0;s:15:"secondary-links";}',
    ))->values(array(
      'name' => 'menu_masks',
      'value' => 'a:22:{i:0;i:127;i:1;i:63;i:2;i:62;i:3;i:61;i:4;i:59;i:5;i:31;i:6;i:30;i:7;i:29;i:8;i:24;i:9;i:21;i:10;i:15;i:11;i:14;i:12;i:13;i:13;i:12;i:14;i:11;i:15;i:7;i:16;i:6;i:17;i:5;i:18;i:4;i:19;i:3;i:20;i:2;i:21;i:1;}',
    ))->values(array(
      'name' => 'menu_override_parent_selector',
      'value' => 'b:0;',
    ))->values(array(
      'name' => 'minimum_word_size',
      'value' => 's:1:"3";',
    ))->values(array(
      'name' => 'node_admin_theme',
      'value' => 'i:0;',
    ))->values(array(
      'name' => 'node_options_article',
      'value' => 'a:1:{i:0;s:7:"promote";}',
    ))->values(array(
      'name' => 'node_options_book',
      'value' => 'a:1:{i:0;s:6:"status";}',
    ))->values(array(
      'name' => 'node_options_forum',
      'value' => 'a:1:{i:0;s:6:"status";}',
    ))->values(array(
      'name' => 'node_options_test_event',
      'value' => 'a:2:{i:0;s:6:"sticky";i:1;s:8:"revision";}',
    ))->values(array(
      'name' => 'node_options_test_page',
      'value' => 'a:3:{i:0;s:6:"status";i:1;s:7:"promote";i:2;s:6:"sticky";}',
    ))->values(array(
      'name' => 'node_options_test_planet',
      'value' => 'a:2:{i:0;s:6:"sticky";i:1;s:8:"revision";}',
    ))->values(array(
      'name' => 'node_options_test_story',
      'value' => 'a:2:{i:0;s:6:"status";i:1;s:7:"promote";}',
    ))->values(array(
      'name' => 'node_preview',
      'value' => 'i:0;',
    ))->values(array(
      'name' => 'node_rank_comments',
      'value' => 's:1:"5";',
    ))->values(array(
      'name' => 'node_rank_promote',
      'value' => 's:1:"0";',
    ))->values(array(
      'name' => 'node_rank_recent',
      'value' => 's:1:"0";',
    ))->values(array(
      'name' => 'node_rank_relevance',
      'value' => 's:1:"2";',
    ))->values(array(
      'name' => 'node_rank_sticky',
      'value' => 's:1:"8";',
    ))->values(array(
      'name' => 'node_rank_views',
      'value' => 's:1:"1";',
    ))->values(array(
      'name' => 'overlap_cjk',
      'value' => 'i:1;',
    ))->values(array(
      'name' => 'page_compression',
      'value' => 's:1:"1";',
    ))->values(array(
      'name' => 'preprocess_css',
      'value' => 'i:0;',
    ))->values(array(
      'name' => 'preprocess_js',
      'value' => 'i:0;',
    ))->values(array(
      'name' => 'search_cron_limit',
      'value' => 's:3:"100";',
    ))->values(array(
      'name' => 'simpletest_clear_results',
      'value' => 'b:1;',
    ))->values(array(
      'name' => 'simpletest_httpauth_method',
      'value' => 'i:1;',
    ))->values(array(
      'name' => 'simpletest_httpauth_password',
      'value' => 'N;',
    ))->values(array(
      'name' => 'simpletest_httpauth_username',
      'value' => 'N;',
    ))->values(array(
      'name' => 'simpletest_verbose',
      'value' => 'b:1;',
    ))->values(array(
      'name' => 'site_403',
      'value' => 's:4:"user";',
    ))->values(array(
      'name' => 'site_404',
      'value' => 's:14:"page-not-found";',
    ))->values(array(
      'name' => 'site_frontpage',
      'value' => 's:4:"node";',
    ))->values(array(
      'name' => 'site_mail',
      'value' => 's:21:"site_mail@example.com";',
    ))->values(array(
      'name' => 'site_name',
      'value' => 's:9:"site_name";',
    ))->values(array(
      'name' => 'site_offline',
      'value' => 'i:0;',
    ))->values(array(
      'name' => 'site_offline_message',
      'value' => 's:94:"Drupal is currently under maintenance. We should be back shortly. Thank you for your patience.";',
    ))->values(array(
      'name' => 'site_slogan',
      'value' => 's:13:"Migrate rocks";',
    ))->values(array(
      'name' => 'statistics_block_top_all_num',
      'value' => 'i:0;',
    ))->values(array(
      'name' => 'statistics_block_top_day_num',
      'value' => 'i:0;',
    ))->values(array(
      'name' => 'statistics_block_top_last_num',
      'value' => 'i:0;',
    ))->values(array(
      'name' => 'statistics_count_content_views',
      'value' => 'i:0;',
    ))->values(array(
      'name' => 'statistics_enable_access_log',
      'value' => 'i:0;',
    ))->values(array(
      'name' => 'statistics_flush_accesslog_timer',
      'value' => 'i:259200;',
    ))->values(array(
      'name' => 'syslog_facility',
      'value' => 'i:128;',
    ))->values(array(
      'name' => 'syslog_identity',
      'value' => 's:6:"drupal";',
    ))->values(array(
      'name' => 'taxonomy_override_selector',
      'value' => 'b:0;',
    ))->values(array(
      'name' => 'taxonomy_terms_per_page_admin',
      'value' => 'i:100;',
    ))->values(array(
      'name' => 'teaser_length',
      'value' => 'i:456;',
    ))->values(array(
      'name' => 'theme_default',
      'value' => 's:7:"garland";',
    ))->values(array(
      'name' => 'theme_settings',
      'value' => 'a:22:{s:11:"toggle_logo";i:1;s:11:"toggle_name";i:1;s:13:"toggle_slogan";i:0;s:14:"toggle_mission";i:1;s:24:"toggle_node_user_picture";i:0;s:27:"toggle_comment_user_picture";i:0;s:13:"toggle_search";i:0;s:14:"toggle_favicon";i:1;s:20:"toggle_primary_links";i:1;s:22:"toggle_secondary_links";i:1;s:21:"toggle_node_info_test";i:1;s:26:"toggle_node_info_something";i:1;s:12:"default_logo";i:1;s:9:"logo_path";s:0:"";s:11:"logo_upload";s:0:"";s:15:"default_favicon";i:1;s:12:"favicon_path";s:0:"";s:14:"favicon_upload";s:0:"";s:26:"toggle_node_info_test_page";i:1;s:27:"toggle_node_info_test_story";i:1;s:27:"toggle_node_info_test_event";i:1;s:28:"toggle_node_info_test_planet";i:1;}',
    ))->values(array(
      'name' => 'update_check_frequency',
      'value' => 's:1:"7";',
    ))->values(array(
      'name' => 'update_fetch_url',
      'value' => 's:41:"http://updates.drupal.org/release-history";',
    ))->values(array(
      'name' => 'update_max_fetch_attempts',
      'value' => 'i:2;',
    ))->values(array(
      'name' => 'update_notification_threshold',
      'value' => 's:3:"all";',
    ))->values(array(
      'name' => 'update_notify_emails',
      'value' => 'a:0:{}',
    ))->values(array(
      'name' => 'upload_article',
      'value' => 'b:0;',
    ))->values(array(
      'name' => 'upload_page',
      'value' => 'b:1;',
    ))->values(array(
      'name' => 'upload_story',
      'value' => 'b:1;',
    ))->values(array(
      'name' => 'user_email_verification',
      'value' => 'i:0;',
    ))->values(array(
      'name' => 'user_mail_password_reset_body',
      'value' => "s:409:\"!username,\n\nA request to reset the password for your account has been made at !site.\n\nYou may now log in to !uri_brief by clicking on this link or copying and pasting it in your browser:\n\n!login_url\n\nThis is a one-time login, so it can be used only once. It expires after one day and nothing will happen if it's not used.\n\nAfter logging in, you will be redirected to !edit_uri so you can change your password.\";",
    ))->values(array(
      'name' => 'user_mail_password_reset_subject',
      'value' => 's:52:"Replacement login information for !username at !site";',
    ))->values(array(
      'name' => 'user_mail_register_admin_created_body',
      'value' => "s:452:\"!username,\n\nA site administrator at !site has created an account for you. You may now log in to !login_uri using the following username and password:\n\nusername: !username\npassword: !password\n\nYou may also log in by clicking on this link or copying and pasting it in your browser:\n\n!login_url\n\nThis is a one-time login, so it can be used only once.\n\nAfter logging in, you will be redirected to !edit_uri so you can change your password.\n\n\n--  !site team\";",
    ))->values(array(
      'name' => 'user_mail_register_admin_created_subject',
      'value' => 's:52:"An administrator created an account for you at !site";',
    ))->values(array(
      'name' => 'user_mail_register_no_approval_required_body',
      'value' => "s:426:\"!username,\n\nThank you for registering at !site. You may now log in to !login_uri using the following username and password:\n\nusername: !username\npassword: !password\n\nYou may also log in by clicking on this link or copying and pasting it in your browser:\n\n!login_url\n\nThis is a one-time login, so it can be used only once.\n\nAfter logging in, you will be redirected to !edit_uri so you can change your password.\n\n\n--  !site team\";",
    ))->values(array(
      'name' => 'user_mail_register_no_approval_required_subject',
      'value' => 's:38:"Account details for !username at !site";',
    ))->values(array(
      'name' => 'user_mail_status_activated_body',
      'value' => "s:419:\"!username,\n\nYour account at !site has been activated.\n\nYou may now log in by clicking on this link or copying and pasting it in your browser:\n\n!login_url\n\nThis is a one-time login, so it can be used only once.\n\nAfter logging in, you will be redirected to !edit_uri so you can change your password.\n\nOnce you have set your own password, you will be able to log in to !login_uri in the future using:\n\nusername: !username\n\";",
    ))->values(array(
      'name' => 'user_mail_status_activated_notify',
      'value' => 'i:0;',
    ))->values(array(
      'name' => 'user_mail_status_activated_subject',
      'value' => 's:49:"Account details for !username at !site (approved)";',
    ))->values(array(
      'name' => 'user_mail_status_blocked_body',
      'value' => "s:51:\"!username,\n\nYour account on !site has been blocked.\";",
    ))->values(array(
      'name' => 'user_mail_status_blocked_notify',
      'value' => 'i:1;',
    ))->values(array(
      'name' => 'user_mail_status_blocked_subject',
      'value' => 's:48:"Account details for !username at !site (blocked)";',
    ))->values(array(
      'name' => 'user_mail_status_deleted_body',
      'value' => "s:51:\"!username,\n\nYour account on !site has been deleted.\";",
    ))->values(array(
      'name' => 'user_mail_status_deleted_subject',
      'value' => 's:48:"Account details for !username at !site (deleted)";',
    ))->values(array(
      'name' => 'user_mail_user_mail_register_pending_approval_body',
      'value' => "s:267:\"!username,\n\nThank you for registering at !site. Your application for an account is currently pending approval. Once it has been approved, you will receive another email containing information about how to log in, set your password, and other details.\n\n\n--  !site team\";",
    ))->values(array(
      'name' => 'user_mail_user_mail_register_pending_approval_subject',
      'value' => 's:63:"Account details for !username at !site (pending admin approval)";',
    ))->values(array(
      'name' => 'user_register',
      'value' => 'i:0;',
    ))->values(array(
      'name' => 'user_signatures',
      'value' => 's:1:"1";',
    ))->execute();
  }

}
