<?php

namespace Drupal\Tests\taxonomy\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\user\RoleInterface;
use Drupal\file\Entity\File;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests access checks of private image fields.
 *
 * @group taxonomy
 */
class TaxonomyImageTest extends TaxonomyTestBase {

  use TestFileCreationTrait {
    getTestFiles as drupalGetTestFiles;
    compareFiles as drupalCompareFiles;
  }

  /**
   * Used taxonomy vocabulary.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['image'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected function setUp(): void {
    parent::setUp();

    // Remove access content permission from registered users.
    user_role_revoke_permissions(RoleInterface::AUTHENTICATED_ID, ['access content']);

    $this->vocabulary = $this->createVocabulary();
    // Add a field to the vocabulary.
    $entity_type = 'taxonomy_term';
    $name = 'field_test';
    FieldStorageConfig::create([
      'field_name' => $name,
      'entity_type' => $entity_type,
      'type' => 'image',
      'settings' => [
        'uri_scheme' => 'private',
      ],
    ])->save();
    FieldConfig::create([
      'field_name' => $name,
      'entity_type' => $entity_type,
      'bundle' => $this->vocabulary->id(),
      'settings' => [],
    ])->save();
    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');
    $display_repository->getViewDisplay($entity_type, $this->vocabulary->id())
      ->setComponent($name, [
        'type' => 'image',
        'settings' => [],
      ])
      ->save();
    $display_repository->getFormDisplay($entity_type, $this->vocabulary->id())
      ->setComponent($name, [
        'type' => 'image_image',
        'settings' => [],
      ])
      ->save();
  }

  public function testTaxonomyImageAccess() {
    $user = $this->drupalCreateUser([
      'administer site configuration',
      'administer taxonomy',
      'access user profiles',
    ]);
    $this->drupalLogin($user);

    // Create a term and upload the image.
    $files = $this->drupalGetTestFiles('image');
    $image = array_pop($files);
    $edit['name[0][value]'] = $this->randomMachineName();
    $edit['files[field_test_0]'] = \Drupal::service('file_system')->realpath($image->uri);
    $this->drupalPostForm('admin/structure/taxonomy/manage/' . $this->vocabulary->id() . '/add', $edit, t('Save'));
    $this->drupalPostForm(NULL, ['field_test[0][alt]' => $this->randomMachineName()], t('Save'));
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['name' => $edit['name[0][value]']]);
    $term = reset($terms);
    $this->assertText(t('Created new term @name.', ['@name' => $term->getName()]));

    // Create a user that should have access to the file and one that doesn't.
    $access_user = $this->drupalCreateUser(['access content']);
    $no_access_user = $this->drupalCreateUser();
    $image = File::load($term->field_test->target_id);

    // Ensure a user that should be able to access the file can access it.
    $this->drupalLogin($access_user);
    $this->drupalGet(file_create_url($image->getFileUri()));
    $this->assertSession()->statusCodeEquals(200);

    // Ensure a user that should not be able to access the file cannot access
    // it.
    $this->drupalLogin($no_access_user);
    $this->drupalGet(file_create_url($image->getFileUri()));
    $this->assertSession()->statusCodeEquals(403);
  }

}
