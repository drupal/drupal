<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Wizard\WizardPluginBaseUnitTest.
 */

namespace Drupal\views\Tests\Wizard;

use Drupal\Core\Form\FormState;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\views\Tests\ViewUnitTestBase;
use Drupal\views_ui\ViewUI;

/**
 * Tests the wizard base plugin class.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\wizard\WizardPluginBase
 */
class WizardPluginBaseUnitTest extends ViewUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('language', 'system', 'user', 'views_ui');

  /**
   * Contains thw wizard plugin manager.
   *
   * @var \Drupal\views\Plugin\views\wizard\WizardPluginBase
   */
  protected $wizard;

  protected function setUp() {
    parent::setUp();

    $this->installConfig(array('language'));

    $this->wizard = $this->container->get('plugin.manager.views.wizard')->createInstance('standard:views_test_data', array());
  }

  /**
   * Tests the creating of a view.
   *
   * @see \Drupal\views\Plugin\views\wizard\WizardPluginBase
   */
  public function testCreateView() {
    $form = array();
    $form_state = new FormState();
    $form = $this->wizard->buildForm($form, $form_state);
    $random_id = strtolower($this->randomMachineName());
    $random_label = $this->randomMachineName();
    $random_description = $this->randomMachineName();

    // Add a new language and mark it as default.
    ConfigurableLanguage::createFromLangcode('it')->save();
    \Drupal::config('system.site')->set('langcode', 'it')->save();

    $form_state->setValues([
      'id' => $random_id,
      'label' => $random_label,
      'description' => $random_description,
      'base_table' => 'views_test_data',
    ]);

    $this->wizard->validateView($form, $form_state);
    $view = $this->wizard->createView($form, $form_state);
    $this->assertTrue($view instanceof ViewUI, 'The created view is a ViewUI object.');
    $this->assertEqual($view->get('id'), $random_id);
    $this->assertEqual($view->get('label'), $random_label);
    $this->assertEqual($view->get('description'), $random_description);
    $this->assertEqual($view->get('base_table'), 'views_test_data');
    $this->assertEqual($view->get('langcode'), 'it');
  }
}

