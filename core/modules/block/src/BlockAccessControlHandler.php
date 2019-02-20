<?php

namespace Drupal\block;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Component\Plugin\Exception\MissingValueContextException;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Condition\ConditionAccessResolverTrait;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
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
   * The plugin context handler.
   *
   * @var \Drupal\Core\Plugin\Context\ContextHandlerInterface
   */
  protected $contextHandler;

  /**
   * The context manager service.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('context.handler'),
      $container->get('context.repository')
    );
  }

  /**
   * Constructs the block access control handler instance
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Plugin\Context\ContextHandlerInterface $context_handler
   *   The ContextHandler for applying contexts to conditions properly.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $context_repository
   *   The lazy context repository service.
   */
  public function __construct(EntityTypeInterface $entity_type, ContextHandlerInterface $context_handler, ContextRepositoryInterface $context_repository) {
    parent::__construct($entity_type);
    $this->contextHandler = $context_handler;
    $this->contextRepository = $context_repository;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\block\BlockInterface $entity */
    if ($operation != 'view') {
      return parent::checkAccess($entity, $operation, $account);
    }

    // Don't grant access to disabled blocks.
    if (!$entity->status()) {
      return AccessResult::forbidden()->addCacheableDependency($entity);
    }
    else {
      $conditions = [];
      $missing_context = FALSE;
      $missing_value = FALSE;
      foreach ($entity->getVisibilityConditions() as $condition_id => $condition) {
        if ($condition instanceof ContextAwarePluginInterface) {
          try {
            $contexts = $this->contextRepository->getRuntimeContexts(array_values($condition->getContextMapping()));
            $this->contextHandler->applyContextMapping($condition, $contexts);
          }
          catch (MissingValueContextException $e) {
            $missing_value = TRUE;
          }
          catch (ContextException $e) {
            $missing_context = TRUE;
          }
        }
        $conditions[$condition_id] = $condition;
      }

      if ($missing_context) {
        // If any context is missing then we might be missing cacheable
        // metadata, and don't know based on what conditions the block is
        // accessible or not. Make sure the result cannot be cached.
        $access = AccessResult::forbidden()->setCacheMaxAge(0);
      }
      elseif ($missing_value) {
        // The contexts exist but have no value. Deny access without
        // disabling caching. For example the node type condition will have a
        // missing context on any non-node route like the frontpage.
        $access = AccessResult::forbidden();
      }
      elseif ($this->resolveConditions($conditions, 'and') !== FALSE) {
        // Delegate to the plugin.
        $block_plugin = $entity->getPlugin();
        try {
          if ($block_plugin instanceof ContextAwarePluginInterface) {
            $contexts = $this->contextRepository->getRuntimeContexts(array_values($block_plugin->getContextMapping()));
            $this->contextHandler->applyContextMapping($block_plugin, $contexts);
          }
          $access = $block_plugin->access($account, TRUE);
        }
        catch (MissingValueContextException $e) {
          // The contexts exist but have no value. Deny access without
          // disabling caching.
          $access = AccessResult::forbidden();
        }
        catch (ContextException $e) {
          // If any context is missing then we might be missing cacheable
          // metadata, and don't know based on what conditions the block is
          // accessible or not. Make sure the result cannot be cached.
          $access = AccessResult::forbidden()->setCacheMaxAge(0);
        }
      }
      else {
        $reason = count($conditions) > 1
          ? "One of the block visibility conditions ('%s') denied access."
          : "The block visibility condition '%s' denied access.";
        $access = AccessResult::forbidden(sprintf($reason, implode("', '", array_keys($conditions))));
      }

      $this->mergeCacheabilityFromConditions($access, $conditions);

      // Ensure that access is evaluated again when the block changes.
      return $access->addCacheableDependency($entity);
    }
  }

  /**
   * Merges cacheable metadata from conditions onto the access result object.
   *
   * @param \Drupal\Core\Access\AccessResult $access
   *   The access result object.
   * @param \Drupal\Core\Condition\ConditionInterface[] $conditions
   *   List of visibility conditions.
   */
  protected function mergeCacheabilityFromConditions(AccessResult $access, array $conditions) {
    foreach ($conditions as $condition) {
      if ($condition instanceof CacheableDependencyInterface) {
        $access->addCacheTags($condition->getCacheTags());
        $access->addCacheContexts($condition->getCacheContexts());
        $access->setCacheMaxAge(Cache::mergeMaxAges($access->getCacheMaxAge(), $condition->getCacheMaxAge()));
      }
    }
  }

}
