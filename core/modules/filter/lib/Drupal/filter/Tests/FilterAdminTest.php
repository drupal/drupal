<?php

/**
 * @file
 * Definition of Drupal\filter\Tests\FilterAdminTest.
 */

namespace Drupal\filter\Tests;

use Drupal\simpletest\WebTestBase;

class FilterAdminTest extends WebTestBase {
  protected $profile = 'standard';

  public static function getInfo() {
    return array(
      'name' => 'Filter administration functionality',
      'description' => 'Thoroughly test the administrative interface of the filter module.',
      'group' => 'Filter',
    );
  }

  function setUp() {
    parent::setUp();

    // Create users.
    $filtered_html_format = filter_format_load('filtered_html');
    $full_html_format = filter_format_load('full_html');
    $this->admin_user = $this->drupalCreateUser(array(
      'administer filters',
      filter_permission_name($filtered_html_format),
      filter_permission_name($full_html_format),
    ));

    $this->web_user = $this->drupalCreateUser(array('create page content', 'edit own page content'));
    $this->drupalLogin($this->admin_user);
  }

  function testFormatAdmin() {
    // Add text format.
    $this->drupalGet('admin/config/content/formats');
    $this->clickLink('Add text format');
    $format_id = drupal_strtolower($this->randomName());
    $name = $this->randomName();
    $edit = array(
      'format' => $format_id,
      'name' => $name,
    );
    $this->drupalPost(NULL, $edit, t('Save configuration'));

    // Verify default weight of the text format.
    $this->drupalGet('admin/config/content/formats');
    $this->assertFieldByName("formats[$format_id][weight]", 0, t('Text format weight was saved.'));

    // Change the weight of the text format.
    $edit = array(
      "formats[$format_id][weight]" => 5,
    );
    $this->drupalPost('admin/config/content/formats', $edit, t('Save changes'));
    $this->assertFieldByName("formats[$format_id][weight]", 5, t('Text format weight was saved.'));

    // Edit text format.
    $this->drupalGet('admin/config/content/formats');
    $this->assertLinkByHref('admin/config/content/formats/' . $format_id);
    $this->drupalGet('admin/config/content/formats/' . $format_id);
    $this->drupalPost(NULL, array(), t('Save configuration'));

    // Verify that the custom weight of the text format has been retained.
    $this->drupalGet('admin/config/content/formats');
    $this->assertFieldByName("formats[$format_id][weight]", 5, t('Text format weight was retained.'));

    // Disable text format.
    $this->assertLinkByHref('admin/config/content/formats/' . $format_id . '/disable');
    $this->drupalGet('admin/config/content/formats/' . $format_id . '/disable');
    $this->drupalPost(NULL, array(), t('Disable'));

    // Verify that disabled text format no longer exists.
    $this->drupalGet('admin/config/content/formats/' . $format_id);
    $this->assertResponse(404, t('Disabled text format no longer exists.'));

    // Attempt to create a format of the same machine name as the disabled
    // format but with a different human readable name.
    $edit = array(
      'format' => $format_id,
      'name' => 'New format',
    );
    $this->drupalPost('admin/config/content/formats/add', $edit, t('Save configuration'));
    $this->assertText('The machine-readable name is already in use. It must be unique.');

    // Attempt to create a format of the same human readable name as the
    // disabled format but with a different machine name.
    $edit = array(
      'format' => 'new_format',
      'name' => $name,
    );
    $this->drupalPost('admin/config/content/formats/add', $edit, t('Save configuration'));
    $this->assertRaw(t('Text format names must be unique. A format named %name already exists.', array(
      '%name' => $name,
    )));
  }

