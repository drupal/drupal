<?php

declare(strict_types=1);

namespace Drupal\Tests\taxonomy\Functional;

use Drupal\Tests\TestFileCreationTrait;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\taxonomy\VocabularyInterface;

/**
 * Tests image upload on taxonomy terms.
 *
 * @group taxonomy
 */
class TaxonomyImageTest extends TaxonomyTestBase {

  use TestFileCreationTrait {
    getTestFiles as drupalGetTestFiles;
    compareFiles as drupalCompareFiles;
  }

  /**
   * The taxonomy vocabulary used for the test.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected VocabularyInterface $vocabulary;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['image'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->vocabulary = $this->createVocabulary();
    $entity_type = 'taxonomy_term';
    $name = 'field_test';
    FieldStorageConfig::create([
      'field_name' => $name,
      'entity_type' => $entity_type,
      'type' => 'image',
    ])->save();
    FieldConfig::create([
      'field_name' => $name,
      'entity_type' => $entity_type,
      'bundle' => $this->vocabulary->id(),
      'settings' => [],
    ])->save();
    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');
    $display_repository->getFormDisplay($entity_type, $this->vocabulary->id())
      ->setComponent($name, [
        'type' => 'image_image',
        'settings' => [],
      ])
      ->save();
  }

  /**
   * Tests that a file can be uploaded before the taxonomy term has a name.
   */
  public function testTaxonomyImageUpload(): void {
    $user = $this->drupalCreateUser(['administer taxonomy']);
    $this->drupalLogin($user);

    $files = $this->drupalGetTestFiles('image');
    $image = array_pop($files);

    // Ensure that a file can be uploaded before taxonomy term has a name.
    $edit = [
      'files[field_test_0]' => \Drupal::service('file_system')->realpath($image->uri),
    ];
    $this->drupalGet('admin/structure/taxonomy/manage/' . $this->vocabulary->id() . '/add');
    $this->submitForm($edit, 'Upload');

    $edit = [
      'name[0][value]' => $this->randomMachineName(),
      'field_test[0][alt]' => $this->randomMachineName(),
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Created new term');
  }

}
