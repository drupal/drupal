<?php

namespace Drupal\file\Tests;

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
  public static $modules = ['language', 'content_translation'];

  /**
   * The name of the file field used in the test.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

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
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add language'));

    $edit = [];
    $edit['predefined_langcode'] = 'nl';
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add language'));

    // Enable translation for "Basic page" nodes.
    $edit = [
      'entity_types[node]' => 1,
      'settings[node][page][translatable]' => 1,
      "settings[node][page][fields][$this->fieldName]" => 1,
    ];
    $this->drupalPostForm('admin/config/regional/content-language', $edit, t('Save configuration'));
    \Drupal::entityManager()->clearCachedDefinitions();
  }

  /**
   * Tests synced file fields on translated nodes.
   */
  public function testSyncedFiles() {
    // Verify that the file field on the "Basic page" node type is translatable.
    $definitions = \Drupal::entityManager()->getFieldDefinitions('node', 'page');
    $this->assertTrue($definitions[$this->fieldName]->isTranslatable(), 'Node file field is translatable.');

    // Create a default language node.
    $default_language_node = $this->drupalCreateNode(['type' => 'page', 'title' => 'Lost in translation']);

    // Edit the node to upload a file.
    $edit = [];
    $name = 'files[' . $this->fieldName . '_0]';
    $edit[$name] = drupal_realpath($this->drupalGetTestFiles('text')[0]->uri);
    $this->drupalPostForm('node/' . $default_language_node->id() . '/edit', $edit, t('Save'));
    $first_fid = $this->getLastFileId();

    // Translate the node into French: remove the existing file.
    $this->drupalPostForm('node/' . $default_language_node->id() . '/translations/add/en/fr', [], t('Remove'));

    // Upload a different file.
    $edit = [];
    $edit['title[0][value]'] = 'Bill Murray';
    $name = 'files[' . $this->fieldName . '_0]';
    $edit[$name] = drupal_realpath($this->drupalGetTestFiles('text')[1]->uri);
    $this->drupalPostForm(NULL, $edit, t('Save (this translation)'));
    // This inspects the HTML after the post of the translation, the file
    // should be displayed on the original node.
    $this->assertRaw('file--mime-text-plain');
    $second_fid = $this->getLastFileId();

    \Drupal::entityTypeManager()->getStorage('file')->resetCache();

    /* @var $file \Drupal\file\FileInterface */

    // Ensure the file status of the first file permanent.
    $file = File::load($first_fid);
    $this->assertTrue($file->isPermanent());

    // Ensure the file status of the second file is permanent.
    $file = File::load($second_fid);
    $this->assertTrue($file->isPermanent());

    // Translate the node into dutch: remove the existing file.
    $this->drupalPostForm('node/' . $default_language_node->id() . '/translations/add/en/nl', [], t('Remove'));

    // Upload a different file.
    $edit = [];
    $edit['title[0][value]'] = 'Scarlett Johansson';
    $name = 'files[' . $this->fieldName . '_0]';
    $edit[$name] = drupal_realpath($this->drupalGetTestFiles('text')[2]->uri);
    $this->drupalPostForm(NULL, $edit, t('Save (this translation)'));
    $third_fid = $this->getLastFileId();

    \Drupal::entityTypeManager()->getStorage('file')->resetCache();

    // Ensure the first file is untouched.
    $file = File::load($first_fid);
    $this->assertTrue($file->isPermanent(), 'First file still exists and is permanent.');
    // This inspects the HTML after the post of the translation, the file
    // should be displayed on the original node.
    $this->assertRaw('file--mime-text-plain');

    // Ensure the file status of the second file is permanent.
    $file = File::load($second_fid);
    $this->assertTrue($file->isPermanent());

    // Ensure the file status of the third file is permanent.
    $file = File::load($third_fid);
    $this->assertTrue($file->isPermanent());

    // Edit the second translation: remove the existing file.
    $this->drupalPostForm('fr/node/' . $default_language_node->id() . '/edit', [], t('Remove'));

    // Upload a different file.
    $edit = [];
    $edit['title[0][value]'] = 'David Bowie';
    $name = 'files[' . $this->fieldName . '_0]';
    $edit[$name] = drupal_realpath($this->drupalGetTestFiles('text')[3]->uri);
    $this->drupalPostForm(NULL, $edit, t('Save (this translation)'));
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
    $this->drupalPostForm('nl/node/' . $default_language_node->id() . '/delete', [], t('Delete Dutch translation'));

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
    $this->drupalPostForm('node/' . $default_language_node->id() . '/delete', [], t('Delete all translations'));

    \Drupal::entityTypeManager()->getStorage('file')->resetCache();

    // Ensure the file status of the all files are now temporary.
    $file = File::load($first_fid);
    $this->assertTrue($file->isTemporary(), 'First file still exists and is temporary.');

    $file = File::load($replaced_second_fid);
    $this->assertTrue($file->isTemporary());
  }

}
