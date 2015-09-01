<?php

/**
 * @file
 * Contains \Drupal\filter\Tests\FilterFormatAccessTest.
 */

namespace Drupal\filter\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Access\AccessResult;
use Drupal\simpletest\WebTestBase;

/**
 * Tests access to text formats.
 *
 * @group Access
 * @group filter
 */
class FilterFormatAccessTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['block', 'filter', 'node'];

  /**
   * A user with administrative permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * A user with 'administer filters' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $filterAdminUser;

  /**
   * A user with permission to create and edit own content.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * An object representing an allowed text format.
   *
   * @var object
   */
  protected $allowedFormat;

  /**
   * An object representing a secondary allowed text format.
   *
   * @var object
   */
  protected $secondAllowedFormat;

  /**
   * An object representing a disallowed text format.
   *
   * @var object
   */
  protected $disallowedFormat;

  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));

    // Create a user who can administer text formats, but does not have
    // specific permission to use any of them.
    $this->filterAdminUser = $this->drupalCreateUser(array(
      'administer filters',
      'create page content',
      'edit any page content',
    ));

    // Create three text formats. Two text formats are created for all users so
    // that the drop-down list appears for all tests.
    $this->drupalLogin($this->filterAdminUser);
    $formats = array();
    for ($i = 0; $i < 3; $i++) {
      $edit = array(
        'format' => Unicode::strtolower($this->randomMachineName()),
        'name' => $this->randomMachineName(),
      );
      $this->drupalPostForm('admin/config/content/formats/add', $edit, t('Save configuration'));
      $this->resetFilterCaches();
      $formats[] = entity_load('filter_format', $edit['format']);
    }
    list($this->allowedFormat, $this->secondAllowedFormat, $this->disallowedFormat) = $formats;
    $this->drupalLogout();

    // Create a regular user with access to two of the formats.
    $this->webUser = $this->drupalCreateUser(array(
      'create page content',
      'edit any page content',
      $this->allowedFormat->getPermissionName(),
      $this->secondAllowedFormat->getPermissionName(),
    ));

    // Create an administrative user who has access to use all three formats.
    $this->adminUser = $this->drupalCreateUser(array(
      'administer filters',
      'create page content',
      'edit any page content',
      $this->allowedFormat->getPermissionName(),
      $this->secondAllowedFormat->getPermissionName(),
      $this->disallowedFormat->getPermissionName(),
    ));
    $this->drupalPlaceBlock('local_tasks_block');
  }

  /**
   * Tests the Filter format access permissions functionality.
   */
  function testFormatPermissions() {
    // Make sure that a regular user only has access to the text formats for
    // which they were granted access.
    $fallback_format = entity_load('filter_format', filter_fallback_format());
    $this->assertTrue($this->allowedFormat->access('use', $this->webUser), 'A regular user has access to use a text format they were granted access to.');
    $this->assertEqual(AccessResult::allowed()->addCacheContexts(['user.permissions']), $this->allowedFormat->access('use', $this->webUser, TRUE), 'A regular user has access to use a text format they were granted access to.');
    $this->assertFalse($this->disallowedFormat->access('use', $this->webUser), 'A regular user does not have access to use a text format they were not granted access to.');
    $this->assertEqual(AccessResult::neutral()->cachePerPermissions(), $this->disallowedFormat->access('use', $this->webUser, TRUE), 'A regular user does not have access to use a text format they were not granted access to.');
    $this->assertTrue($fallback_format->access('use', $this->webUser), 'A regular user has access to use the fallback format.');
    $this->assertEqual(AccessResult::allowed(), $fallback_format->access('use', $this->webUser, TRUE), 'A regular user has access to use the fallback format.');

    // Perform similar checks as above, but now against the entire list of
    // available formats for this user.
    $this->assertTrue(in_array($this->allowedFormat->id(), array_keys(filter_formats($this->webUser))), 'The allowed format appears in the list of available formats for a regular user.');
    $this->assertFalse(in_array($this->disallowedFormat->id(), array_keys(filter_formats($this->webUser))), 'The disallowed format does not appear in the list of available formats for a regular user.');
    $this->assertTrue(in_array(filter_fallback_format(), array_keys(filter_formats($this->webUser))), 'The fallback format appears in the list of available formats for a regular user.');

    // Make sure that a regular user only has permission to use the format
    // they were granted access to.
    $this->assertTrue($this->webUser->hasPermission($this->allowedFormat->getPermissionName()), 'A regular user has permission to use the allowed text format.');
    $this->assertFalse($this->webUser->hasPermission($this->disallowedFormat->getPermissionName()), 'A regular user does not have permission to use the disallowed text format.');

    // Make sure that the allowed format appears on the node form and that
    // the disallowed format does not.
    $this->drupalLogin($this->webUser);
    $this->drupalGet('node/add/page');
    $elements = $this->xpath('//select[@name=:name]/option', array(
      ':name' => 'body[0][format]',
      ':option' => $this->allowedFormat->id(),
    ));
    $options = array();
    foreach ($elements as $element) {
      $options[(string) $element['value']] = $element;
    }
    $this->assertTrue(isset($options[$this->allowedFormat->id()]), 'The allowed text format appears as an option when adding a new node.');
    $this->assertFalse(isset($options[$this->disallowedFormat->id()]), 'The disallowed text format does not appear as an option when adding a new node.');
    $this->assertFalse(isset($options[filter_fallback_format()]), 'The fallback format does not appear as an option when adding a new node.');

    // Check regular user access to the filter tips pages.
    $this->drupalGet('filter/tips/' . $this->allowedFormat->id());
    $this->assertResponse(200);
    $this->drupalGet('filter/tips/' . $this->disallowedFormat->id());
    $this->assertResponse(403);
    $this->drupalGet('filter/tips/' . filter_fallback_format());
    $this->assertResponse(200);
    $this->drupalGet('filter/tips/invalid-format');
    $this->assertResponse(404);

    // Check admin user access to the filter tips pages.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('filter/tips/' . $this->allowedFormat->id());
    $this->assertResponse(200);
    $this->drupalGet('filter/tips/' . $this->disallowedFormat->id());
    $this->assertResponse(200);
    $this->drupalGet('filter/tips/' . filter_fallback_format());
    $this->assertResponse(200);
    $this->drupalGet('filter/tips/invalid-format');
    $this->assertResponse(404);
  }

  /**
   * Tests if text format is available to a role.
   */
  function testFormatRoles() {
    // Get the role ID assigned to the regular user.
    $roles = $this->webUser->getRoles(TRUE);
    $rid = $roles[0];

    // Check that this role appears in the list of roles that have access to an
    // allowed text format, but does not appear in the list of roles that have
    // access to a disallowed text format.
    $this->assertTrue(in_array($rid, array_keys(filter_get_roles_by_format($this->allowedFormat))), 'A role which has access to a text format appears in the list of roles that have access to that format.');
    $this->assertFalse(in_array($rid, array_keys(filter_get_roles_by_format($this->disallowedFormat))), 'A role which does not have access to a text format does not appear in the list of roles that have access to that format.');

    // Check that the correct text format appears in the list of formats
    // available to that role.
    $this->assertTrue(in_array($this->allowedFormat->id(), array_keys(filter_get_formats_by_role($rid))), 'A text format which a role has access to appears in the list of formats available to that role.');
    $this->assertFalse(in_array($this->disallowedFormat->id(), array_keys(filter_get_formats_by_role($rid))), 'A text format which a role does not have access to does not appear in the list of formats available to that role.');

    // Check that the fallback format is always allowed.
    $this->assertEqual(filter_get_roles_by_format(entity_load('filter_format', filter_fallback_format())), user_role_names(), 'All roles have access to the fallback format.');
    $this->assertTrue(in_array(filter_fallback_format(), array_keys(filter_get_formats_by_role($rid))), 'The fallback format appears in the list of allowed formats for any role.');
  }

  /**
   * Tests editing a page using a disallowed text format.
   *
   * Verifies that regular users and administrators are able to edit a page, but
   * not allowed to change the fields which use an inaccessible text format.
   * Also verifies that fields which use a text format that does not exist can
   * be edited by administrators only, but that the administrator is forced to
   * choose a new format before saving the page.
   */
  function testFormatWidgetPermissions() {
    $body_value_key = 'body[0][value]';
    $body_format_key = 'body[0][format]';

    // Create node to edit.
    $this->drupalLogin($this->adminUser);
    $edit = array();
    $edit['title[0][value]'] = $this->randomMachineName(8);
    $edit[$body_value_key] = $this->randomMachineName(16);
    $edit[$body_format_key] = $this->disallowedFormat->id();
    $this->drupalPostForm('node/add/page', $edit, t('Save'));
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);

    // Try to edit with a less privileged user.
    $this->drupalLogin($this->webUser);
    $this->drupalGet('node/' . $node->id());
    $this->clickLink(t('Edit'));

    // Verify that body field is read-only and contains replacement value.
    $this->assertFieldByXPath("//textarea[@name='$body_value_key' and @disabled='disabled']", t('This field has been disabled because you do not have sufficient permissions to edit it.'), 'Text format access denied message found.');

    // Verify that title can be changed, but preview displays original body.
    $new_edit = array();
    $new_edit['title[0][value]'] = $this->randomMachineName(8);
    $this->drupalPostForm(NULL, $new_edit, t('Preview'));
    $this->assertText($edit[$body_value_key], 'Old body found in preview.');

    // Save and verify that only the title was changed.
    $this->drupalPostForm('node/' . $node->id() . '/edit', $new_edit, t('Save'));
    $this->assertNoText($edit['title[0][value]'], 'Old title not found.');
    $this->assertText($new_edit['title[0][value]'], 'New title found.');
    $this->assertText($edit[$body_value_key], 'Old body found.');

    // Check that even an administrator with "administer filters" permission
    // cannot edit the body field if they do not have specific permission to
    // use its stored format. (This must be disallowed so that the
    // administrator is never forced to switch the text format to something
    // else.)
    $this->drupalLogin($this->filterAdminUser);
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertFieldByXPath("//textarea[@name='$body_value_key' and @disabled='disabled']", t('This field has been disabled because you do not have sufficient permissions to edit it.'), 'Text format access denied message found.');

    // Disable the text format used above.
    $this->disallowedFormat->disable()->save();
    $this->resetFilterCaches();

    // Log back in as the less privileged user and verify that the body field
    // is still disabled, since the less privileged user should not be able to
    // edit content that does not have an assigned format.
    $this->drupalLogin($this->webUser);
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertFieldByXPath("//textarea[@name='$body_value_key' and @disabled='disabled']", t('This field has been disabled because you do not have sufficient permissions to edit it.'), 'Text format access denied message found.');

    // Log back in as the filter administrator and verify that the body field
    // can be edited.
    $this->drupalLogin($this->filterAdminUser);
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertNoFieldByXPath("//textarea[@name='$body_value_key' and @disabled='disabled']", NULL, 'Text format access denied message not found.');
    $this->assertFieldByXPath("//select[@name='$body_format_key']", NULL, 'Text format selector found.');

    // Verify that trying to save the node without selecting a new text format
    // produces an error message, and does not result in the node being saved.
    $old_title = $new_edit['title[0][value]'];
    $new_title = $this->randomMachineName(8);
    $edit = array();
    $edit['title[0][value]'] = $new_title;
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));
    $this->assertText(t('!name field is required.', array('!name' => t('Text format'))), 'Error message is displayed.');
    $this->drupalGet('node/' . $node->id());
    $this->assertText($old_title, 'Old title found.');
    $this->assertNoText($new_title, 'New title not found.');

    // Now select a new text format and make sure the node can be saved.
    $edit[$body_format_key] = filter_fallback_format();
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));
    $this->assertUrl('node/' . $node->id());
    $this->assertText($new_title, 'New title found.');
    $this->assertNoText($old_title, 'Old title not found.');

    // Switch the text format to a new one, then disable that format and all
    // other formats on the site (leaving only the fallback format).
    $this->drupalLogin($this->adminUser);
    $edit = array($body_format_key => $this->allowedFormat->id());
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));
    $this->assertUrl('node/' . $node->id());
    foreach (filter_formats() as $format) {
      if (!$format->isFallbackFormat()) {
        $format->disable()->save();
      }
    }

    // Since there is now only one available text format, the widget for
    // selecting a text format would normally not display when the content is
    // edited. However, we need to verify that the filter administrator still
    // is forced to make a conscious choice to reassign the text to a different
    // format.
    $this->drupalLogin($this->filterAdminUser);
    $old_title = $new_title;
    $new_title = $this->randomMachineName(8);
    $edit = array();
    $edit['title[0][value]'] = $new_title;
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));
    $this->assertText(t('!name field is required.', array('!name' => t('Text format'))), 'Error message is displayed.');
    $this->drupalGet('node/' . $node->id());
    $this->assertText($old_title, 'Old title found.');
    $this->assertNoText($new_title, 'New title not found.');
    $edit[$body_format_key] = filter_fallback_format();
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));
    $this->assertUrl('node/' . $node->id());
    $this->assertText($new_title, 'New title found.');
    $this->assertNoText($old_title, 'Old title not found.');
  }

  /**
   * Rebuilds text format and permission caches in the thread running the tests.
   */
  protected function resetFilterCaches() {
    filter_formats_reset();
  }
}
