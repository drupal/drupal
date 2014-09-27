<?php

/**
 * @file
 * Contains \Drupal\menu_ui\MenuSettingsForm.
 */

namespace Drupal\menu_ui;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Select the menus to be used for the main and secondary links for this site.
 */
class MenuSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'menu_configure';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('menu_ui.settings');
    $form['intro'] = array(
      '#type' => 'item',
      '#markup' => t('The Menu UI module allows on-the-fly creation of menu links in the content authoring forms. To configure these settings for a particular content type, visit the <a href="@content-types">Content types</a> page, click the <em>edit</em> link for the content type, and go to the <em>Menu settings</em> section.', array('@content-types' => $this->url('node.overview_types'))),
    );

    $menu_options = menu_ui_get_menus();

    $main = $config->get('main_links');
    $form['menu_main_links_source'] = array(
      '#type' => 'select',
      '#title' => t('Source for the Main links'),
      '#default_value' => $main,
      '#empty_option' => t('No Main links'),
      '#options' => $menu_options,
      '#tree' => FALSE,
      '#description' => t('Select what should be displayed as the Main links (typically at the top of the page).'),
    );

    $form['menu_secondary_links_source'] = array(
      '#type' => 'select',
      '#title' => t('Source for the Secondary links'),
      '#default_value' => $config->get('secondary_links'),
      '#empty_option' => t('No Secondary links'),
      '#options' => $menu_options,
      '#tree' => FALSE,
      '#description' => t('Select the source for the Secondary links. An advanced option allows you to use the same source for both Main links (currently %main) and Secondary links: if your source menu has two levels of hierarchy, the top level menu links will appear in the Main links, and the children of the active link will appear in the Secondary links.', array('%main' => $main ? $menu_options[$main] : t('none'))),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('menu_ui.settings')
      ->set('main_links', $form_state->getValue('menu_main_links_source'))
      ->set('secondary_links', $form_state->getValue('menu_secondary_links_source'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
