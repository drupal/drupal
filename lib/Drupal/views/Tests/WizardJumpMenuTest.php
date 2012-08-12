<?php

/**
 * @file
 * Definition of Drupal\views\Tests\WizardJumpMenuTest.
 */

namespace Drupal\views\Tests;

/**
 * Tests the ability of the views wizard to create views with a jump menu style plugin.
 */
class WizardJumpMenuTest extends WizardTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Views UI wizard jump menu functionality',
      'description' => 'Test the ability of the views wizard to create views with a jump menu style plugin.',
      'group' => 'Views UI',
    );
  }

  /**
   * Tests the jump menu style plugin.
   */
  function testJumpMenus() {
    // We'll run this test for several different base tables that appear in the
    // wizard.
    $base_table_methods = array(
      'node' => 'createNodeAndGetPath',
      'users' => 'createUserAndGetPath',
      'comment' => 'createCommentAndGetPath',
      'taxonomy_term' => 'createTaxonomyTermAndGetPath',
      'file_managed' => 'createFileAndGetPath',
      'node_revision' => 'createNodeRevisionAndGetPath',
    );

    foreach ($base_table_methods as $base_table => $method) {
      // For each base table, find the path that we expect the jump menu to
      // redirect us to.
      $path_info = $this->{$method}();
      if (is_array($path_info)) {
        $path = $path_info['path'];
        $options = isset($path_info['options']) ? $path_info['options'] : array();
      }
      else {
        $path = $path_info;
        $options = array();
      }

      // Create a page view for the specified base table that uses the jump
      // menu style plugin.
      $view = array();
      $view['human_name'] = $this->randomName(16);
      $view['name'] = strtolower($this->randomName(16));
      $view['description'] = $this->randomName(16);
      $view['show[wizard_key]'] = $base_table;
      $view['page[create]'] = 1;
      $view['page[title]'] = $this->randomName(16);
      $view['page[path]'] = $this->randomName(16);
      $view['page[style][style_plugin]'] = 'jump_menu';
      $view['page[style][row_plugin]'] = 'fields';
      $this->drupalPost('admin/structure/views/add', $view, t('Save & exit'));

      // Submit the jump menu form, and check that we are redirected to the
      // expected URL.

      $edit = array();
      $edit['jump'] = url($path, $options);

      // The urls are built with :: to be able to have a unique path all the time,
      // so try to find out the real path of $edit.
      $view_object = views_get_view($view['name']);
      if (!$view_object) {
        $this->fail('The view could not be loaded.');
        return;
      }
      $view_object->preview('page');
      $form = $view_object->style_plugin->render();
      $jump_options = $form['jump']['#options'];
      foreach ($jump_options as $key => $title) {
        if (strpos($key, $edit['jump']) !== FALSE) {
          $edit['jump'] = $key;
        }
      }

      $this->drupalPost($view['page[path]'], $edit, t('Go'));
      $this->assertResponse(200);
      $this->assertUrl($path, $options);
    }
  }

  /**
   * Helper function to create a node and return its expected path.
   */
  function createNodeAndGetPath() {
    $node = $this->drupalCreateNode();
    return $node->uri();
  }

  /**
   * Helper function to create a user and return its expected path.
   */
  function createUserAndGetPath() {
    $account = $this->drupalCreateUser();
    return $account->uri();
  }

  /**
   * Helper function to create a comment and return its expected path.
   */
  function createCommentAndGetPath() {
    $node = $this->drupalCreateNode();
    $comment = entity_create('comment', array(
      'cid' => NULL,
      'nid' => $node->nid,
      'pid' => 0,
      'uid' => 0,
      'status' => COMMENT_PUBLISHED,
      'subject' => $this->randomName(),
      'language' => LANGUAGE_NOT_SPECIFIED,
      'comment_body' => array(LANGUAGE_NOT_SPECIFIED => array($this->randomName())),
    ));
    $comment->save();
    return $comment->uri();
  }

  /**
   * Helper function to create a taxonomy term and return its expected path.
   */
  function createTaxonomyTermAndGetPath() {
    $vocabulary = entity_create('taxonomy_vocabulary',  array(
      'name' => $this->randomName(),
      'machine_name' => drupal_strtolower($this->randomName()),
    ));
    $vocabulary->save();

    $term = entity_create('taxonomy_term', array(
      'name' => $this->randomName(),
      'vid' => $vocabulary->vid,
      'vocabulary_machine_name' => $vocabulary->machine_name,
    ));
    $term->save();
    return $term->uri();
  }

  /**
   * Helper function to create a file and return its expected path.
   */
  function createFileAndGetPath() {
    $file = entity_create('file', array(
      'uid' => 1,
      'filename' => 'views-ui-jump-menu-test.txt',
      'uri' => 'public://views-ui-jump-menu-test.txt',
      'filemime' => 'text/plain',
      'timestamp' => 1,
      'status' => FILE_STATUS_PERMANENT,
    ));
    file_put_contents($file->uri, 'test content');
    $file->save();
    return file_create_url($file->uri);
  }

  /**
   * Helper function to create a node revision and return its expected path.
   */
  function createNodeRevisionAndGetPath() {
    // The node needs at least two revisions in order for Drupal to allow
    // access to the revision path.
    $settings = array('revision' => TRUE);
    $node = $this->drupalCreateNode($settings);
    $node->vid = NULL;
    $node->save();
    return 'node/' . $node->nid . '/revisions/' . $node->vid . '/view';
  }
}
