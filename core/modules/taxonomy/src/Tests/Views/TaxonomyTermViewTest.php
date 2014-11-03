<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Tests\Views\TaxonomyTermViewTest.
 */

namespace Drupal\taxonomy\Tests\Views;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Language\Language;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\user\Entity\Role;

/**
 * Tests the taxonomy term view page and its translation.
 *
 * @group taxonomy
 */
class TaxonomyTermViewTest extends TaxonomyTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('taxonomy', 'views');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create an administrative user.
    $this->admin_user = $this->drupalCreateUser(array('administer taxonomy', 'bypass node access'));
    $this->drupalLogin($this->admin_user);

    // Create a vocabulary and add two term reference fields to article nodes.

    $this->field_name_1 = Unicode::strtolower($this->randomMachineName());
    entity_create('field_storage_config', array(
      'field_name' => $this->field_name_1,
      'entity_type' => 'node',
      'type' => 'taxonomy_term_reference',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'settings' => array(
        'allowed_values' => array(
          array(
            'vocabulary' => $this->vocabulary->id(),
            'parent' => 0,
          ),
        ),
      ),
    ))->save();
    entity_create('field_config', array(
      'field_name' => $this->field_name_1,
      'bundle' => 'article',
      'entity_type' => 'node',
    ))->save();
    entity_get_form_display('node', 'article', 'default')
      ->setComponent($this->field_name_1, array(
        'type' => 'options_select',
      ))
      ->save();
    entity_get_display('node', 'article', 'default')
      ->setComponent($this->field_name_1, array(
        'type' => 'taxonomy_term_reference_link',
      ))
      ->save();
  }

  /**
   * Tests that the taxonomy term view is working properly.
   */
  public function testTaxonomyTermView() {
    // Create terms in the vocabulary.
    $term = $this->createTerm($this->vocabulary);

    // Post an article.
    $edit = array();
    $edit['title[0][value]'] = $original_title = $this->randomMachineName();
    $edit['body[0][value]'] = $this->randomMachineName();
    $edit["{$this->field_name_1}[]"] = $term->id();
    $this->drupalPostForm('node/add/article', $edit, t('Save'));
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);

    $this->drupalGet('taxonomy/term/' . $term->id());
    $this->assertText($term->label());
    $this->assertText($node->label());

    \Drupal::moduleHandler()->install(array('language', 'content_translation'));
    $language = ConfigurableLanguage::createFromLangcode('ur');
    $language->save();
    // Enable translation for the article content type and ensure the change is
    // picked up.
    content_translation_set_config('node', 'article', 'enabled', TRUE);
    $roles = $this->admin_user->getRoles(TRUE);
    Role::load(reset($roles))
      ->grantPermission('create content translations')
      ->grantPermission('translate any entity')
      ->save();
    drupal_static_reset();
    \Drupal::entityManager()->clearCachedDefinitions();
    \Drupal::service('router.builder')->rebuild();

    $edit['title[0][value]'] = $translated_title = $this->randomMachineName();

    $this->drupalPostForm('node/' . $node->id() . '/translations/add/en/ur', $edit, t('Save (this translation)'));

    $this->drupalGet('taxonomy/term/' . $term->id());
    $this->assertText($term->label());
    $this->assertText($original_title);
    $this->assertNoText($translated_title);

    $this->drupalGet('ur/taxonomy/term/' . $term->id());
    $this->assertText($term->label());
    $this->assertNoText($original_title);
    $this->assertText($translated_title);
  }

}
