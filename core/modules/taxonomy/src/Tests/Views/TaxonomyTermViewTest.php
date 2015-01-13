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
use Drupal\views\Views;

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
   * An user with permissions to administer taxonomy.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Name of the taxonomy term reference field.
   *
   * @var string
   */
  protected $fieldName1;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create an administrative user.
    $this->adminUser = $this->drupalCreateUser(['administer taxonomy', 'bypass node access']);
    $this->drupalLogin($this->adminUser);

    // Create a vocabulary and add two term reference fields to article nodes.

    $this->fieldName1 = Unicode::strtolower($this->randomMachineName());
    entity_create('field_storage_config', array(
      'field_name' => $this->fieldName1,
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
      'field_name' => $this->fieldName1,
      'bundle' => 'article',
      'entity_type' => 'node',
    ))->save();
    entity_get_form_display('node', 'article', 'default')
      ->setComponent($this->fieldName1, array(
        'type' => 'options_select',
      ))
      ->save();
    entity_get_display('node', 'article', 'default')
      ->setComponent($this->fieldName1, array(
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
    $edit["{$this->fieldName1}[]"] = $term->id();
    $this->drupalPostForm('node/add/article', $edit, t('Save'));
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);

    $this->drupalGet('taxonomy/term/' . $term->id());
    $this->assertText($term->label());
    $this->assertText($node->label());

    \Drupal::service('module_installer')->install(array('language', 'content_translation'));
    $language = ConfigurableLanguage::createFromLangcode('ur');
    $language->save();
    // Enable translation for the article content type and ensure the change is
    // picked up.
    \Drupal::service('content_translation.manager')->setEnabled('node', 'article', TRUE);
    $roles = $this->adminUser->getRoles(TRUE);
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

    // Uninstall language module and ensure that the language is not part of the
    // query anymore.
    // @see \Drupal\views\Plugin\views\filter\LanguageFilter::query()
    \Drupal::service('module_installer')->uninstall(['content_translation', 'language']);

    $view = Views::getView('taxonomy_term');
    $view->initDisplay();
    $view->setArguments([$term->id()]);
    $view->build();
    /** @var \Drupal\Core\Database\Query\Select $query */
    $query = $view->build_info['query'];
    $tables = $query->getTables();

    // Ensure that the join to node_field_data is not added by default.
    $this->assertEqual(['node', 'taxonomy_index'], array_keys($tables));
    // Ensure that the filter to the language column is not there by default.
    $condition = $query->conditions();
    // We only want to check the no. of conditions in the query.
    unset($condition['#conjunction']);
    $this->assertEqual(1, count($condition));
  }

}
