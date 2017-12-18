<?php

namespace Drupal\quickedit\Tests;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Unicode;
use Drupal\block_content\Entity\BlockContent;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\simpletest\WebTestBase;
use Drupal\filter\Entity\FilterFormat;

/**
 * Tests loading of in-place editing functionality and lazy loading of its
 * in-place editors.
 *
 * @group quickedit
 */
class QuickEditLoadingTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'contextual',
    'quickedit',
    'filter',
    'node',
    'image',
  ];

  /**
   * An user with permissions to create and edit articles.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $authorUser;

  /**
   * A test node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $testNode;

  /**
   * A author user with permissions to access in-place editor.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $editorUser;

  protected function setUp() {
    parent::setUp();

    // Create a text format.
    $filtered_html_format = FilterFormat::create([
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
      'weight' => 0,
      'filters' => [],
    ]);
    $filtered_html_format->save();

    // Create a node type.
    $this->drupalCreateContentType([
      'type' => 'article',
      'name' => 'Article',
    ]);

    // Set the node type to initially not have revisions.
    // Testing with revisions will be done later.
    $node_type = NodeType::load('article');
    $node_type->setNewRevision(FALSE);
    $node_type->save();

    // Create one node of the above node type using the above text format.
    $this->testNode = $this->drupalCreateNode([
      'type' => 'article',
      'body' => [
        0 => [
          'value' => '<p>How are you?</p>',
          'format' => 'filtered_html',
        ]
      ],
      'revision_log' => $this->randomString(),
    ]);

    // Create 2 users, the only difference being the ability to use in-place
    // editing
    $basic_permissions = ['access content', 'create article content', 'edit any article content', 'use text format filtered_html', 'access contextual links'];
    $this->authorUser = $this->drupalCreateUser($basic_permissions);
    $this->editorUser = $this->drupalCreateUser(array_merge($basic_permissions, ['access in-place editing']));
  }

  /**
   * Test the loading of Quick Edit when a user doesn't have access to it.
   */
  public function testUserWithoutPermission() {
    $this->drupalLogin($this->authorUser);
    $this->drupalGet('node/1');

    // Library and in-place editors.
    $this->assertNoRaw('core/modules/quickedit/js/quickedit.js', 'Quick Edit library not loaded.');
    $this->assertNoRaw('core/modules/quickedit/js/editors/formEditor.js', "'form' in-place editor not loaded.");

    // HTML annotation and title class does not exist for users without
    // permission to in-place edit.
    $this->assertNoRaw('data-quickedit-entity-id="node/1"');
    $this->assertNoRaw('data-quickedit-field-id="node/1/body/en/full"');
    $this->assertNoFieldByXPath('//h1[contains(@class, "js-quickedit-page-title")]');

    // Retrieving the metadata should result in an empty 403 response.
    $post = ['fields[0]' => 'node/1/body/en/full'];
    $response = $this->drupalPostWithFormat(Url::fromRoute('quickedit.metadata'), 'json', $post);
    $this->assertIdentical(Json::encode(['message' => "The 'access in-place editing' permission is required."]), $response);
    $this->assertResponse(403);

    // Quick Edit's JavaScript would never hit these endpoints if the metadata
    // was empty as above, but we need to make sure that malicious users aren't
    // able to use any of the other endpoints either.
    $post = ['editors[0]' => 'form'] + $this->getAjaxPageStatePostData();
    $response = $this->drupalPost('quickedit/attachments', '', $post, ['query' => [MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_ajax']]);
    $message = Json::encode(['message' => "The 'access in-place editing' permission is required."]);
    $this->assertIdentical($message, $response);
    $this->assertResponse(403);
    $post = ['nocssjs' => 'true'] + $this->getAjaxPageStatePostData();
    $response = $this->drupalPost('quickedit/form/' . 'node/1/body/en/full', '', $post, ['query' => [MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_ajax']]);
    $this->assertIdentical($message, $response);
    $this->assertResponse(403);
    $edit = [];
    $edit['form_id'] = 'quickedit_field_form';
    $edit['form_token'] = 'xIOzMjuc-PULKsRn_KxFn7xzNk5Bx7XKXLfQfw1qOnA';
    $edit['form_build_id'] = 'form-kVmovBpyX-SJfTT5kY0pjTV35TV-znor--a64dEnMR8';
    $edit['body[0][summary]'] = '';
    $edit['body[0][value]'] = '<p>Malicious content.</p>';
    $edit['body[0][format]'] = 'filtered_html';
    $edit['op'] = t('Save');
    $response = $this->drupalPost('quickedit/form/' . 'node/1/body/en/full', '', $edit, ['query' => [MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_ajax']]);
    $this->assertIdentical($message, $response);
    $this->assertResponse(403);
    $post = ['nocssjs' => 'true'];
    $response = $this->drupalPostWithFormat('quickedit/entity/' . 'node/1', 'json', $post);
    $this->assertIdentical(Json::encode(['message' => "The 'access in-place editing' permission is required."]), $response);
    $this->assertResponse(403);
  }

  /**
   * Tests the loading of Quick Edit when a user does have access to it.
   *
   * Also ensures lazy loading of in-place editors works.
   */
  public function testUserWithPermission() {
    $this->drupalLogin($this->editorUser);
    $this->drupalGet('node/1');

    // Library and in-place editors.
    $settings = $this->getDrupalSettings();
    $libraries = explode(',', $settings['ajaxPageState']['libraries']);
    $this->assertTrue(in_array('quickedit/quickedit', $libraries), 'Quick Edit library loaded.');
    $this->assertFalse(in_array('quickedit/quickedit.inPlaceEditor.form', $libraries), "'form' in-place editor not loaded.");

    // HTML annotation and title class must always exist (to not break the
    // render cache).
    $this->assertRaw('data-quickedit-entity-id="node/1"');
    $this->assertRaw('data-quickedit-field-id="node/1/body/en/full"');
    $this->assertFieldByXPath('//h1[contains(@class, "js-quickedit-page-title")]');

    // There should be only one revision so far.
    $node = Node::load(1);
    $vids = \Drupal::entityManager()->getStorage('node')->revisionIds($node);
    $this->assertIdentical(1, count($vids), 'The node has only one revision.');
    $original_log = $node->revision_log->value;

    // Retrieving the metadata should result in a 200 JSON response.
    $htmlPageDrupalSettings = $this->drupalSettings;
    $post = ['fields[0]' => 'node/1/body/en/full'];
    $response = $this->drupalPostWithFormat('quickedit/metadata', 'json', $post);
    $this->assertResponse(200);
    $expected = [
      'node/1/body/en/full' => [
        'label' => 'Body',
        'access' => TRUE,
        'editor' => 'form',
      ]
    ];
    $this->assertIdentical(Json::decode($response), $expected, 'The metadata HTTP request answers with the correct JSON response.');
    // Restore drupalSettings to build the next requests; simpletest wipes them
    // after a JSON response.
    $this->drupalSettings = $htmlPageDrupalSettings;

    // Retrieving the attachments should result in a 200 response, containing:
    //  1. a settings command with useless metadata: AjaxController is dumb
    //  2. an insert command that loads the required in-place editors
    $post = ['editors[0]' => 'form'] + $this->getAjaxPageStatePostData();
    $response = $this->drupalPost('quickedit/attachments', '', $post, ['query' => [MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_ajax']]);
    $ajax_commands = Json::decode($response);
    $this->assertIdentical(2, count($ajax_commands), 'The attachments HTTP request results in two AJAX commands.');
    // First command: settings.
    $this->assertIdentical('settings', $ajax_commands[0]['command'], 'The first AJAX command is a settings command.');
    // Second command: insert libraries into DOM.
    $this->assertIdentical('insert', $ajax_commands[1]['command'], 'The second AJAX command is an append command.');
    $this->assertTrue(in_array('quickedit/quickedit.inPlaceEditor.form', explode(',', $ajax_commands[0]['settings']['ajaxPageState']['libraries'])), 'The quickedit.inPlaceEditor.form library is loaded.');

    // Retrieving the form for this field should result in a 200 response,
    // containing only a quickeditFieldForm command.
    $post = ['nocssjs' => 'true', 'reset' => 'true'] + $this->getAjaxPageStatePostData();
    $response = $this->drupalPost('quickedit/form/' . 'node/1/body/en/full', '', $post, ['query' => [MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_ajax']]);
    $this->assertResponse(200);
    $ajax_commands = Json::decode($response);
    $this->assertIdentical(1, count($ajax_commands), 'The field form HTTP request results in one AJAX command.');
    $this->assertIdentical('quickeditFieldForm', $ajax_commands[0]['command'], 'The first AJAX command is a quickeditFieldForm command.');
    $this->assertIdentical('<form ', Unicode::substr($ajax_commands[0]['data'], 0, 6), 'The quickeditFieldForm command contains a form.');

    // Prepare form values for submission. drupalPostAjaxForm() is not suitable
    // for handling pages with JSON responses, so we need our own solution here.
    $form_tokens_found = preg_match('/\sname="form_token" value="([^"]+)"/', $ajax_commands[0]['data'], $token_match) && preg_match('/\sname="form_build_id" value="([^"]+)"/', $ajax_commands[0]['data'], $build_id_match);
    $this->assertTrue($form_tokens_found, 'Form tokens found in output.');

    if ($form_tokens_found) {
      $edit = [
        'body[0][summary]' => '',
        'body[0][value]' => '<p>Fine thanks.</p>',
        'body[0][format]' => 'filtered_html',
        'op' => t('Save'),
      ];
      $post = [
        'form_id' => 'quickedit_field_form',
        'form_token' => $token_match[1],
        'form_build_id' => $build_id_match[1],
      ];
      $post += $edit + $this->getAjaxPageStatePostData();

      // Submit field form and check response. This should store the updated
      // entity in PrivateTempStore on the server.
      $response = $this->drupalPost('quickedit/form/' . 'node/1/body/en/full', '', $post, ['query' => [MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_ajax']]);
      $this->assertResponse(200);
      $ajax_commands = Json::decode($response);
      $this->assertIdentical(1, count($ajax_commands), 'The field form HTTP request results in one AJAX command.');
      $this->assertIdentical('quickeditFieldFormSaved', $ajax_commands[0]['command'], 'The first AJAX command is a quickeditFieldFormSaved command.');
      $this->assertTrue(strpos($ajax_commands[0]['data'], 'Fine thanks.'), 'Form value saved and printed back.');
      $this->assertIdentical($ajax_commands[0]['other_view_modes'], [], 'Field was not rendered in any other view mode.');

      // Ensure the text on the original node did not change yet.
      $this->drupalGet('node/1');
      $this->assertText('How are you?');

      // Save the entity by moving the PrivateTempStore values to entity storage.
      $post = ['nocssjs' => 'true'];
      $response = $this->drupalPostWithFormat('quickedit/entity/' . 'node/1', 'json', $post);
      $this->assertResponse(200);
      $ajax_commands = Json::decode($response);
      $this->assertIdentical(1, count($ajax_commands), 'The entity submission HTTP request results in one AJAX command.');
      $this->assertIdentical('quickeditEntitySaved', $ajax_commands[0]['command'], 'The first AJAX command is a quickeditEntitySaved command.');
      $this->assertIdentical($ajax_commands[0]['data']['entity_type'], 'node', 'Saved entity is of type node.');
      $this->assertIdentical($ajax_commands[0]['data']['entity_id'], '1', 'Entity id is 1.');

      // Ensure the text on the original node did change.
      $this->drupalGet('node/1');
      $this->assertText('Fine thanks.');

      // Ensure no new revision was created and the log message is unchanged.
      $node = Node::load(1);
      $vids = \Drupal::entityManager()->getStorage('node')->revisionIds($node);
      $this->assertIdentical(1, count($vids), 'The node has only one revision.');
      $this->assertIdentical($original_log, $node->revision_log->value, 'The revision log message is unchanged.');

      // Now configure this node type to create new revisions automatically,
      // then again retrieve the field form, fill it, submit it (so it ends up
      // in PrivateTempStore) and then save the entity. Now there should be two
      // revisions.
      $node_type = NodeType::load('article');
      $node_type->setNewRevision(TRUE);
      $node_type->save();

      // Retrieve field form.
      $post = ['nocssjs' => 'true', 'reset' => 'true'];
      $response = $this->drupalPost('quickedit/form/' . 'node/1/body/en/full', '', $post, ['query' => [MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_ajax']]);
      $this->assertResponse(200);
      $ajax_commands = Json::decode($response);
      $this->assertIdentical(1, count($ajax_commands), 'The field form HTTP request results in one AJAX command.');
      $this->assertIdentical('quickeditFieldForm', $ajax_commands[0]['command'], 'The first AJAX command is a quickeditFieldForm command.');
      $this->assertIdentical('<form ', Unicode::substr($ajax_commands[0]['data'], 0, 6), 'The quickeditFieldForm command contains a form.');

      // Submit field form.
      preg_match('/\sname="form_token" value="([^"]+)"/', $ajax_commands[0]['data'], $token_match);
      preg_match('/\sname="form_build_id" value="([^"]+)"/', $ajax_commands[0]['data'], $build_id_match);
      $edit['body[0][value]'] = '<p>kthxbye</p>';
      $post = [
        'form_id' => 'quickedit_field_form',
        'form_token' => $token_match[1],
        'form_build_id' => $build_id_match[1],
      ];
      $post += $edit + $this->getAjaxPageStatePostData();
      $response = $this->drupalPost('quickedit/form/' . 'node/1/body/en/full', '', $post, ['query' => [MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_ajax']]);
      $this->assertResponse(200);
      $ajax_commands = Json::decode($response);
      $this->assertIdentical(1, count($ajax_commands), 'The field form HTTP request results in one AJAX command.');
      $this->assertIdentical('quickeditFieldFormSaved', $ajax_commands[0]['command'], 'The first AJAX command is an quickeditFieldFormSaved command.');
      $this->assertTrue(strpos($ajax_commands[0]['data'], 'kthxbye'), 'Form value saved and printed back.');

      // Save the entity.
      $post = ['nocssjs' => 'true'];
      $response = $this->drupalPostWithFormat('quickedit/entity/' . 'node/1', 'json', $post);
      $this->assertResponse(200);
      $ajax_commands = Json::decode($response);
      $this->assertIdentical(1, count($ajax_commands));
      $this->assertIdentical('quickeditEntitySaved', $ajax_commands[0]['command'], 'The first AJAX command is an quickeditEntitySaved command.');
      $this->assertEqual($ajax_commands[0]['data'], ['entity_type' => 'node', 'entity_id' => 1], 'Updated entity type and ID returned');

      // Test that a revision was created with the correct log message.
      $vids = \Drupal::entityManager()->getStorage('node')->revisionIds(Node::load(1));
      $this->assertIdentical(2, count($vids), 'The node has two revisions.');
      $revision = node_revision_load($vids[0]);
      $this->assertIdentical($original_log, $revision->revision_log->value, 'The first revision log message is unchanged.');
      $revision = node_revision_load($vids[1]);
      $this->assertIdentical('Updated the <em class="placeholder">Body</em> field through in-place editing.', $revision->revision_log->value, 'The second revision log message was correctly generated by Quick Edit module.');
    }
  }

  /**
   * Test quickedit does not appear for entities with pending revisions.
   */
  public function testWithPendingRevision() {
    $this->drupalLogin($this->editorUser);

    // Verify that the preview is loaded correctly.
    $this->drupalPostForm('node/add/article', ['title[0][value]' => 'foo'], 'Preview');
    $this->assertResponse(200);
    // Verify that quickedit is not active on preview.
    $this->assertNoRaw('data-quickedit-entity-id="node/' . $this->testNode->id() . '"');
    $this->assertNoRaw('data-quickedit-field-id="node/' . $this->testNode->id() . '/title/' . $this->testNode->language()->getId() . '/full"');

    $this->drupalGet('node/' . $this->testNode->id());
    $this->assertRaw('data-quickedit-entity-id="node/' . $this->testNode->id() . '"');
    $this->assertRaw('data-quickedit-field-id="node/' . $this->testNode->id() . '/title/' . $this->testNode->language()->getId() . '/full"');

    $this->testNode->title = 'Updated node';
    $this->testNode->setNewRevision(TRUE);
    $this->testNode->isDefaultRevision(FALSE);
    $this->testNode->save();

    $this->drupalGet('node/' . $this->testNode->id());
    $this->assertResponse(200);
    $this->assertNoRaw('data-quickedit-entity-id="node/' . $this->testNode->id() . '"');
    $this->assertNoRaw('data-quickedit-field-id="node/' . $this->testNode->id() . '/title/' . $this->testNode->language()->getId() . '/full"');
  }

  /**
   * Tests the loading of Quick Edit for the title base field.
   */
  public function testTitleBaseField() {
    $this->drupalLogin($this->editorUser);
    $this->drupalGet('node/1');

    // Ensure that the full page title is actually in-place editable
    $node = Node::load(1);
    $elements = $this->xpath('//h1/span[@data-quickedit-field-id="node/1/title/en/full" and normalize-space(text())=:title]', [':title' => $node->label()]);
    $this->assertTrue(!empty($elements), 'Title with data-quickedit-field-id attribute found.');

    // Retrieving the metadata should result in a 200 JSON response.
    $htmlPageDrupalSettings = $this->drupalSettings;
    $post = ['fields[0]' => 'node/1/title/en/full'];
    $response = $this->drupalPostWithFormat('quickedit/metadata', 'json', $post);
    $this->assertResponse(200);
    $expected = [
      'node/1/title/en/full' => [
        'label' => 'Title',
        'access' => TRUE,
        'editor' => 'plain_text',
      ]
    ];
    $this->assertIdentical(Json::decode($response), $expected, 'The metadata HTTP request answers with the correct JSON response.');
    // Restore drupalSettings to build the next requests; simpletest wipes them
    // after a JSON response.
    $this->drupalSettings = $htmlPageDrupalSettings;

    // Retrieving the form for this field should result in a 200 response,
    // containing only a quickeditFieldForm command.
    $post = ['nocssjs' => 'true', 'reset' => 'true'] + $this->getAjaxPageStatePostData();
    $response = $this->drupalPost('quickedit/form/' . 'node/1/title/en/full', '', $post, ['query' => [MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_ajax']]);
    $this->assertResponse(200);
    $ajax_commands = Json::decode($response);
    $this->assertIdentical(1, count($ajax_commands), 'The field form HTTP request results in one AJAX command.');
    $this->assertIdentical('quickeditFieldForm', $ajax_commands[0]['command'], 'The first AJAX command is a quickeditFieldForm command.');
    $this->assertIdentical('<form ', Unicode::substr($ajax_commands[0]['data'], 0, 6), 'The quickeditFieldForm command contains a form.');

    // Prepare form values for submission. drupalPostAjaxForm() is not suitable
    // for handling pages with JSON responses, so we need our own solution
    // here.
    $form_tokens_found = preg_match('/\sname="form_token" value="([^"]+)"/', $ajax_commands[0]['data'], $token_match) && preg_match('/\sname="form_build_id" value="([^"]+)"/', $ajax_commands[0]['data'], $build_id_match);
    $this->assertTrue($form_tokens_found, 'Form tokens found in output.');

    if ($form_tokens_found) {
      $edit = [
        'title[0][value]' => 'Obligatory question',
        'op' => t('Save'),
      ];
      $post = [
        'form_id' => 'quickedit_field_form',
        'form_token' => $token_match[1],
        'form_build_id' => $build_id_match[1],
      ];
      $post += $edit + $this->getAjaxPageStatePostData();

      // Submit field form and check response. This should store the
      // updated entity in PrivateTempStore on the server.
      $response = $this->drupalPost('quickedit/form/' . 'node/1/title/en/full', '', $post, ['query' => [MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_ajax']]);
      $this->assertResponse(200);
      $ajax_commands = Json::decode($response);
      $this->assertIdentical(1, count($ajax_commands), 'The field form HTTP request results in one AJAX command.');
      $this->assertIdentical('quickeditFieldFormSaved', $ajax_commands[0]['command'], 'The first AJAX command is a quickeditFieldFormSaved command.');
      $this->assertTrue(strpos($ajax_commands[0]['data'], 'Obligatory question'), 'Form value saved and printed back.');

      // Ensure the text on the original node did not change yet.
      $this->drupalGet('node/1');
      $this->assertNoText('Obligatory question');

      // Save the entity by moving the PrivateTempStore values to entity storage.
      $post = ['nocssjs' => 'true'];
      $response = $this->drupalPostWithFormat('quickedit/entity/' . 'node/1', 'json', $post);
      $this->assertResponse(200);
      $ajax_commands = Json::decode($response);
      $this->assertIdentical(1, count($ajax_commands), 'The entity submission HTTP request results in one AJAX command.');
      $this->assertIdentical('quickeditEntitySaved', $ajax_commands[0]['command'], 'The first AJAX command is n quickeditEntitySaved command.');
      $this->assertIdentical($ajax_commands[0]['data']['entity_type'], 'node', 'Saved entity is of type node.');
      $this->assertIdentical($ajax_commands[0]['data']['entity_id'], '1', 'Entity id is 1.');

      // Ensure the text on the original node did change.
      $this->drupalGet('node/1');
      $this->assertText('Obligatory question');
    }
  }

  /**
   * Tests that Quick Edit doesn't make fields rendered with display options
   * editable.
   */
  public function testDisplayOptions() {
    $node = Node::load('1');
    $display_settings = [
      'label' => 'inline',
    ];
    $build = $node->body->view($display_settings);
    $output = \Drupal::service('renderer')->renderRoot($build);
    $this->assertFalse(strpos($output, 'data-quickedit-field-id'), 'data-quickedit-field-id attribute not added when rendering field using dynamic display options.');
  }

  /**
   * Tests that Quick Edit works with custom render pipelines.
   */
  public function testCustomPipeline() {
    \Drupal::service('module_installer')->install(['quickedit_test']);

    $custom_render_url = 'quickedit/form/node/1/body/en/quickedit_test-custom-render-data';
    $this->drupalLogin($this->editorUser);

    // Request editing to render results with the custom render pipeline.
    $post = ['nocssjs' => 'true'] + $this->getAjaxPageStatePostData();
    $response = $this->drupalPost($custom_render_url, '', $post, ['query' => [MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_ajax']]);
    $ajax_commands = Json::decode($response);

    // Prepare form values for submission. drupalPostAJAX() is not suitable for
    // handling pages with JSON responses, so we need our own solution here.
    $form_tokens_found = preg_match('/\sname="form_token" value="([^"]+)"/', $ajax_commands[0]['data'], $token_match) && preg_match('/\sname="form_build_id" value="([^"]+)"/', $ajax_commands[0]['data'], $build_id_match);
    $this->assertTrue($form_tokens_found, 'Form tokens found in output.');

    if ($form_tokens_found) {
      $post = [
        'form_id' => 'quickedit_field_form',
        'form_token' => $token_match[1],
        'form_build_id' => $build_id_match[1],
        'body[0][summary]' => '',
        'body[0][value]' => '<p>Fine thanks.</p>',
        'body[0][format]' => 'filtered_html',
        'op' => t('Save'),
      ];
      // Assume there is another field on this page, which doesn't use a custom
      // render pipeline, but the default one, and it uses the "full" view mode.
      $post += ['other_view_modes[]' => 'full'];

      // Submit field form and check response. Should render with the custom
      // render pipeline.
      $response = $this->drupalPost($custom_render_url, '', $post, ['query' => [MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_ajax']]);
      $this->assertResponse(200);
      $ajax_commands = Json::decode($response);
      $this->assertIdentical(1, count($ajax_commands), 'The field form HTTP request results in one AJAX command.');
      $this->assertIdentical('quickeditFieldFormSaved', $ajax_commands[0]['command'], 'The first AJAX command is a quickeditFieldFormSaved command.');
      $this->assertTrue(strpos($ajax_commands[0]['data'], 'Fine thanks.'), 'Form value saved and printed back.');
      $this->assertTrue(strpos($ajax_commands[0]['data'], '<div class="quickedit-test-wrapper">') !== FALSE, 'Custom render pipeline used to render the value.');
      $this->assertIdentical(array_keys($ajax_commands[0]['other_view_modes']), ['full'], 'Field was also rendered in the "full" view mode.');
      $this->assertTrue(strpos($ajax_commands[0]['other_view_modes']['full'], 'Fine thanks.'), '"full" version of field contains the form value.');
    }
  }

  /**
   * Tests Quick Edit on a node that was concurrently edited on the full node
   * form.
   */
  public function testConcurrentEdit() {
    $this->drupalLogin($this->editorUser);

    $post = ['nocssjs' => 'true'] + $this->getAjaxPageStatePostData();
    $response = $this->drupalPost('quickedit/form/' . 'node/1/body/en/full', '', $post, ['query' => [MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_ajax']]);
    $this->assertResponse(200);
    $ajax_commands = Json::decode($response);

    // Prepare form values for submission. drupalPostAJAX() is not suitable for
    // handling pages with JSON responses, so we need our own solution here.
    $form_tokens_found = preg_match('/\sname="form_token" value="([^"]+)"/', $ajax_commands[0]['data'], $token_match) && preg_match('/\sname="form_build_id" value="([^"]+)"/', $ajax_commands[0]['data'], $build_id_match);
    $this->assertTrue($form_tokens_found, 'Form tokens found in output.');

    if ($form_tokens_found) {
      $post = [
        'nocssjs' => 'true',
        'form_id' => 'quickedit_field_form',
        'form_token' => $token_match[1],
        'form_build_id' => $build_id_match[1],
        'body[0][summary]' => '',
        'body[0][value]' => '<p>Fine thanks.</p>',
        'body[0][format]' => 'filtered_html',
        'op' => t('Save'),
      ];

      // Save the node on the regular node edit form.
      $this->drupalPostForm('node/1/edit', [], t('Save'));
      // Ensure different save timestamps for field editing.
      sleep(2);

      // Submit field form and check response. Should throw a validation error
      // because the node was changed in the meantime.
      $response = $this->drupalPost('quickedit/form/' . 'node/1/body/en/full', '', $post, ['query' => [MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_ajax']]);
      $this->assertResponse(200);
      $ajax_commands = Json::decode($response);
      $this->assertIdentical(2, count($ajax_commands), 'The field form HTTP request results in two AJAX commands.');
      $this->assertIdentical('quickeditFieldFormValidationErrors', $ajax_commands[1]['command'], 'The second AJAX command is a quickeditFieldFormValidationErrors command.');
      $this->assertTrue(strpos($ajax_commands[1]['data'], 'The content has either been modified by another user, or you have already submitted modifications. As a result, your changes cannot be saved.'), 'Error message returned to user.');
    }
  }

  /**
   * Tests that Quick Edit's data- attributes are present for content blocks.
   */
  public function testContentBlock() {
    \Drupal::service('module_installer')->install(['block_content']);

    // Create and place a content_block block.
    $block = BlockContent::create([
      'info' => $this->randomMachineName(),
      'type' => 'basic',
      'langcode' => 'en',
    ]);
    $block->save();
    $this->drupalPlaceBlock('block_content:' . $block->uuid());

    // Check that the data- attribute is present.
    $this->drupalLogin($this->editorUser);
    $this->drupalGet('');
    $this->assertRaw('data-quickedit-entity-id="block_content/1"');
  }

  /**
   * Tests that Quick Edit can handle an image field.
   */
  public function testImageField() {
    // Add an image field to the content type.
    FieldStorageConfig::create([
      'field_name' => 'field_image',
      'type' => 'image',
      'entity_type' => 'node',
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_image',
      'field_type' => 'image',
      'label' => t('Image'),
      'entity_type' => 'node',
      'bundle' => 'article',
    ])->save();
    entity_get_form_display('node', 'article', 'default')
      ->setComponent('field_image', [
        'type' => 'image_image',
      ])
      ->save();

    // Add an image to the node.
    $this->drupalLogin($this->editorUser);
    $image = $this->drupalGetTestFiles('image')[0];
    $this->drupalPostForm('node/1/edit', [
      'files[field_image_0]' => $image->uri,
    ], t('Upload'));
    $this->drupalPostForm(NULL, [
      'field_image[0][alt]' => 'Vivamus aliquet elit',
    ], t('Save'));

    // The image field form should load normally.
    $response = $this->drupalPost('quickedit/form/node/1/field_image/en/full', '', ['nocssjs' => 'true'] + $this->getAjaxPageStatePostData(), ['query' => [MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_ajax']]);
    $this->assertResponse(200);
    $ajax_commands = Json::decode($response);
    $this->assertIdentical('<form ', Unicode::substr($ajax_commands[0]['data'], 0, 6), 'The quickeditFieldForm command contains a form.');
  }

}
