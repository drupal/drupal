<?php

namespace Drupal\menu_link_content\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Menu\MenuParentFormSelectorInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to add/update content menu links.
 *
 * @internal
 */
class MenuLinkContentForm extends ContentEntityForm {

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
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(EntityManagerInterface $entity_manager, MenuParentFormSelectorInterface $menu_parent_selector, LanguageManagerInterface $language_manager, PathValidatorInterface $path_validator, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL) {
    parent::__construct($entity_manager, $entity_type_bundle_info, $time);
    $this->menuParentSelector = $menu_parent_selector;
    $this->pathValidator = $path_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('menu.parent_form_selector'),
      $container->get('language_manager'),
      $container->get('path.validator'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $default = $this->entity->getMenuName() . ':' . $this->entity->getParentId();
    $id = $this->entity->isNew() ? '' : $this->entity->getPluginId();
    $form['menu_parent'] = $this->menuParentSelector->parentSelectElement($default, $id);
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
  public function buildEntity(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\menu_link_content\MenuLinkContentInterface $entity */
    $entity = parent::buildEntity($form, $form_state);

    list($menu_name, $parent) = explode(':', $form_state->getValue('menu_parent'), 2);

    $entity->parent->value = $parent;
    $entity->menu_name->value = $menu_name;
    $entity->enabled->value = (!$form_state->isValueEmpty(['enabled', 'value']));
    $entity->expanded->value = (!$form_state->isValueEmpty(['expanded', 'value']));

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
        ['menu_link_content' => $menu_link->id()]
      );
    }
    else {
      drupal_set_message($this->t('There was an error saving the menu link.'), 'error');
      $form_state->setRebuild();
    }
  }

}
