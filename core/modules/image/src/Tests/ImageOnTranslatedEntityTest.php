<?php

/**
 * @file
 * Contains \Drupal\image\Tests\ImageOnTranslatedEntityTest.
 */

namespace Drupal\image\Tests;

use Drupal\file\Entity\File;

/**
 * Uploads images to translated nodes.
 *
 * @group image
 */
class ImageOnTranslatedEntityTest extends ImageFieldTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('language', 'content_translation', 'field_ui');

  /**
   * The name of the image field used in the test.
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
    $this->drupalCreateContentType(array('type' => 'basicpage', 'name' => 'Basic page'));

    // Create a image field on the "Basic page" node type.
    $this->fieldName = strtolower($this->randomMachineName());
    $this->createImageField($this->fieldName, 'basicpage', [], ['title_field' => 1]);

    // Create and login user.
    $permissions = array(
      'access administration pages',
      'administer content translation',
      'administer content types',
      'administer languages',
      'administer node fields',
      'create content translations',
      'create basicpage content',
      'edit any basicpage content',
      'translate any entity',
      'delete any basicpage content',
    );
    $admin_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($admin_user);

    // Add a second and third language.
    $edit = array();
    $edit['predefined_langcode'] = 'fr';
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add language'));

    $edit = array();
    $edit['predefined_langcode'] = 'nl';
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add language'));
  }

  /**
   * Tests synced file fields on translated nodes.
   */
  public function testSyncedImages() {
    // Enable translation for "Basic page" nodes.
    $edit = array(
      'entity_types[node]' => 1,
      'settings[node][basicpage][translatable]' => 1,
      "settings[node][basicpage][fields][$this->fieldName]" => 1,
      "settings[node][basicpage][columns][$this->fieldName][file]" => 1,
      // Explicitly disable alt and title since the javascript disables the
      // checkboxes on the form.
      "settings[node][basicpage][columns][$this->fieldName][alt]" => FALSE,
      "settings[node][basicpage][columns][$this->fieldName][title]" => FALSE,
    );
    $this->drupalPostForm('admin/config/regional/content-language', $edit, 'Save configuration');

    // Verify that the image field on the "Basic basic" node type is
    // translatable.
    $definitions = \Drupal::entityManager()->getFieldDefinitions('node', 'basicpage');
    $this->assertTrue($definitions[$this->fieldName]->isTranslatable(), 'Node image field is translatable.');

    // Create a default language node.
    $default_language_node = $this->drupalCreateNode(array('type' => 'basicpage', 'title' => 'Lost in translation'));

    // Edit the node to upload a file.
    $edit = array();
    $name = 'files[' . $this->fieldName . '_0]';
    $edit[$name] = drupal_realpath($this->drupalGetTestFiles('image')[0]->uri);
    $this->drupalPostForm('node/' . $default_language_node->id() . '/edit', $edit, t('Save'));
    $edit = [$this->fieldName . '[0][alt]' => 'Lost in translation image', $this->fieldName . '[0][title]' => 'Lost in translation image title'];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $first_fid = $this->getLastFileId();

    // Translate the node into French: remove the existing file.
    $this->drupalPostForm('node/' . $default_language_node->id() . '/translations/add/en/fr', array(), t('Remove'));

    // Upload a different file.
    $edit = array();
    $edit['title[0][value]'] = 'Scarlett Johansson';
    $name = 'files[' . $this->fieldName . '_0]';
    $edit[$name] = drupal_realpath($this->drupalGetTestFiles('image')[1]->uri);
    $this->drupalPostForm(NULL, $edit, t('Save (this translation)'));
    $edit = [$this->fieldName . '[0][alt]' => 'Scarlett Johansson image', $this->fieldName . '[0][title]' => 'Scarlett Johansson image title'];
    $this->drupalPostForm(NULL, $edit, t('Save (this translation)'));
    // This inspects the HTML after the post of the translation, the image
    // should be displayed on the original node.
    $this->assertRaw('alt="Lost in translation image"');
    $this->assertRaw('title="Lost in translation image title"');
    $second_fid = $this->getLastFileId();
    // View the translated node.
    $this->drupalGet('fr/node/' . $default_language_node->id());
    $this->assertRaw('alt="Scarlett Johansson image"');

    \Drupal::entityTypeManager()->getStorage('file')->resetCache();

    /* @var $file \Drupal\file\FileInterface */

    // Ensure the file status of the first file permanent.
    $file = File::load($first_fid);
    $this->assertTrue($file->isPermanent());

    // Ensure the file status of the second file is permanent.
    $file = File::load($second_fid);
    $this->assertTrue($file->isPermanent());

    // Translate the node into dutch: remove the existing file.
    $this->drupalPostForm('node/' . $default_language_node->id() . '/translations/add/en/nl', array(), t('Remove'));

    // Upload a different file.
    $edit = array();
    $edit['title[0][value]'] = 'Akiko Takeshita';
    $name = 'files[' . $this->fieldName . '_0]';
    $edit[$name] = drupal_realpath($this->drupalGetTestFiles('image')[2]->uri);
    $this->drupalPostForm(NULL, $edit, t('Save (this translation)'));
    $edit = [$this->fieldName . '[0][alt]' => 'Akiko Takeshita image', $this->fieldName . '[0][title]' => 'Akiko Takeshita image title'];
    $this->drupalPostForm(NULL, $edit, t('Save (this translation)'));
    $third_fid = $this->getLastFileId();

    \Drupal::entityTypeManager()->getStorage('file')->resetCache();

    // Ensure the first file is untouched.
    $file = File::load($first_fid);
    $this->assertTrue($file->isPermanent(), 'First file still exists and is permanent.');
    // This inspects the HTML after the post of the translation, the image
    // should be displayed on the original node.
    $this->assertRaw('alt="Lost in translation image"');
    $this->assertRaw('title="Lost in translation image title"');
    // View the translated node.
    $this->drupalGet('nl/node/' . $default_language_node->id());
    $this->assertRaw('alt="Akiko Takeshita image"');
    $this->assertRaw('title="Akiko Takeshita image title"');

    // Ensure the file status of the second file is permanent.
    $file = File::load($second_fid);
    $this->assertTrue($file->isPermanent());

    // Ensure the file status of the third file is permanent.
    $file = File::load($third_fid);
    $this->assertTrue($file->isPermanent());

    // Edit the second translation: remove the existing file.
    $this->drupalPostForm('fr/node/' . $default_language_node->id() . '/edit', array(), t('Remove'));

    // Upload a different file.
    $edit = array();
    $edit['title[0][value]'] = 'Giovanni Ribisi';
    $name = 'files[' . $this->fieldName . '_0]';
    $edit[$name] = drupal_realpath($this->drupalGetTestFiles('image')[3]->uri);
    $this->drupalPostForm(NULL, $edit, t('Save (this translation)'));
    $name = $this->fieldName . '[0][alt]';

    $edit = [$name => 'Giovanni Ribisi image'];
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

    // Ensure the file status of the old second file is now temporary.
    $file = File::load($second_fid);
    $this->assertTrue($file->isTemporary());

    // Delete the third translation.
    $this->drupalPostForm('nl/node/' . $default_language_node->id() . '/delete', array(), t('Delete Dutch translation'));

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
    $this->drupalPostForm('node/' . $default_language_node->id() . '/delete', array(), t('Delete all translations'));

    \Drupal::entityTypeManager()->getStorage('file')->resetCache();

    // Ensure the file status of the all files are now temporary.
    $file = File::load($first_fid);
    $this->assertTrue($file->isTemporary(), 'First file still exists and is temporary.');

    $file = File::load($replaced_second_fid);
    $this->assertTrue($file->isTemporary());
  }

}
