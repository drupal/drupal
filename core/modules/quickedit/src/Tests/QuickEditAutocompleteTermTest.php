<?php

namespace Drupal\quickedit\Tests;

use Drupal\Component\Serialization\Json;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\field\Tests\EntityReference\EntityReferenceTestTrait;
use Drupal\simpletest\WebTestBase;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\Entity\Term;

/**
 * Tests in-place editing of autocomplete tags.
 *
 * @group quickedit
 */
class QuickEditAutocompleteTermTest extends WebTestBase {

  use EntityReferenceTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'taxonomy', 'quickedit');

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

  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(array(
      'type' => 'article',
    ));
    // Create the vocabulary for the tag field.
    $this->vocabulary = Vocabulary::create([
      'name' => 'quickedit testing tags',
      'vid' => 'quickedit_testing_tags',
    ]);
    $this->vocabulary->save();
    $this->fieldName = 'field_' . $this->vocabulary->id();

    $handler_settings = array(
      'target_bundles' => array(
        $this->vocabulary->id() => $this->vocabulary->id(),
      ),
      'auto_create' => TRUE,
    );
    $this->createEntityReferenceField('node', 'article', $this->fieldName, 'Tags', 'taxonomy_term', 'default', $handler_settings, FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    entity_get_form_display('node', 'article', 'default')
      ->setComponent($this->fieldName, [
        'type' => 'entity_reference_autocomplete_tags',
        'weight' => -4,
      ])
      ->save();

    entity_get_display('node', 'article', 'default')
      ->setComponent($this->fieldName, [
        'type' => 'entity_reference_label',
        'weight' => 10,
      ])
      ->save();
    entity_get_display('node', 'article', 'teaser')
      ->setComponent($this->fieldName, [
        'type' => 'entity_reference_label',
        'weight' => 10,
      ])
      ->save();

    $this->term1 = $this->createTerm();
    $this->term2 = $this->createTerm();

    $node = array();
    $node['type'] = 'article';
    $node[$this->fieldName][]['target_id'] = $this->term1->id();
    $node[$this->fieldName][]['target_id'] = $this->term2->id();
    $this->node = $this->drupalCreateNode($node);

    $this->editorUser = $this->drupalCreateUser(['access content', 'create article content', 'edit any article content', 'access in-place editing']);
  }

  /**
   * Tests Quick Edit autocomplete term behavior.
   */
  public function testAutocompleteQuickEdit() {
    $this->drupalLogin($this->editorUser);

    $quickedit_uri = 'quickedit/form/node/' . $this->node->id() . '/' . $this->fieldName . '/' . $this->node->language()->getId() . '/full';
    $post = array('nocssjs' => 'true') + $this->getAjaxPageStatePostData();
    $response = $this->drupalPost($quickedit_uri, '', $post, ['query' => [MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_ajax']]);
    $ajax_commands = Json::decode($response);

    // Prepare form values for submission. drupalPostAJAX() is not suitable for
    // handling pages with JSON responses, so we need our own solution here.
    $form_tokens_found = preg_match('/\sname="form_token" value="([^"]+)"/', $ajax_commands[0]['data'], $token_match) && preg_match('/\sname="form_build_id" value="([^"]+)"/', $ajax_commands[0]['data'], $build_id_match);
    $this->assertTrue($form_tokens_found, 'Form tokens found in output.');

    if ($form_tokens_found) {
      $post = array(
        'form_id' => 'quickedit_field_form',
        'form_token' => $token_match[1],
        'form_build_id' => $build_id_match[1],
        $this->fieldName . '[target_id]' => implode(', ', array($this->term1->getName(), 'new term', $this->term2->getName())),
        'op' => t('Save'),
      );

      // Submit field form and check response. Should render back all the terms.
      $response = $this->drupalPost($quickedit_uri, '', $post, ['query' => [MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_ajax']]);
      $this->assertResponse(200);
      $ajax_commands = Json::decode($response);
      $this->setRawContent($ajax_commands[0]['data']);
      $this->assertLink($this->term1->getName());
      $this->assertLink($this->term2->getName());
      $this->assertText('new term');
      $this->assertNoLink('new term');

      // Load the form again, which should now get it back from
      // PrivateTempStore.
      $quickedit_uri = 'quickedit/form/node/' . $this->node->id() . '/' . $this->fieldName . '/' . $this->node->language()->getId() . '/full';
      $post = array('nocssjs' => 'true') + $this->getAjaxPageStatePostData();
      $response = $this->drupalPost($quickedit_uri, '', $post, ['query' => [MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_ajax']]);
      $ajax_commands = Json::decode($response);

      // The AjaxResponse's first command is an InsertCommand which contains
      // the form to edit the taxonomy term field, it should contain all three
      // taxonomy terms, including the one that has just been newly created and
      // which is not yet stored.
      $this->setRawContent($ajax_commands[0]['data']);
      $expected = array(
        $this->term1->getName() . ' (' . $this->term1->id() . ')',
        'new term',
        $this->term2->getName() . ' (' . $this->term2->id() . ')',
      );
      $this->assertFieldByName($this->fieldName . '[target_id]', implode(', ', $expected));

      // Save the entity.
      $post = array('nocssjs' => 'true');
      $response = $this->drupalPostWithFormat('quickedit/entity/node/' . $this->node->id(), 'json', $post);
      $this->assertResponse(200);

      // The full node display should now link to all entities, with the new
      // one created in the database as well.
      $this->drupalGet('node/' . $this->node->id());
      $this->assertLink($this->term1->getName());
      $this->assertLink($this->term2->getName());
      $this->assertLink('new term');
    }
  }

  /**
   * Returns a new term with random name and description in $this->vocabulary.
   *
   * @return \Drupal\taxonomy\TermInterface
   *   The created taxonomy term.
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
