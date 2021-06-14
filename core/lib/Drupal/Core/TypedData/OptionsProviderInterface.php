<?php

namespace Drupal\Core\TypedData;

use Drupal\Core\Session\AccountInterface;

/**
 * Interface for retrieving all possible and settable values.
 *
 * While possible values specify which values existing data might have, settable
 * values define the values that are allowed to be set by a user.
 *
 * For example, in a workflow scenario, the settable values for a state field
 * might depend on the currently set state, while possible values are all
 * states. Thus settable values would be used in an editing context, while
 * possible values would be used for presenting filtering options in a search.
 *
 * For convenience, lists of both settable and possible values are also provided
 * as structured options arrays that can be used in an Options widget such as a
 * select box or checkboxes.
 *
 * Note that this interface is mostly applicable for primitive data values, but
 * can be used on complex data structures if a (primitive) main property is
 * specified. In that case, the allowed values and options apply to the main
 * property only.
 *
 * @see \Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase
 */
interface OptionsProviderInterface {

  /**
   * Returns an array of possible values.
   *
   * If the optional $account parameter is passed, then the array is filtered to
   * values viewable by the account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (optional) The user account for which to filter the possible values. If
   *   omitted, all possible values are returned.
   *
   * @return array
   *   An array of possible values.
   */
  public function getPossibleValues(AccountInterface $account = NULL);

  /**
   * Returns an array of possible values with labels for display.
   *
   * If the optional $account parameter is passed, then the array is filtered to
   * values viewable by the account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (optional) The user account for which to filter the possible options.
   *   If omitted, all possible options are returned.
   *
   * @return array
   *   An array of possible options for the object that may be used in an
   *   Options widget, for example when existing data should be filtered. It may
   *   either be a flat array of option labels keyed by values, or a
   *   two-dimensional array of option groups (array of flat option arrays,
   *   keyed by option group label). Note that labels should NOT be sanitized.
   */
  public function getPossibleOptions(AccountInterface $account = NULL);

  /**
   * Returns an array of settable values.
   *
   * If the optional $account parameter is passed, then the array is filtered to
   * values settable by the account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (optional) The user account for which to filter the settable values. If
   *   omitted, all settable values are returned.
   *
   * @return array
   *   An array of settable values.
   */
  public function getSettableValues(AccountInterface $account = NULL);

  /**
   * Returns an array of settable values with labels for display.
   *
   * If the optional $account parameter is passed, then the array is filtered to
   * values settable by the account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (optional) The user account for which to filter the settable options. If
   *   omitted, all settable options are returned.
   *
   * @return array
   *   An array of settable options for the object that may be used in an
   *   Options widget, usually when new data should be entered. It may either be
   *   a flat array of option labels keyed by values, or a two-dimensional array
   *   of option groups (array of flat option arrays, keyed by option group
   *   label). Note that labels should NOT be sanitized.
   */
  public function getSettableOptions(AccountInterface $account = NULL);

}
