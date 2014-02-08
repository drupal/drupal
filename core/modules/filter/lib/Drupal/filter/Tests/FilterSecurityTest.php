<?php

/**
 * @file
 * Definition of Drupal\filter\Tests\FilterSecurityTest.
 */

namespace Drupal\filter\Tests;

use Drupal\Core\Language\Language;
use Drupal\simpletest\WebTestBase;
use Drupal\filter\Plugin\FilterInterface;

/**
 * Security tests for missing/vanished text formats or filters.
 */
class FilterSecurityTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'filter_test');

  /**
   * A user with administrative permissions.
   *
   * @var object
   */
  protected $admin_user;

  public static function getInfo() {
    return array(
      'name' => 'Security',
      'description' => 'Test the behavior of check_markup() when a filter or text format vanishes, or when check_markup() is called in such a way that it is instructed to skip all filters of the "FilterInterface::TYPE_HTML_RESTRICTOR" type.',
      'group' => 'Filter',
    );
  }

  function setUp() {
    parent::setUp();

    // Create Basic page node type.
    $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));

    // Create Filtered HTML format.
    $filtered_html_format = entity_create('filter_format', array(
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
      'filters' => array(
        // Note that the filter_html filter is of the type FilterInterface::TYPE_HTML_RESTRICTOR.
        'filter_html' => array(
          'status' => 1,
        ),
      )
    ));
    $filtered_html_format->save();

    $filtered_html_permission = $filtered_html_format->getPermissionName();
    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, array($filtered_html_permission));

    $this->admin_user = $this->drupalCreateUser(array('administer modules', 'administer filters', 'administer site configuration'));
    $this->drupalLogin($this->admin_user);
  }

  /**
   * Tests removal of filtered content when an active filter is disabled.
   *
   * Tests that filtered content is emptied when an actively used filter module
   * is disabled.
   */
  function testDisableFilterModule() {
    // Create a new node.
    $node = $this->drupalCreateNode(array('promote' => 1));
    $body_raw = $node->body->value;
    $format_id = $node->body->format;
    $this->drupalGet('node/' . $node->id());
    $this->assertText($body_raw, 'Node body found.');

    // Enable the filter_test_replace filter.
    $edit = array(
      'filters[filter_test_replace][status]' => 1,
    );
    $this->drupalPostForm('admin/config/content/formats/manage/' . $format_id, $edit, t('Save configuration'));

    // Verify that filter_test_replace filter replaced the content.
    $this->drupalGet('node/' . $node->id());
    $this->assertNoText($body_raw, 'Node body not found.');
    $this->assertText('Filter: Testing filter', 'Testing filter output found.');

    // Disable the text format entirely.
    $this->drupalPostForm('admin/config/content/formats/manage/' . $format_id . '/disable', array(), t('Disable'));

    // Verify that the content is empty, because the text format does not exist.
    $this->drupalGet('node/' . $node->id());
    $this->assertNoText($body_raw, 'Node body not found.');
  }

  /**
   * Tests that security filters are enforced even when marked to be skipped.
   */
  function testSkipSecurityFilters() {
    $text = "Text with some disallowed tags: <script />, <em><object>unicorn</object></em>, <i><table></i>.";
    $expected_filtered_text = "Text with some disallowed tags: , <em>unicorn</em>, .";
    $this->assertEqual(check_markup($text, 'filtered_html', '', FALSE, array()), $expected_filtered_text, 'Expected filter result.');
    $this->assertEqual(check_markup($text, 'filtered_html', '', FALSE, array(FilterInterface::TYPE_HTML_RESTRICTOR)), $expected_filtered_text, 'Expected filter result, even when trying to disable filters of the FilterInterface::TYPE_HTML_RESTRICTOR type.');
  }
}
