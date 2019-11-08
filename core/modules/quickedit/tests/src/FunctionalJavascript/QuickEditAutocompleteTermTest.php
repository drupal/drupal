<?php

namespace Drupal\Tests\quickedit\FunctionalJavascript;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\contextual\FunctionalJavascript\ContextualLinkClickTrait;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;

/**
 * Tests in-place editing of autocomplete tags.
 *
 * @group quickedit
 */
class QuickEditAutocompleteTermTest extends WebDriverTestBase {

  use EntityReferenceTestTrait;
  use ContextualLinkClickTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'taxonomy',
    'quickedit',
    'contextual',
    'ckeditor',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Stores the node used for the tests.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * Stores the vocabulary used in the tests.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

  /**
   * Stores the first term used in the tests.
   *
   * @var \Drupal\taxonomy\TermInterface
   */
  protected $term1;

  /**
   * Stores the second term used in the tests.
   *
   * @var \Drupal\taxonomy\TermInterface
   */
  protected $term2;

  /**
   * Stores the field name for the autocomplete field.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * An user with permissions to access in-place editor.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $editorUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType([
      'type' => 'article',
    ]);
    $this->vocabulary = Vocabulary::create([
      'name' => 'quickedit testing tags',
      'vid' => 'quickedit_testing_tags',
    ]);
    $this->vocabulary->save();
    $this->fieldName = 'field_' . $this->vocabulary->id();

    $handler_settings = [
      'target_bundles' => [
        $this->vocabulary->id() => $this->vocabulary->id(),
      ],
      'auto_create' => TRUE,
    ];
    $this->createEntityReferenceField('node', 'article', $this->fieldName, 'Tags', 'taxonomy_term', 'default', $handler_settings, FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    \Drupal::service('entity_display.repository')->getFormDisplay('node', 'article')
      ->setComponent($this->fieldName, [
        'type' => 'entity_reference_autocomplete_tags',
        'weight' => -4,
      ])
      ->save();

    \Drupal::service('entity_display.repository')->getViewDisplay('node', 'article')
      ->setComponent($this->fieldName, [
        'type' => 'entity_reference_label',
        'weight' => 10,
      ])
      ->save();
    \Drupal::service('entity_display.repository')->getViewDisplay('node', 'article', 'teaser')
      ->setComponent($this->fieldName, [
        'type' => 'entity_reference_label',
        'weight' => 10,
      ])
      ->save();

    $this->term1 = $this->createTerm();
    $this->term2 = $this->createTerm();

    $node = [];
    $node['type'] = 'article';
    $node[$this->fieldName][]['target_id'] = $this->term1->id();
    $node[$this->fieldName][]['target_id'] = $this->term2->id();
    $this->node = $this->drupalCreateNode($node);

    $this->editorUser = $this->drupalCreateUser([
      'access content',
      'create article content',
      'edit any article content',
      'administer nodes',
      'access contextual links',
      'access in-place editing',
    ]);
  }

  /**
   * Tests Quick Edit autocomplete term behavior.
   */
  public function testAutocompleteQuickEdit() {
    $page = $this->getSession()->getPage();
    $assert = $this->assertSession();

    $this->drupalLogin($this->editorUser);
    $this->drupalGet('node/' . $this->node->id());

    // Wait "Quick edit" button for node.
    $assert->waitForElement('css', '[data-quickedit-entity-id="node/' . $this->node->id() . '"] .contextual .quickedit');
    // Click by "Quick edit".
    $this->clickContextualLink('[data-quickedit-entity-id="node/' . $this->node->id() . '"]', 'Quick edit');
    // Switch to body field.
    $page->find('css', '[data-quickedit-field-id="node/' . $this->node->id() . '/' . $this->fieldName . '/' . $this->node->language()->getId() . '/full"]')->click();

    // Open Quick Edit.
    $quickedit_field_locator = '[name="field_quickedit_testing_tags[target_id]"]';
    $tag_field = $assert->waitForElementVisible('css', $quickedit_field_locator);
    $tag_field->focus();
    $tags = $tag_field->getValue();

    // Check existing terms.
    $this->assertTrue(strpos($tags, $this->term1->label()) !== FALSE);
    $this->assertTrue(strpos($tags, $this->term2->label()) !== FALSE);

    // Add new term.
    $new_tag = $this->randomMachineName();
    $tags .= ', ' . $new_tag;
    $assert->waitForElementVisible('css', $quickedit_field_locator)->setValue($tags);
    $assert->waitOnAutocomplete();
    // Wait and click by "Save" button after body field was changed.
    $assert->waitForElementVisible('css', '.quickedit-toolgroup.ops [type="submit"][aria-hidden="false"]')->click();
    $assert->waitOnAutocomplete();

    // Reload the page and check new term.
    $this->drupalGet('node/' . $this->node->id());
    $link = $assert->waitForLink($new_tag);
    $this->assertNotEmpty($link);
  }

  /**
   * Returns a new term with random name and description in $this->vocabulary.
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\taxonomy\Entity\Term
   *   The created taxonomy term.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createTerm() {
    $filter_formats = filter_formats();
    $format = array_pop($filter_formats);
    $term = Term::create([
      'name' => $this->randomMachineName(),
      'description' => $this->randomMachineName(),
      // Use the first available text format.
      'format' => $format->id(),
      'vid' => $this->vocabulary->id(),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ]);
    $term->save();
    return $term;
  }

}
