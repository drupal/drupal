<?php

/**
 * @file
 * Contains \Drupal\node\Tests\Views\NodeLanguageTest.
 */

namespace Drupal\node\Tests\Views;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\views\Plugin\views\PluginBase;
use Drupal\views\Tests\ViewTestData;

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
    parent::setUp(FALSE);

    // Create Page content type.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));
      ViewTestData::createTestViews(get_class($this), array('node_test_views'));
    }

    // Add two new languages.
    ConfigurableLanguage::createFromLangcode('fr')->save();
    ConfigurableLanguage::createFromLangcode('es')->save();

    // Make the body field translatable. The title is already translatable by
    // definition.
    $field_storage = FieldStorageConfig::loadByName('node', 'body');
    $field_storage->setTranslatable(TRUE);
    $field_storage->save();

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

    // Override the config for the front page view, so that the language
    // filter is set to the site default language instead. This should just
    // show the English nodes, no matter what the content language is.
    $config = $this->config('views.view.frontpage');
    $config->set('display.default.display_options.filters.langcode.value', array(PluginBase::VIEWS_QUERY_LANGUAGE_SITE_DEFAULT => PluginBase::VIEWS_QUERY_LANGUAGE_SITE_DEFAULT));
    $config->save();
    foreach ($this->node_titles as $langcode => $titles) {
      $this->drupalGet(($langcode == 'en' ? '' : "$langcode/") . 'node');
      foreach ($this->node_titles as $control_langcode => $control_titles) {
        foreach ($control_titles as $title) {
          if ($control_langcode == 'en') {
            $this->assertText($title, 'English title is shown when filtering is site default');
          }
          else {
            $this->assertNoText($title, 'Non-English title is not shown when filtering is site default');
          }
        }
      }
    }

    // Override the config so that the language filter is set to the UI
    // language, and make that have a fixed value of 'es'.
    //
    // IMPORTANT: Make sure this part of the test is last -- it is changing
    // language configuration!
    $config->set('display.default.display_options.filters.langcode.value', array('***LANGUAGE_language_interface***' => '***LANGUAGE_language_interface***'));
    $config->save();
    $language_config = $this->config('language.types');
    $language_config->set('negotiation.language_interface.enabled', array('language-selected' => 1));
    $language_config->save();
    $language_config = $this->config('language.negotiation');
    $language_config->set('selected_langcode', 'es');
    $language_config->save();

    // With a fixed language selected, there is no language-based URL.
    $this->drupalGet('node');
    foreach ($this->node_titles as $control_langcode => $control_titles) {
      foreach ($control_titles as $title) {
        if ($control_langcode == 'es') {
          $this->assertText($title, 'Spanish title is shown when filtering is fixed UI language');
        }
        else {
          $this->assertNoText($title, 'Non-Spanish title is not shown when filtering is fixed UI language');
        }
      }
    }
  }
}
