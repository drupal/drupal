<?php

namespace Drupal\Core\Http;

/**
 * Defines a single link relation type.
 *
 * An example of a link relation type is 'canonical'. It represents a canonical,
 * definite representation of a resource.
 *
 * @see \Drupal\Core\Http\LinkRelationTypeManager
 * @see https://tools.ietf.org/html/rfc5988#page-6
 */
interface LinkRelationTypeInterface {

  /**
   * Indicates whether this link relation type is of the 'registered' kind.
   *
   * @return bool
   *
   * @see https://tools.ietf.org/html/rfc5988#section-4.1
   */
  public function isRegistered();

  /**
   * Indicates whether this link relation type is of the 'extension' kind.
   *
   * @return bool
   *
   * @see https://tools.ietf.org/html/rfc5988#section-4.2
   */
  public function isExtension();

  /**
   * Returns the registered link relation type name.
   *
   * Only available for link relation types of the KIND_REGISTERED kind.
   *
   * @return string|null
   *   The name of the registered relation type.
   *
   * @see https://tools.ietf.org/html/rfc5988#section-4.1
   */
  public function getRegisteredName();

  /**
   * Returns the extension link relation type URI.
   *
   * Only available for link relation types of the KIND_EXTENSION kind.
   *
   * @return string
   *   The URI of the extension relation type.
   *
   * @see https://tools.ietf.org/html/rfc5988#section-4.2
   */
  public function getExtensionUri();

  /**
   * Returns the link relation type description.
   *
   * @return string
   *   The link relation type description.
   *
   * @see https://tools.ietf.org/html/rfc5988#section-6.2.1
   */
  public function getDescription();

  /**
   * Returns the URL pointing to the reference of the link relation type.
   *
   * @return string
   *   The URL pointing to the reference.
   *
   * @see https://tools.ietf.org/html/rfc5988#section-6.2.1
   */
  public function getReference();

  /**
   * Returns some extra notes/comments about this link relation type.
   *
   * @return string
   *   The notes about the link relation.
   *
   * @see https://tools.ietf.org/html/rfc5988#section-6.2.1
   */
  public function getNotes();

}
