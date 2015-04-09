<?php

/**
 * @file
 * Contains \Drupal\user\UserListBuilder.
 */

namespace Drupal\user;

use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of user entities.
 *
 * @see \Drupal\user\Entity\User
 */
class UserListBuilder extends EntityListBuilder {

  /**
   * The entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The redirect destination service.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * Constructs a new UserListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   The entity query factory.
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, QueryFactory $query_factory, DateFormatter $date_formatter,  RedirectDestinationInterface $redirect_destination) {
    parent::__construct($entity_type, $storage);
    $this->queryFactory = $query_factory;
    $this->dateFormatter = $date_formatter;
    $this->redirectDestination = $redirect_destination;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('entity.query'),
      $container->get('date.formatter'),
      $container->get('redirect.destination')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $entity_query = $this->queryFactory->get('user');
    $entity_query->condition('uid', 0, '<>');
    $entity_query->pager(50);
    $header = $this->buildHeader();
    $entity_query->tableSort($header);
    $uids = $entity_query->execute();
    return $this->storage->loadMultiple($uids);
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = array(
      'username' => array(
        'data' => $this->t('Username'),
        'field' => 'name',
        'specifier' => 'name',
      ),
      'status' => array(
        'data' => $this->t('Status'),
        'field' => 'status',
        'specifier' => 'status',
        'class' => array(RESPONSIVE_PRIORITY_LOW),
      ),
      'roles' => array(
        'data' => $this->t('Roles'),
        'class' => array(RESPONSIVE_PRIORITY_LOW),
      ),
      'member_for' => array(
        'data' => $this->t('Member for'),
        'field' => 'created',
        'specifier' => 'created',
        'sort' => 'desc',
        'class' => array(RESPONSIVE_PRIORITY_LOW),
      ),
      'access' => array(
        'data' => $this->t('Last access'),
        'field' => 'access',
        'specifier' => 'access',
        'class' => array(RESPONSIVE_PRIORITY_LOW),
      ),
    );
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['username']['data'] = array(
      '#theme' => 'username',
      '#account' => $entity,
    );
    $row['status'] = $entity->isActive() ? $this->t('active') : $this->t('blocked');

    $roles = array_map('\Drupal\Component\Utility\SafeMarkup::checkPlain', user_role_names(TRUE));
    unset($roles[RoleInterface::AUTHENTICATED_ID]);
    $users_roles = array();
    foreach ($entity->getRoles() as $role) {
      if (isset($roles[$role])) {
        $users_roles[] = $roles[$role];
      }
    }
    asort($users_roles);
    $row['roles']['data'] = array(
      '#theme' => 'item_list',
      '#items' => $users_roles,
    );
    $row['member_for'] = $this->dateFormatter->formatInterval(REQUEST_TIME - $entity->getCreatedTime());
    $row['access'] = $entity->access ? $this->t('@time ago', array(
      '@time' => $this->dateFormatter->formatInterval(REQUEST_TIME - $entity->getLastAccessedTime()),
    )) : t('never');
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);
    if (isset($operations['edit'])) {
      $destination = $this->redirectDestination->getAsArray();
      $operations['edit']['query'] = $destination;
    }
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['accounts'] = parent::render();
    $build['accounts']['#empty'] = $this->t('No people available.');
    $build['pager']['#type'] = 'pager';
    return $build;
  }

}
