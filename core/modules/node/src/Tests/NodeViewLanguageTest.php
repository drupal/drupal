<?php

namespace Drupal\node\Tests;

use Drupal\language\Entity\ConfigurableLanguage;

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
    ConfigurableLanguage::createFromLangcode('es')->save();

    // Set language field visible.
    entity_get_display('node', 'page', 'full')
      ->setComponent('langcode')
      ->save();

    // Create a node in Spanish.
    $node = $this->drupalCreateNode(array('langcode' => 'es'));

    $this->drupalGet($node->urlInfo());
    $this->assertText('Spanish', 'The language field is displayed properly.');
  }

}
