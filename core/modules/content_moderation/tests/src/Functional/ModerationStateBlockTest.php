<?php

declare(strict_types=1);

namespace Drupal\Tests\content_moderation\Functional;

use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;

/**
 * Tests general content moderation workflow for blocks.
 *
 * @group content_moderation
 */
class ModerationStateBlockTest extends ModerationStateTestBase {

  /**
   * {@inheritdoc}
   *
   * @todo Remove and fix test to not rely on super user.
   * @see https://www.drupal.org/project/drupal/issues/3437620
   */
  protected bool $usesSuperUserAccessPolicy = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create the "basic" block type.
    $bundle = BlockContentType::create([
      'id' => 'basic',
      'label' => 'basic',
      'revision' => FALSE,
    ]);
    $bundle->save();

    // Add the body field to it.
    block_content_add_body_field($bundle->id());
  }

  /**
   * Tests moderating content blocks.
   *
   * Blocks and any non-node-type-entities do not have a concept of
   * "published". As such, we must use the "default revision" to know what is
   * going to be "published", i.e. visible to the user.
   *
   * The one exception is a block that has never been "published". When a block
   * is first created, it becomes the "default revision". For each edit of the
   * block after that, Content Moderation checks the "default revision" to
   * see if it is set to a published moderation state. If it is not, the entity
   * being saved will become the "default revision".
   *
   * The test below is intended, in part, to make this behavior clear.
   *
   * @see \Drupal\content_moderation\EntityOperations::entityPresave
   * @see \Drupal\content_moderation\Tests\ModerationFormTest::testModerationForm
   */
  public function testCustomBlockModeration(): void {
    $this->drupalLogin($this->rootUser);

    // Enable moderation for content blocks.
    $edit['bundles[basic]'] = TRUE;
    $this->drupalGet('admin/config/workflow/workflows/manage/editorial/type/block_content');
    $this->submitForm($edit, 'Save');

    // Create a content block at block/add and save it as draft.
    $body = 'Body of moderated block';
    $edit = [
      'info[0][value]' => 'Moderated block',
      'moderation_state[0][state]' => 'draft',
      'body[0][value]' => $body,
    ];
    $this->drupalGet('block/add');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('basic Moderated block has been created.');

    // Place the block in the Sidebar First region.
    $instance = [
      'id' => 'moderated_block',
      'settings[label]' => $edit['info[0][value]'],
      'region' => 'sidebar_first',
    ];
    $block = BlockContent::load(1);
    $url = 'admin/structure/block/add/block_content:' . $block->uuid() . '/' . $this->config('system.theme')->get('default');
    $this->drupalGet($url);
    $this->submitForm($instance, 'Save block');

    // Navigate to home page and check that the block is visible. It should be
    // visible because it is the default revision.
    $this->drupalGet('');
    $this->assertSession()->pageTextContains($body);

    // Update the block.
    $updated_body = 'This is the new body value';
    $edit = [
      'body[0][value]' => $updated_body,
      'moderation_state[0][state]' => 'draft',
    ];
    $this->drupalGet('admin/content/block/' . $block->id());
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('basic Moderated block has been updated.');

    // Navigate to the home page and check that the block shows the updated
    // content. It should show the updated content because the block's default
    // revision is not a published moderation state.
    $this->drupalGet('');
    $this->assertSession()->pageTextContains($updated_body);

    // Publish the block so we can create a pending revision.
    $this->drupalGet('admin/content/block/' . $block->id());
    $this->submitForm(['moderation_state[0][state]' => 'published'], 'Save');

    // Create a pending revision.
    $pending_revision_body = 'This is the pending revision body value';
    $edit = [
      'body[0][value]' => $pending_revision_body,
      'moderation_state[0][state]' => 'draft',
    ];
    $this->drupalGet('admin/content/block/' . $block->id());
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('basic Moderated block has been updated.');

    // Navigate to home page and check that the pending revision doesn't show,
    // since it should not be set as the default revision.
    $this->drupalGet('');
    $this->assertSession()->pageTextContains($updated_body);

    // Open the latest tab and publish the new draft.
    $edit = [
      'new_state' => 'published',
    ];
    $this->drupalGet('admin/content/block/' . $block->id() . '/latest');
    $this->submitForm($edit, 'Apply');
    $this->assertSession()->pageTextContains('The moderation state has been updated.');

    // Navigate to home page and check that the pending revision is now the
    // default revision and therefore visible.
    $this->drupalGet('');
    $this->assertSession()->pageTextContains($pending_revision_body);

    // Check that revision is checked by default when content moderation is
    // enabled.
    $this->drupalGet('/admin/content/block/' . $block->id());
    $this->assertSession()->checkboxChecked('revision');
    $this->assertSession()->pageTextContains('Revisions must be required when moderation is enabled.');
    $this->assertSession()->fieldDisabled('revision');
  }

}
