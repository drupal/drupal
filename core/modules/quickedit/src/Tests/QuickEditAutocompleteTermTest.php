<?php

/**
 * @file
 * Contains \Drupal\quickedit\Tests\QuickEditAutocompleteTermTest.
 */

namespace Drupal\quickedit\Tests;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Tests using in-place editing for an autocomplete entity reference widget.
 */
class QuickEditAutocompleteTermTest extends WebTestBase {

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
  protected $field_name;

  public static function getInfo() {
    return array(
      'name' => 'In-place editing of autocomplete tags',
      'description' => 'Tests in-place editing of autocomplete tags.',
      'group' => 'Quick Edit',
    );
  }

  protected function setUp() {
    parent::setUp();

    $type = $this->drupalCreateContentType(array(
      'type' => 'article',
    ));
    // Create the vocabulary for the tag field.
    $this->vocabulary = entity_create('taxonomy_vocabulary',  array(
      'name' => 'quickedit testing tags',
      'vid' => 'quickedit_testing_tags',
    ));
    $this->vocabulary->save();
    $this->field_name = 'field_' . $this->vocabulary->id();
    entity_create('field_config', array(
      'name' => $this->field_name,
      'entity_type' => 'node',
      'type' => 'taxonomy_term_reference',
      // Set cardinality to unlimited for tagging.
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
    $instance = entity_create('field_instance_config', array(
      'field_name' => $this->field_name,
      'entity_type' => 'node',
      'label' => 'Tags',
      'bundle' => 'article',
    ))->save();

    entity_get_form_display('node', 'article', 'default')
      ->setComponent($this->field_name, array(
        'type' => 'taxonomy_autocomplete',
        'weight' => -4,
      ))
      ->save();

    entity_get_display('node', 'article', 'default')
      ->setComponent($this->field_name, array(
        'type' => 'taxonomy_term_reference_link',
        'weight' => 10,
      ))
      ->save();
    entity_get_display('node', 'article', 'teaser')
      ->setComponent($this->field_name, array(
        'type' => 'taxonomy_term_reference_link',
        'weight' => 10,
      ))
      ->save();

    $this->term1 = $this->createTerm();
    $this->term2 = $this->createTerm();

    $node = array();
    $node['type'] = 'article';
    $node[$this->field_name][]['target_id'] = $this->term1->id();
    $node[$this->field_name][]['target_id'] = $this->term2->id();
    $this->node = $this->drupalCreateNode($node);

    $this->editor_user = $this->drupalCreateUser(array('access content', 'create article content', 'edit any article content', 'access in-place editing'));
  }

  /**
   * Tests Quick Edit autocomplete term behavior.
   */
  public function testAutocompleteQuickEdit() {
    $this->drupalLogin($this->editor_user);

    $quickedit_uri = 'quickedit/form/node/'. $this->node->id() . '/' . $this->field_name . '/und/full';
    $post = array('nocssjs' => 'true') + $this->getAjaxPageStatePostData();
    $response = $this->drupalPost($quickedit_uri, 'application/vnd.drupal-ajax', $post);
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
        $this->field_name => implode(', ', array($this->term1->getName(), 'new term', $this->term2->getName())),
        'op' => t('Save'),
      );

      // Submit field form and check response. Should render back all the terms.
      $response = $this->drupalPost($quickedit_uri, 'application/vnd.drupal-ajax', $post);
      $this->assertResponse(200);
      $ajax_commands = Json::decode($response);
      $this->drupalSetContent($ajax_commands[0]['data']);
      $this->assertLink($this->term1->getName());
      $this->assertLink($this->term2->getName());
      $this->assertText('new term');
      $this->assertNoLink('new term');

      // Load the form again, which should now get it back from TempStore.
      $quickedit_uri = 'quickedit/form/node/'. $this->node->id() . '/' . $this->field_name . '/und/full';
      $post = array('nocssjs' => 'true') + $this->getAjaxPageStatePostData();
      $response = $this->drupalPost($quickedit_uri, 'application/vnd.drupal-ajax', $post);
      $ajax_commands = Json::decode($response);

      // The AjaxResponse's first command is an InsertCommand which contains
      // the form to edit the taxonomy term field, it should contain all three
      // taxonomy terms, including the one that has just been newly created and
      // which is not yet stored.
      $this->drupalSetContent($ajax_commands[0]['data']);
      $this->assertFieldByName($this->field_name, implode(', ', array($this->term1->getName(), 'new term', $this->term2->label())));

      // Save the entity.
      $post = array('nocssjs' => 'true');
      $response = $this->drupalPost('quickedit/entity/node/' . $this->node->id(), 'application/json', $post);
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
    $term = entity_create('taxonomy_term', array(
      'name' => $this->randomName(),
      'description' => $this->randomName(),
      // Use the first available text format.
      'format' => $format->format,
      'vid' => $this->vocabulary->id(),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ));
    $term->save();
    return $term;
  }

}
