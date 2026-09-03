<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Kernel;

use Drupal\entity_test\Entity\EntityTestMulRevPub;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\FileReferenceResolver;
use Drupal\file\FileReferenceUsage;
use Drupal\language\Entity\ConfigurableLanguage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the FileReferenceResolver methods.
 */
#[Group('file')]
#[RunTestsInSeparateProcesses]
#[CoversClass(FileReferenceResolver::class)]
#[CoversMethod(FileReferenceResolver::class, 'isFileReferencedByField')]
#[CoversMethod(FileReferenceResolver::class, 'getReferencingRevision')]
class FileReferenceResolverTest extends FileManagedUnitTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test', 'language'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('entity_test_mulrevpub');
    FieldStorageConfig::create([
      'field_name' => 'field_file',
      'entity_type' => 'entity_test_mulrevpub',
      'type' => 'file',
    ])->save();
    FieldConfig::create([
      'entity_type' => 'entity_test_mulrevpub',
      'bundle' => 'entity_test_mulrevpub',
      'field_name' => 'field_file',
      'label' => 'File',
      'translatable' => TRUE,
    ])->save();
  }

  /**
   * Tests that reference in a non-default revision are resolved.
   */
  public function testFileReferenceResolveFromReferencingRevision(): void {
    $file1 = $this->createFile();
    $file2 = $this->createFile();

    $entity = EntityTestMulRevPub::create([
      'name' => $this->randomMachineName(),
      'field_file' => $file1,
    ]);
    $entity->save();
    $first_revision_id = $entity->getRevisionId();

    // Create new revision with file2.
    $entity = EntityTestMulRevPub::load($entity->id());
    $entity->setNewRevision();
    $entity->set('field_file', $file2);
    $entity->save();

    $resolver = $this->container->get(FileReferenceResolver::class);

    // The default revision references file2.
    $file_2_references = iterator_to_array($resolver->getReferences($file2), FALSE);
    $this->assertCount(1, $file_2_references);
    $this->assertInstanceOf(FileReferenceUsage::class, $file_2_references[0]);
    $this->assertEquals($entity->id(), $file_2_references[0]->id);

    // File1 is not the default revision anymore. It is associated with the
    // older revision.
    $file_1_references = iterator_to_array($resolver->getReferences($file1), FALSE);
    $this->assertCount(1, $file_1_references);
    $this->assertInstanceOf(FileReferenceUsage::class, $file_1_references[0]);
    $this->assertEquals($first_revision_id, $file_1_references[0]->revisionId);

    // Verify that usage can be resolved back to the original revision entity.
    $loaded = $resolver->loadEntityFromUsage($file_1_references[0]);
    $this->assertEquals($first_revision_id, $loaded->getRevisionId());
    $this->assertEquals($file1->id(), $loaded->get('field_file')->target_id);
  }

  /**
   * Tests that references in a translation are resolved.
   */
  public function testFileReferenceResolveFromTranslationRevision(): void {
    ConfigurableLanguage::create([
      'id' => 'es',
      'label' => 'Spanish',
    ])->save();
    $es_file = $this->createFile();

    $entity = EntityTestMulRevPub::create([
      'name' => $this->randomMachineName(),
      'langcode' => 'en',
    ]);
    $entity->save();

    // Add a Spanish translation with a different file attached.
    $entity->addTranslation('es', [
      'name' => $this->randomMachineName(),
      'field_file' => $es_file,
    ])->save();

    $resolver = $this->container->get(FileReferenceResolver::class);

    // Ensure that file from spanish translation is also included in references.
    $es_references = iterator_to_array($resolver->getReferences($es_file), FALSE);
    $this->assertCount(1, $es_references);
    $this->assertInstanceOf(FileReferenceUsage::class, $es_references[0]);
    $this->assertEquals($entity->id(), $es_references[0]->id);
    $this->assertNull($es_references[0]->revisionId);
  }

}
