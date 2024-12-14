<?php

declare(strict_types=1);

namespace Drupal\Tests\comment\Kernel;

use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Core\Extension\ModuleUninstallValidatorException;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;

/**
 * Tests comment module uninstall.
 *
 * @group comment
 */
class CommentUninstallTest extends KernelTestBase {

  use CommentTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'comment',
    'field',
    'node',
    'system',
    'text',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('comment');
    $this->installConfig(['comment']);
    $this->installSchema('user', ['users_data']);

    NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ])->save();

    // Create comment field on article so that it adds 'comment_body' field.
    FieldStorageConfig::create([
      'type' => 'text_long',
      'entity_type' => 'comment',
      'field_name' => 'comment',
    ])->save();
    $this->addDefaultCommentField('node', 'article');
  }

  /**
   * Tests if comment module uninstall fails if the field exists.
   */
  public function testCommentUninstallWithField(): void {
    // Ensure that the field exists before uninstalling.
    $field_storage = FieldStorageConfig::loadByName('comment', 'comment_body');
    $this->assertNotNull($field_storage);

    // Uninstall the comment module which should trigger an exception.
    $this->expectException(ModuleUninstallValidatorException::class);
    $this->expectExceptionMessage('The following reasons prevent the modules from being uninstalled: The <em class="placeholder">Comments</em> field type is used in the following field: node.comment');
    $this->container->get('module_installer')->uninstall(['comment']);
  }

  /**
   * Tests if uninstallation succeeds if the field has been deleted beforehand.
   */
  public function testCommentUninstallWithoutField(): void {
    // Tests if uninstall succeeds if the field has been deleted beforehand.
    // Manually delete the comment_body field before module uninstall.
    FieldStorageConfig::loadByName('comment', 'comment_body')->delete();

    // Check that the field is now deleted.
    $field_storage = FieldStorageConfig::loadByName('comment', 'comment_body');
    $this->assertNull($field_storage);

    // Manually delete the comment field on the node before module uninstall.
    $field_storage = FieldStorageConfig::loadByName('node', 'comment');
    $this->assertNotNull($field_storage);
    $field_storage->delete();

    // Check that the field is now deleted.
    $field_storage = FieldStorageConfig::loadByName('node', 'comment');
    $this->assertNull($field_storage);

    field_purge_batch(10);
    // Ensure that uninstall succeeds even if the field has already been deleted
    // manually beforehand.
    $this->container->get('module_installer')->uninstall(['comment']);
  }

}
