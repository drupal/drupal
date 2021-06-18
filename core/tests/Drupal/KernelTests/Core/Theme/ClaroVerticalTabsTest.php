<?php

namespace Drupal\KernelTests\Core\Theme;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Core\Form\FormState;

/**
 * Confirms that Claro can render vertical tabs correctly.
 *
 * @group Theme
 */
class ClaroVerticalTabsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * Confirms that Claro can render vertical tabs correctly.
   */
  public function testVerticalTabs() {
    // Enable the Claro theme.
    \Drupal::service('theme_installer')->install(['claro']);
    $this->config('system.theme')->set('default', 'claro')->save();

    $form = [
      '#parents' => [],
      '#array_parents' => [],
      '#tree' => FALSE,
    ];

    $form['vertical_tabs'] = [
      '#type' => 'vertical_tabs',
      '#title' => 'Vertical Tabs',
    ];
    $form['vertical_tabs_details'] = [
      '#type' => 'details',
      '#title' => 'Details',
      '#group' => 'vertical_tabs',
    ];

    // Needs to be rendered after the vertical tabs.
    $form['container'] = [
      '#type' => 'container',
      '#title' => 'Container',
    ];
    $form['container_details'] = [
      '#type' => 'details',
      '#title' => 'Details',
      '#group' => 'container',
    ];

    /** @var \Drupal\Core\Form\FormBuilderInterface $form_builder */
    $form_builder = $this->container->get('form_builder');
    $form_state = new FormState();
    $form_builder->doBuildForm('form', $form, $form_state);
    $this->render($form);

    // Assert that the vertical tab details has the appropriate class.
    $this->assertCount(1, $this->cssSelect('.vertical-tabs__items details.vertical-tabs__item'));
    // Assert that there is a details element.
    $this->assertCount(1, $this->cssSelect('#edit-container-details'));
    // Assert that details element doesn't have the vertical tab classes.
    $this->assertCount(0, $this->cssSelect('#edit-container-details.vertical-tabs__item'));
  }

}
