<?php

namespace Drupal\content_moderation\Tests;

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
   */
  protected function setUp() {
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
   * Tests moderating custom blocks.
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
  public function testCustomBlockModeration() {
    $this->drupalLogin($this->rootUser);

    $this->drupalGet('admin/structure/block/block-content/types');
    $this->assertLinkByHref('admin/structure/block/block-content/manage/basic/moderation');
    $this->drupalGet('admin/structure/block/block-content/manage/basic');
    $this->assertLinkByHref('admin/structure/block/block-content/manage/basic/moderation');
    $this->drupalGet('admin/structure/block/block-content/manage/basic/moderation');

    // Enable moderation for custom blocks at
    // admin/structure/block/block-content/manage/basic/moderation.
    $edit = [
      'enable_moderation_state' => TRUE,
      'allowed_moderation_states_unpublished[draft]' => TRUE,
      'allowed_moderation_states_published[published]' => TRUE,
      'default_moderation_state' => 'draft',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText(t('Your settings have been saved.'));

    // Create a custom block at block/add and save it as draft.
    $body = 'Body of moderated block';
    $edit = [
      'info[0][value]' => 'Moderated block',
      'body[0][value]' => $body,
    ];
    $this->drupalPostForm('block/add', $edit, t('Save and Create New Draft'));
    $this->assertText(t('basic Moderated block has been created.'));

    // Place the block in the Sidebar First region.
    $instance = array(
      'id' => 'moderated_block',
      'settings[label]' => $edit['info[0][value]'],
      'region' => 'sidebar_first',
    );
    $block = BlockContent::load(1);
    $url = 'admin/structure/block/add/block_content:' . $block->uuid() . '/' . $this->config('system.theme')->get('default');
    $this->drupalPostForm($url, $instance, t('Save block'));

    // Navigate to home page and check that the block is visible. It should be
    // visible because it is the default revision.
    $this->drupalGet('');
    $this->assertText($body);

    // Update the block.
    $updated_body = 'This is the new body value';
    $edit = [
      'body[0][value]' => $updated_body,
    ];
    $this->drupalPostForm('block/' . $block->id(), $edit, t('Save and Create New Draft'));
    $this->assertText(t('basic Moderated block has been updated.'));

    // Navigate to the home page and check that the block shows the updated
    // content. It should show the updated content because the block's default
    // revision is not a published moderation state.
    $this->drupalGet('');
    $this->assertText($updated_body);

    // Publish the block so we can create a forward revision.
    $this->drupalPostForm('block/' . $block->id(), [], t('Save and Publish'));

    // Create a forward revision.
    $forward_revision_body = 'This is the forward revision body value';
    $edit = [
      'body[0][value]' => $forward_revision_body,
    ];
    $this->drupalPostForm('block/' . $block->id(), $edit, t('Save and Create New Draft'));
    $this->assertText(t('basic Moderated block has been updated.'));

    // Navigate to home page and check that the forward revision doesn't show,
    // since it should not be set as the default revision.
    $this->drupalGet('');
    $this->assertText($updated_body);

    // Open the latest tab and publish the new draft.
    $edit = [
      'new_state' => 'published',
    ];
    $this->drupalPostForm('block/' . $block->id() . '/latest', $edit, t('Apply'));
    $this->assertText(t('The moderation state has been updated.'));

    // Navigate to home page and check that the forward revision is now the
    // default revision and therefore visible.
    $this->drupalGet('');
    $this->assertText($forward_revision_body);
  }

}
