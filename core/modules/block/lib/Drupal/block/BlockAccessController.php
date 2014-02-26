<?php

/**
 * @file
 * Contains \Drupal\block\BlockAccessController.
 */

namespace Drupal\block;

use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityControllerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Component\Utility\Unicode;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Block access controller.
 */
class BlockAccessController extends EntityAccessController implements EntityControllerInterface {

  /**
   * The node grant storage.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * Constructs a BlockAccessController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The alias manager.
   */
  public function __construct(EntityTypeInterface $entity_type, AliasManagerInterface $alias_manager) {
    parent::__construct($entity_type);
    $this->aliasManager = $alias_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('path.alias_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    if ($operation != 'view') {
      return parent::checkAccess($entity, $operation, $langcode, $account);
    }

    // Deny access to disabled blocks.
    if (!$entity->status()) {
      return FALSE;
    }

    // If the plugin denies access, then deny access.
    if (!$entity->getPlugin()->access($account)) {
      return FALSE;
    }

    // Otherwise, check for other access restrictions.

    // User role access handling.
    // If a block has no roles associated, it is displayed for every role.
    // For blocks with roles associated, if none of the user's roles matches
    // the settings from this block, access is denied.
    $visibility = $entity->get('visibility');
    if (!empty($visibility['role']['roles']) && !array_intersect(array_filter($visibility['role']['roles']), $account->getRoles())) {
      // No match.
      return FALSE;
    }

    // Page path handling.
    // Limited visibility blocks must list at least one page.
    if (!empty($visibility['path']['visibility']) && $visibility['path']['visibility'] == BLOCK_VISIBILITY_LISTED && empty($visibility['path']['pages'])) {
      return FALSE;
    }

    // Match path if necessary.
    if (!empty($visibility['path']['pages'])) {
      // Assume there are no matches until one is found.
      $page_match = FALSE;

      // Convert path to lowercase. This allows comparison of the same path
      // with different case. Ex: /Page, /page, /PAGE.
      $pages = drupal_strtolower($visibility['path']['pages']);
      if ($visibility['path']['visibility'] < BLOCK_VISIBILITY_PHP) {
        // Compare the lowercase path alias (if any) and internal path.
        $path = current_path();
        $path_alias = Unicode::strtolower($this->aliasManager->getPathAlias($path));
        $page_match = drupal_match_path($path_alias, $pages) || (($path != $path_alias) && drupal_match_path($path, $pages));
        // When $block->visibility has a value of 0
        // (BLOCK_VISIBILITY_NOTLISTED), the block is displayed on all pages
        // except those listed in $block->pages. When set to 1
        // (BLOCK_VISIBILITY_LISTED), it is displayed only on those pages
        // listed in $block->pages.
        $page_match = !($visibility['path']['visibility'] xor $page_match);
      }

      // If there are page visibility restrictions and this page does not
      // match, deny access.
      if (!$page_match) {
        return FALSE;
      }
    }

    // Language visibility settings.
    if (!empty($visibility['language']['langcodes']) && array_filter($visibility['language']['langcodes'])) {
      if (empty($visibility['language']['langcodes'][\Drupal::languageManager()->getCurrentLanguage($visibility['language']['language_type'])->id])) {
        return FALSE;
      }
    }
    return TRUE;
  }

}
