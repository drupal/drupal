<?php

/**
 * @file
 * Contains \Drupal\menu\MenuSettingsForm.
 */

namespace Drupal\menu;

use Drupal\Core\Form\ConfigFormBase;

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
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, array &$form_state) {
    $config = $this->configFactory->get('menu.settings');
    $form['intro'] = array(
      '#type' => 'item',
      '#markup' => t('The menu module allows on-the-fly creation of menu links in the content authoring forms. To configure these settings for a particular content type, visit the <a href="@content-types">Content types</a> page, click the <em>edit</em> link for the content type, and go to the <em>Menu settings</em> section.', array('@content-types' => url('admin/structure/types'))),
    );

    $menu_options = menu_get_menus();

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
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->configFactory->get('menu.settings')
      ->set('main_links', $form_state['values']['menu_main_links_source'])
      ->set('secondary_links', $form_state['values']['menu_secondary_links_source'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
