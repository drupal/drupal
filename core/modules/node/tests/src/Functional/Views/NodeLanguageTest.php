<?php

namespace Drupal\Tests\node\Functional\Views;

use Drupal\Core\Language\LanguageInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\views\Plugin\views\PluginBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;

/**
 * Tests node language fields, filters, and sorting.
 *
 * @group node
 */
class NodeLanguageTest extends NodeTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['language', 'node_test_views'];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_language'];

  /**
   * List of node titles by language.
   *
   * @var array
   */
  public $nodeTitles = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp(FALSE);

    // Create Page content type.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);
      ViewTestData::createTestViews(get_class($this), ['node_test_views']);
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
    $this->nodeTitles = [
      LanguageInterface::LANGCODE_NOT_SPECIFIED => [
        'First node und',
      ],
      'es' => [
        'Primero nodo es',
        'Segundo nodo es',
        'Tercera nodo es',
      ],
      'en' => [
        'First node en',
        'Second node en',
      ],
      'fr' => [
        'Premier nœud fr',
      ]
    ];

    // Create nodes with translations.
    foreach ($this->nodeTitles['es'] as $index => $title) {
      $node = $this->drupalCreateNode(['title' => $title, 'langcode' => 'es', 'type' => 'page', 'promote' => 1]);
      foreach (['en', 'fr'] as $langcode) {
        if (isset($this->nodeTitles[$langcode][$index])) {
          $translation = $node->addTranslation($langcode, ['title' => $this->nodeTitles[$langcode][$index]]);
          $translation->body->value = $this->randomMachineName(32);
        }
      }
      $node->save();
    }
    // Create non-translatable nodes.
    foreach ($this->nodeTitles[LanguageInterface::LANGCODE_NOT_SPECIFIED] as $index => $title) {
      $node = $this->drupalCreateNode(['title' => $title, 'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED, 'type' => 'page', 'promote' => 1]);
      $node->body->value = $this->randomMachineName(32);
      $node->save();
    }

    $this->container->get('router.builder')->rebuild();

    $user = $this->drupalCreateUser(['access content overview', 'access content']);
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
    foreach ($this->nodeTitles as $langcode => $list) {
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
    foreach ($this->nodeTitles['es'] as $title) {
      $pos_es_max = max($pos_es_max, strpos($page, $title));
    }
    foreach ($this->nodeTitles['fr'] as $title) {
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
    foreach ($this->nodeTitles as $langcode => $titles) {
      // The frontpage view does not display content without a language.
      if ($langcode == LanguageInterface::LANGCODE_NOT_SPECIFIED) {
        continue;
      }
      $this->drupalGet(($langcode == 'en' ? '' : "$langcode/") . 'node');
      foreach ($titles as $title) {
        $this->assertText($title);
      }
      foreach ($this->nodeTitles as $control_langcode => $control_titles) {
        if ($langcode != $control_langcode) {
          foreach ($control_titles as $title) {
            $this->assertNoText($title);
          }
        }
      }
    }

    // Test the node admin view filter. By default all translations should show.
    $this->drupalGet('admin/content');
    foreach ($this->nodeTitles as $titles) {
      foreach ($titles as $title) {
        $this->assertText($title);
      }
    }
    // When filtered, only the specific languages should show.
    foreach ($this->nodeTitles as $langcode => $titles) {
      $this->drupalGet('admin/content', ['query' => ['langcode' => $langcode]]);
      foreach ($titles as $title) {
        $this->assertText($title);
      }
      foreach ($this->nodeTitles as $control_langcode => $control_titles) {
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
    $config->set('display.default.display_options.filters.langcode.value', [PluginBase::VIEWS_QUERY_LANGUAGE_SITE_DEFAULT => PluginBase::VIEWS_QUERY_LANGUAGE_SITE_DEFAULT]);
    $config->save();
    foreach ($this->nodeTitles as $langcode => $titles) {
      if ($langcode == LanguageInterface::LANGCODE_NOT_SPECIFIED) {
        continue;
      }
      $this->drupalGet(($langcode == 'en' ? '' : "$langcode/") . 'node');
      foreach ($this->nodeTitles as $control_langcode => $control_titles) {
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
    $config->set('display.default.display_options.filters.langcode.value', ['***LANGUAGE_language_interface***' => '***LANGUAGE_language_interface***']);
    $config->save();
    $language_config = $this->config('language.types');
    $language_config->set('negotiation.language_interface.enabled', ['language-selected' => 1]);
    $language_config->save();
    $language_config = $this->config('language.negotiation');
    $language_config->set('selected_langcode', 'es');
    $language_config->save();

    // With a fixed language selected, there is no language-based URL.
    $this->drupalGet('node');
    foreach ($this->nodeTitles as $control_langcode => $control_titles) {
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

  /**
   * Tests native name display in language field.
   */
  public function testNativeLanguageField() {
    $this->assertLanguageNames();

    // Modify test view to display native language names and set translations.
    $config = $this->config('views.view.test_language');
    $config->set('display.default.display_options.fields.langcode.settings.native_language', TRUE);
    $config->save();
    \Drupal::languageManager()->getLanguageConfigOverride('fr', 'language.entity.fr')->set('label', 'Français')->save();
    \Drupal::languageManager()->getLanguageConfigOverride('es', 'language.entity.es')->set('label', 'Español')->save();
    $this->assertLanguageNames(TRUE);

    // Modify test view to use the views built-in language field and test that.
    \Drupal::state()->set('node_test_views.use_basic_handler', TRUE);
    Views::viewsData()->clear();
    $config = $this->config('views.view.test_language');
    $config->set('display.default.display_options.fields.langcode.native_language', FALSE);
    $config->clear('display.default.display_options.fields.langcode.settings');
    $config->clear('display.default.display_options.fields.langcode.type');
    $config->set('display.default.display_options.fields.langcode.plugin_id', 'language');
    $config->save();
    $this->assertLanguageNames();
    $config->set('display.default.display_options.fields.langcode.native_language', TRUE)->save();
    $this->assertLanguageNames(TRUE);
  }

  /**
   * Asserts the presence of language names in their English or native forms.
   *
   * @param bool $native
   *   (optional) Whether to assert the language name in its native form.
   */
  protected function assertLanguageNames($native = FALSE) {
    $this->drupalGet('test-language');
    if ($native) {
      $this->assertText('Français', 'French language shown in native form.');
      $this->assertText('Español', 'Spanish language shown in native form.');
      $this->assertNoText('French', 'French language not shown in English.');
      $this->assertNoText('Spanish', 'Spanish language not shown in English.');
    }
    else {
      $this->assertNoText('Français', 'French language not shown in native form.');
      $this->assertNoText('Español', 'Spanish language not shown in native form.');
      $this->assertText('French', 'French language shown in English.');
      $this->assertText('Spanish', 'Spanish language shown in English.');
    }
  }

}
