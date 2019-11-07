<?php

namespace Drupal\path_alias;

use Drupal\Core\Path\AliasRepositoryInterface as CoreAliasRepositoryInterface;

/**
 * Provides an interface for path alias lookup operations.
 *
 * The path alias repository service is only used internally in order to
 * optimize alias lookup queries needed in the critical path of each request.
 * However, it is not marked as an internal service because alternative storage
 * backends still need to override it if they provide a different storage class
 * for the PathAlias entity type.
 *
 * Whenever you need to determine whether an alias exists for a system path, or
 * whether a system path has an alias, the 'path_alias.manager' service should
 * be used instead.
 */
interface AliasRepositoryInterface extends CoreAliasRepositoryInterface {}
