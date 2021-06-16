<?php

namespace Drupal\taxonomy;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of taxonomy vocabulary entities.
 *
 * @see \Drupal\taxonomy\Entity\Vocabulary
 */
class VocabularyListBuilder extends DraggableListBuilder {

  /**
   * {@inheritdoc}
   */
  protected $entitiesKey = 'vocabularies';

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new VocabularyListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(EntityTypeInterface $entity_type,
                              AccountInterface $current_user,
                              EntityTypeManagerInterface $entity_type_manager,
                              RendererInterface $renderer = NULL,
                              MessengerInterface $messenger) {
    parent::__construct($entity_type, $entity_type_manager->getStorage($entity_type->id()));

    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('renderer'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'taxonomy_overview_vocabularies';
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    if (isset($operations['edit'])) {
      $operations['edit']['title'] = t('Edit vocabulary');
    }

    if ($entity->access('access taxonomy overview')) {
      $operations['list'] = [
        'title' => t('List terms'),
        'weight' => 0,
        'url' => $entity->toUrl('overview-form'),
      ];
    }

    $taxonomy_term_access_control_handler = $this->entityTypeManager->getAccessControlHandler('taxonomy_term');
    if ($taxonomy_term_access_control_handler->createAccess($entity->id())) {
      $operations['add'] = [
        'title' => t('Add terms'),
        'weight' => 10,
        'url' => Url::fromRoute('entity.taxonomy_term.add_form', ['taxonomy_vocabulary' => $entity->id()]),
      ];
    }

    unset($operations['delete']);

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = t('Vocabulary name');
    $header['description'] = t('Description');

    if ($this->currentUser->hasPermission('administer vocabularies') && !empty($this->weightKey)) {
      $header['weight'] = t('Weight');
    }

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['description']['data'] = ['#markup' => $entity->getDescription()];
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $entities = $this->load();
    // If there are not multiple vocabularies, disable dragging by unsetting the
    // weight key.
    if (count($entities) <= 1) {
      unset($this->weightKey);
    }
    $build = parent::render();

    // If the weight key was unset then the table is in the 'table' key,
    // otherwise in vocabularies. The empty message is only needed if the table
    // is possibly empty, so there is no need to support the vocabularies key
    // here.
    if (isset($build['table'])) {
      $access_control_handler = $this->entityTypeManager->getAccessControlHandler('taxonomy_vocabulary');
      $create_access = $access_control_handler->createAccess(NULL, NULL, [], TRUE);
      $this->renderer->addCacheableDependency($build['table'], $create_access);
      if ($create_access->isAllowed()) {
        $build['table']['#empty'] = t('No vocabularies available. <a href=":link">Add vocabulary</a>.', [
          ':link' => Url::fromRoute('entity.taxonomy_vocabulary.add_form')->toString(),
        ]);
      }
      else {
        $build['table']['#empty'] = t('No vocabularies available.');
      }
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['vocabularies']['#attributes'] = ['id' => 'taxonomy'];
    $form['actions']['submit']['#value'] = t('Save');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->messenger->addStatus($this->t('The configuration options have been saved.'));
  }

}
