<?php

namespace Drupal\Tests\taxonomy\Functional\Views;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;
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
  public static $modules = ['taxonomy', 'views'];

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
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    // Create an administrative user.
    $this->adminUser = $this->drupalCreateUser(['administer taxonomy', 'bypass node access']);
    $this->drupalLogin($this->adminUser);

    // Create a vocabulary and add two term reference fields to article nodes.

    $this->fieldName1 = Unicode::strtolower($this->randomMachineName());

    $handler_settings = [
      'target_bundles' => [
        $this->vocabulary->id() => $this->vocabulary->id(),
      ],
      'auto_create' => TRUE,
    ];
    $this->createEntityReferenceField('node', 'article', $this->fieldName1, NULL, 'taxonomy_term', 'default', $handler_settings, FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    entity_get_form_display('node', 'article', 'default')
      ->setComponent($this->fieldName1, [
        'type' => 'options_select',
      ])
      ->save();
    entity_get_display('node', 'article', 'default')
      ->setComponent($this->fieldName1, [
        'type' => 'entity_reference_label',
      ])
      ->save();
  }

  /**
   * Tests that the taxonomy term view is working properly.
   */
  public function testTaxonomyTermView() {
    // Create terms in the vocabulary.
    $term = $this->createTerm();

    // Post an article.
    $edit = [];
    $edit['title[0][value]'] = $original_title = $this->randomMachineName();
    $edit['body[0][value]'] = $this->randomMachineName();
    $edit["{$this->fieldName1}[]"] = $term->id();
    $this->drupalPostForm('node/add/article', $edit, t('Save'));
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);

    $this->drupalGet('taxonomy/term/' . $term->id());
    $this->assertText($term->label());
    $this->assertText($node->label());

    \Drupal::service('module_installer')->install(['language', 'content_translation']);
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
    \Drupal::service('entity.definition_update_manager')->applyUpdates();

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
    $node->delete();
    \Drupal::service('module_installer')->uninstall(['content_translation', 'language']);

    $view = Views::getView('taxonomy_term');
    $view->initDisplay();
    $view->setArguments([$term->id()]);
    $view->build();
    /** @var \Drupal\Core\Database\Query\Select $query */
    $query = $view->build_info['query'];
    $tables = $query->getTables();

    // Ensure that the join to node_field_data is not added by default.
    $this->assertEqual(['node_field_data', 'taxonomy_index'], array_keys($tables));
    // Ensure that the filter to the language column is not there by default.
    $condition = $query->conditions();
    // We only want to check the no. of conditions in the query.
    unset($condition['#conjunction']);
    $this->assertEqual(1, count($condition));

    // Clear permissions for anonymous users to check access for default views.
    Role::load(RoleInterface::ANONYMOUS_ID)->revokePermission('access content')->save();

    // Test the default views disclose no data by default.
    $this->drupalLogout();
    $this->drupalGet('taxonomy/term/' . $term->id());
    $this->assertResponse(403);
    $this->drupalGet('taxonomy/term/' . $term->id() . '/feed');
    $this->assertResponse(403);
  }

}
