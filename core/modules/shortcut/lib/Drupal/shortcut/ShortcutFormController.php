<?php

/**
 * @file
 * Contains \Drupal\shortcut\ShortcutFormController.
 */

namespace Drupal\shortcut;

use Drupal\Core\Entity\ContentEntityFormController;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the shortcut entity forms.
 */
class ShortcutFormController extends ContentEntityFormController {

  /**
   * The path alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The URL generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs a new action form.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The path alias manager.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The URL generator.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   */
  public function __construct(EntityManagerInterface $entity_manager, AliasManagerInterface $alias_manager, UrlGeneratorInterface $url_generator, FormBuilderInterface $form_builder) {
    $this->entityManager = $entity_manager;
    $this->aliasManager = $alias_manager;
    $this->urlGenerator = $url_generator;
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('path.alias_manager'),
      $container->get('url_generator'),
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, array &$form_state) {
    $form = parent::form($form, $form_state);
    $entity = $this->entity;

    $form['title'] = array(
      '#type' => 'textfield',
      '#title' => t('Name'),
      '#default_value' => $entity->title->value,
      '#size' => 40,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#weight' => -10,
    );

    $form['path'] = array(
      '#type' => 'textfield',
      '#title' => t('Path'),
      '#size' => 40,
      '#maxlength' => 255,
      '#field_prefix' => $this->urlGenerator->generateFromRoute('<front>', array(), array('absolute' => TRUE)),
      '#default_value' => $entity->path->value,
    );

    $form['langcode'] = array(
      '#title' => t('Language'),
      '#type' => 'language_select',
      '#default_value' => $entity->getUntranslated()->language()->id,
      '#languages' => Language::STATE_ALL,
    );

    $form['shortcut_set'] = array(
      '#type' => 'value',
      '#value' => $entity->bundle(),
    );
    $form['route_name'] = array(
      '#type' => 'value',
      '#value' => $entity->getRouteName(),
    );
    $form['route_parameters'] = array(
      '#type' => 'value',
      '#value' => $entity->getRouteParams(),
    );

    return $form;
  }

  /**
   * Overrides EntityFormController::buildEntity().
   */
  public function buildEntity(array $form, array &$form_state) {
    $entity = parent::buildEntity($form, $form_state);

    // Set the computed 'path' value so it can used in the preSave() method to
    // derive the route name and parameters.
    $entity->path->value = $form_state['values']['path'];

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, array &$form_state) {
    if (!shortcut_valid_link($form_state['values']['path'])) {
      $this->formBuilder->setErrorByName('path', $form_state, $this->t('The shortcut must correspond to a valid path on the site.'));
    }

    parent::validate($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, array &$form_state) {
    $entity = $this->entity;
    $entity->save();

    if ($entity->isNew()) {
      $message = $this->t('The shortcut %link has been updated.', array('%link' => $entity->title->value));
    }
    else {
      $message = $this->t('Added a shortcut for %title.', array('%title' => $entity->title->value));
    }
    drupal_set_message($message);

    $form_state['redirect_route'] = array(
      'route_name' => 'shortcut.set_customize',
      'route_parameters' => array('shortcut_set' => $entity->bundle()),
    );
  }

}
