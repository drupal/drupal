<?php

declare(strict_types=1);

namespace Drupal\Tests\action\Kernel\Migrate\d7;

use Drupal\system\Entity\Action;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests migration of action items.
 *
 * @group action
 */
class MigrateActionsTest extends MigrateDrupal7TestBase {

  protected static $modules = ['action', 'comment', 'node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->executeMigration('d7_action');
  }

  /**
   * Tests Drupal 7 action migration to Drupal 8.
   */
  public function testActions(): void {
    // Test advanced actions.
    $this->assertEntity('unpublish_comment_containing_keyword_s_', 'Unpublish comment containing keyword(s)', 'comment', ["keywords" => [0 => "drupal"]]);
    $this->assertEntity('unpublish_content_containing_keyword_s_', 'Unpublish content containing keyword(s)', 'node', ["keywords" => [0 => "drupal"]]);
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
