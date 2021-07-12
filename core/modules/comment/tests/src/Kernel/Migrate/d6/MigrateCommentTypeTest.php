<?php

namespace Drupal\Tests\comment\Kernel\Migrate\d6;

use Drupal\comment\Entity\CommentType;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Tests the migration of comment types from Drupal 6.
 *
 * @group comment
 * @group migrate_drupal_6
 */
class MigrateCommentTypeTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['comment'];

  /**
   * Asserts a comment type entity.
   *
   * @param string $id
   *   The entity ID.
   * @param string $label
   *   The entity label.
   *
   * @internal
   */
  protected function assertEntity(string $id, string $label): void {
    $entity = CommentType::load($id);
    $this->assertInstanceOf(CommentType::class, $entity);
    $this->assertSame($label, $entity->label());
    $this->assertSame('node', $entity->getTargetEntityTypeId());
  }

  /**
   * Tests the migrated comment types.
   */
  public function testMigration() {
    $this->installConfig(['comment']);
    $this->executeMigration('d6_comment_type');

    $this->assertEntity('comment_node_article', 'Article comment');
    $this->assertEntity('comment_node_company', 'Company comment');
    $this->assertEntity('comment_node_employee', 'Employee comment');
    $this->assertEntity('comment_node_event', 'Event comment');
    $this->assertEntity('comment_forum', 'Forum topic comment');
    $this->assertEntity('comment_node_page', 'Page comment');
    $this->assertEntity('comment_node_sponsor', 'Sponsor comment');
    $this->assertEntity('comment_node_story', 'Story comment');
    $this->assertEntity('comment_node_test_event', 'Migrate test event comment');
    $this->assertEntity('comment_node_test_page', 'Migrate test page comment');
    $this->assertEntity('comment_node_test_planet', 'Migrate test planet comment');
    $this->assertEntity('comment_node_test_story', 'Migrate test story comment');
    $this->assertEntity('comment_node_a_thirty_two_char', 'Test long name comment');
  }

  /**
   * Tests comment type migration without node or / and comment on source.
   *
   * Usually, MigrateDumpAlterInterface::migrateDumpAlter() should be used when
   * the source fixture needs to be changed in a Migrate kernel test, but that
   * would end in three additional tests and an extra overhead in maintenance.
   *
   * @param string[] $disabled_source_modules
   *   List of the modules to disable in the source Drupal database.
   * @param string[][] $expected_messages
   *   List of the expected migration messages, keyed by the message type.
   *   Message type should be "status" "warning" or "error".
   *
   * @dataProvider providerTestNoCommentTypeMigration
   */
  public function testNoCommentTypeMigration(array $disabled_source_modules, array $expected_messages) {
    if (!empty($disabled_source_modules)) {
      $this->sourceDatabase->update('system')
        ->condition('name', $disabled_source_modules, 'IN')
        ->fields(['status' => 0])
        ->execute();
    }

    $this->startCollectingMessages();
    $this->executeMigration('d6_comment_type');

    $expected_messages += [
      'status' => [],
      'warning' => [],
      'error' => [],
    ];
    $actual_messages = $this->migrateMessages + [
      'status' => [],
      'warning' => [],
      'error' => [],
    ];

    foreach ($expected_messages as $type => $expected_messages_by_type) {
      $this->assertSameSize($expected_messages_by_type, $actual_messages[$type]);
      // Cast the actual messages to string.
      $actual_messages_by_type = array_reduce($actual_messages[$type], function (array $carry, $actual_message) {
        $carry[] = (string) $actual_message;
        return $carry;
      }, []);
      $missing_expected_messages_by_type = array_diff($expected_messages_by_type, $actual_messages_by_type);
      $unexpected_messages_by_type = array_diff($actual_messages_by_type, $expected_messages_by_type);
      $this->assertEmpty($unexpected_messages_by_type, sprintf('No additional messages are present with type "%s". This expectation is wrong, because there are additional messages present: "%s"', $type, implode('", "', $unexpected_messages_by_type)));
      $this->assertEmpty($missing_expected_messages_by_type, sprintf('Every expected messages are present with type "%s". This expectation is wrong, because the following messages aren\'t present: "%s"', $type, implode('", "', $missing_expected_messages_by_type)));
    }

    $this->assertEmpty(CommentType::loadMultiple());
  }

  /**
   * Provides test cases for ::testNoCommentTypeMigration().
   */
  public function providerTestNoCommentTypeMigration() {
    return [
      'Node module is disabled in source' => [
        'Disabled source modules' => ['node'],
        'Expected messages' => [
          'error' => [
            'Migration d6_comment_type did not meet the requirements. The node module is not enabled in the source site. source_module_additional: node.',
          ],
        ],
      ],
      'Comment module is disabled in source' => [
        'Disabled source modules' => ['comment'],
        'Expected messages' => [
          'error' => [
            'Migration d6_comment_type did not meet the requirements. The module comment is not enabled in the source site. source_module: comment.',
          ],
        ],
      ],
      'Node and comment modules are disabled in source' => [
        'Disabled source modules' => ['comment', 'node'],
        'Expected messages' => [
          'error' => [
            'Migration d6_comment_type did not meet the requirements. The module comment is not enabled in the source site. source_module: comment.',
          ],
        ],
      ],
    ];
  }

}
