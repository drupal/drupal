<?php

declare(strict_types=1);

namespace Drupal\Core\Config\Action\Attribute;

// cspell:ignore inflector
use Drupal\Core\Config\Action\Exists;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * @internal
 *   This API is experimental.
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
final class ActionMethod {

  /**
   * @param \Drupal\Core\Config\Action\Exists $exists
   *   Determines behavior of action depending on entity existence.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|string $adminLabel
   *   The admin label for the user interface.
   * @param bool|string $pluralize
   *   Determines whether to create a pluralized version of the method to enable
   *   the action to be called multiple times before saving the entity. The
   *   default behavior is to create an action with a plural form as determined
   *   by \Symfony\Component\String\Inflector\EnglishInflector::pluralize().
   *   For example, 'grantPermission' has a pluralized version of
   *   'grantPermissions'. If a string is provided this will be the full action
   *   ID. For example, if the method is called 'addArray' this can be set to
   *   'addMultipleArrays'. Set to FALSE if a pluralized version does not make
   *   logical sense.
   */
  public function __construct(
    public readonly Exists $exists = Exists::ErrorIfNotExists,
    public readonly TranslatableMarkup|string $adminLabel = '',
    public readonly bool|string $pluralize = TRUE,
  ) {
  }

}
