<?php

declare(strict_types=1);

namespace Drupal\filter;

use Drupal\Core\Session\AccountInterface;

/**
 * Provides an interface for a repository for filter formats.
 */
interface FilterFormatRepositoryInterface {

  /**
   * Returns all enabled formats.
   *
   * @return array<string, \Drupal\filter\FilterFormatInterface>
   *   An array of text format objects, keyed by the format ID, and ordered by
   *   their weight.
   */
  public function getAllFormats(): array;

  /**
   * Returns only those formats which the specified account can use.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account to check.
   *
   * @return array<string, \Drupal\filter\FilterFormatInterface>
   *   An array of text format objects, keyed by the format ID and ordered by
   *   their weight.
   */
  public function getFormatsForAccount(AccountInterface $account): array;

  /**
   * Returns a list of text formats that are allowed for a given role.
   *
   * @param string $roleId
   *   The role identifier.
   *
   * @return array<string, \Drupal\filter\FilterFormatInterface>
   *   An array of text format objects that are allowed for the role, keyed by
   *   the text format ID and ordered by weight.
   */
  public function getFormatsByRole(string $roleId): array;

  /**
   * Returns the default text format for a particular user.
   *
   * The default text format is the first available format that the user is
   * allowed to access, when the formats are ordered by weight. It should
   * generally be used as a default choice when presenting the user with a list
   * of possible text formats (for example, in a node creation form).
   *
   * Conversely, when existing content that does not have an assigned text
   * format needs to be filtered for display, the default text format is the
   * wrong choice, because it is not guaranteed to be consistent from user to
   * user, and some trusted users may have an unsafe text format set by default,
   * which should not be used on text of unknown origin. Instead, the fallback
   * format returned by filter_fallback_format() should be used, since that is
   * intended to be a safe, consistent format that is always available to all
   * users.
   *
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   (optional) The user account to check. If omitted, to the currently
   *   logged-in user account will be used. Defaults to NULL.
   *
   * @return \Drupal\filter\FilterFormatInterface
   *   The default text format for a particular user.
   */
  public function getDefaultFormat(?AccountInterface $account = NULL): FilterFormatInterface;

  /**
   * Returns the ID of the fallback text format that all users have access to.
   *
   * The fallback text format is a regular text format in every respect, except
   * it does not participate in the filter permission system and cannot be
   * disabled. It needs to exist because any user who has permission to create
   * formatted content must always have at least one text format they can use.
   *
   * Because the fallback format is available to all users, it should always be
   * configured securely. For example, when the Filter module is installed, this
   * format is initialized to output plain text. Installation profiles and site
   * administrators have the freedom to configure it further.
   *
   * Note that the fallback format is completely distinct from the default
   * format, which differs per user and is simply the first format which that
   * user has access to. The default and fallback formats are only guaranteed to
   * be the same for users who do not have access to any other format;
   * otherwise, the fallback format's weight determines its placement with
   * respect to the user's other formats.
   *
   * Any modules implementing a format deletion functionality must not delete
   * this format.
   *
   * @return string|null
   *   The ID of the fallback text format.
   *
   * @see ::getDefaultFormat()
   * @see hook_filter_format_disable()
   */
  public function getFallbackFormatId(): ?string;

}
