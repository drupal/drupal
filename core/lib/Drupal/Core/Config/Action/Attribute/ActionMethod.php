<?php

declare(strict_types=1);

namespace Drupal\Core\Config\Action\Attribute;

// cspell:ignore inflector
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Config\Action\Exists;
use Drupal\Core\Extension\ExtensionDiscovery;
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
   * @param string|null $name
   *   The name of the action, if it should differ from the method name. Will be
   *   pluralized if $pluralize is TRUE. Must follow the rules for a valid PHP
   *   function name (e.g., no spaces, no Unicode characters, etc.). If used,
   *   the actual name of the method will NOT be available as an action name.
   *
   * @see https://www.php.net/manual/en/functions.user-defined.php
   */
  public function __construct(
    public readonly Exists $exists = Exists::ErrorIfNotExists,
    public readonly TranslatableMarkup|string $adminLabel = '',
    public readonly bool|string $pluralize = TRUE,
    public readonly ?string $name = NULL,
  ) {
    if ($name && !preg_match(ExtensionDiscovery::PHP_FUNCTION_PATTERN, $name)) {
      throw new InvalidPluginDefinitionException('entity_method', sprintf("'%s' is not a valid PHP function name.", $name));
    }
  }

}
