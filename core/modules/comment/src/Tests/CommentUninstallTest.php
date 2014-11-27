<?php

/**
 * @file
 * Definition of Drupal\comment\Tests\CommentUninstallTest.
 */

namespace Drupal\comment\Tests;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\simpletest\WebTestBase;

/**
 * Tests comment module uninstallation.
 *
 * @group comment
 */
class CommentUninstallTest extends WebTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('comment', 'node');

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
    $field_storage = FieldStorageConfig::loadByName('comment', 'comment_body');
    $this->assertNotNull($field_storage, 'The comment_body field exists.');

    // Uninstall the comment module which should trigger field deletion.
    $this->container->get('module_installer')->uninstall(array('comment'));

    // Check that the field is now deleted.
    $field_storage = FieldStorageConfig::loadByName('comment', 'comment_body');
    $this->assertNull($field_storage, 'The comment_body field has been deleted.');
  }


  /**
   * Tests if uninstallation succeeds if the field has been deleted beforehand.
   */
  function testCommentUninstallWithoutField() {
    // Manually delete the comment_body field before module uninstallation.
    $field_storage = FieldStorageConfig::loadByName('comment', 'comment_body');
    $this->assertNotNull($field_storage, 'The comment_body field exists.');
    $field_storage->delete();

    // Check that the field is now deleted.
    $field_storage = FieldStorageConfig::loadByName('comment', 'comment_body');
    $this->assertNull($field_storage, 'The comment_body field has been deleted.');

    // Ensure that uninstallation succeeds even if the field has already been
    // deleted manually beforehand.
    $this->container->get('module_installer')->uninstall(array('comment'));
  }

}
