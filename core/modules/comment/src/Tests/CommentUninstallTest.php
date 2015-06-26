<?php

/**
 * @file
 * Contains \Drupal\comment\Tests\CommentUninstallTest.
 */

namespace Drupal\comment\Tests;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Extension\ModuleUninstallValidatorException;
use Drupal\simpletest\WebTestBase;

/**
 * Tests comment module uninstallation.
 *
 * @group comment
 */
class CommentUninstallTest extends WebTestBase {

  use CommentTestTrait;

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
    $this->addDefaultCommentField('node', 'article');
  }

  /**
   * Tests if comment module uninstallation fails if the field exists.
   *
   * @throws \Drupal\Core\Extension\ModuleUninstallValidatorException
   */
  function testCommentUninstallWithField() {
    // Ensure that the field exists before uninstallation.
    $field_storage = FieldStorageConfig::loadByName('comment', 'comment_body');
    $this->assertNotNull($field_storage, 'The comment_body field exists.');

    // Uninstall the comment module which should trigger an exception.
    try {
      $this->container->get('module_installer')->uninstall(array('comment'));
      $this->fail("Expected an exception when uninstall was attempted.");
    }
    catch (ModuleUninstallValidatorException $e) {
      $this->pass("Caught an exception when uninstall was attempted.");
    }
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

    // Manually delete the comment field on the node before module uninstallation.
    $field_storage = FieldStorageConfig::loadByName('node', 'comment');
    $this->assertNotNull($field_storage, 'The comment field exists.');
    $field_storage->delete();

    // Check that the field is now deleted.
    $field_storage = FieldStorageConfig::loadByName('node', 'comment');
    $this->assertNull($field_storage, 'The comment field has been deleted.');

    field_purge_batch(10);
    // Ensure that uninstallation succeeds even if the field has already been
    // deleted manually beforehand.
    $this->container->get('module_installer')->uninstall(array('comment'));
  }

}
