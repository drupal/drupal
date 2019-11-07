<?php

namespace Drupal\path_alias;

use Drupal\Core\Path\AliasRepository as CoreAliasRepository;

/**
 * Provides the default path alias lookup operations.
 */
class AliasRepository extends CoreAliasRepository implements AliasRepositoryInterface {}
