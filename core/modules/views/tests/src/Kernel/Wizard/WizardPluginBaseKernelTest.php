<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel\Wizard;

use Drupal\Core\Form\FormState;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views_ui\ViewUI;

/**
 * Tests the wizard base plugin class.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\wizard\WizardPluginBase
 */
class WizardPluginBaseKernelTest extends ViewsKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['language', 'system', 'user', 'views_ui'];

  /**
   * Contains thw wizard plugin manager.
   *
   * @var \Drupal\views\Plugin\views\wizard\WizardPluginBase
   */
  protected $wizard;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp();

    $this->installConfig(['language']);

    $this->wizard = $this->container->get('plugin.manager.views.wizard')->createInstance('standard:views_test_data', []);
  }

  /**
   * Tests the creating of a view.
   *
   * @see \Drupal\views\Plugin\views\wizard\WizardPluginBase
   */
  public function testCreateView(): void {
    $form = [];
    $form_state = new FormState();
    $form = $this->wizard->buildForm($form, $form_state);
    $random_id = $this->randomMachineName();
    $random_label = $this->randomMachineName();
    $random_description = $this->randomMachineName();

    // Add a new language and mark it as default.
    ConfigurableLanguage::createFromLangcode('it')->save();
    $this->config('system.site')->set('default_langcode', 'it')->save();

    $form_state->setValues([
      'id' => $random_id,
      'label' => $random_label,
      'description' => $random_description,
      'base_table' => 'views_test_data',
    ]);

    $this->wizard->validateView($form, $form_state);
    $view = $this->wizard->createView($form, $form_state);
    $this->assertInstanceOf(ViewUI::class, $view);
    $this->assertEquals($random_id, $view->get('id'));
    $this->assertEquals($random_label, $view->get('label'));
    $this->assertEquals($random_description, $view->get('description'));
    $this->assertEquals('views_test_data', $view->get('base_table'));
    $this->assertEquals('it', $view->get('langcode'));
  }

}
