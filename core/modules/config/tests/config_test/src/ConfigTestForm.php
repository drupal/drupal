<?php

namespace Drupal\config_test;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the test config edit forms.
 */
class ConfigTestForm extends EntityForm {

  /**
   * The entity query.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * Constructs a new ConfigTestForm.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The entity query.
   */
  public function __construct(QueryFactory $entity_query) {
    $this->entityQuery = $entity_query;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $entity = $this->entity;
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => 'Label',
      '#default_value' => $entity->label(),
      '#required' => TRUE,
    );
    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#required' => TRUE,
      '#machine_name' => array(
        'exists' => [$this, 'exists'],
        'replace_pattern' => '[^a-z0-9_.]+',
      ),
    );
    $form['weight'] = array(
      '#type' => 'weight',
      '#title' => 'Weight',
      '#default_value' => $entity->get('weight'),
    );
    $form['style'] = array(
      '#type' => 'select',
      '#title' => 'Image style',
      '#options' => array(),
      '#default_value' => $entity->get('style'),
      '#access' => FALSE,
    );
    if ($this->moduleHandler->moduleExists('image')) {
      $form['style']['#access'] = TRUE;
      $form['style']['#options'] = image_style_options();
    }

    // The main premise of entity forms is that we get to work with an entity
    // object at all times instead of checking submitted values from the form
    // state.
    $size = $entity->get('size');

    $form['size_wrapper'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'id' => 'size-wrapper',
      ),
    );
    $form['size_wrapper']['size'] = array(
      '#type' => 'select',
      '#title' => 'Size',
      '#options' => array(
        'custom' => 'Custom',
      ),
      '#empty_option' => '- None -',
      '#default_value' => $size,
      '#ajax' => array(
        'callback' => '::updateSize',
        'wrapper' => 'size-wrapper',
      ),
    );
    $form['size_wrapper']['size_submit'] = array(
      '#type' => 'submit',
      '#value' => t('Change size'),
      '#attributes' => array(
        'class' => array('js-hide'),
      ),
      '#submit' => array(array(get_class($this), 'changeSize')),
    );
    $form['size_wrapper']['size_value'] = array(
      '#type' => 'select',
      '#title' => 'Custom size value',
      '#options' => array(
        'small' => 'Small',
        'medium' => 'Medium',
        'large' => 'Large',
      ),
      '#default_value' => $entity->get('size_value'),
      '#access' => !empty($size),
    );

    $form['langcode'] = array(
      '#type' => 'language_select',
      '#title' => t('Language'),
      '#languages' => LanguageInterface::STATE_ALL,
      '#default_value' => $entity->language()->getId(),
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Save',
    );
    $form['actions']['delete'] = array(
      '#type' => 'submit',
      '#value' => 'Delete',
    );

    return $form;
  }

  /**
   * Ajax callback for the size selection element.
   */
  public static function updateSize(array $form, FormStateInterface $form_state) {
    return $form['size_wrapper'];
  }

  /**
   * Element submit handler for non-JS testing.
   */
  public static function changeSize(array $form, FormStateInterface $form_state) {
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = $entity->save();

    if ($status === SAVED_UPDATED) {
      drupal_set_message(format_string('%label configuration has been updated.', array('%label' => $entity->label())));
    }
    else {
      drupal_set_message(format_string('%label configuration has been created.', array('%label' => $entity->label())));
    }

    $form_state->setRedirectUrl($this->entity->urlInfo('collection'));
  }

  /**
   * Determines if the entity already exists.
   *
   * @param string|int $entity_id
   *   The entity ID.
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return bool
   *   TRUE if the entity exists, FALSE otherwise.
   */
  public function exists($entity_id, array $element, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $entity */
    $entity = $form_state->getFormObject()->getEntity();
    return (bool) $this->entityQuery->get($entity->getEntityTypeId())
      ->condition($entity->getEntityType()->getKey('id'), $entity_id)
      ->execute();
  }

}
