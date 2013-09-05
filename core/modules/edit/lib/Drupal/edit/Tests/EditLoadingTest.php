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
    $response = $this->retrieveMetadata(array('node/1/body/und/full'));
    $this->assertIdentical('{}', $response);
    $this->assertResponse(403);

    // Edit's JavaScript would never hit these endpoints if the metadata was
    // empty as above, but we need to make sure that malicious users aren't able
    // to use any of the other endpoints either.
    $response = $this->retrieveAttachments(array('form'));
    // @todo Uncomment the below once https://drupal.org/node/2063303 is fixed.
    // $this->assertIdentical('[]', $response);
    $this->assertResponse(403);
    $response = $this->retrieveFieldForm('node/1/body/und/full');
    // @todo Uncomment the below once https://drupal.org/node/2063303 is fixed.
    // $this->assertIdentical('[]', $response);
    $this->assertResponse(403);
    $edit = array();
    $edit['form_id'] = 'edit_field_form';
    $edit['form_token'] = 'xIOzMjuc-PULKsRn_KxFn7xzNk5Bx7XKXLfQfw1qOnA';
    $edit['form_build_id'] = 'form-kVmovBpyX-SJfTT5kY0pjTV35TV-znor--a64dEnMR8';
    $edit['body[und][0][summary]'] = '';
    $edit['body[und][0][value]'] = '<p>Malicious content.</p>';
    $edit['body[und][0][format]'] = 'filtered_html';
    $edit['op'] = t('Save');
    $response = $this->submitFieldForm('node/1/body/und/full', $edit);
    // @todo Uncomment the below once https://drupal.org/node/2063303 is fixed.
    // $this->assertIdentical('[]', $response);
    $this->assertResponse(403);
    $response = $this->saveEntity('node/1');
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
    $response = $this->retrieveMetadata(array('node/1/body/und/full'));
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
    $response = $this->retrieveAttachments(array('form'));
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
    $response = $this->retrieveFieldForm('node/1/body/und/full');
    $this->assertResponse(200);
    $ajax_commands = drupal_json_decode($response);
    $this->assertIdentical(1, count($ajax_commands), 'The field form HTTP request results in one AJAX command.');
    $this->assertIdentical('editFieldForm', $ajax_commands[0]['command'], 'The first AJAX command is an editFieldForm command.');
    $this->assertIdentical('<form ', Unicode::substr($ajax_commands[0]['data'], 0, 6), 'The editFieldForm command contains a form.');

    // Prepare form values for submission. drupalPostAJAX() is not suitable
    // for handling pages with JSON responses, so we need our own solution
    // here.
    $form_tokens_found = preg_match('/\sname="form_token" value="([^"]+)"/', $ajax_commands[0]['data'], $token_match) && preg_match('/\sname="form_build_id" value="([^"]+)"/', $ajax_commands[0]['data'], $build_id_match);
    $this->assertTrue($form_tokens_found, 'Form tokens found in output.');

    if ($form_tokens_found) {
      $edit = array();
      $edit['form_id'] = 'edit_field_form';
      $edit['form_token'] = $token_match[1];
      $edit['form_build_id'] = $build_id_match[1];
      $edit['body[und][0][summary]'] = '';
      $edit['body[und][0][value]'] = '<p>Fine thanks.</p>';
      $edit['body[und][0][format]'] = 'filtered_html';
      $edit['op'] = t('Save');

      // Submit field form and check response. This should store the
      // updated entity in TempStore on the server.
      $response = $this->submitFieldForm('node/1/body/und/full', $edit);
      $this->assertResponse(200);
      $ajax_commands = drupal_json_decode($response);
      $this->assertIdentical(1, count($ajax_commands), 'The field form HTTP request results in one AJAX command.');
      $this->assertIdentical('editFieldFormSaved', $ajax_commands[0]['command'], 'The first AJAX command is an editFieldFormSaved command.');
      $this->assertTrue(strpos($ajax_commands[0]['data'], 'Fine thanks.'), 'Form value saved and printed back.');

      // Ensure the text on the original node did not change yet.
      $this->drupalGet('node/1');
      $this->assertText('How are you?');

      // Save the entity by moving the TempStore values to entity storage.
      $response = $this->saveEntity('node/1');
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

  /**
   * Retrieve Edit metadata from the server. May also result in additional
   * JavaScript settings and CSS/JS being loaded.
   *
   * @param array $ids
   *   An array of edit ids.
   *
   * @return string
   *   The response body.
   */
  protected function retrieveMetadata($ids) {
    // Build POST values.
    $post = array();
    for ($i = 0; $i < count($ids); $i++) {
      $post['fields[' . $i . ']'] = $ids[$i];
    }

    // Serialize POST values.
    foreach ($post as $key => $value) {
      // Encode according to application/x-www-form-urlencoded
      // Both names and values needs to be urlencoded, according to
      // http://www.w3.org/TR/html4/interact/forms.html#h-17.13.4.1
      $post[$key] = urlencode($key) . '=' . urlencode($value);
    }
    $post = implode('&', $post);

    // Perform HTTP request.
    return $this->curlExec(array(
      CURLOPT_URL => url('edit/metadata', array('absolute' => TRUE)),
      CURLOPT_POST => TRUE,
      CURLOPT_POSTFIELDS => $post . $this->getAjaxPageStatePostData(),
      CURLOPT_HTTPHEADER => array(
        'Accept: application/json',
        'Content-Type: application/x-www-form-urlencoded',
      ),
    ));
  }

  /**
   * Retrieves AJAX commands to load attachments for the given in-place editors.
   *
   * @param array $editors
   *   An array of in-place editor ids.
   *
   * @return string
   *   The response body.
   */
  protected function retrieveAttachments($editors) {
    // Build POST values.
    $post = array();
    for ($i = 0; $i < count($editors); $i++) {
      $post['editors[' . $i . ']'] = $editors[$i];
    }

    // Serialize POST values.
    foreach ($post as $key => $value) {
      // Encode according to application/x-www-form-urlencoded
      // Both names and values needs to be urlencoded, according to
      // http://www.w3.org/TR/html4/interact/forms.html#h-17.13.4.1
      $post[$key] = urlencode($key) . '=' . urlencode($value);
    }
    $post = implode('&', $post);

    // Perform HTTP request.
    return $this->curlExec(array(
      CURLOPT_URL => url('edit/attachments', array('absolute' => TRUE)),
      CURLOPT_POST => TRUE,
      CURLOPT_POSTFIELDS => $post . $this->getAjaxPageStatePostData(),
      CURLOPT_HTTPHEADER => array(
        'Accept: application/vnd.drupal-ajax',
        'Content-Type: application/x-www-form-urlencoded',
      ),
    ));
  }

  /**
   * Submit field form data to the server.
   *
   * @param string $field_id
   *   An Edit field ID.
   * @param array $post
   *   An array of post data to send.
   *
   * @return string
   *   The response body.
   */
  protected function submitFieldForm($field_id, $post) {
    // Serialize POST values.
    foreach ($post as $key => $value) {
      // Encode according to application/x-www-form-urlencoded
      // Both names and values needs to be urlencoded, according to
      // http://www.w3.org/TR/html4/interact/forms.html#h-17.13.4.1
      $post[$key] = urlencode($key) . '=' . urlencode($value);
    }
    $post = implode('&', $post);

    // Perform HTTP request.
    return $this->curlExec(array(
      CURLOPT_URL => url('edit/form/' . $field_id, array('absolute' => TRUE)),
      CURLOPT_POST => TRUE,
      CURLOPT_POSTFIELDS => $post . $this->getAjaxPageStatePostData(),
      CURLOPT_HTTPHEADER => array(
        'Accept: application/json',
        'Content-Type: application/x-www-form-urlencoded',
      ),
    ));
  }

  /**
   * Retrieve field form from the server. May also result in additional
   * JavaScript settings and CSS/JS being loaded.
   *
   * @param string $field_id
   *   An Edit field ID.
   *
   * @return string
   *   The response body.
   */
  protected function retrieveFieldForm($field_id) {
    // Build & serialize POST value.
    $post = urlencode('nocssjs') . '=' . urlencode('true');

    // Perform HTTP request.
    return $this->curlExec(array(
      CURLOPT_URL => url('edit/form/' . $field_id, array('absolute' => TRUE)),
      CURLOPT_POST => TRUE,
      CURLOPT_POSTFIELDS => $post . $this->getAjaxPageStatePostData(),
      CURLOPT_HTTPHEADER => array(
        'Accept: application/vnd.drupal-ajax',
        'Content-Type: application/x-www-form-urlencoded',
      ),
    ));
  }

  /**
   * Save entity edits on the server.
   *
   * @param string $entity_type_id
   *   The edit type and ID URI portion.
   *
   * @return string
   *   The response body.
   */
  protected function saveEntity($entity_type_id) {
    // Build & serialize POST value.
    $post = urlencode('nocssjs') . '=' . urlencode('true');

    // Perform HTTP request.
    return $this->curlExec(array(
      CURLOPT_URL => url('edit/entity/' . $entity_type_id, array('absolute' => TRUE)),
      CURLOPT_POST => TRUE,
      CURLOPT_POSTFIELDS => $post . $this->getAjaxPageStatePostData(),
      CURLOPT_HTTPHEADER => array(
        'Accept: application/json',
        'Content-Type: application/x-www-form-urlencoded',
      ),
    ));
  }

  /**
   * Get extra information to the POST data as ajax.js does.
   *
   * @return string
   *   Additional post data.
   */
  protected function getAjaxPageStatePostData() {
    $extra_post = '';
    $drupal_settings = $this->drupalSettings;
    if (isset($drupal_settings['ajaxPageState'])) {
      $extra_post .= '&' . urlencode('ajax_page_state[theme]') . '=' . urlencode($drupal_settings['ajaxPageState']['theme']);
      $extra_post .= '&' . urlencode('ajax_page_state[theme_token]') . '=' . urlencode($drupal_settings['ajaxPageState']['theme_token']);
      foreach ($drupal_settings['ajaxPageState']['css'] as $key => $value) {
        $extra_post .= '&' . urlencode("ajax_page_state[css][$key]") . '=1';
      }
      foreach ($drupal_settings['ajaxPageState']['js'] as $key => $value) {
        $extra_post .= '&' . urlencode("ajax_page_state[js][$key]") . '=1';
      }
    }
    return $extra_post;
  }

}
