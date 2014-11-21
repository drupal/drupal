<?php

/**
 * @file
 * Contains \Drupal\block\BlockAccessControlHandler.
 */

namespace Drupal\block;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Condition\ConditionAccessResolverTrait;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Executable\ExecutableManagerInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the access control handler for the block entity type.
 *
 * @see \Drupal\block\Entity\Block
 */
class BlockAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  use ConditionAccessResolverTrait;

  /**
   * The condition plugin manager.
   *
   * @var \Drupal\Core\Executable\ExecutableManagerInterface
   */
  protected $manager;

  /**
   * The plugin context handler.
   *
   * @var \Drupal\Core\Plugin\Context\ContextHandlerInterface
   */
  protected $contextHandler;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('plugin.manager.condition'),
      $container->get('context.handler')
    );
  }

  /**
   * Constructs the block access control handler instance
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Executable\ExecutableManagerInterface $manager
   *   The ConditionManager for checking visibility of blocks.
   * @param \Drupal\Core\Plugin\Context\ContextHandlerInterface $context_handler
   *   The ContextHandler for applying contexts to conditions properly.
   */
  public function __construct(EntityTypeInterface $entity_type, ExecutableManagerInterface $manager, ContextHandlerInterface $context_handler) {
    parent::__construct($entity_type);
    $this->manager = $manager;
    $this->contextHandler = $context_handler;
  }


  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    /** @var \Drupal\block\BlockInterface $entity */
    if ($operation != 'view') {
      return parent::checkAccess($entity, $operation, $langcode, $account);
    }

    // Don't grant access to disabled blocks.
    if (!$entity->status()) {
      return AccessResult::forbidden()->cacheUntilEntityChanges($entity);
    }
    else {
      $contexts = $entity->getContexts();
      $conditions = [];
      foreach ($entity->getVisibilityConditions() as $condition_id => $condition) {
        if ($condition instanceof ContextAwarePluginInterface) {
          try {
            $this->contextHandler->applyContextMapping($condition, $contexts);
          }
          catch (ContextException $e) {
            return AccessResult::forbidden()->setCacheable(FALSE);
          }
        }
        $conditions[$condition_id] = $condition;
      }
      if ($this->resolveConditions($conditions, 'and') !== FALSE) {
        // Delegate to the plugin.
        $access = $entity->getPlugin()->access($account);
      }
      else {
        $access = AccessResult::forbidden();
      }
      // This should not be hardcoded to an uncacheable access check result, but
      // in order to fix that, we need condition plugins to return cache contexts,
      // otherwise it will be impossible to determine by which cache contexts the
      // result should be varied.
      // @todo Change this to use $access->cacheUntilEntityChanges($entity) once
      //   https://www.drupal.org/node/2375695 is resolved.
      return $access->setCacheable(FALSE);
    }
  }

}
