<?php

/**
 * @file
 * Contains \Drupal\edit\Tests\EditLoadingTest.
 */

namespace Drupal\edit\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\edit\Ajax\MetadataCommand;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Component\Utility\Unicode;

/**
 * Tests loading of Edit and lazy-loading of in-place editors.
 */
class EditLoadingTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('contextual', 'edit', 'filter', 'node');

  public static function getInfo() {
    return array(
      'name' => 'In-place editing loading',
      'description' => 'Tests loading of in-place editing functionality and lazy loading of its in-place editors.',
      'group' => 'Edit',
    );
  }

  function setUp() {
    parent::setUp();

    // Create a text format.
    $filtered_html_format = entity_create('filter_format', array(
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
      'weight' => 0,
      'filters' => array(),
    ));
    $filtered_html_format->save();

    // Create a node type.
    $this->drupalCreateContentType(array(
      'type' => 'article',
      'name' => 'Article',
    ));

    // Create one node of the above node type using the above text format.
    $this->drupalCreateNode(array(
      'type' => 'article',
      'body' => array(
        0 => array(
          'value' => '<p>How are you?</p>',
          'format' => 'filtered_html',
        )
      )
    ));

    // Create 2 users, the only difference being the ability to use in-place
    // editing
    $basic_permissions = array('access content', 'create article content', 'edit any article content', 'use text format filtered_html', 'access contextual links');
    $this->author_user = $this->drupalCreateUser($basic_permissions);
    $this->editor_user = $this->drupalCreateUser(array_merge($basic_permissions, array('access in-place editing')));
  }

  /**
   * Test the loading of Edit when a user doesn't have access to it.
   */
  function testUserWithoutPermission() {
    $this->drupalLogin($this->author_user);
    $this->drupalGet('node/1');

    // Settings, library and in-place editors.
    $settings = $this->drupalGetSettings();
    $this->assertFalse(isset($settings['edit']), 'Edit settings do not exist.');
    $this->assertFalse(isset($settings['ajaxPageState']['js']['core/modules/edit/js/edit.js']), 'Edit library not loaded.');
    $this->assertFalse(isset($settings['ajaxPageState']['js']['core/modules/edit/js/createjs/editingWidgets/formwidget.js']), "'form' in-place editor not loaded.");

    // HTML annotation must always exist (to not break the render cache).
    $this->assertRaw('data-edit-entity="node/1"');
    $this->assertRaw('data-edit-id="node/1/body/und/full"');

    // Retrieving the metadata should result in an empty 403 response.
    $post = array('fields[0]' => 'node/1/body/und/full');
    $response = $this->drupalPost('edit/metadata', 'application/json', $post);
    $this->assertIdentical('{}', $response);
    $this->assertResponse(403);

    // Edit's JavaScript would never hit these endpoints if the metadata was
    // empty as above, but we need to make sure that malicious users aren't able
    // to use any of the other endpoints either.
    $post = array('editors[0]' => 'form') + $this->getAjaxPageStatePostData();
    $response = $this->drupalPost('edit/attachments', 'application/vnd.drupal-ajax', $post);
    // @todo Uncomment the below once https://drupal.org/node/2063303 is fixed.
    // $this->assertIdentical('[]', $response);
    $this->assertResponse(403);
    $post = array('nocssjs' => 'true') + $this->getAjaxPageStatePostData();
    $response = $this->drupalPost('edit/form/' . 'node/1/body/und/full', 'application/vnd.drupal-ajax', $post);
    // @todo Uncomment the below once https://drupal.org/node/2063303 is fixed.
    // $this->assertIdentical('[]', $response);
    $this->assertResponse(403);
    $edit = array();
    $edit['form_id'] = 'edit_field_form';
    $edit['form_token'] = 'xIOzMjuc-PULKsRn_KxFn7xzNk5Bx7XKXLfQfw1qOnA';
    $edit['form_build_id'] = 'form-kVmovBpyX-SJfTT5kY0pjTV35TV-znor--a64dEnMR8';
    $edit['body[0][summary]'] = '';
    $edit['body[0][value]'] = '<p>Malicious content.</p>';
    $edit['body[0][format]'] = 'filtered_html';
    $edit['op'] = t('Save');
    $response = $this->drupalPost('edit/form/' . 'node/1/body/und/full', 'application/vnd.drupal-ajax', $post);
    // @todo Uncomment the below once https://drupal.org/node/2063303 is fixed.
    // $this->assertIdentical('[]', $response);
    $this->assertResponse(403);
    $post = array('nocssjs' => 'true');
    $response = $this->drupalPost('edit/entity/' . 'node/1', 'application/json', $post);
    // @todo Uncomment the below once https://drupal.org/node/2063303 is fixed.
    // $this->assertIdentical('[]', $response);
    $this->assertResponse(403);
  }

  /**
   * Tests the loading of Edit when a user does have access to it.
   *
   * Also ensures lazy loading of in-place editors works.
   */
  function testUserWithPermission() {
    $this->drupalLogin($this->editor_user);
    $this->drupalGet('node/1');

    // Settings, library and in-place editors.
    $settings = $this->drupalGetSettings();
    $this->assertTrue(isset($settings['edit']), 'Edit settings exist.');
    $this->assertTrue(isset($settings['ajaxPageState']['js']['core/modules/edit/js/edit.js']), 'Edit library loaded.');
    $this->assertFalse(isset($settings['ajaxPageState']['js']['core/modules/edit/js/createjs/editingWidgets/formwidget.js']), "'form' in-place editor not loaded.");

    // HTML annotation must always exist (to not break the render cache).
    $this->assertRaw('data-edit-entity="node/1"');
    $this->assertRaw('data-edit-id="node/1/body/und/full"');

    // Retrieving the metadata should result in a 200 JSON response.
    $htmlPageDrupalSettings = $this->drupalSettings;
    $post = array('fields[0]' => 'node/1/body/und/full');
    $response = $this->drupalPost('edit/metadata', 'application/json', $post);
    $this->assertResponse(200);
    $expected = array(
      'node/1/body/und/full' => array(
        'label' => 'Body',
        'access' => TRUE,
        'editor' => 'form',
        'aria' => 'Entity node 1, field Body',
      )
    );
    $this->assertIdentical(drupal_json_decode($response), $expected, 'The metadata HTTP request answers with the correct JSON response.');
    // Restore drupalSettings to build the next requests; simpletest wipes them
    // after a JSON response.
    $this->drupalSettings = $htmlPageDrupalSettings;

    // Retrieving the attachments should result in a 200 response, containing:
    //  1. a settings command with useless metadata: AjaxController is dumb
    //  2. an insert command that loads the required in-place editors
    $post = array('editors[0]' => 'form') + $this->getAjaxPageStatePostData();
    $response = $this->drupalPost('edit/attachments', 'application/vnd.drupal-ajax', $post);
    $ajax_commands = drupal_json_decode($response);
    $this->assertIdentical(2, count($ajax_commands), 'The attachments HTTP request results in two AJAX commands.');
    // First command: settings.
    $this->assertIdentical('settings', $ajax_commands[0]['command'], 'The first AJAX command is a settings command.');
    // Second command: insert libraries into DOM.
    $this->assertIdentical('insert', $ajax_commands[1]['command'], 'The second AJAX command is an append command.');
    $command = new AppendCommand('body', '<script src="' . file_create_url('core/modules/edit/js/editors/formEditor.js') . '?v=' . \Drupal::VERSION . '"></script>' . "\n");
    $this->assertIdentical($command->render(), $ajax_commands[1], 'The append command contains the expected data.');

    // Retrieving the form for this field should result in a 200 response,
    // containing only an editFieldForm command.
    $post = array('nocssjs' => 'true') + $this->getAjaxPageStatePostData();
    $response = $this->drupalPost('edit/form/' . 'node/1/body/und/full', 'application/vnd.drupal-ajax', $post);
    $this->assertResponse(200);
    $ajax_commands = drupal_json_decode($response);
    $this->assertIdentical(1, count($ajax_commands), 'The field form HTTP request results in one AJAX command.');
    $this->assertIdentical('editFieldForm', $ajax_commands[0]['command'], 'The first AJAX command is an editFieldForm command.');
    $this->assertIdentical('<form ', Unicode::substr($ajax_commands[0]['data'], 0, 6), 'The editFieldForm command contains a form.');

    // Prepare form values for submission. drupalPostAjaxForm() is not suitable
    // for handling pages with JSON responses, so we need our own solution
    // here.
    $form_tokens_found = preg_match('/\sname="form_token" value="([^"]+)"/', $ajax_commands[0]['data'], $token_match) && preg_match('/\sname="form_build_id" value="([^"]+)"/', $ajax_commands[0]['data'], $build_id_match);
    $this->assertTrue($form_tokens_found, 'Form tokens found in output.');

    if ($form_tokens_found) {
      $post = array(
        'form_id' => 'edit_field_form',
        'form_token' => $token_match[1],
        'form_build_id' => $build_id_match[1],
        'body[0][summary]' => '',
        'body[0][value]' => '<p>Fine thanks.</p>',
        'body[0][format]' => 'filtered_html',
        'op' => t('Save'),
      );
      $post += $this->getAjaxPageStatePostData();

      // Submit field form and check response. This should store the
      // updated entity in TempStore on the server.
      $response = $this->drupalPost('edit/form/' . 'node/1/body/und/full', 'application/vnd.drupal-ajax', $post);
      $this->assertResponse(200);
      $ajax_commands = drupal_json_decode($response);
      $this->assertIdentical(1, count($ajax_commands), 'The field form HTTP request results in one AJAX command.');
      $this->assertIdentical('editFieldFormSaved', $ajax_commands[0]['command'], 'The first AJAX command is an editFieldFormSaved command.');
      $this->assertTrue(strpos($ajax_commands[0]['data'], 'Fine thanks.'), 'Form value saved and printed back.');

      // Ensure the text on the original node did not change yet.
      $this->drupalGet('node/1');
      $this->assertText('How are you?');

      // Save the entity by moving the TempStore values to entity storage.
      $post = array('nocssjs' => 'true');
      $response = $this->drupalPost('edit/entity/' . 'node/1', 'application/json', $post);
      $this->assertResponse(200);
      $ajax_commands = drupal_json_decode($response);
      $this->assertIdentical(1, count($ajax_commands), 'The entity submission HTTP request results in one AJAX command.');
      $this->assertIdentical('editEntitySaved', $ajax_commands[0]['command'], 'The first AJAX command is an editEntitySaved command.');
      $this->assertIdentical($ajax_commands[0]['data']['entity_type'], 'node', 'Saved entity is of type node.');
      $this->assertIdentical($ajax_commands[0]['data']['entity_id'], '1', 'Entity id is 1.');

      // Ensure the text on the original node did change.
      $this->drupalGet('node/1');
      $this->assertText('Fine thanks.');
    }
  }

}
