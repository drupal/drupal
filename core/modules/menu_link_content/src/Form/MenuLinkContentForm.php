<?php

/**
 * @file
 * Contains \Drupal\menu_link_content\Form\MenuLinkContentForm.
 */

namespace Drupal\menu_link_content\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Menu\Form\MenuLinkFormInterface;
use Drupal\Core\Menu\MenuLinkInterface;
use Drupal\Core\Menu\MenuParentFormSelectorInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\RequestContext;

/**
 * Provides a form to add/update content menu links.
 *
 * Note: This is not only a content entity form, but also implements the
 * MenuLinkFormInterface, so the same class can be used in places expecting a
 * generic menu link plugin configuration form.
 */
class MenuLinkContentForm extends ContentEntityForm implements MenuLinkFormInterface {

  /**
   * The content menu link.
   *
   * @var \Drupal\menu_link_content\MenuLinkContentInterface
   */
  protected $entity;

  /**
   * The parent form selector service.
   *
   * @var \Drupal\Core\Menu\MenuParentFormSelectorInterface
   */
  protected $menuParentSelector;

  /**
   * The request context.
   *
   * @var \Symfony\Component\Routing\RequestContext
   */
  protected $requestContext;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The access manager.
   *
   * @var \Drupal\Core\Access\AccessManagerInterface
   */
  protected $accessManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * Constructs a MenuLinkContentForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Menu\MenuParentFormSelectorInterface $menu_parent_selector
   *   The menu parent form selector service.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The alias manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler;
   * @param \Symfony\Component\Routing\RequestContext $request_context
   *   The request context.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Access\AccessManagerInterface $access_manager
   *   The access manager.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator.
   */
  public function __construct(EntityManagerInterface $entity_manager, MenuParentFormSelectorInterface $menu_parent_selector, AliasManagerInterface $alias_manager, ModuleHandlerInterface $module_handler, RequestContext $request_context, LanguageManagerInterface $language_manager, AccessManagerInterface $access_manager, AccountInterface $account, PathValidatorInterface $path_validator) {
    parent::__construct($entity_manager, $language_manager);
    $this->menuParentSelector = $menu_parent_selector;
    $this->pathAliasManager = $alias_manager;
    $this->moduleHandler = $module_handler;
    $this->requestContext = $request_context;
    $this->languageManager = $language_manager;
    $this->accessManager = $access_manager;
    $this->account = $account;
    $this->pathValidator = $path_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('menu.parent_form_selector'),
      $container->get('path.alias_manager'),
      $container->get('module_handler'),
      $container->get('router.request_context'),
      $container->get('language_manager'),
      $container->get('access_manager'),
      $container->get('current_user'),
      $container->get('path.validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setMenuLinkInstance(MenuLinkInterface $menu_link) {
    // Load the entity for the entity form. Loading by entity ID is much faster
    // than loading by UUID, so use that ID if we have it.
    $metadata = $menu_link->getMetaData();
    if (!empty($metadata['entity_id'])) {
      $this->entity = $this->entityManager->getStorage('menu_link_content')->load($metadata['entity_id']);
    }
    else {
      // Fallback to the loading by UUID.
      $links = $this->entityManager->getStorage('menu_link_content')->loadByProperties(array('uuid' => $menu_link->getDerivativeId()));
      $this->entity = reset($links);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $this->setOperation('default');
    $this->init($form_state);

    return $this->form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->doValidate($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Remove button and internal Form API values from submitted values.
    parent::submitForm($form, $form_state);
    $this->save($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(array &$form, FormStateInterface $form_state) {
    $new_definition = array();
    $new_definition['expanded'] = !$form_state->isValueEmpty(array('expanded', 'value')) ? 1 : 0;
    $new_definition['enabled'] = !$form_state->isValueEmpty(array('enabled', 'value')) ? 1 : 0;
    list($menu_name, $parent) = explode(':', $form_state->getValue('menu_parent'), 2);
    if (!empty($menu_name)) {
      $new_definition['menu_name'] = $menu_name;
    }
    $new_definition['parent'] = isset($parent) ? $parent : '';

    $new_definition['url'] = NULL;
    $new_definition['route_name'] = NULL;
    $new_definition['route_parameters'] = [];
    $new_definition['options'] = [];

    $extracted = $this->pathValidator->getUrlIfValid($form_state->getValue('url'));

    if ($extracted) {
      if ($extracted->isExternal()) {
        $new_definition['url'] = $extracted->getUri();
      }
      else {
        $new_definition['route_name'] = $extracted->getRouteName();
        $new_definition['route_parameters'] = $extracted->getRouteParameters();
        $new_definition['options'] = $extracted->getOptions();
      }
    }

    $new_definition['title'] = $form_state->getValue(array('title', 0, 'value'));
    $new_definition['description'] = $form_state->getValue(array('description', 0, 'value'));
    $new_definition['weight'] = (int) $form_state->getValue(array('weight', 0, 'value'));

    return $new_definition;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // We always show the internal path here.
    /** @var \Drupal\Core\Url $url */
    $url = $this->getEntity()->getUrlObject();
    if ($url->isExternal()) {
      $default_value = $url->toString();
    }
    elseif ($url->getRouteName() == '<front>') {
      // The default route for new entities is <front>, but we just want an
      // empty form field.
      $default_value = $this->getEntity()->isNew() ? '' : '<front>';
    }
    else {
      // @todo Url::getInternalPath() calls UrlGenerator::getPathFromRoute()
      // which need a replacement since it is deprecated.
      // https://www.drupal.org/node/2307061
      $default_value = $url->getInternalPath();
    }

    // @todo Add a helper method to Url to render just the query string and
    // fragment. https://www.drupal.org/node/2305013
    $options = $url->getOptions();
    if (isset($options['query'])) {
      $default_value .= $options['query'] ? ('?' . UrlHelper::buildQuery($options['query'])) : '';
    }
    if (isset($options['fragment']) && $options['fragment'] !== '') {
      $default_value .= '#' . $options['fragment'];
    }

    $form['url'] = array(
      '#title' => $this->t('Link path'),
      '#type' => 'textfield',
      '#description' => $this->t('The path for this menu link. This can be an internal Drupal path such as %add-node or an external URL such as %drupal. Enter %front to link to the front page.', array('%front' => '<front>', '%add-node' => 'node/add', '%drupal' => 'http://drupal.org')),
      '#default_value' => $default_value,
      '#required' => TRUE,
      '#weight' => -2,
    );

    $language_configuration = $this->moduleHandler->invoke('language', 'get_default_configuration', array('menu_link_content', 'menu_link_content'));
    if ($this->entity->isNew()) {
      $default_language = isset($language_configuration['langcode']) ? $language_configuration['langcode'] : $this->languageManager->getDefaultLanguage()->getId();
    }
    else {
      $default_language = $this->entity->getUntranslated()->language()->getId();
    }
    $form['langcode'] = array(
      '#title' => t('Language'),
      '#type' => 'language_select',
      '#default_value' => $default_language,
      '#languages' => Language::STATE_ALL,
      '#access' => !empty($language_configuration['language_show']),
    );

    $default = $this->entity->getMenuName() . ':' . $this->entity->getParentId();
    $form['menu_parent'] = $this->menuParentSelector->parentSelectElement($default, $this->entity->getPluginId());
    $form['menu_parent']['#weight'] = 10;
    $form['menu_parent']['#title'] = $this->t('Parent link');
    $form['menu_parent']['#description'] = $this->t('The maximum depth for a link and all its children is fixed. Some menu links may not be available as parents if selecting them would exceed this limit.');
    $form['menu_parent']['#attributes']['class'][] = 'menu-title-select';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $element = parent::actions($form, $form_state);
    $element['submit']['#button_type'] = 'primary';
    $element['delete']['#access'] = $this->entity->access('delete');

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, FormStateInterface $form_state) {
    $this->doValidate($form, $form_state);

    parent::validate($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\menu_link_content\MenuLinkContentInterface $entity */
    $entity = parent::buildEntity($form, $form_state);
    $new_definition = $this->extractFormValues($form, $form_state);

    $entity->parent->value = $new_definition['parent'];
    $entity->menu_name->value = $new_definition['menu_name'];
    $entity->enabled->value = (bool) $new_definition['enabled'];
    $entity->expanded->value = $new_definition['expanded'];

    $entity->url->value = $new_definition['url'];
    $entity->route_name->value = $new_definition['route_name'];
    $entity->setRouteParameters($new_definition['route_parameters']);
    $entity->setOptions($new_definition['options']);

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // The entity is rebuilt in parent::submit().
    $menu_link = $this->entity;
    $saved = $menu_link->save();

    if ($saved) {
      drupal_set_message($this->t('The menu link has been saved.'));
      $form_state->setRedirect(
        'entity.menu_link_content.canonical',
        array('menu_link_content' => $menu_link->id())
      );
    }
    else {
      drupal_set_message($this->t('There was an error saving the menu link.'), 'error');
      $form_state->setRebuild();
    }
  }

  /**
   * Validates the form, both on the menu link edit and content menu link form.
   *
   * $form is not currently used, but passed here to match the normal form
   * validation method signature.
   *
   * @param array $form
   *   A nested array form elements comprising the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function doValidate(array $form, FormStateInterface $form_state) {
    $extracted = $this->pathValidator->getUrlIfValid($form_state->getValue('url'));

    if (!$extracted) {
      $form_state->setErrorByName('url', $this->t("The path '@link_path' is either invalid or you do not have access to it.", array('@link_path' => $form_state->getValue('url'))));
    }
  }

}
