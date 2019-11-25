<?php

namespace Drupal\Tests\media_library\FunctionalJavascript;

use Drupal\media_library\MediaLibraryState;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Tests the media library UI access.
 *
 * @group media_library
 */
class WidgetAccessTest extends MediaLibraryTestBase {

  /**
   * Tests that the widget access works as expected.
   */
  public function testWidgetAccess() {
    $assert_session = $this->assertSession();

    $role = Role::load(RoleInterface::ANONYMOUS_ID);
    $role->revokePermission('view media');
    $role->save();

    // Create a working state.
    $allowed_types = ['type_one', 'type_two', 'type_three', 'type_four'];
    // The opener parameters are not relevant to the test, but the opener
    // expects them to be there or it will deny access.
    $state = MediaLibraryState::create('media_library.opener.field_widget', $allowed_types, 'type_three', 2, [
      'entity_type_id' => 'node',
      'bundle' => 'basic_page',
      'field_name' => 'field_unlimited_media',
    ]);
    $url_options = ['query' => $state->all()];

    // Verify that unprivileged users can't access the widget view.
    $this->drupalGet('admin/content/media-widget', $url_options);
    $assert_session->responseContains('Access denied');
    $this->drupalGet('admin/content/media-widget-table', $url_options);
    $assert_session->responseContains('Access denied');
    $this->drupalGet('media-library', $url_options);
    $assert_session->responseContains('Access denied');

    // Allow users with 'view media' permission to access the media library view
    // and controller. Since we are using the node entity type in the state
    // object, ensure the user also has permission to work with those.
    $this->grantPermissions($role, [
      'create basic_page content',
      'view media',
    ]);
    $this->drupalGet('admin/content/media-widget', $url_options);
    $assert_session->elementExists('css', '.view-media-library');
    $this->drupalGet('admin/content/media-widget-table', $url_options);
    $assert_session->elementExists('css', '.view-media-library');
    $this->drupalGet('media-library', $url_options);
    $assert_session->elementExists('css', '.view-media-library');
    // Assert the user does not have access to the media add form if the user
    // does not have the 'create media' permission.
    $assert_session->fieldNotExists('files[upload][]');

    // Assert users can not access the widget displays of the media library view
    // without a valid media library state.
    $this->drupalGet('admin/content/media-widget');
    $assert_session->responseContains('Access denied');
    $this->drupalGet('admin/content/media-widget-table');
    $assert_session->responseContains('Access denied');
    $this->drupalGet('media-library');
    $assert_session->responseContains('Access denied');

    // Assert users with the 'create media' permission can access the media add
    // form.
    $this->grantPermissions($role, [
      'create media',
    ]);
    $this->drupalGet('media-library', $url_options);
    $assert_session->elementExists('css', '.view-media-library');
    $assert_session->fieldExists('Add files');

    // Assert the media library can not be accessed if the required state
    // parameters are changed without changing the hash.
    $this->drupalGet('media-library', [
      'query' => array_merge($url_options['query'], ['media_library_opener_id' => 'fail']),
    ]);
    $assert_session->responseContains('Access denied');
    $this->drupalGet('media-library', [
      'query' => array_merge($url_options['query'], ['media_library_allowed_types' => ['type_one', 'type_two']]),
    ]);
    $assert_session->responseContains('Access denied');
    $this->drupalGet('media-library', [
      'query' => array_merge($url_options['query'], ['media_library_selected_type' => 'type_one']),
    ]);
    $assert_session->responseContains('Access denied');
    $this->drupalGet('media-library', [
      'query' => array_merge($url_options['query'], ['media_library_remaining' => 3]),
    ]);
    $assert_session->responseContains('Access denied');
    $this->drupalGet('media-library', [
      'query' => array_merge($url_options['query'], ['hash' => 'fail']),
    ]);
    $assert_session->responseContains('Access denied');
  }

}
