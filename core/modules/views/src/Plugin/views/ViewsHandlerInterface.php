<?php

namespace Drupal\views\Plugin\views;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides an interface for all views handlers.
 */
interface ViewsHandlerInterface extends ViewsPluginInterface {

  /**
   * Run before the view is built.
   *
   * This gives all the handlers some time to set up before any handler has
   * been fully run.
   */
  public function preQuery();

  /**
   * Determines the entity type used by this handler.
   *
   * If this handler uses a relationship, the base class of the relationship is
   * taken into account.
   *
   * @return string
   *   The machine name of the entity type.
   */
  public function getEntityType();

  /**
   * Determines if the handler is considered 'broken', meaning it's a
   * a placeholder used when a handler can't be found.
   */
  public function broken();

  /**
   * Ensure the main table for this handler is in the query. This is used
   * a lot.
   */
  public function ensureMyTable();

  /**
   * Check whether given user has access to this handler.
   *
   * @param AccountInterface $account
   *   The user account to check.
   *
   * @return bool
   *   TRUE if the user has access to the handler, FALSE otherwise.
   */
  public function access(AccountInterface $account);

  /**
   * Get the join object that should be used for this handler.
   *
   * This method isn't used a great deal, but it's very handy for easily
   * getting the join if it is necessary to make some changes to it, such
   * as adding an 'extra'.
   */
  public function getJoin();

  /**
   * Sanitize the value for output.
   *
   * @param $value
   *   The value being rendered.
   * @param $type
   *   The type of sanitization needed. If not provided,
   *   \Drupal\Component\Utility\Html::escape() is used.
   *
   * @return \Drupal\views\Render\ViewsRenderPipelineMarkup
   *   Returns the safe value.
   */
  public function sanitizeValue($value, $type = NULL);

  /**
   * Fetches a handler to join one table to a primary table from the data cache.
   *
   * @param string $table
   *   The table to join from.
   * @param string $base_table
   *   The table to join to.
   *
   * @return \Drupal\views\Plugin\views\join\JoinPluginBase
   */
  public static function getTableJoin($table, $base_table);

  /**
   * Shortcut to get a handler's raw field value.
   *
   * This should be overridden for handlers with formulae or other
   * non-standard fields. Because this takes an argument, fields
   * overriding this can just call return parent::getField($formula)
   */
  public function getField($field = NULL);

  /**
   * Run after the view is executed, before the result is cached.
   *
   * This gives all the handlers some time to modify values. This is primarily
   * used so that handlers that pull up secondary data can put it in the
   * $values so that the raw data can be used externally.
   */
  public function postExecute(&$values);

  /**
   * Shortcut to display the exposed options form.
   */
  public function showExposeForm(&$form, FormStateInterface $form_state);

  /**
   * Called just prior to query(), this lets a handler set up any relationship
   * it needs.
   */
  public function setRelationship();

  /**
   * Return a string representing this handler's name in the UI.
   */
  public function adminLabel($short = FALSE);

  /**
   * Breaks x,y,z and x+y+z into an array.
   *
   * @param string $str
   *   The string to split.
   * @param bool $force_int
   *   Enforce a numeric check.
   *
   * @return \stdClass
   *   A stdClass object containing value and operator properties.
   */
  public static function breakString($str, $force_int = FALSE);

  /**
   * Provide text for the administrative summary.
   */
  public function adminSummary();

}
