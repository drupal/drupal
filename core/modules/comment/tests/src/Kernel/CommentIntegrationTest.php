<?php

namespace Drupal\Tests\comment\Kernel;

use Drupal\comment\Entity\Comment;
use Drupal\comment\Entity\CommentType;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests integration of comment with other components.
 *
 * @group comment
 */
class CommentIntegrationTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'comment',
    'field',
    'entity_test',
    'user',
    'system',
    'dblog',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('user');
    $this->installEntitySchema('comment');
    $this->installSchema('dblog', ['watchdog']);

    // Create a new 'comment' comment-type.
    CommentType::create([
      'id' => 'comment',
      'label' => $this->randomString(),
      'target_entity_type_id' => 'entity_test',
    ])->save();
  }

  /**
   * Tests view mode setting integration.
   *
   * @see comment_entity_view_display_presave()
   * @see CommentDefaultFormatter::calculateDependencies()
   */
  public function testViewMode() {
    $mode = $this->randomMachineName();
    // Create a new comment view mode and a view display entity.
    EntityViewMode::create([
      'id' => "comment.$mode",
      'targetEntityType' => 'comment',
      'settings' => ['comment_type' => 'comment'],
      'label' => $mode,
    ])->save();
    EntityViewDisplay::create([
      'targetEntityType' => 'comment',
      'bundle' => 'comment',
      'mode' => $mode,
    ])->setStatus(TRUE)->save();

    // Create a comment field attached to a host 'entity_test' entity.
    FieldStorageConfig::create([
      'entity_type' => 'entity_test',
      'type' => 'comment',
      'field_name' => $field_name = $this->randomMachineName(),
      'settings' => [
        'comment_type' => 'comment',
      ],
    ])->save();
    FieldConfig::create([
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'field_name' => $field_name,
    ])->save();

    $component = [
      'type' => 'comment_default',
      'settings' => ['view_mode' => $mode, 'pager_id' => 0],
    ];
    // Create a new 'entity_test' view display on host entity that uses the
    // custom comment display in field formatter to show the field.
    EntityViewDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
    ])->setComponent($field_name, $component)->setStatus(TRUE)->save();

    $host_display_id = 'entity_test.entity_test.default';
    $comment_display_id = "comment.comment.$mode";

    // Disable the "comment.comment.$mode" display.
    EntityViewDisplay::load($comment_display_id)->setStatus(FALSE)->save();

    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $host_display */
    $host_display = EntityViewDisplay::load($host_display_id);

    // Check that the field formatter has been disabled on host view display.
    $this->assertNull($host_display->getComponent($field_name));
    $this->assertTrue($host_display->get('hidden')[$field_name]);

    // Check that the proper warning has been logged.
    $arguments = [
      '@id' => $host_display_id,
      '@name' => $field_name,
      '@display' => EntityViewMode::load("comment.$mode")->label(),
      '@mode' => $mode,
    ];
    $logged = Database::getConnection()->select('watchdog')
      ->fields('watchdog', ['variables'])
      ->condition('type', 'system')
      ->condition('message', "View display '@id': Comment field formatter '@name' was disabled because it is using the comment view display '@display' (@mode) that was just disabled.")
      ->execute()
      ->fetchField();
    $this->assertEquals(serialize($arguments), $logged);

    // Re-enable the comment view display.
    EntityViewDisplay::load($comment_display_id)->setStatus(TRUE)->save();
    // Re-enable the comment field formatter on host entity view display.
    EntityViewDisplay::load($host_display_id)->setComponent($field_name, $component)->save();

    // Delete the "comment.$mode" view mode.
    EntityViewMode::load("comment.$mode")->delete();

    // Check that the comment view display entity has been deleted too.
    $this->assertNull(EntityViewDisplay::load($comment_display_id));

    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display */
    $host_display = EntityViewDisplay::load($host_display_id);

    // Check that the field formatter has been disabled on host view display.
    $this->assertNull($host_display->getComponent($field_name));
    $this->assertTrue($host_display->get('hidden')[$field_name]);
  }

  /**
   * Tests the default owner of comment entities.
   */
  public function testCommentDefaultOwner() {
    $comment = Comment::create([
      'comment_type' => 'comment',
    ]);
    $this->assertEquals(0, $comment->getOwnerId());

    $user = $this->createUser();
    $this->container->get('current_user')->setAccount($user);
    $comment = Comment::create([
      'comment_type' => 'comment',
    ]);
    $this->assertEquals($user->id(), $comment->getOwnerId());
  }

}
