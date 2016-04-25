<?php

namespace Drupal\field_ui\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the generic base class for entity display mode forms.
 */
abstract class EntityDisplayModeFormBase extends EntityForm {

  /**
   * The entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * The entity type definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * The entity manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new EntityDisplayModeFormBase.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   The entity query factory.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(QueryFactory $query_factory, EntityManagerInterface $entity_manager) {
    $this->queryFactory = $query_factory;
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query'),
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function init(FormStateInterface $form_state) {
    parent::init($form_state);
    $this->entityType = $this->entityManager->getDefinition($this->entity->getEntityTypeId());
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#maxlength' => 100,
      '#default_value' => $this->entity->label(),
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#description' => $this->t('A unique machine-readable name. Can only contain lowercase letters, numbers, and underscores.'),
      '#disabled' => !$this->entity->isNew(),
      '#default_value' => $this->entity->id(),
      '#field_prefix' => $this->entity->isNew() ? $this->entity->getTargetType() . '.' : '',
      '#machine_name' => array(
        'exists' => array($this, 'exists'),
        'replace_pattern' => '[^a-z0-9_.]+',
      ),
    );

    return $form;
  }

  /**
   * Determines if the display mode already exists.
   *
   * @param string|int $entity_id
   *   The entity ID.
   * @param array $element
   *   The form element.
   *
   * @return bool
   *   TRUE if the display mode exists, FALSE otherwise.
   */
  public function exists($entity_id, array $element) {
    // Do not allow to add internal 'default' view mode.
    if ($entity_id == 'default') {
      return TRUE;
    }
    return (bool) $this->queryFactory
      ->get($this->entity->getEntityTypeId())
      ->condition('id', $element['#field_prefix'] . $entity_id)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    drupal_set_message($this->t('Saved the %label @entity-type.', array('%label' => $this->entity->label(), '@entity-type' => $this->entityType->getLowercaseLabel())));
    $this->entity->save();
    \Drupal::entityManager()->clearCachedFieldDefinitions();
    $form_state->setRedirectUrl($this->entity->urlInfo('collection'));
  }

}
