<?php

namespace Drupal\filter;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of filter format entities.
 *
 * @see \Drupal\filter\Entity\FilterFormat
 */
class FilterFormatListBuilder extends DraggableListBuilder {

  /**
   * {@inheritdoc}
   */
  protected $entitiesKey = 'formats';

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The filter format repository service.
   */
  protected FilterFormatRepositoryInterface $formatRepository;

  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, ConfigFactoryInterface $config_factory, MessengerInterface $messenger, ?FilterFormatRepositoryInterface $format_repository = NULL) {
    parent::__construct($entity_type, $storage);

    $this->configFactory = $config_factory;
    $this->messenger = $messenger;
    if (!$format_repository) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $format_repository argument is deprecated in drupal:11.4.0 and the $format_repository argument will be required in drupal:12.0.0. See https://www.drupal.org/node/3035368', E_USER_DEPRECATED);
      $format_repository = \Drupal::service(FilterFormatRepositoryInterface::class);
    }
    $this->formatRepository = $format_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('config.factory'),
      $container->get('messenger'),
      $container->get(FilterFormatRepositoryInterface::class)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'filter_admin_overview';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Name');
    $header['roles'] = $this->t('Roles');
    $header['status'] = $this->t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    // Check whether this is the fallback text format. This format is available
    // to all roles and cannot be disabled via the admin interface.
    $row['label'] = $entity->label();
    $row['roles'] = [];
    if ($entity->isFallbackFormat()) {
      $fallback_choice = $this->configFactory->get('filter.settings')->get('always_show_fallback_choice');
      if ($fallback_choice) {
        $row['roles']['#markup'] = $this->t('All roles may use this format');
      }
      else {
        $row['roles']['#markup'] = $this->t('This format is shown when no other formats are available');
      }
      // Emphasize the fallback role text since it is important to understand
      // how it works which configuring filter formats. Additionally, it is not
      // a list of roles unlike the other values in this column.
      $row['roles']['#prefix'] = '<em>';
      $row['roles']['#suffix'] = '</em>';
    }
    else {
      $row['roles'] = [
        '#theme' => 'item_list',
        '#items' => $entity->getRoles(),
        '#empty' => $this->t('No roles may use this format'),
        '#context' => ['list_style' => 'comma-list'],
      ];
    }
    if ($entity->status()) {
      $status = $this->t('Enabled');
    }
    else {
      $status = $this->t('Disabled');
    }
    $row['status']['#markup'] = $status;
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity/* , ?CacheableMetadata $cacheability = NULL */) {
    $args = func_get_args();
    $cacheability = $args[1] ?? new CacheableMetadata();
    $operations = parent::getDefaultOperations($entity, $cacheability);

    if (isset($operations['edit'])) {
      $operations['edit']['title'] = $this->t('Configure');
    }

    // The fallback format may not be disabled.
    if ($entity->isFallbackFormat()) {
      unset($operations['disable']);
    }

    // Remove disable and edit operations for disabled formats.
    if (!$entity->status()) {
      if (isset($operations['disable'])) {
        unset($operations['disable']);
      }
      if (isset($operations['edit'])) {
        unset($operations['edit']);
      }
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['actions']['submit']['#value'] = $this->t('Save');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->messenger->addStatus($this->t('The text format ordering has been saved.'));
  }

}
