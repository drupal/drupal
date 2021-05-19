<?php

namespace Drupal\Tests\file\Functional;

use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;

/**
 * Uploads private files to translated node and checks access.
 *
 * @group file
 */
class PrivateFileOnTranslatedEntityTest extends FileFieldTestBase {

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

    // Create the "Basic page" node type.
    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    // Create a file field on the "Basic page" node type.
    $this->fieldName = strtolower($this->randomMachineName());
    $this->createFileField($this->fieldName, 'node', 'page', ['uri_scheme' => 'private']);

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
    ];
    $admin_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($admin_user);

    // Add a second language.
    $edit = [];
    $edit['predefined_langcode'] = 'fr';
    $this->drupalPostForm('admin/config/regional/language/add', $edit, 'Add language');

    // Enable translation for "Basic page" nodes.
    $edit = [
      'entity_types[node]' => 1,
      'settings[node][page][translatable]' => 1,
      "settings[node][page][fields][$this->fieldName]" => 1,
    ];
    $this->drupalPostForm('admin/config/regional/content-language', $edit, 'Save configuration');
  }

  /**
   * Tests private file fields on translated nodes.
   */
  public function testPrivateLanguageFile() {
    // Verify that the file field on the "Basic page" node type is translatable.
    $definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', 'page');
    $this->assertTrue($definitions[$this->fieldName]->isTranslatable(), 'Node file field is translatable.');

    // Create a default language node.
    $default_language_node = $this->drupalCreateNode(['type' => 'page']);

    // Edit the node to upload a file.
    $edit = [];
    $name = 'files[' . $this->fieldName . '_0]';
    $edit[$name] = \Drupal::service('file_system')->realpath($this->drupalGetTestFiles('text')[0]->uri);
    $this->drupalPostForm('node/' . $default_language_node->id() . '/edit', $edit, 'Save');
    $last_fid_prior = $this->getLastFileId();

    // Languages are cached on many levels, and we need to clear those caches.
    $this->rebuildContainer();

    // Ensure the file can be downloaded.
    \Drupal::entityTypeManager()->getStorage('node')->resetCache([$default_language_node->id()]);
    $node = Node::load($default_language_node->id());
    $node_file = File::load($node->{$this->fieldName}->target_id);
    $this->drupalGet(file_create_url($node_file->getFileUri()));
    $this->assertSession()->statusCodeEquals(200);

    // Translate the node into French.
    $this->drupalGet('node/' . $default_language_node->id() . '/translations');
    $this->clickLink(t('Add'));

    // Remove the existing file.
    $this->submitForm([], 'Remove');

    // Upload a different file.
    $edit = [];
    $edit['title[0][value]'] = $this->randomMachineName();
    $name = 'files[' . $this->fieldName . '_0]';
    $edit[$name] = \Drupal::service('file_system')->realpath($this->drupalGetTestFiles('text')[1]->uri);
    $this->submitForm($edit, 'Save (this translation)');
    $last_fid = $this->getLastFileId();

    // Verify the translation was created.
    \Drupal::entityTypeManager()->getStorage('node')->resetCache([$default_language_node->id()]);
    $default_language_node = Node::load($default_language_node->id());
    $this->assertTrue($default_language_node->hasTranslation('fr'), 'Node found in database.');
    // Verify that the new file got saved.
    $this->assertGreaterThan($last_fid_prior, $last_fid);

    // Ensure the file attached to the translated node can be downloaded.
    $french_node = $default_language_node->getTranslation('fr');
    $node_file = File::load($french_node->{$this->fieldName}->target_id);
    $this->drupalGet(file_create_url($node_file->getFileUri()));
    $this->assertSession()->statusCodeEquals(200);
  }

}
