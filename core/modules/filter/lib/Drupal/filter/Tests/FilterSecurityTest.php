<?php

/**
 * @file
 * Definition of Drupal\filter\Tests\FilterSecurityTest.
 */

namespace Drupal\filter\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Security tests for missing/vanished text formats or filters.
 */
class FilterSecurityTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Security',
      'description' => 'Test the behavior of check_markup() when a filter or text format vanishes.',
      'group' => 'Filter',
    );
  }

  function setUp() {
    parent::setUp(array('node', 'php', 'filter_test'));

    // Create Basic page node type.
    $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));

    // Create Filtered HTML format.
    $filtered_html_format = array(
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
    );
    $filtered_html_format = (object) $filtered_html_format;
    filter_format_save($filtered_html_format);

    $filtered_html_permission = filter_permission_name($filtered_html_format);
    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, array($filtered_html_permission));

    $this->admin_user = $this->drupalCreateUser(array('administer modules', 'administer filters', 'administer site configuration'));
    $this->drupalLogin($this->admin_user);
  }

  /**
   * Test that filtered content is emptied when an actively used filter module is disabled.
   */
  function testDisableFilterModule() {
    // Create a new node.
    $node = $this->drupalCreateNode(array('promote' => 1));
    $body_raw = $node->body[LANGUAGE_NOT_SPECIFIED][0]['value'];
    $format_id = $node->body[LANGUAGE_NOT_SPECIFIED][0]['format'];
    $this->drupalGet('node/' . $node->nid);
    $this->assertText($body_raw, t('Node body found.'));

    // Enable the filter_test_replace filter.
    $edit = array(
      'filters[filter_test_replace][status]' => 1,
    );
    $this->drupalPost('admin/config/content/formats/' . $format_id, $edit, t('Save configuration'));

    // Verify that filter_test_replace filter replaced the content.
    $this->drupalGet('node/' . $node->nid);
    $this->assertNoText($body_raw, t('Node body not found.'));
    $this->assertText('Filter: Testing filter', t('Testing filter output found.'));

    // Disable the text format entirely.
    $this->drupalPost('admin/config/content/formats/' . $format_id . '/disable', array(), t('Disable'));

    // Verify that the content is empty, because the text format does not exist.
    $this->drupalGet('node/' . $node->nid);
    $this->assertNoText($body_raw, t('Node body not found.'));
  }
}
