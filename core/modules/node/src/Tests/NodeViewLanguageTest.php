<?php

/**
 * @file
 * Contains \Drupal\node\Tests\NodeViewLanguageTest.
 */

namespace Drupal\node\Tests;

use Drupal\Core\Language\Language;

/**
 * Tests the node language extra field display.
 *
 * @group node
 */
class NodeViewLanguageTest extends NodeTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'datetime', 'language');

  /**
   * Tests the language extra field display.
   */
  public function testViewLanguage() {
    // Add Spanish language.
    $language = new Language(array('id' => 'es'));
    language_save($language);

    // Set language field visible.
    entity_get_display('node', 'page', 'full')
      ->setComponent('langcode')
      ->save();

    // Create a node in Spanish.
    $node = $this->drupalCreateNode(array('langcode' => 'es'));

    $this->drupalGet($node->getSystemPath());
    $this->assertText('Spanish','The language field is displayed properly.');
  }

}
