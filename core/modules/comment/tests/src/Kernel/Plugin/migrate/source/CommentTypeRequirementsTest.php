<?php

declare(strict_types=1);

namespace Drupal\Tests\comment\Kernel\Plugin\migrate\source;

use Drupal\migrate\Exception\RequirementsException;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests check requirements for comment type source plugin.
 *
 * @group comment
 */
class CommentTypeRequirementsTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['comment'];

  /**
   * Tests thrown exceptions when node or comment aren't enabled on source.
   *
   * @param string[] $disabled_source_modules
   *   List of the modules to disable in the source Drupal database.
   * @param string $exception_message
   *   The expected message of the RequirementsException.
   * @param string $migration_plugin_id
   *   The plugin ID of the comment type migration to test.
   *
   * @dataProvider providerTestCheckCommentTypeRequirements
   */
  public function testCheckCommentTypeRequirements(array $disabled_source_modules, string $exception_message, string $migration_plugin_id): void {
    if (!empty($disabled_source_modules)) {
      $this->sourceDatabase->update('system')
        ->condition('name', $disabled_source_modules, 'IN')
        ->fields(['status' => 0])
        ->execute();
    }

    $this->expectException(RequirementsException::class);
    $this->expectExceptionMessage($exception_message);
    $this->getMigration($migration_plugin_id)
      ->getSourcePlugin()
      ->checkRequirements();
  }

  /**
   * Test cases for ::testCheckCommentTypeRequirements().
   */
  public static function providerTestCheckCommentTypeRequirements() {
    return [
      'D6 comment is disabled on source' => [
        'disabled_source_modules' => ['comment'],
        'exception_message' => 'The module comment is not enabled in the source site.',
        'migration_plugin_id' => 'd6_comment_type',
      ],
      'D6 node is disabled on source' => [
        'disabled_source_modules' => ['node'],
        'exception_message' => 'The node module is not enabled in the source site.',
        'migration_plugin_id' => 'd6_comment_type',
      ],
      'D6 comment and node are disabled on source' => [
        'disabled_source_modules' => ['comment', 'node'],
        'exception_message' => 'The module comment is not enabled in the source site.',
        'migration_plugin_id' => 'd6_comment_type',
      ],
      'D7 comment is disabled on source' => [
        'disabled_source_modules' => ['comment'],
        'exception_message' => 'The module comment is not enabled in the source site.',
        'migration_plugin_id' => 'd7_comment_type',
      ],
      'D7 node is disabled on source' => [
        'disabled_source_modules' => ['node'],
        'exception_message' => 'The node module is not enabled in the source site.',
        'migration_plugin_id' => 'd7_comment_type',
      ],
      'D7 comment and node are disabled on source' => [
        'disabled_source_modules' => ['comment', 'node'],
        'exception_message' => 'The module comment is not enabled in the source site.',
        'migration_plugin_id' => 'd7_comment_type',
      ],
    ];
  }

}
