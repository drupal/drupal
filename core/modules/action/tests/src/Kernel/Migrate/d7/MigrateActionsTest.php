<?php

namespace Drupal\Tests\action\Kernel\Migrate\d7;

use Drupal\system\Entity\Action;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests migration of action items.
 *
 * @group action
 */
class MigrateActionsTest extends MigrateDrupal7TestBase {

  public static $modules = ['action', 'comment', 'node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->executeMigration('d7_action');
  }

  /**
   * Test Drupal 7 action migration to Drupal 8.
   */
  public function testActions() {
    // Test default actions.
    $this->assertEntity('node_publish_action', 'Publish content', 'node', []);
    $this->assertEntity('node_make_sticky_action', 'Make content sticky', 'node', []);
    $this->assertEntity('user_block_user_action', 'Block current user', 'user', []);
    $this->assertEntity('comment_publish_action', 'Publish comment', 'comment', []);

    // Test advanced actions.
    $this->assertEntity('unpublish_comment_containing_keyword_s_', 'Unpublish comment containing keyword(s)', 'comment', ["keywords" => [0 => "drupal"]]);
    $this->assertEntity('change_the_author_of_content', 'Change the author of content', 'node', ["owner_uid" => "2"]);
    $this->assertEntity('unpublish_content_containing_keyword_s_', 'Unpublish content containing keyword(s)', 'node', ["keywords" => [0 => "drupal"]]);
    $this->assertEntity('display_a_message_to_the_user', 'Display a message to the user', 'system', ["message" => "Drupal migration test"]);
    $this->assertEntity('send_e_mail', 'Send e-mail', 'system', [
      "recipient" => "test@example.com",
      "subject" => "Drupal migration test",
      "message" => "Drupal migration test",
    ]);
    $this->assertEntity('redirect_to_url', 'Redirect to URL', 'system', ["url" => "https://www.drupal.org"]);

  }

  /**
   * Asserts various aspects of an Action entity.
   *
   * @param string $id
   *   The expected Action ID.
   * @param string $label
   *   The expected Action label.
   * @param string $type
   *   The expected Action type.
   * @param array $configuration
   *   The expected Action configuration.
   */
  protected function assertEntity($id, $label, $type, $configuration) {
    $action = Action::load($id);

    $this->assertInstanceOf(Action::class, $action);
    /** @var \Drupal\system\Entity\Action $action */
    $this->assertIdentical($id, $action->id());
    $this->assertIdentical($label, $action->label());
    $this->assertIdentical($type, $action->getType());
    $this->assertIdentical($configuration, $action->get('configuration'));
  }

}
