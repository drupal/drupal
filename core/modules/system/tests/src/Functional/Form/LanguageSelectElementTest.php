<?php

namespace Drupal\Tests\system\Functional\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Language\LanguageInterface;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests that the language select form element prints and submits the right
 * options.
 *
 * @group Form
 */
class LanguageSelectElementTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['form_test', 'language'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that the options printed by the language select element are correct.
   */
  public function testLanguageSelectElementOptions() {
    // Add some languages.
    ConfigurableLanguage::create([
      'id' => 'aaa',
      'label' => $this->randomMachineName(),
    ])->save();

    ConfigurableLanguage::create([
      'id' => 'bbb',
      'label' => $this->randomMachineName(),
    ])->save();

    \Drupal::languageManager()->reset();

    $this->drupalGet('form-test/language_select');
    // Check that the language fields were rendered on the page.
    $ids = [
        'edit-languages-all' => LanguageInterface::STATE_ALL,
        'edit-languages-configurable' => LanguageInterface::STATE_CONFIGURABLE,
        'edit-languages-locked' => LanguageInterface::STATE_LOCKED,
        'edit-languages-config-and-locked' => LanguageInterface::STATE_CONFIGURABLE | LanguageInterface::STATE_LOCKED,
    ];
    foreach ($ids as $id => $flags) {
      $this->assertSession()->fieldExists($id);
      $options = [];
      /** @var \Drupal\Core\Language\LanguageManagerInterface $language_manager */
      $language_manager = $this->container->get('language_manager');
      foreach ($language_manager->getLanguages($flags) as $langcode => $language) {
        $options[$langcode] = $language->isLocked() ? "- {$language->getName()} -" : $language->getName();
      }
      $this->_testLanguageSelectElementOptions($id, $options);
    }

    // Test that the #options were not altered by #languages.
    $this->assertSession()->fieldExists('edit-language-custom-options');
    $this->_testLanguageSelectElementOptions('edit-language-custom-options', ['opt1' => 'First option', 'opt2' => 'Second option', 'opt3' => 'Third option']);
  }

  /**
   * Tests the case when the language select elements should not be printed.
   *
   * This happens when the language module is disabled.
   */
  public function testHiddenLanguageSelectElement() {
    // Disable the language module, so that the language select field will not
    // be rendered.
    $this->container->get('module_installer')->uninstall(['language']);
    $this->drupalGet('form-test/language_select');
    // Check that the language fields were rendered on the page.
    $ids = ['edit-languages-all', 'edit-languages-configurable', 'edit-languages-locked', 'edit-languages-config-and-locked'];
    foreach ($ids as $id) {
      $this->assertSession()->fieldNotExists($id);
    }

    // Check that the submitted values were the default values of the language
    // field elements.
    $edit = [];
    $this->submitForm($edit, 'Submit');
    $values = Json::decode($this->getSession()->getPage()->getContent());
    $this->assertEquals('xx', $values['languages_all']);
    $this->assertEquals('en', $values['languages_configurable']);
    $this->assertEquals(LanguageInterface::LANGCODE_NOT_SPECIFIED, $values['languages_locked']);
    $this->assertEquals('dummy_value', $values['languages_config_and_locked']);
    $this->assertEquals('opt2', $values['language_custom_options']);
  }

  /**
   * Helper function to check the options of a language select form element.
   *
   * @param string $id
   *   The id of the language select element to check.
   * @param array $options
   *   An array with options to compare with.
   */
  protected function _testLanguageSelectElementOptions($id, $options) {
    // Check that the options in the language field are exactly the same,
    // including the order, as the languages sent as a parameter.
    $found_options = $this->assertSession()->selectExists($id)->findAll('css', 'option');
    $found_options = array_map(function ($item) {
      return $item->getText();
    }, $found_options);
    $this->assertEquals(array_values($options), $found_options);
  }

}
