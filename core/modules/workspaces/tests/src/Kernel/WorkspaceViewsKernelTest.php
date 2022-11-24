<?php

namespace Drupal\Tests\workspaces\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views_ui\ViewUI;

/**
 * Tests the views data for workspaces.
 *
 * @group views
 * @group workspaces
 * @see \Drupal\views\Plugin\views\wizard\WizardPluginBase
 */
class WorkspaceViewsKernelTest extends ViewsKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['views_ui', 'workspaces'];

  /**
   * Tests creating a view of workspace entities.
   *
   * @see \Drupal\views\Plugin\views\wizard\WizardPluginBase
   */
  public function testCreateWorkspaceView() {
    $wizard = \Drupal::service('plugin.manager.views.wizard')->createInstance('standard:workspace', []);
    $form = [];
    $form_state = new FormState();
    $form = $wizard->buildForm($form, $form_state);
    $random_id = strtolower($this->randomMachineName());
    $random_label = $this->randomMachineName();

    $form_state->setValues([
      'id' => $random_id,
      'label' => $random_label,
      'base_table' => 'workspace',
    ]);

    $wizard->validateView($form, $form_state);
    $view = $wizard->createView($form, $form_state);
    $this->assertInstanceOf(ViewUI::class, $view);
    $this->assertEquals($random_id, $view->get('id'));
    $this->assertEquals($random_label, $view->get('label'));
    $this->assertEquals('workspace', $view->get('base_table'));
  }

}
