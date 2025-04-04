<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Config;

use Drupal\Core\Language\Language;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that config overrides are applied in the correct order.
 *
 * Overrides should be applied in the following order, from lowest priority
 * to highest:
 * - Language overrides.
 * - Module overrides.
 * - settings.php overrides.
 *
 * @group config
 */
class ConfigOverridesPriorityTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'config',
    'config_override_test',
    'language',
  ];

  /**
   * Tests the order of config overrides.
   */
  public function testOverridePriorities(): void {
    $GLOBALS['config_test_run_module_overrides'] = FALSE;

    $non_overridden_mail = 'site@example.com';
    $language_overridden_mail = 'french@example.com';

    $language_overridden_name = 'French site name';
    $module_overridden_name = 'Wow overridden site name';
    $non_overridden_name = 'Wow this name is on disk mkay';

    $module_overridden_slogan = 'Yay for overrides!';
    $non_overridden_slogan = 'Yay for defaults!';

    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $this->container->get('config.factory');
    $config_factory
      ->getEditable('system.site')
      ->set('name', $non_overridden_name)
      ->set('slogan', $non_overridden_slogan)
      ->set('mail', $non_overridden_mail)
      ->set('weight_select_max', 50)
      // `name` and `slogan` are translatable, hence a `langcode` is required.
      // @see \Drupal\Core\Config\Plugin\Validation\Constraint\LangcodeRequiredIfTranslatableValuesConstraint
      ->set('langcode', 'en')
      ->save();

    // Ensure that no overrides are applying.
    $this->assertEquals($non_overridden_name, $config_factory->get('system.site')->get('name'));
    $this->assertEquals($non_overridden_slogan, $config_factory->get('system.site')->get('slogan'));
    $this->assertEquals($non_overridden_mail, $config_factory->get('system.site')->get('mail'));
    $this->assertEquals(50, $config_factory->get('system.site')->get('weight_select_max'));

    // Override using language.
    $language = new Language([
      'name' => 'French',
      'id' => 'fr',
    ]);
    \Drupal::languageManager()->setConfigOverrideLanguage($language);
    \Drupal::languageManager()
      ->getLanguageConfigOverride($language->getId(), 'system.site')
      ->set('name', $language_overridden_name)
      ->set('mail', $language_overridden_mail)
      ->save();

    $this->assertEquals($language_overridden_name, $config_factory->get('system.site')->get('name'));
    $this->assertEquals($non_overridden_slogan, $config_factory->get('system.site')->get('slogan'));
    $this->assertEquals($language_overridden_mail, $config_factory->get('system.site')->get('mail'));
    $this->assertEquals(50, $config_factory->get('system.site')->get('weight_select_max'));

    // Enable module overrides. Do not override system.site:mail to prove that
    // the language override still applies.
    $GLOBALS['config_test_run_module_overrides'] = TRUE;
    $config_factory->reset('system.site');
    $this->assertEquals($module_overridden_name, $config_factory->get('system.site')->get('name'));
    $this->assertEquals($module_overridden_slogan, $config_factory->get('system.site')->get('slogan'));
    $this->assertEquals($language_overridden_mail, $config_factory->get('system.site')->get('mail'));
    $this->assertEquals(50, $config_factory->get('system.site')->get('weight_select_max'));

    // Configure a global override to simulate overriding using settings.php. Do
    // not override system.site:mail or system.site:slogan to prove that the
    // language and module overrides still apply.
    $GLOBALS['config']['system.site']['name'] = 'Site name global conf override';
    $config_factory->reset('system.site');
    $this->assertEquals('Site name global conf override', $config_factory->get('system.site')->get('name'));
    $this->assertEquals($module_overridden_slogan, $config_factory->get('system.site')->get('slogan'));
    $this->assertEquals($language_overridden_mail, $config_factory->get('system.site')->get('mail'));
    $this->assertEquals(50, $config_factory->get('system.site')->get('weight_select_max'));

    $this->assertEquals($non_overridden_name, $config_factory->get('system.site')->getOriginal('name', FALSE));
    $this->assertEquals($non_overridden_slogan, $config_factory->get('system.site')->getOriginal('slogan', FALSE));
    $this->assertEquals($non_overridden_mail, $config_factory->get('system.site')->getOriginal('mail', FALSE));
    $this->assertEquals(50, $config_factory->get('system.site')->getOriginal('weight_select_max', FALSE));

    unset($GLOBALS['config_test_run_module_overrides']);
  }

}
