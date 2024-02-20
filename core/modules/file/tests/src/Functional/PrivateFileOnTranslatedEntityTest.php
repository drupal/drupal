<?php

namespace Drupal\Tests\file\Functional;

use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\Tests\content_translation\Traits\ContentTranslationTestTrait;

/**
 * Uploads private files to translated node and checks access.
 *
 * @group file
 */
class PrivateFileOnTranslatedEntityTest extends FileFieldTestBase {

  use ContentTranslationTestTrait;

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
    $this->fieldName = $this->randomMachineName();
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
    static::createLanguageFromLangcode('fr');

    // Enable translation for "Basic page" nodes.
    static::enableContentTranslation('node', 'page');
    static::setFieldTranslatable('node', 'page', $this->fieldName, TRUE);
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
    $file = File::create(
      [
        'uri' => $this->drupalGetTestFiles('text')[0]->uri,
      ]
    );
    $file->save();

    $default_language_node->set($this->fieldName, $file->id());
    $default_language_node->save();
    $last_fid_prior = $this->getLastFileId();

    // Languages are cached on many levels, and we need to clear those caches.
    $this->rebuildContainer();

    // Ensure the file can be downloaded.
    \Drupal::entityTypeManager()->getStorage('node')->resetCache([$default_language_node->id()]);
    $node = Node::load($default_language_node->id());
    $node_file = File::load($node->{$this->fieldName}->target_id);
    $this->drupalGet($node_file->createFileUrl(FALSE));
    $this->assertSession()->statusCodeEquals(200);

    // Translate the node into French.
    $node->addTranslation(
      'fr', [
        'title' => $this->randomString(),
      ]
    );
    $node->save();

    // Remove the existing file.
    $existing_file = $node->{$this->fieldName}->entity;
    if ($existing_file) {
      $node->set($this->fieldName, NULL);
      $existing_file->delete();
      $node->save();
    }

    // Upload a different file.
    $default_language_node = $node->getTranslation('fr');
    $file = File::create(
      [
        'uri' => $this->drupalGetTestFiles('text')[1]->uri,
      ]
    );
    $file->save();
    $default_language_node->set($this->fieldName, $file->id());
    $default_language_node->save();
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
    $this->drupalGet($node_file->createFileUrl(FALSE));
    $this->assertSession()->statusCodeEquals(200);
  }

}
