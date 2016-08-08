<?php

namespace Drupal\Tests\content_moderation\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\content_moderation\Entity\ModerationState;

/**
 * @coversDefaultClass \Drupal\content_moderation\Entity\ModerationState
 *
 * @group content_moderation
 */
class ModerationStateEntityTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['content_moderation'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('moderation_state');
  }

  /**
   * Verify moderation state methods based on entity properties.
   *
   * @covers ::isPublishedState
   * @covers ::isDefaultRevisionState
   *
   * @dataProvider moderationStateProvider
   */
  public function testModerationStateProperties($published, $default_revision, $is_published, $is_default) {
    $moderation_state_id = $this->randomMachineName();
    $moderation_state = ModerationState::create([
      'id' => $moderation_state_id,
      'label' => $this->randomString(),
      'published' => $published,
      'default_revision' => $default_revision,
    ]);
    $moderation_state->save();

    $moderation_state = ModerationState::load($moderation_state_id);
    $this->assertEquals($is_published, $moderation_state->isPublishedState());
    $this->assertEquals($is_default, $moderation_state->isDefaultRevisionState());
  }

  /**
   * Data provider for ::testModerationStateProperties.
   */
  public function moderationStateProvider() {
    return [
      // Draft, Needs review; should not touch the default revision.
      [FALSE, FALSE, FALSE, FALSE],
      // Published; this state should update and publish the default revision.
      [TRUE, TRUE, TRUE, TRUE],
      // Archive; this state should update but not publish the default revision.
      [FALSE, TRUE, FALSE, TRUE],
      // We try to prevent creating this state via the UI, but when a moderation
      // state is a published state, it should also become the default revision.
      [TRUE, FALSE, TRUE, TRUE],
    ];
  }

}
