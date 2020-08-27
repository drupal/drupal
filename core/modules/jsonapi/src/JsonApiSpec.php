<?php

namespace Drupal\jsonapi;

/**
 * Defines constants used for compliance with the JSON:API specification.
 *
 * @internal JSON:API maintains no PHP API since its API is the HTTP API. This
 *   class may change at any time and this will break any dependencies on it.
 *
 * @see https://www.drupal.org/project/drupal/issues/3032787
 * @see jsonapi.api.php
 *
 * @see http://jsonapi.org/format
 */
class JsonApiSpec {

  /**
   * The minimum supported specification version.
   *
   * @see http://jsonapi.org/format/#document-jsonapi-object
   */
  const SUPPORTED_SPECIFICATION_VERSION = '1.0';

  /**
   * The URI of the supported specification document.
   */
  const SUPPORTED_SPECIFICATION_PERMALINK = 'http://jsonapi.org/format/1.0/';

  /**
   * Member name: globally allowed characters.
   *
   * U+0080 and above (non-ASCII Unicode characters) are allowed, but are not
   * URL-safe. It is RECOMMENDED to not use them.
   *
   * A character class, for use in regular expressions.
   *
   * @see http://jsonapi.org/format/#document-member-names-allowed-characters
   * @see http://php.net/manual/en/regexp.reference.character-classes.php
   */
  const MEMBER_NAME_GLOBALLY_ALLOWED_CHARACTER_CLASS = '[a-zA-Z0-9\x{80}-\x{10FFFF}]';

  /**
   * Member name: allowed characters except as the first or last character.
   *
   * Space (U+0020) is allowed, but is not URL-safe. It is RECOMMENDED to not
   * use it.
   *
   * A character class, for use in regular expressions.
   *
   * @see http://jsonapi.org/format/#document-member-names-allowed-characters
   * @see http://php.net/manual/en/regexp.reference.character-classes.php
   */
  const MEMBER_NAME_INNER_ALLOWED_CHARACTERS = "[a-zA-Z0-9\x{80}-\x{10FFFF}\-_ ]";

  /**
   * Checks whether the given member name is valid.
   *
   * Requirements:
   * - it MUST contain at least one character.
   * - it MUST contain only the allowed characters
   * - it MUST start and end with a "globally allowed character"
   *
   * @param string $member_name
   *   A member name to validate.
   *
   * @return bool
   *   Whether the given member name is in compliance with the JSON:API
   *   specification.
   *
   * @see http://jsonapi.org/format/#document-member-names
   */
  public static function isValidMemberName($member_name) {
    // @todo When D8 requires PHP >=5.6, move to a MEMBER_NAME_REGEXP constant.
    static $regexp;
    // @codingStandardsIgnoreStart
    if (!isset($regexp)) {
      $regexp = '/^' .
        // First character must be "globally allowed". Length must be >=1.
        self::MEMBER_NAME_GLOBALLY_ALLOWED_CHARACTER_CLASS . '{1}' .
        '(' .
          // As many non-globally allowed characters as desired.
          self::MEMBER_NAME_INNER_ALLOWED_CHARACTERS . '*' .
          // If length > 1, then it must end in a "globally allowed" character.
          self::MEMBER_NAME_GLOBALLY_ALLOWED_CHARACTER_CLASS . '{1}' .
        // >1 characters is optional.
        ')?' .
        '$/u';
    }
    // @codingStandardsIgnoreEnd

    return preg_match($regexp, $member_name) === 1;
  }

  /**
   * The reserved (official) query parameters.
   *
   * @todo When D8 requires PHP >= 5.6, convert to an array.
   */
  const RESERVED_QUERY_PARAMETERS = 'filter|sort|page|fields|include';

  /**
   * The query parameter for providing a version (revision) value.
   *
   * @var string
   */
  const VERSION_QUERY_PARAMETER = 'resourceVersion';

  /**
   * Gets the reserved (official) JSON:API query parameters.
   *
   * @return string[]
   *   Gets the query parameters reserved by the specification.
   */
  public static function getReservedQueryParameters() {
    return explode('|', static::RESERVED_QUERY_PARAMETERS);
  }

  /**
   * Checks whether the given custom query parameter name is valid.
   *
   * A custom query parameter name must be a valid member name, with one
   * additional requirement: it MUST contain at least one non a-z character.
   *
   * Requirements:
   * - it MUST contain at least one character.
   * - it MUST contain only the allowed characters
   * - it MUST start and end with a "globally allowed character"
   * - it MUST contain at least none a-z (U+0061 to U+007A) character
   *
   * It is RECOMMENDED that a hyphen (U+002D), underscore (U+005F) or capital
   * letter is used (i.e. camelCasing).
   *
   * @param string $custom_query_parameter_name
   *   A custom query parameter name to validate.
   *
   * @return bool
   *   Whether the given query parameter is in compliane with the JSON:API
   *   specification.
   *
   * @see http://jsonapi.org/format/#query-parameters
   */
  public static function isValidCustomQueryParameter($custom_query_parameter_name) {
    return static::isValidMemberName($custom_query_parameter_name) && preg_match('/[^a-z]/u', $custom_query_parameter_name) === 1;
  }

}
