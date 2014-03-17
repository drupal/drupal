<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Tests\TaxonomyImageTest.
 */

namespace Drupal\taxonomy\Tests;

/**
 * Provides helper methods for taxonomy terms with image fields.
 */
class TaxonomyImageTest extends TaxonomyTestBase {

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
  public static $modules = array('image');

  public static function getInfo() {
    return array(
      'name' => 'Taxonomy Image Test',
      'description' => 'Tests access checks of private image fields',
      'group' => 'Taxonomy',
    );
  }

  public function setUp() {
    parent::setUp();

    // Remove access content permission from registered users.
    user_role_revoke_permissions(DRUPAL_AUTHENTICATED_RID, array('access content'));

    $this->vocabulary = $this->createVocabulary();
    // Add a field instance to the vocabulary.
    $entity_type = 'taxonomy_term';
    $name = 'field_test';
    entity_create('field_config', array(
      'name' => $name,
      'entity_type' => $entity_type,
      'type' => 'image',
      'settings' => array(
        'uri_scheme' => 'private',
      ),
    ))->save();
    entity_create('field_instance_config', array(
      'field_name' => $name,
      'entity_type' => $entity_type,
      'bundle' => $this->vocabulary->id(),
      'settings' => array(),
    ))->save();
    entity_get_display($entity_type, $this->vocabulary->id(), 'default')
      ->setComponent($name, array(
        'type' => 'image',
        'settings' => array(),
      ))
      ->save();
    entity_get_form_display($entity_type, $this->vocabulary->id(), 'default')
      ->setComponent($name, array(
        'type' => 'image_image',
        'settings' => array(),
      ))
      ->save();
  }

  public function testTaxonomyImageAccess() {
    $user = $this->drupalCreateUser(array('administer site configuration', 'administer taxonomy', 'access user profiles'));
    $this->drupalLogin($user);

    // Create a term and upload the image.
    $files = $this->drupalGetTestFiles('image');
    $image = array_pop($files);
    $edit['name'] = $this->randomName();
    $edit['files[field_test_0]'] = drupal_realpath($image->uri);
    $this->drupalPostForm('admin/structure/taxonomy/manage/' . $this->vocabulary->id()  . '/add', $edit, t('Save'));
    $terms = entity_load_multiple_by_properties('taxonomy_term', array('name' => $edit['name']));
    $term = reset($terms);
    $this->assertText(t('Created new term @name.', array('@name' => $term->getName())));

    // Create a user that should have access to the file and one that doesn't.
    $access_user = $this->drupalCreateUser(array('access content'));
    $no_access_user = $this->drupalCreateUser();
    $image = file_load($term->field_test->target_id);
    $this->drupalLogin($access_user);
    $this->drupalGet(file_create_url($image->getFileUri()));
    $this->assertResponse(200, 'Private image on term is accessible with right permission');

    $this->drupalLogin($no_access_user);
    $this->drupalGet(file_create_url($image->getFileUri()));
    $this->assertResponse(403, 'Private image on term not accessible without right permission');
  }

}
