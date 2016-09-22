<?php

namespace Drupal\content_moderation\Tests;

/**
 * Tests moderation state transition config entity.
 *
 * @group content_moderation
 */
class ModerationStateTransitionsTest extends ModerationStateTestBase {

  /**
   * Tests route access/permissions.
   */
  public function testAccess() {
    $paths = [
      'admin/config/workflow/moderation/transitions',
      'admin/config/workflow/moderation/transitions/add',
      'admin/config/workflow/moderation/transitions/draft_published',
      'admin/config/workflow/moderation/transitions/draft_published/delete',
    ];

    foreach ($paths as $path) {
      $this->drupalGet($path);
      // No access.
      $this->assertResponse(403);
    }
    $this->drupalLogin($this->adminUser);
    foreach ($paths as $path) {
      $this->drupalGet($path);
      // User has access.
      $this->assertResponse(200);
    }
  }

  /**
   * Tests administration of moderation state transition entity.
   */
  public function testTransitionAdministration() {
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/config/workflow/moderation');
    $this->clickLink('Moderation state transitions');
    $this->assertLink('Add moderation state transition');
    $this->assertText('Create New Draft');

    // Edit the Draft » Draft review.
    $this->drupalGet('admin/config/workflow/moderation/transitions/draft_draft');
    $this->assertFieldByName('label', 'Create New Draft');
    $this->assertFieldByName('stateFrom', 'draft');
    $this->assertFieldByName('stateTo', 'draft');
    $this->drupalPostForm(NULL, [
      'label' => 'Create Draft',
    ], t('Save'));
    $this->assertText('Saved the Create Draft Moderation state transition.');
    $this->drupalGet('admin/config/workflow/moderation/transitions/draft_draft');
    $this->assertFieldByName('label', 'Create Draft');
    // Now set it back.
    $this->drupalPostForm(NULL, [
      'label' => 'Create New Draft',
    ], t('Save'));
    $this->assertText('Saved the Create New Draft Moderation state transition.');

    // Add a new state.
    $this->drupalGet('admin/config/workflow/moderation/states/add');
    $this->drupalPostForm(NULL, [
      'label' => 'Expired',
      'id' => 'expired',
    ], t('Save'));
    $this->assertText('Created the Expired Moderation state.');

    // Add a new transition.
    $this->drupalGet('admin/config/workflow/moderation/transitions');
    $this->clickLink(t('Add moderation state transition'));
    $this->drupalPostForm(NULL, [
      'label' => 'Published » Expired',
      'id' => 'published_expired',
      'stateFrom' => 'published',
      'stateTo' => 'expired',
    ], t('Save'));
    $this->assertText('Created the Published » Expired Moderation state transition.');

    // Delete the new transition.
    $this->drupalGet('admin/config/workflow/moderation/transitions/published_expired');
    $this->clickLink('Delete');
    $this->assertText('Are you sure you want to delete Published » Expired?');
    $this->drupalPostForm(NULL, [], t('Delete'));
    $this->assertText('Moderation transition Published » Expired deleted');
  }

}
