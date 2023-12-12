<?php

declare(strict_types=1);

namespace Drupal\Tests\media_library\FunctionalJavascript;

use Drupal\field\Entity\FieldConfig;
use Drupal\media\Entity\Media;
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
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that the widget access works as expected.
   */
  public function testWidgetAccess() {
    $assert_session = $this->assertSession();
    $session = $this->getSession();

    $this->createMediaItems([
      'type_one' => ['Horse', 'Bear'],
    ]);
    $account = $this->drupalCreateUser(['create basic_page content']);
    $this->drupalLogin($account);

    // Assert users can not select media items they do not have access to.
    $unpublished_media = Media::create([
      'name' => 'Mosquito',
      'bundle' => 'type_one',
      'field_media_test' => 'Mosquito',
      'status' => FALSE,
    ]);
    $unpublished_media->save();
    // Visit a node create page.
    $this->drupalGet('node/add/basic_page');
    // Set the hidden value and trigger the mousedown event on the button via
    // JavaScript since the field and button are hidden.
    $session->executeScript("jQuery('[data-media-library-widget-value=\"field_unlimited_media\"]').val('1,2,{$unpublished_media->id()}')");
    $session->executeScript("jQuery('[data-media-library-widget-update=\"field_unlimited_media\"]').trigger('mousedown')");
    $this->assertElementExistsAfterWait('css', '.js-media-library-item');
    // Assert the published items are selected and the unpublished item is not
    // selected.
    $assert_session->pageTextContains('Horse');
    $assert_session->pageTextContains('Bear');
    $assert_session->pageTextNotContains('Mosquito');
    $this->drupalLogout();

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
    $assert_session->elementExists('css', '.js-media-library-view');
    $this->drupalGet('admin/content/media-widget-table', $url_options);
    $assert_session->elementExists('css', '.js-media-library-view');
    $this->drupalGet('media-library', $url_options);
    $assert_session->elementExists('css', '.js-media-library-view');
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
    $assert_session->elementExists('css', '.js-media-library-view');
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

  /**
   * Tests the widget with a required field that the user can't access.
   */
  public function testRequiredFieldNoAccess() {
    // Make field_single_media_type required.
    $fieldConfig = FieldConfig::loadByName('node', 'basic_page', 'field_single_media_type');
    assert($fieldConfig instanceof FieldConfig);
    $fieldConfig->setRequired(TRUE)
      ->save();

    // Deny access to the field.
    \Drupal::state()->set('media_library_test_entity_field_access_deny_fields', ['field_single_media_type']);

    $user = $this->drupalCreateUser([
      'access administration pages',
      'access content',
      'create basic_page content',
      'create type_one media',
      'view media',
    ]);
    $this->drupalLogin($user);
    $this->drupalGet('node/add/basic_page');

    $this->assertSession()->elementNotExists('css', '.field--name-field-single-media-type');

    $this->submitForm([
      'title[0][value]' => $this->randomMachineName(),
    ], 'Save');

    $this->assertSession()->elementNotExists('css', '.messages--error');
    $this->assertSession()->pageTextNotContains('Single media type field is required.');
  }

}
