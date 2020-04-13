<?php

namespace Drupal\Tests\media_library\FunctionalJavascript;

use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Tests that the widget works as expected for anonymous users.
 *
 * @group media_library
 */
class WidgetAnonymousTest extends MediaLibraryTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a few example media items for use in selection.
    $this->createMediaItems([
      'type_one' => [
        'Dog',
      ],
    ]);

    // Allow the anonymous user to create pages and view media.
    $role = Role::load(RoleInterface::ANONYMOUS_ID);
    $this->grantPermissions($role, [
      'access content',
      'create basic_page content',
      'view media',
    ]);
  }

  /**
   * Tests that the widget works as expected for anonymous users.
   */
  public function testWidgetAnonymous() {
    $assert_session = $this->assertSession();

    // Allow the anonymous user to create pages and view media.
    $role = Role::load(RoleInterface::ANONYMOUS_ID);
    $this->grantPermissions($role, [
      'access content',
      'create basic_page content',
      'view media',
    ]);

    // Ensure the widget works as an anonymous user.
    $this->drupalGet('node/add/basic_page');

    // Add to the unlimited cardinality field.
    $this->openMediaLibraryForField('field_unlimited_media');

    // Select the first media item (should be Dog).
    $this->selectMediaItem(0);
    $this->pressInsertSelected('Added one media item.');

    // Ensure that the selection completed successfully.
    $this->waitForText('Dog');

    // Save the form.
    $assert_session->elementExists('css', '.js-media-library-widget-toggle-weight')->click();
    $this->submitForm([
      'title[0][value]' => 'My page',
      'field_unlimited_media[selection][0][weight]' => '0',
    ], 'Save');
    $assert_session->pageTextContains('Basic Page My page has been created');
    $assert_session->pageTextContains('Dog');
  }

}
