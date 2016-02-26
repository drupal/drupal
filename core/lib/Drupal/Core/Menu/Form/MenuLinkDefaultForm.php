<?php

/**
 * @file
 * Contains \Drupal\Core\Menu\Form\MenuLinkDefaultForm.
 */

namespace Drupal\Core\Menu\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuLinkInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Menu\MenuParentFormSelectorInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an edit form for static menu links.
 *
 * @see \Drupal\Core\Menu\MenuLinkDefault
 */
class MenuLinkDefaultForm implements MenuLinkFormInterface, ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The edited menu link.
   *
   * @var \Drupal\Core\Menu\MenuLinkInterface
   */
  protected $menuLink;

  /**
   * The menu link manager.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * The parent form selector service.
   *
   * @var \Drupal\Core\Menu\MenuParentFormSelectorInterface
   */
  protected $menuParentSelector;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The module data from system_get_info().
   *
   * @var array
   */
  protected $moduleData;

  /**
   * Constructs a new \Drupal\Core\Menu\Form\MenuLinkDefaultForm.
   *
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager
   *   The menu link manager.
   * @param \Drupal\Core\Menu\MenuParentFormSelectorInterface $menu_parent_selector
   *   The menu parent form selector service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler;
   */
  public function __construct(MenuLinkManagerInterface $menu_link_manager, MenuParentFormSelectorInterface $menu_parent_selector, TranslationInterface $string_translation, ModuleHandlerInterface $module_handler) {
    $this->menuLinkManager = $menu_link_manager;
    $this->menuParentSelector = $menu_parent_selector;
    $this->stringTranslation = $string_translation;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.menu.link'),
      $container->get('menu.parent_form_selector'),
      $container->get('string_translation'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setMenuLinkInstance(MenuLinkInterface $menu_link) {
    $this->menuLink = $menu_link;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['#title'] = $this->t('Edit menu link %title', array('%title' => $this->menuLink->getTitle()));

    $provider = $this->menuLink->getProvider();
    $form['info'] = array(
      '#type' => 'item',
      '#title' => $this->t('This link is provided by the @name module. The title and path cannot be edited.', array('@name' => $this->moduleHandler->getName($provider))),
    );
    $form['id'] = array(
      '#type' => 'value',
      '#value' => $this->menuLink->getPluginId(),
    );
    $link = array(
      '#type' => 'link',
      '#title' => $this->menuLink->getTitle(),
      '#url' => $this->menuLink->getUrlObject(),
    );
    $form['path'] = array(
      'link' => $link,
      '#type' => 'item',
      '#title' => $this->t('Link'),
    );

    $form['enabled'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable menu link'),
      '#description' => $this->t('Menu links that are not enabled will not be listed in any menu.'),
      '#default_value' => $this->menuLink->isEnabled(),
    );

    $form['expanded'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show as expanded'),
      '#description' => $this->t('If selected and this menu link has children, the menu will always appear expanded.'),
      '#default_value' => $this->menuLink->isExpanded(),
    );

    $menu_parent = $this->menuLink->getMenuName() . ':' . $this->menuLink->getParent();
    $form['menu_parent'] = $this->menuParentSelector->parentSelectElement($menu_parent, $this->menuLink->getPluginId());
    $form['menu_parent']['#title'] = $this->t('Parent link');
    $form['menu_parent']['#description'] = $this->t('The maximum depth for a link and all its children is fixed. Some menu links may not be available as parents if selecting them would exceed this limit.');
    $form['menu_parent']['#attributes']['class'][] = 'menu-title-select';

    $delta = max(abs($this->menuLink->getWeight()), 50);
    $form['weight'] = array(
      '#type' => 'number',
      '#min' => -$delta,
      '#max' => $delta,
      '#default_value' => $this->menuLink->getWeight(),
      '#title' => $this->t('Weight'),
      '#description' => $this->t('Link weight among links in the same menu at the same depth. In the menu, the links with high weight will sink and links with a low weight will be positioned nearer the top.'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(array &$form, FormStateInterface $form_state) {
    // Start from the complete, original, definition.
    $new_definition = $this->menuLink->getPluginDefinition();
    // Since the ID may not be present in the definition used to construct the
    // plugin, add it here so it's available to any consumers of this method.
    $new_definition['id'] = $form_state->getValue('id');
    $new_definition['enabled'] = $form_state->getValue('enabled') ? 1 : 0;
    $new_definition['weight'] = (int) $form_state->getValue('weight');
    $new_definition['expanded'] = $form_state->getValue('expanded') ? 1 : 0;
    list($menu_name, $parent) = explode(':', $form_state->getValue('menu_parent'), 2);
    if (!empty($menu_name)) {
      $new_definition['menu_name'] = $menu_name;
    }
    if (isset($parent)) {
      $new_definition['parent'] = $parent;
    }
    return $new_definition;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $new_definition = $this->extractFormValues($form, $form_state);

    return $this->menuLinkManager->updateDefinition($this->menuLink->getPluginId(), $new_definition);
  }

}
