<?php

/**
 * @file
 * Contains \Drupal\menu_link\MenuLinkForm.
 */

namespace Drupal\menu_link;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Language\Language;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Routing\UrlGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the node edit forms.
 */
class MenuLinkForm extends EntityForm {

  /**
   * The menu link storage.
   *
   * @var \Drupal\menu_link\MenuLinkStorageInterface
   */
  protected $menuLinkStorage;

  /**
   * The path alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $pathAliasManager;

  /**
   * The URL generator.
   *
   * @var \Drupal\Core\Routing\UrlGenerator
   */
  protected $urlGenerator;

  /**
   * Constructs a new MenuLinkForm object.
   *
   * @param \Drupal\menu_link\MenuLinkStorageInterface $menu_link_storage
   *   The menu link storage.
   * @param \Drupal\Core\Path\AliasManagerInterface $path_alias_manager
   *   The path alias manager.
   * @param \Drupal\Core\Routing\UrlGenerator $url_generator
   *   The URL generator.
   */
  public function __construct(MenuLinkStorageInterface $menu_link_storage, AliasManagerInterface $path_alias_manager, UrlGenerator $url_generator) {
    $this->menuLinkStorage = $menu_link_storage;
    $this->pathAliasManager = $path_alias_manager;
    $this->urlGenerator = $url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('menu_link'),
      $container->get('path.alias_manager'),
      $container->get('url_generator')
    );
  }

  /**
   * Overrides EntityForm::form().
   */
  public function form(array $form, array &$form_state) {
    $menu_link = $this->entity;
    // Since menu_link_load() no longer returns a translated and access checked
    // item, do it here instead.
    _menu_link_translate($menu_link);

    $form['link_title'] = array(
      '#type' => 'textfield',
      '#title' => t('Menu link title'),
      '#default_value' => $menu_link->link_title,
      '#description' => t('The text to be used for this link in the menu.'),
      '#required' => TRUE,
    );
    foreach (array('link_path', 'mlid', 'module', 'has_children', 'options') as $key) {
      $form[$key] = array('#type' => 'value', '#value' => $menu_link->{$key});
    }
    // Any item created or edited via this interface is considered "customized".
    $form['customized'] = array('#type' => 'value', '#value' => 1);

    // We are not using url() when constructing this path because it would add
    // $base_path.
    $path = $menu_link->link_path;
    if (isset($menu_link->options['query'])) {
      $path .= '?' . $this->urlGenerator->httpBuildQuery($menu_link->options['query']);
    }
    if (isset($menu_link->options['fragment'])) {
      $path .= '#' . $menu_link->options['fragment'];
    }
    if ($menu_link->module == 'menu_ui') {
      $form['link_path'] = array(
        '#type' => 'textfield',
        '#title' => t('Path'),
        '#maxlength' => 255,
        '#default_value' => $path,
        '#description' => t('The path for this menu link. This can be an internal Drupal path such as %add-node or an external URL such as %drupal. Enter %front to link to the front page.', array('%front' => '<front>', '%add-node' => 'node/add', '%drupal' => 'http://drupal.org')),
        '#required' => TRUE,
      );
    }
    else {
      $form['_path'] = array(
        '#type' => 'item',
        '#title' => t('Path'),
        '#description' => l($menu_link->link_title, $menu_link->href, $menu_link->options),
      );
    }

    $form['description'] = array(
      '#type' => 'textarea',
      '#title' => t('Description'),
      '#default_value' => isset($menu_link->options['attributes']['title']) ? $menu_link->options['attributes']['title'] : '',
      '#rows' => 1,
      '#description' => t('Shown when hovering over the menu link.'),
    );
    $form['enabled'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enabled'),
      '#default_value' => !$menu_link->hidden,
      '#description' => t('Menu links that are not enabled will not be listed in any menu.'),
    );
    $form['expanded'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show as expanded'),
      '#default_value' => $menu_link->expanded,
      '#description' => t('If selected and this menu link has children, the menu will always appear expanded.'),
    );

    // Generate a list of possible parents (not including this link or descendants).
    $options = menu_ui_parent_options(menu_ui_get_menus(), $menu_link);
    $default = $menu_link->menu_name . ':' . $menu_link->plid;
    if (!isset($options[$default])) {
      $default = 'tools:0';
    }
    $form['parent'] = array(
      '#type' => 'select',
      '#title' => t('Parent link'),
      '#default_value' => $default,
      '#options' => $options,
      '#description' => t('The maximum depth for a link and all its children is fixed at !maxdepth. Some menu links may not be available as parents if selecting them would exceed this limit.', array('!maxdepth' => MENU_MAX_DEPTH)),
      '#attributes' => array('class' => array('menu-title-select')),
    );

    // Get number of items in menu so the weight selector is sized appropriately.
    $delta = $this->menuLinkStorage->countMenuLinks($menu_link->menu_name);
    $form['weight'] = array(
      '#type' => 'weight',
      '#title' => t('Weight'),
      // Old hardcoded value.
      '#delta' => max($delta, 50),
      '#default_value' => $menu_link->weight,
      '#description' => t('Optional. In the menu, the heavier links will sink and the lighter links will be positioned nearer the top.'),
    );

    // Language module allows to configure the menu link language independently
    // of the menu language. It also allows to optionally show the language
    // selector on the menu link form so that the language of each menu link can
    // be configured individually.
    if ($this->moduleHandler->moduleExists('language')) {
      $language_configuration = language_get_default_configuration('menu_link', $menu_link->bundle());
      $default_langcode = ($menu_link->isNew() ? $language_configuration['langcode'] : $menu_link->langcode);
      $language_show = $language_configuration['language_show'];
    }
    // Without Language module menu links inherit the menu language and no
    // language selector is shown.
    else {
      $default_langcode = ($menu_link->isNew() ? entity_load('menu', $menu_link->menu_name)->langcode : $menu_link->langcode);
      $language_show = FALSE;
    }

    $form['langcode'] = array(
      '#type' => 'language_select',
      '#title' => t('Language'),
      '#languages' => Language::STATE_ALL,
      '#default_value' => $default_langcode,
      '#access' => $language_show,
    );

    return parent::form($form, $form_state, $menu_link);
  }

  /**
   * Overrides EntityForm::actions().
   */
  protected function actions(array $form, array &$form_state) {
    $element = parent::actions($form, $form_state);
    $element['submit']['#button_type'] = 'primary';
    return $element;
  }

  /**
   * Overrides EntityForm::validate().
   */
  public function validate(array $form, array &$form_state) {
    $menu_link = $this->buildEntity($form, $form_state);

    $normal_path = $this->pathAliasManager->getPathByAlias($menu_link->link_path);
    if ($menu_link->link_path != $normal_path) {
      drupal_set_message(t('The menu system stores system paths only, but will use the URL alias for display. %link_path has been stored as %normal_path', array('%link_path' => $menu_link->link_path, '%normal_path' => $normal_path)));
      $menu_link->link_path = $normal_path;
      $form_state['values']['link_path'] = $normal_path;
    }
    if (!UrlHelper::isExternal($menu_link->link_path)) {
      $parsed_link = parse_url($menu_link->link_path);
      if (isset($parsed_link['query'])) {
        $menu_link->options['query'] = array();
        parse_str($parsed_link['query'], $menu_link->options['query']);
      }
      else {
        // Use unset() rather than setting to empty string
        // to avoid redundant serialized data being stored.
        unset($menu_link->options['query']);
      }
      if (isset($parsed_link['fragment'])) {
        $menu_link->options['fragment'] = $parsed_link['fragment'];
      }
      else {
        unset($menu_link->options['fragment']);
      }
      if (isset($parsed_link['path']) && $menu_link->link_path != $parsed_link['path']) {
        $menu_link->link_path = $parsed_link['path'];
      }
    }
    if (!trim($menu_link->link_path) || !drupal_valid_path($menu_link->link_path, TRUE)) {
      $this->setFormError('link_path', $form_state, $this->t("The path '@link_path' is either invalid or you do not have access to it.", array('@link_path' => $menu_link->link_path)));
    }

    parent::validate($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, array &$form_state) {
    // @todo: Remove this when menu links are converted to content entities in
    //   http://drupal.org/node/1842858.
    $entity = clone $this->entity;
    // If you submit a form, the form state comes from caching, which forces
    // the controller to be the one before caching. Ensure to have the
    // controller of the current request.
    $form_state['controller'] = $this;

    // Copy top-level form values to entity properties, without changing
    // existing entity properties that are not being edited by
    // this form.
    foreach ($form_state['values'] as $key => $value) {
      $entity->$key = $value;
    }

    // Invoke all specified builders for copying form values to entity properties.
    if (isset($form['#entity_builders'])) {
      foreach ($form['#entity_builders'] as $function) {
        call_user_func_array($function, array($entity->getEntityTypeId(), $entity, &$form, &$form_state));
      }
    }

    return $entity;
  }

  /**
   * Overrides EntityForm::submit().
   */
  public function submit(array $form, array &$form_state) {
    // Build the menu link object from the submitted values.
    $menu_link = parent::submit($form, $form_state);

    // The value of "hidden" is the opposite of the value supplied by the
    // "enabled" checkbox.
    $menu_link->hidden = (int) !$menu_link->enabled;
    unset($menu_link->enabled);

    $menu_link->options['attributes']['title'] = $menu_link->description;
    list($menu_link->menu_name, $menu_link->plid) = explode(':', $menu_link->parent);

    return $menu_link;
  }

  /**
   * Overrides EntityForm::save().
   */
  public function save(array $form, array &$form_state) {
    $menu_link = $this->entity;

    $saved = $menu_link->save();

    if ($saved) {
      drupal_set_message(t('The menu link has been saved.'));
      $form_state['redirect_route'] = array(
        'route_name' => 'menu_ui.menu_edit',
        'route_parameters' => array(
          'menu' => $menu_link->menu_name,
        ),
      );
    }
    else {
      drupal_set_message(t('There was an error saving the menu link.'), 'error');
      $form_state['rebuild'] = TRUE;
    }
  }

}
