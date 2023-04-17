<?php

namespace Drupal\block_content;

use Drupal\block_content\Access\DependentAccessInterface;
use Drupal\block_content\Event\BlockContentGetDependencyEvent;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Defines the access control handler for the content block entity type.
 *
 * @see \Drupal\block_content\Entity\BlockContent
 */
class BlockContentAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * BlockContentAccessControlHandler constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $dispatcher
   *   The event dispatcher.
   */
  public function __construct(EntityTypeInterface $entity_type, EventDispatcherInterface $dispatcher) {
    parent::__construct($entity_type);
    $this->eventDispatcher = $dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('event_dispatcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    assert($entity instanceof BlockContentInterface);
    $bundle = $entity->bundle();
    $forbidIfNotDefaultAndLatest = fn (): AccessResultInterface => AccessResult::forbiddenIf($entity->isDefaultRevision() && $entity->isLatestRevision());
    $forbidIfNotReusable = fn (): AccessResultInterface => AccessResult::forbiddenIf($entity->isReusable() === FALSE, sprintf('Block content must be reusable to use `%s` operation', $operation));
    $access = match ($operation) {
      // Allow view and update access to user with the 'edit any (type) block
      // content' permission or the 'administer blocks' permission.
      'view' => AccessResult::allowedIf($entity->isPublished())
        ->orIf(AccessResult::allowedIfHasPermissions($account, [
          'access block library',
        ]))->orIf(AccessResult::allowedIfHasPermissions($account, [
          'administer block content',
        ])),
      'update' => AccessResult::allowedIfHasPermissions($account, [
        'access block library',
        'edit any ' . $bundle . ' block content',
      ])->orIf(AccessResult::allowedIfHasPermissions($account, [
        'administer block content',
      ])),
      'delete' => AccessResult::allowedIfHasPermissions($account, [
        'access block library',
        'delete any ' . $bundle . ' block content',
      ])->orIf(AccessResult::allowedIfHasPermissions($account, [
        'administer block content',
      ])),
      // Revisions.
      'view all revisions' => AccessResult::allowedIfHasPermissions($account, [
        'access block library',
        'view any ' . $bundle . ' block content history',
      ])->orIf(AccessResult::allowedIfHasPermissions($account, [
        'administer block content',
      ])),
      'revert' => AccessResult::allowedIfHasPermissions($account, [
        'access block library',
        'revert any ' . $bundle . ' block content revisions',
      ])->orIf($forbidIfNotDefaultAndLatest())->orIf($forbidIfNotReusable()),
      'delete revision' => AccessResult::allowedIfHasPermissions($account, [
        'access block library',
        'delete any ' . $bundle . ' block content revisions',
      ])
        ->orIf($forbidIfNotDefaultAndLatest())
        ->orIf($forbidIfNotReusable())
        ->orIf(AccessResult::allowedIfHasPermissions($account, [
          'administer block content',
        ])),

      default => parent::checkAccess($entity, $operation, $account),
    };

    // Add the entity as a cacheable dependency because access will at least be
    // determined by whether the block is reusable.
    $access->addCacheableDependency($entity);
    if ($entity->isReusable() === FALSE && $access->isForbidden() !== TRUE) {
      if (!$entity instanceof DependentAccessInterface) {
        throw new \LogicException("Non-reusable block entities must implement \Drupal\block_content\Access\DependentAccessInterface for access control.");
      }
      $dependency = $entity->getAccessDependency();
      if (empty($dependency)) {
        // If an access dependency has not been set let modules set one.
        $event = new BlockContentGetDependencyEvent($entity);
        $this->eventDispatcher->dispatch($event, BlockContentEvents::BLOCK_CONTENT_GET_DEPENDENCY);
        $dependency = $event->getAccessDependency();
        if (empty($dependency)) {
          return AccessResult::forbidden("Non-reusable blocks must set an access dependency for access control.");
        }
      }
      /** @var \Drupal\Core\Entity\EntityInterface $dependency */
      $access = $access->andIf($dependency->access($operation, $account, TRUE));
    }
    return $access;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions($account, [
      'create ' . $entity_bundle . ' block content',
      'access block library',
    ])->orIf(AccessResult::allowedIfHasPermissions($account, [
      'administer block content',
    ]));
  }

}
