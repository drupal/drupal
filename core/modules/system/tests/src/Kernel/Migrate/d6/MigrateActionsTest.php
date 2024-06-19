<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Kernel\Migrate\d6;

use Drupal\system\Entity\Action;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Tests migration of action items.
 *
 * @group migrate_drupal_6
 */
class MigrateActionsTest extends MigrateDrupal6TestBase {

  protected static $modules = ['comment', 'node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->executeMigration('d6_action');
  }

  /**
   * Tests Drupal 6 action migration to Drupal 8.
   */
  public function testActions(): void {
    // Test default actions.
    $this->assertEntity('node_publish_action', 'Publish post', 'node', []);
    $this->assertEntity('node_make_sticky_action', 'Make post sticky', 'node', []);
    $this->assertEntity('user_block_user_action', 'Block current user', 'user', []);
    $this->assertEntity('comment_publish_action', 'Publish comment', 'comment', []);

    // Test advanced actions.
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
   *
   * @internal
   */
  protected function assertEntity(string $id, string $label, string $type, array $configuration): void {
    $action = Action::load($id);

    $this->assertInstanceOf(Action::class, $action);
    /** @var \Drupal\system\Entity\Action $action */
    $this->assertSame($id, $action->id());
    $this->assertSame($label, $action->label());
    $this->assertSame($type, $action->getType());
    $this->assertSame($configuration, $action->get('configuration'));
  }

}
