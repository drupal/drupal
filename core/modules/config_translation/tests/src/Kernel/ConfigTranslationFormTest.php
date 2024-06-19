<?php

declare(strict_types=1);

namespace Drupal\Tests\config_translation\Kernel;

use Drupal\config_translation\Form\ConfigTranslationAddForm;
use Drupal\config_translation\Form\ConfigTranslationEditForm;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests for altering configuration translation forms.
 *
 * @group config_translation
 */
class ConfigTranslationFormTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'config_translation',
    'config_translation_test',
    'language',
    'locale',
  ];

  /**
   * Tests altering of the configuration translation forms.
   */
  public function testConfigTranslationFormAlter(): void {
    $this->installConfig(['config_translation_test']);

    $definitions = $this->container->get('plugin.manager.config_translation.mapper')->getDefinitions();
    $plugin_id = key($definitions);
    $langcode = 'xx';

    ConfigurableLanguage::create(['id' => $langcode, 'label' => 'XX'])->save();

    $this->container->get('state')->set('config_translation_test_alter_form_alter', TRUE);

    $form_builder = $this->container->get('form_builder');
    $route_match = $this->container->get('current_route_match');

    $add_form = $form_builder->getForm(ConfigTranslationAddForm::class, $route_match, $plugin_id, $langcode);
    $edit_form = $form_builder->getForm(ConfigTranslationEditForm::class, $route_match, $plugin_id, $langcode);

    // Test that hook_form_BASE_FORM_ID_alter() was called for the base form ID
    // 'config_translation_form'.
    $this->assertTrue($add_form['#base_altered']);
    $this->assertTrue($edit_form['#base_altered']);

    // Test that hook_form_FORM_ID_alter() was called for the form IDs
    // 'config_translation_add_form' and 'config_translation_edit_form'.
    $this->assertTrue($add_form['#altered']);
    $this->assertTrue($edit_form['#altered']);
  }

}
