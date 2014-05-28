<?php

/**
 * @file
 * Definition of Drupal\comment\Tests\CommentUninstallTest.
 */

namespace Drupal\comment\Tests;

use Drupal\field\Entity\FieldConfig;
use Drupal\simpletest\WebTestBase;

/**
 * Tests comment module uninstallation.
 */
class CommentUninstallTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('comment', 'node');

  public static function getInfo() {
    return array(
      'name' => 'Comment uninstallation',
      'description' => 'Tests comment module uninstallation.',
      'group' => 'Comment',
    );
  }

  protected function setUp() {
    parent::setup();

    // Create an article content type.
    $this->drupalCreateContentType(array('type' => 'article', 'name' => t('Article')));
    // Create comment field on article so that adds 'comment_body' field.
    $this->container->get('comment.manager')->addDefaultField('node', 'article');
  }

  /**
   * Tests if comment module uninstallation properly deletes the field.
   */
  function testCommentUninstallWithField() {
    // Ensure that the field exists before uninstallation.
    $field = FieldConfig::loadByName('comment', 'comment_body');
    $this->assertNotNull($field, 'The comment_body field exists.');

    // Uninstall the comment module which should trigger field deletion.
    $this->container->get('module_handler')->uninstall(array('comment'));

    // Check that the field is now deleted.
    $field = FieldConfig::loadByName('comment', 'comment_body');
    $this->assertNull($field, 'The comment_body field has been deleted.');
  }


  /**
   * Tests if uninstallation succeeds if the field has been deleted beforehand.
   */
  function testCommentUninstallWithoutField() {
    // Manually delete the comment_body field before module uninstallation.
    $field = FieldConfig::loadByName('comment', 'comment_body');
    $this->assertNotNull($field, 'The comment_body field exists.');
    $field->delete();

    // Check that the field is now deleted.
    $field = FieldConfig::loadByName('comment', 'comment_body');
    $this->assertNull($field, 'The comment_body field has been deleted.');

    // Ensure that uninstallation succeeds even if the field has already been
    // deleted manually beforehand.
    $this->container->get('module_handler')->uninstall(array('comment'));
  }

}
