<?php

/**
 * @file
 * Definition of Drupal\forum\Tests\ForumUninstallTest.
 */

namespace Drupal\forum\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests forum module uninstallation.
 */
class ForumUninstallTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('forum');

  public static function getInfo() {
    return array(
      'name' => 'Forum uninstallation',
      'description' => 'Tests forum module uninstallation.',
      'group' => 'Forum',
    );
  }

  /**
   * Tests if forum module uninstallation properly deletes the field.
   */
  function testForumUninstallWithField() {
    // Ensure that the field exists before uninstallation.
    $field = field_info_field('node', 'taxonomy_forums');
    $this->assertNotNull($field, 'The taxonomy_forums field exists.');

    // Uninstall the forum module which should trigger field deletion.
    $this->container->get('module_handler')->uninstall(array('forum'));

    // Check that the field is now deleted.
    $field = field_info_field('node', 'taxonomy_forums');
    $this->assertNull($field, 'The taxonomy_forums field has been deleted.');
  }


  /**
   * Tests if uninstallation succeeds if the field has been deleted beforehand.
   */
  function testForumUninstallWithoutField() {
    // Manually delete the taxonomy_forums field before module uninstallation.
    $field = field_info_field('node', 'taxonomy_forums');
    $this->assertNotNull($field, 'The taxonomy_forums field exists.');
    $field->delete();

    // Check that the field is now deleted.
    $field = field_info_field('node', 'taxonomy_forums');
    $this->assertNull($field, 'The taxonomy_forums field has been deleted.');

    // Ensure that uninstallation succeeds even if the field has already been
    // deleted manually beforehand.
    $this->container->get('module_handler')->uninstall(array('forum'));
  }

}
