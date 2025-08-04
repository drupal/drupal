<?php

namespace Drupal\node\Cache;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CalculatedCacheContextInterface;
use Drupal\Core\Cache\Context\UserCacheContextBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the node access view cache context service.
 *
 * Cache context ID: 'user.node_grants' (to vary by all operations' grants).
 * Calculated cache context ID: 'user.node_grants:%operation', e.g.
 * 'user.node_grants:view' (to vary by the view operation's grants).
 *
 * This allows for node access grants-sensitive caching when listing nodes.
 *
 * @see \Drupal\node\Hook\NodeHooks1::queryNodeAccessAlter()
 * @ingroup node_access
 */
class NodeAccessGrantsCacheContext extends UserCacheContextBase implements CalculatedCacheContextInterface {

  public function __construct(
    AccountInterface $user,
    protected ?EntityTypeManagerInterface $entityTypeManager = NULL,
  ) {
    parent::__construct($user);
    if (!$entityTypeManager) {
      @trigger_error('Calling NodeAccessGrantsCacheContext::__construct() without the $entityTypeManager argument is deprecated in drupal:11.3.0 and the $entityTypeManager argument will be required in drupal:12.0.0. See https://www.drupal.org/node/3038909', E_USER_DEPRECATED);
      $this->entityTypeManager = \Drupal::entityTypeManager();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t("Content access view grants");
  }

  /**
   * {@inheritdoc}
   */
  public function getContext($operation = NULL) {
    // If the current user either can bypass node access then we don't need to
    // determine the exact node grants for the current user.
    if ($this->user->hasPermission('bypass node access')) {
      return 'all';
    }

    // When no specific operation is specified, check the grants for all three
    // possible operations.
    if ($operation === NULL) {
      $result = [];
      foreach (['view', 'update', 'delete'] as $op) {
        $result[] = $this->checkNodeGrants($op);
      }
      return implode('-', $result);
    }
    else {
      return $this->checkNodeGrants($operation);
    }
  }

  /**
   * Checks the node grants for the given operation.
   *
   * @param string $operation
   *   The operation to check the node grants for.
   *
   * @return string
   *   The string representation of the cache context.
   */
  protected function checkNodeGrants($operation) {
    // When checking the grants for the 'view' operation and the current user
    // has a global view grant (i.e. a view grant for node ID 0) — note that
    // this is automatically the case if no node access modules exist (no
    // hook_node_grants() implementations) then we don't need to determine the
    // exact node view grants for the current user.
    if ($operation === 'view' && $this->entityTypeManager->getAccessControlHandler('node')->checkAllGrants($this->user)) {
      return 'view.all';
    }

    $grants = node_access_grants($operation, $this->user);
    $grants_context_parts = [];
    foreach ($grants as $realm => $gids) {
      $grants_context_parts[] = $realm . ':' . implode(',', $gids);
    }
    return $operation . '.' . implode(';', $grants_context_parts);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($operation = NULL) {
    $cacheable_metadata = new CacheableMetadata();

    if (!\Drupal::moduleHandler()->hasImplementations('node_grants')) {
      return $cacheable_metadata;
    }

    // The node grants may change if the user is updated. (The max-age is set to
    // zero below, but sites may override this cache context, and change it to a
    // non-zero value. In such cases, this cache tag is needed for correctness.)
    $cacheable_metadata->setCacheTags(['user:' . $this->user->id()]);

    // If the site is using node grants, this cache context can not be
    // optimized.
    return $cacheable_metadata->setCacheMaxAge(0);
  }

}
