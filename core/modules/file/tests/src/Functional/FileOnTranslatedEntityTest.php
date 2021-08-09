<?php

namespace Drupal\Tests\file\Functional;

use Drupal\file\Entity\File;

/**
 * Uploads files to translated nodes.
 *
 * @group file
 */
class FileOnTranslatedEntityTest extends FileFieldTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['language', 'content_translation'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The name of the file field used in the test.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // This test expects unused managed files to be marked as temporary a file.
    $this->config('file.settings')
      ->set('make_unused_managed_files_temporary', TRUE)
      ->save();

    // Create the "Basic page" node type.
    // @todo Remove the disabling of new revision creation in
    //   https://www.drupal.org/node/1239558.
    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page', 'new_revision' => FALSE]);

    // Create a file field on the "Basic page" node type.
    $this->fieldName = strtolower($this->randomMachineName());
    $this->createFileField($this->fieldName, 'node', 'page');

    // Create and log in user.
    $permissions = [
      'access administration pages',
      'administer content translation',
      'administer content types',
      'administer languages',
      'create content translations',
      'create page content',
      'edit any page content',
      'translate any entity',
      'delete any page content',
    ];
    $admin_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($admin_user);

    // Add a second and third language.
    $edit = [];
    $edit['predefined_langcode'] = 'fr';
    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm($edit, 'Add language');

    $edit = [];
    $edit['predefined_langcode'] = 'nl';
    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm($edit, 'Add language');

    // Enable translation for "Basic page" nodes.
    $edit = [
      'entity_types[node]' => 1,
      'settings[node][page][translatable]' => 1,
      "settings[node][page][fields][$this->fieldName]" => 1,
    ];
    $this->drupalGet('admin/config/regional/content-language');
    $this->submitForm($edit, 'Save configuration');
  }

  /**
   * Tests synced file fields on translated nodes.
   */
  public function testSyncedFiles() {
    // Verify that the file field on the "Basic page" node type is translatable.
    $definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', 'page');
    $this->assertTrue($definitions[$this->fieldName]->isTranslatable(), 'Node file field is translatable.');

    // Create a default language node.
    $default_language_node = $this->drupalCreateNode(['type' => 'page', 'title' => 'Lost in translation']);

    // Edit the node to upload a file.
    $edit = [];
    $name = 'files[' . $this->fieldName . '_0]';
    $edit[$name] = \Drupal::service('file_system')->realpath($this->drupalGetTestFiles('text')[0]->uri);
    $this->drupalGet('node/' . $default_language_node->id() . '/edit');
    $this->submitForm($edit, 'Save');
    $first_fid = $this->getLastFileId();

    // Translate the node into French: remove the existing file.
    $this->drupalGet('node/' . $default_language_node->id() . '/translations/add/en/fr');
    $this->submitForm([], 'Remove');

    // Upload a different file.
    $edit = [];
    $edit['title[0][value]'] = 'Bill Murray';
    $name = 'files[' . $this->fieldName . '_0]';
    $edit[$name] = \Drupal::service('file_system')->realpath($this->drupalGetTestFiles('text')[1]->uri);
    $this->submitForm($edit, 'Save (this translation)');
    // This inspects the HTML after the post of the translation, the file
    // should be displayed on the original node.
    $this->assertSession()->responseContains('file--mime-text-plain');
    $second_fid = $this->getLastFileId();

    \Drupal::entityTypeManager()->getStorage('file')->resetCache();

    /** @var \Drupal\file\FileInterface $file */

    // Ensure the file status of the first file permanent.
    $file = File::load($first_fid);
    $this->assertTrue($file->isPermanent());

    // Ensure the file status of the second file is permanent.
    $file = File::load($second_fid);
    $this->assertTrue($file->isPermanent());

    // Translate the node into dutch: remove the existing file.
    $this->drupalGet('node/' . $default_language_node->id() . '/translations/add/en/nl');
    $this->submitForm([], 'Remove');

    // Upload a different file.
    $edit = [];
    $edit['title[0][value]'] = 'Scarlett Johansson';
    $name = 'files[' . $this->fieldName . '_0]';
    $edit[$name] = \Drupal::service('file_system')->realpath($this->drupalGetTestFiles('text')[2]->uri);
    $this->submitForm($edit, 'Save (this translation)');
    $third_fid = $this->getLastFileId();

    \Drupal::entityTypeManager()->getStorage('file')->resetCache();

    // Ensure the first file is untouched.
    $file = File::load($first_fid);
    $this->assertTrue($file->isPermanent(), 'First file still exists and is permanent.');
    // This inspects the HTML after the post of the translation, the file
    // should be displayed on the original node.
    $this->assertSession()->responseContains('file--mime-text-plain');

    // Ensure the file status of the second file is permanent.
    $file = File::load($second_fid);
    $this->assertTrue($file->isPermanent());

    // Ensure the file status of the third file is permanent.
    $file = File::load($third_fid);
    $this->assertTrue($file->isPermanent());

    // Edit the second translation: remove the existing file.
    $this->drupalGet('fr/node/' . $default_language_node->id() . '/edit');
    $this->submitForm([], 'Remove');

    // Upload a different file.
    $edit = [];
    $edit['title[0][value]'] = 'David Bowie';
    $name = 'files[' . $this->fieldName . '_0]';
    $edit[$name] = \Drupal::service('file_system')->realpath($this->drupalGetTestFiles('text')[3]->uri);
    $this->submitForm($edit, 'Save (this translation)');
    $replaced_second_fid = $this->getLastFileId();

    \Drupal::entityTypeManager()->getStorage('file')->resetCache();

    // Ensure the first and third files are untouched.
    $file = File::load($first_fid);
    $this->assertTrue($file->isPermanent(), 'First file still exists and is permanent.');

    $file = File::load($third_fid);
    $this->assertTrue($file->isPermanent());

    // Ensure the file status of the replaced second file is permanent.
    $file = File::load($replaced_second_fid);
    $this->assertTrue($file->isPermanent());

    // Delete the third translation.
    $this->drupalGet('nl/node/' . $default_language_node->id() . '/delete');
    $this->submitForm([], 'Delete Dutch translation');

    \Drupal::entityTypeManager()->getStorage('file')->resetCache();

    // Ensure the first and replaced second files are untouched.
    $file = File::load($first_fid);
    $this->assertTrue($file->isPermanent(), 'First file still exists and is permanent.');

    $file = File::load($replaced_second_fid);
    $this->assertTrue($file->isPermanent());

    // Ensure the file status of the third file is now temporary.
    $file = File::load($third_fid);
    $this->assertTrue($file->isTemporary());

    // Delete the all translations.
    $this->drupalGet('node/' . $default_language_node->id() . '/delete');
    $this->submitForm([], 'Delete all translations');

    \Drupal::entityTypeManager()->getStorage('file')->resetCache();

    // Ensure the file status of the all files are now temporary.
    $file = File::load($first_fid);
    $this->assertTrue($file->isTemporary(), 'First file still exists and is temporary.');

    $file = File::load($replaced_second_fid);
    $this->assertTrue($file->isTemporary());
  }

}
