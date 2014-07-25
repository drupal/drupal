<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Drupal6NodeType.
 */

namespace Drupal\migrate_drupal\Tests\Dump;
/**
 * Database dump for testing node type migration.
 */
class Drupal6NodeType extends Drupal6DumpBase {

  /**
   * {@inheritdoc}
   */
  public function load() {
    $this->createTable('node_type');
    $this->database->insert('node_type')->fields(
      array(
        'type',
        'name',
        'module',
        'description',
        'help',
        'has_title',
        'title_label',
        'has_body',
        'body_label',
        'min_word_count',
        'custom',
        'modified',
        'locked',
        'orig_type',
      ))
      ->values(array(
        'type' => 'test_page',
        'name' => 'Migrate test page',
        'module' => 'node',
        'description' => "A <em>page</em>, similar in form to a <em>story</em>, is a simple method for creating and displaying information that rarely changes, such as an \"About us\" section of a website. By default, a <em>page</em> entry does not allow visitor comments and is not featured on the site's initial home page.",
        'help' => '',
        'has_title' => 1,
        'title_label' => 'Title',
        'has_body' => 1,
        'body_label' => 'This is the body field label',
        'min_word_count' => 0,
        'custom' => 1,
        'modified' => 1,
        'locked' => 0,
        'orig_type' => 'page',
      ))
      ->values(array(
        'type' => 'test_story',
        'name' => 'Migrate test story',
        'module' => 'node',
        'description' => "A <em>story</em>, similar in form to a <em>page</em>, is ideal for creating and displaying content that informs or engages website visitors. Press releases, site announcements, and informal blog-like entries may all be created with a <em>story</em> entry. By default, a <em>story</em> entry is automatically featured on the site's initial home page, and provides the ability to post comments.",
        'help' => '',
        'has_title' => 1,
        'title_label' => 'Title',
        'has_body' => 0,
        'body_label' => 'Body',
        'min_word_count' => 0,
        'custom' => 1,
        'modified' => 1,
        'locked' => 0,
        'orig_type' => 'story',
      ))
      ->values(array(
        'type' => 'test_event',
        'name' => 'Migrate test event',
        'module' => 'node',
        'description' => "test event description here",
        'help' => 'help text here',
        'has_title' => 1,
        'title_label' => 'Event Name',
        'has_body' => 1,
        'body_label' => 'Body',
        'min_word_count' => 0,
        'custom' => 1,
        'modified' => 1,
        'locked' => 0,
        'orig_type' => 'event',
      ))
      ->execute();

    $this->database->merge('node_type')
      ->key(array('type' => 'story'))
      ->fields(array(
        'name' => 'Story',
        'module' => 'node',
        'description' => "A <em>story</em>, similar in form to a <em>page</em>, is ideal for creating and displaying content that informs or engages website visitors. Press releases, site announcements, and informal blog-like entries may all be created with a <em>story</em> entry. By default, a <em>story</em> entry is automatically featured on the site's initial home page, and provides the ability to post comments.",
        'help' => '',
        'has_title' => '1',
        'title_label' => 'Title',
        'has_body' => '1',
        'body_label' => 'Body',
        'min_word_count' => '0',
        'custom' => '1',
        'modified' => '1',
        'locked' => '0',
        'orig_type' => 'story',
      ))
      ->execute();

    $this->createTable('variable');
    $this->database->insert('variable')->fields(array(
      'name',
      'value',
    ))
    ->values(array(
      'name' => 'node_options_test_page',
      'value' => serialize(array(
        0 => 'status',
        1 => 'promote',
        2 => 'sticky',
      )),
    ))
    ->values(array(
      'name' => 'node_options_test_story',
      'value' => serialize(array(
        0 => 'status',
        1 => 'promote',
      )),
    ))
    ->values(array(
      'name' => 'node_options_test_event',
      'value' => serialize(array(
        0 => 'sticky',
        1 => 'revision',
      )),
    ))
    ->values(array(
      'name' => 'theme_settings',
      'value' => serialize(array(
        'toggle_logo' => 1,
        'toggle_name' => 1,
        'toggle_slogan' => 0,
        'toggle_mission' => 1,
        'toggle_node_user_picture' => 0,
        'toggle_comment_user_picture' => 0,
        'toggle_search' => 0,
        'toggle_favicon' => 1,
        'toggle_primary_links' => 1,
        'toggle_secondary_links' => 1,
        'toggle_node_info_test' => 1,
        'toggle_node_info_something' => 1,
        'default_logo' => 1,
        'logo_path' => '',
        'logo_upload' => '',
        'default_favicon' => 1,
        'favicon_path' => '',
        'favicon_upload' => '',
        'toggle_node_info_test_page' => 1,
        'toggle_node_info_test_story' => 1,
        'toggle_node_info_test_event' => 1,
      )),
    ))
    ->execute();
  }
}
