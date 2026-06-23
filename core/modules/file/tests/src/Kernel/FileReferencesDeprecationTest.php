<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests file_get_file_references().
 */
#[Group('file')]
#[RunTestsInSeparateProcesses]
#[IgnoreDeprecations]
#[CoversFunction('file_get_file_references')]
#[CoversFunction('file_field_find_file_reference_column')]
class FileReferencesDeprecationTest extends FileManagedUnitTestBase {

  /**
   * Tests basic file reference cases.
   */
  public function testFileReferences(): void {
    $this->expectUserDeprecationMessage('file_get_file_references() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use \Drupal::service(\Drupal\file\FileReferenceResolver::class)->getReferences($file) instead. See https://www.drupal.org/node/3573884');
    $this->expectUserDeprecationMessage('file_field_find_file_reference_column() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. There is no replacement. See https://www.drupal.org/node/3573884');

    $this->enableModules(['node']);
    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);
    NodeType::create([
      'type' => 'page',
      'name' => 'Page',
    ])->save();

    // Create a file field attached to 'page' node-type.
    FieldStorageConfig::create([
      'type' => 'file',
      'entity_type' => 'node',
      'field_name' => 'field_file',
    ])->save();
    FieldConfig::create([
      'entity_type' => 'node',
      'bundle' => 'page',
      'field_name' => 'field_file',
      'label' => 'File',
    ])->save();

    // Create a node, attach a file and add a Romanian translation.
    $node = Node::create(['type' => 'page', 'title' => 'Page']);
    $node
      ->set('field_file', $file = $this->createFile())
      ->save();

    $node = Node::load($node->id());

    $this->assertEquals(['field_file' => ['node' => [$node->id() => $node]]], file_get_file_references($file));
  }

}
