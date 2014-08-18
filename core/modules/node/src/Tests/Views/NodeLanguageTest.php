<?php

/**
 * @file
 * Contains \Drupal\node\Tests\Views\NodeLanguageTest.
 */

namespace Drupal\node\Tests\Views;

use Drupal\Core\Language\Language;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests node language fields, filters, and sorting.
 *
 * @group node
 */
class NodeLanguageTest extends NodeTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('language');

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_language');

  /**
   * List of node titles by language.
   *
   * @var array
   */
  public $node_titles = array();

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create Page content type.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));
    }

    // Add two new languages.
    $language = new Language(array(
      'id' => 'fr',
      'name' => 'French',
    ));
    language_save($language);

    $language = new Language(array(
      'id' => 'es',
      'name' => 'Spanish',
    ));
    language_save($language);

    // Make the body field translatable. The title is already translatable by
    // definition.
    $field = FieldStorageConfig::loadByName('node', 'body');
    $field->translatable = TRUE;
    $field->save();

    // Set up node titles. They should not include the words "French",
    // "English", or "Spanish", as there is a language field in the view
    // that prints out those words.
    $this->node_titles = array(
      'es' => array(
        'Primero nodo es',
        'Segundo nodo es',
        'Tercera nodo es',
      ),
      'en' => array(
        'First node en',
        'Second node en',
      ),
      'fr' => array(
        'Premier nœud fr',
      )
    );

    // Create nodes with translations.
    foreach ($this->node_titles['es'] as $index => $title) {
      $node = $this->drupalCreateNode(array('title' => $title, 'langcode' => 'es', 'type' => 'page', 'promote' => 1));
      foreach (array('en', 'fr') as $langcode) {
        if (isset($this->node_titles[$langcode][$index])) {
          $translation = $node->addTranslation($langcode, array('title' => $this->node_titles[$langcode][$index]));
          $translation->body->value = $this->randomMachineName(32);
        }
      }
      $node->save();
    }

    $user = $this->drupalCreateUser(array('access content overview', 'access content'));
    $this->drupalLogin($user);
  }

  /**
   * Tests translation language filter, field, and sort.
   */
  public function testLanguages() {
    // Test the page with no arguments. It is filtered to Spanish and French.
    // The page shows node titles and languages.
    $this->drupalGet('test-language');
    $message = 'French/Spanish page';

    // Test that the correct nodes are shown.
    foreach ($this->node_titles as $langcode => $list) {
      foreach ($list as $title) {
        if ($langcode == 'en') {
          $this->assertNoText($title, $title . ' does not appear on ' . $message);
        }
        else {
          $this->assertText($title, $title . ' does appear on ' . $message);
        }
      }
    }

    // Test that the language field value is shown.
    $this->assertNoText('English', 'English language is not shown on ' . $message);
    $this->assertText('French', 'French language is shown on ' . $message);
    $this->assertText('Spanish', 'Spanish language is shown on ' . $message);

    // Test page sorting, which is by language code, ascending. So the
    // Spanish nodes should appear before the French nodes.
    $page = $this->getTextContent();
    $pos_es_max = 0;
    $pos_fr_min = 10000;
    foreach ($this->node_titles['es'] as $title) {
      $pos_es_max = max($pos_es_max, strpos($page, $title));
    }
    foreach ($this->node_titles['fr'] as $title) {
      $pos_fr_min = min($pos_fr_min, strpos($page, $title));
    }
    $this->assertTrue($pos_es_max < $pos_fr_min, 'Spanish translations appear before French on ' . $message);

    // Test the argument -- filter to just Spanish.
    $this->drupalGet('test-language/es');
    // This time, test just the language field.
    $message = 'Spanish argument page';
    $this->assertNoText('English', 'English language is not shown on ' . $message);
    $this->assertNoText('French', 'French language is not shown on ' . $message);
    $this->assertText('Spanish', 'Spanish language is shown on ' . $message);

    // Test the front page view filter. Only node titles in the current language
    // should be displayed on the front page by default.
    foreach ($this->node_titles as $langcode => $titles) {
      $this->drupalGet(($langcode == 'en' ? '' : "$langcode/") . 'node');
      foreach ($titles as $title) {
        $this->assertText($title);
      }
      foreach ($this->node_titles as $control_langcode => $control_titles) {
        if ($langcode != $control_langcode) {
          foreach ($control_titles as $title) {
            $this->assertNoText($title);
          }
        }
      }
    }

    // Test the node admin view filter. By default all translations should show.
    $this->drupalGet('admin/content');
    foreach ($this->node_titles as $titles) {
      foreach ($titles as $title) {
        $this->assertText($title);
      }
    }
    // When filtered, only the specific languages should show.
    foreach ($this->node_titles as $langcode => $titles) {
      $this->drupalGet('admin/content', array('query' => array('langcode' => $langcode)));
      foreach ($titles as $title) {
        $this->assertText($title);
      }
      foreach ($this->node_titles as $control_langcode => $control_titles) {
        if ($langcode != $control_langcode) {
          foreach ($control_titles as $title) {
            $this->assertNoText($title);
          }
        }
      }
    }
  }
}
