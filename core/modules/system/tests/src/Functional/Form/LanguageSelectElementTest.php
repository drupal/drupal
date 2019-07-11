<?php

namespace Drupal\Tests\system\Functional\Form;

use Drupal\Component\Render\FormattableMarkup;
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
  public static $modules = ['form_test', 'language'];

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
      $this->assertField($id, new FormattableMarkup('The @id field was found on the page.', ['@id' => $id]));
      $options = [];
      /* @var $language_manager \Drupal\Core\Language\LanguageManagerInterface */
      $language_manager = $this->container->get('language_manager');
      foreach ($language_manager->getLanguages($flags) as $langcode => $language) {
        $options[$langcode] = $language->isLocked() ? t('- @name -', ['@name' => $language->getName()]) : $language->getName();
      }
      $this->_testLanguageSelectElementOptions($id, $options);
    }

    // Test that the #options were not altered by #languages.
    $this->assertField('edit-language-custom-options', new FormattableMarkup('The @id field was found on the page.', ['@id' => 'edit-language-custom-options']));
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
      $this->assertNoField($id, new FormattableMarkup('The @id field was not found on the page.', ['@id' => $id]));
    }

    // Check that the submitted values were the default values of the language
    // field elements.
    $edit = [];
    $this->drupalPostForm(NULL, $edit, t('Submit'));
    $values = Json::decode($this->getSession()->getPage()->getContent());
    $this->assertEqual($values['languages_all'], 'xx');
    $this->assertEqual($values['languages_configurable'], 'en');
    $this->assertEqual($values['languages_locked'], LanguageInterface::LANGCODE_NOT_SPECIFIED);
    $this->assertEqual($values['languages_config_and_locked'], 'dummy_value');
    $this->assertEqual($values['language_custom_options'], 'opt2');
  }

  /**
   * Helper function to check the options of a language select form element.
   *
   * @param string $id
   *   The id of the language select element to check.
   *
   * @param array $options
   *   An array with options to compare with.
   */
  protected function _testLanguageSelectElementOptions($id, $options) {
    // Check that the options in the language field are exactly the same,
    // including the order, as the languages sent as a parameter.
    $elements = $this->xpath("//select[@id='" . $id . "']");
    $count = 0;
    /** @var \Behat\Mink\Element\NodeElement $option */
    foreach ($elements[0]->findAll('css', 'option') as $option) {
      $count++;
      $option_title = current($options);
      $this->assertEqual($option->getText(), $option_title);
      next($options);
    }
    $this->assertEqual($count, count($options), new FormattableMarkup('The number of languages and the number of options shown by the language element are the same: @languages languages, @number options', ['@languages' => count($options), '@number' => $count]));
  }

}