  /**
   * Test filter administration functionality.
   */
  function testFilterAdmin() {
    // URL filter.
    $first_filter = 'filter_url';
    // Line filter.
    $second_filter = 'filter_autop';

    $filtered = 'filtered_html';
    $full = 'full_html';
    $plain = 'plain_text';

    // Check that the fallback format exists and cannot be disabled.
    $this->assertTrue($plain == filter_fallback_format(), t('The fallback format is set to plain text.'));
    $this->drupalGet('admin/config/content/formats');
    $this->assertNoRaw('admin/config/content/formats/' . $plain . '/disable', t('Disable link for the fallback format not found.'));
    $this->drupalGet('admin/config/content/formats/' . $plain . '/disable');
    $this->assertResponse(403, t('The fallback format cannot be disabled.'));

    // Verify access permissions to Full HTML format.
    $this->assertTrue(filter_access(filter_format_load($full), $this->admin_user), t('Admin user may use Full HTML.'));
    $this->assertFalse(filter_access(filter_format_load($full), $this->web_user), t('Web user may not use Full HTML.'));

    // Add an additional tag.
    $edit = array();
    $edit['filters[filter_html][settings][allowed_html]'] = '<a> <em> <strong> <cite> <code> <ul> <ol> <li> <dl> <dt> <dd> <quote>';
    $this->drupalPost('admin/config/content/formats/' . $filtered, $edit, t('Save configuration'));
    $this->assertFieldByName('filters[filter_html][settings][allowed_html]', $edit['filters[filter_html][settings][allowed_html]'], t('Allowed HTML tag added.'));

    $result = db_query('SELECT * FROM {cache_filter}')->fetchObject();
    $this->assertFalse($result, t('Cache cleared.'));

    $elements = $this->xpath('//select[@name=:first]/following::select[@name=:second]', array(
      ':first' => 'filters[' . $first_filter . '][weight]',
      ':second' => 'filters[' . $second_filter . '][weight]',
    ));
    $this->assertTrue(!empty($elements), t('Order confirmed in admin interface.'));

    // Reorder filters.
    $edit = array();
    $edit['filters[' . $second_filter . '][weight]'] = 1;
    $edit['filters[' . $first_filter . '][weight]'] = 2;
    $this->drupalPost(NULL, $edit, t('Save configuration'));
    $this->assertFieldByName('filters[' . $second_filter . '][weight]', 1, t('Order saved successfully.'));
    $this->assertFieldByName('filters[' . $first_filter . '][weight]', 2, t('Order saved successfully.'));

    $elements = $this->xpath('//select[@name=:first]/following::select[@name=:second]', array(
      ':first' => 'filters[' . $second_filter . '][weight]',
      ':second' => 'filters[' . $first_filter . '][weight]',
    ));
    $this->assertTrue(!empty($elements), t('Reorder confirmed in admin interface.'));

    $result = db_query('SELECT * FROM {filter} WHERE format = :format ORDER BY weight ASC', array(':format' => $filtered));
    $filters = array();
    foreach ($result as $filter) {
      if ($filter->name == $second_filter || $filter->name == $first_filter) {
        $filters[] = $filter;
      }
    }
    $this->assertTrue(($filters[0]->name == $second_filter && $filters[1]->name == $first_filter), t('Order confirmed in database.'));

    // Add format.
    $edit = array();
    $edit['format'] = drupal_strtolower($this->randomName());
    $edit['name'] = $this->randomName();
    $edit['roles[' . DRUPAL_AUTHENTICATED_RID . ']'] = 1;
    $edit['filters[' . $second_filter . '][status]'] = TRUE;
    $edit['filters[' . $first_filter . '][status]'] = TRUE;
    $this->drupalPost('admin/config/content/formats/add', $edit, t('Save configuration'));
    $this->assertRaw(t('Added text format %format.', array('%format' => $edit['name'])), t('New filter created.'));

    drupal_static_reset('filter_formats');
    $format = filter_format_load($edit['format']);
    $this->assertNotNull($format, t('Format found in database.'));

    $this->assertFieldByName('roles[' . DRUPAL_AUTHENTICATED_RID . ']', '', t('Role found.'));
    $this->assertFieldByName('filters[' . $second_filter . '][status]', '', t('Line break filter found.'));
    $this->assertFieldByName('filters[' . $first_filter . '][status]', '', t('Url filter found.'));

    // Disable new filter.
    $this->drupalPost('admin/config/content/formats/' . $format->format . '/disable', array(), t('Disable'));
    $this->assertRaw(t('Disabled text format %format.', array('%format' => $edit['name'])), t('Format successfully disabled.'));

    // Allow authenticated users on full HTML.
    $format = filter_format_load($full);
    $edit = array();
    $edit['roles[' . DRUPAL_ANONYMOUS_RID . ']'] = 0;
    $edit['roles[' . DRUPAL_AUTHENTICATED_RID . ']'] = 1;
    $this->drupalPost('admin/config/content/formats/' . $full, $edit, t('Save configuration'));
    $this->assertRaw(t('The text format %format has been updated.', array('%format' => $format->name)), t('Full HTML format successfully updated.'));

    // Switch user.
    $this->drupalLogout();
    $this->drupalLogin($this->web_user);

    $this->drupalGet('node/add/page');
    $this->assertRaw('<option value="' . $full . '">Full HTML</option>', t('Full HTML filter accessible.'));

    // Use filtered HTML and see if it removes tags that are not allowed.
    $body = '<em>' . $this->randomName() . '</em>';
    $extra_text = 'text';
    $text = $body . '<random>' . $extra_text . '</random>';

    $edit = array();
    $langcode = LANGUAGE_NOT_SPECIFIED;
    $edit["title"] = $this->randomName();
    $edit["body[$langcode][0][value]"] = $text;
    $edit["body[$langcode][0][format]"] = $filtered;
    $this->drupalPost('node/add/page', $edit, t('Save'));
    $this->assertRaw(t('Basic page %title has been created.', array('%title' => $edit["title"])), t('Filtered node created.'));

    $node = $this->drupalGetNodeByTitle($edit["title"]);
    $this->assertTrue($node, t('Node found in database.'));

    $this->drupalGet('node/' . $node->nid);
    $this->assertRaw($body . $extra_text, t('Filter removed invalid tag.'));

    // Use plain text and see if it escapes all tags, whether allowed or not.
    $edit = array();
    $edit["body[$langcode][0][format]"] = $plain;
    $this->drupalPost('node/' . $node->nid . '/edit', $edit, t('Save'));
    $this->drupalGet('node/' . $node->nid);
    $this->assertText(check_plain($text), t('The "Plain text" text format escapes all HTML tags.'));

    // Switch user.
    $this->drupalLogout();
    $this->drupalLogin($this->admin_user);

    // Clean up.
    // Allowed tags.
    $edit = array();
    $edit['filters[filter_html][settings][allowed_html]'] = '<a> <em> <strong> <cite> <code> <ul> <ol> <li> <dl> <dt> <dd>';
    $this->drupalPost('admin/config/content/formats/' . $filtered, $edit, t('Save configuration'));
    $this->assertFieldByName('filters[filter_html][settings][allowed_html]', $edit['filters[filter_html][settings][allowed_html]'], t('Changes reverted.'));

    // Full HTML.
    $edit = array();
    $edit['roles[' . DRUPAL_AUTHENTICATED_RID . ']'] = FALSE;
    $this->drupalPost('admin/config/content/formats/' . $full, $edit, t('Save configuration'));
    $this->assertRaw(t('The text format %format has been updated.', array('%format' => $format->name)), t('Full HTML format successfully reverted.'));
    $this->assertFieldByName('roles[' . DRUPAL_AUTHENTICATED_RID . ']', $edit['roles[' . DRUPAL_AUTHENTICATED_RID . ']'], t('Changes reverted.'));

    // Filter order.
    $edit = array();
    $edit['filters[' . $second_filter . '][weight]'] = 2;
    $edit['filters[' . $first_filter . '][weight]'] = 1;
    $this->drupalPost('admin/config/content/formats/' . $filtered, $edit, t('Save configuration'));
    $this->assertFieldByName('filters[' . $second_filter . '][weight]', $edit['filters[' . $second_filter . '][weight]'], t('Changes reverted.'));
    $this->assertFieldByName('filters[' . $first_filter . '][weight]', $edit['filters[' . $first_filter . '][weight]'], t('Changes reverted.'));
  }

  /**
   * Tests the URL filter settings form is properly validated.
   */
  function testUrlFilterAdmin() {
    // The form does not save with an invalid filter URL length.
    $edit = array(
      'filters[filter_url][settings][filter_url_length]' => $this->randomName(4),
    );
    $this->drupalPost('admin/config/content/formats/filtered_html', $edit, t('Save configuration'));
    $this->assertNoRaw(t('The text format %format has been updated.', array('%format' => 'Filtered HTML')));
  }
}
