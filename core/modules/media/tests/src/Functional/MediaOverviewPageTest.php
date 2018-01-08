<?php

namespace Drupal\Tests\media\Functional;

use Drupal\media\Entity\Media;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Tests the Media overview page.
 *
 * @group media
 */
class MediaOverviewPageTest extends MediaFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalLogin($this->nonAdminUser);
  }

  /**
   * Test that the Media overview page (/admin/content/media).
   */
  public function testMediaOverviewPage() {
    $assert_session = $this->assertSession();

    // Check the view exists, is access-restricted, and some defaults are there.
    $this->drupalGet('/admin/content/media');
    $assert_session->statusCodeEquals(403);
    $role = Role::load(RoleInterface::AUTHENTICATED_ID);
    $this->grantPermissions($role, ['access media overview']);
    $this->drupalGet('/admin/content/media');
    $assert_session->statusCodeEquals(200);
    $assert_session->titleEquals('Media | Drupal');
    $assert_session->fieldExists('Media name');
    $assert_session->selectExists('type');
    $assert_session->selectExists('status');
    $assert_session->selectExists('langcode');
    $assert_session->buttonExists('Filter');
    $header = $assert_session->elementExists('css', 'th#view-thumbnail-target-id-table-column');
    $this->assertEquals('Thumbnail', $header->getText());
    $header = $assert_session->elementExists('css', 'th#view-name-table-column');
    $this->assertEquals('Media name', $header->getText());
    $header = $assert_session->elementExists('css', 'th#view-bundle-table-column');
    $this->assertEquals('Type', $header->getText());
    $header = $assert_session->elementExists('css', 'th#view-uid-table-column');
    $this->assertEquals('Author', $header->getText());
    $header = $assert_session->elementExists('css', 'th#view-status-table-column');
    $this->assertEquals('Status', $header->getText());
    $header = $assert_session->elementExists('css', 'th#view-changed-table-column');
    $this->assertEquals('Updated Sort ascending', $header->getText());
    $header = $assert_session->elementExists('css', 'th#view-operations-table-column');
    $this->assertEquals('Operations', $header->getText());
    $assert_session->pageTextContains('No content available.');

    // Create some content for the view.
    $media_type1 = $this->createMediaType();
    $media_type2 = $this->createMediaType();
    $media1 = Media::create([
      'bundle' => $media_type1->id(),
      'name' => 'Media 1',
      'uid' => $this->adminUser->id(),
    ]);
    $media1->save();
    $media2 = Media::create([
      'bundle' => $media_type2->id(),
      'name' => 'Media 2',
      'uid' => $this->adminUser->id(),
      'status' => FALSE,
    ]);
    $media2->save();
    $media3 = Media::create([
      'bundle' => $media_type1->id(),
      'name' => 'Media 3',
      'uid' => $this->nonAdminUser->id(),
    ]);
    $media3->save();

    // Verify the view is now correctly populated.
    $this->grantPermissions($role, [
      'view media',
      'update any media',
      'delete any media',
    ]);
    $this->drupalGet('/admin/content/media');
    $row1 = $assert_session->elementExists('css', 'table tbody tr:nth-child(1)');
    $row2 = $assert_session->elementExists('css', 'table tbody tr:nth-child(2)');
    $row3 = $assert_session->elementExists('css', 'table tbody tr:nth-child(3)');

    // Media thumbnails.
    $assert_session->elementExists('css', 'td.views-field-thumbnail__target-id img', $row1);
    $assert_session->elementExists('css', 'td.views-field-thumbnail__target-id img', $row2);
    $assert_session->elementExists('css', 'td.views-field-thumbnail__target-id img', $row3);

    // Media names.
    $name1 = $assert_session->elementExists('css', 'td.views-field-name a', $row1);
    $this->assertEquals($media1->label(), $name1->getText());
    $name2 = $assert_session->elementExists('css', 'td.views-field-name a', $row2);
    $this->assertEquals($media2->label(), $name2->getText());
    $name3 = $assert_session->elementExists('css', 'td.views-field-name a', $row3);
    $this->assertEquals($media3->label(), $name3->getText());
    $assert_session->linkByHrefExists('/media/' . $media1->id());
    $assert_session->linkByHrefExists('/media/' . $media2->id());
    $assert_session->linkByHrefExists('/media/' . $media3->id());

    // Media types.
    $type_element1 = $assert_session->elementExists('css', 'td.views-field-bundle', $row1);
    $this->assertEquals($media_type1->label(), $type_element1->getText());
    $type_element2 = $assert_session->elementExists('css', 'td.views-field-bundle', $row2);
    $this->assertEquals($media_type2->label(), $type_element2->getText());
    $type_element3 = $assert_session->elementExists('css', 'td.views-field-bundle', $row3);
    $this->assertEquals($media_type1->label(), $type_element3->getText());

    // Media authors.
    $author_element1 = $assert_session->elementExists('css', 'td.views-field-uid', $row1);
    $this->assertEquals($this->adminUser->getDisplayName(), $author_element1->getText());
    $author_element2 = $assert_session->elementExists('css', 'td.views-field-uid', $row2);
    $this->assertEquals($this->adminUser->getDisplayName(), $author_element2->getText());
    $author_element3 = $assert_session->elementExists('css', 'td.views-field-uid', $row3);
    $this->assertEquals($this->nonAdminUser->getDisplayName(), $author_element3->getText());

    // Media publishing status.
    $status_element1 = $assert_session->elementExists('css', 'td.views-field-status', $row1);
    $this->assertEquals('Published', $status_element1->getText());
    $status_element2 = $assert_session->elementExists('css', 'td.views-field-status', $row2);
    $this->assertEquals('Unpublished', $status_element2->getText());
    $status_element3 = $assert_session->elementExists('css', 'td.views-field-status', $row3);
    $this->assertEquals('Published', $status_element3->getText());

    // Timestamp.
    $expected = \Drupal::service('date.formatter')->format($media1->getChangedTime(), 'short');
    $changed_element1 = $assert_session->elementExists('css', 'td.views-field-changed', $row1);
    $this->assertEquals($expected, $changed_element1->getText());

    // Operations.
    $edit_link1 = $assert_session->elementExists('css', 'td.views-field-operations li.edit a', $row1);
    $this->assertEquals('Edit', $edit_link1->getText());
    $assert_session->linkByHrefExists('/media/' . $media1->id() . '/edit');
    $delete_link1 = $assert_session->elementExists('css', 'td.views-field-operations li.delete a', $row1);
    $this->assertEquals('Delete', $delete_link1->getText());
    $assert_session->linkByHrefExists('/media/' . $media1->id() . '/delete');
  }

}
