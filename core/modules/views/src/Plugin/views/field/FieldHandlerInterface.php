<?php

namespace Drupal\views\Plugin\views\field;

use Drupal\views\ResultRow;
use Drupal\views\Plugin\views\ViewsHandlerInterface;

/**
 * Base field handler that has no options and renders an unformatted field.
 */
interface FieldHandlerInterface extends ViewsHandlerInterface {

  /**
   * Adds an ORDER BY clause to the query for click sort columns.
   *
   * @param string $order
   *   The sort order, either 'ASC' or 'DESC'.
   */
  public function clickSort($order);

  /**
   * Determines if this field is click sortable.
   *
   * @return bool
   *   The value of 'click sortable' from the plugin definition, this defaults
   *   to TRUE if not set.
   */
  public function clickSortable();

  /**
   * Gets this field's label.
   */
  public function label();

  /**
   * Returns an HTML element based upon the field's element type.
   *
   * @param bool $none_supported
   *   (optional) Whether or not this HTML element is supported. Defaults to
   *   FALSE.
   * @param bool $default_empty
   *   (optional) Whether or not this HTML element is empty by default. Defaults
   *   to FALSE.
   * @param bool $inline
   *   (optional) Whether or not this HTML element is inline. Defaults to FALSE.
   */
  public function elementType($none_supported = FALSE, $default_empty = FALSE, $inline = FALSE);

  /**
   * Returns an HTML element for the label based upon the field's element type.
   *
   * @param bool $none_supported
   *   (optional) Whether or not this HTML element is supported. Defaults to
   *   FALSE.
   * @param bool $default_empty
   *   (optional) Whether or not this HTML element is empty by default. Defaults
   *   to FALSE.
   */
  public function elementLabelType($none_supported = FALSE, $default_empty = FALSE);

  /**
   * Returns a wrapper HTML element for the field..
   *
   * @param bool $none_supported
   *   (optional) Whether or not this HTML element is supported. Defaults to
   *   FALSE.
   * @param bool $default_empty
   *   (optional) Whether or not this HTML element is empty by default. Defaults
   *   to FALSE.
   */
  public function elementWrapperType($none_supported = FALSE, $default_empty = FALSE);

  /**
   * Provides a list of elements valid for field HTML.
   *
   * This function can be overridden by fields that want more or fewer elements
   * available, though this seems like it would be an incredibly rare
   * occurrence.
   */
  public function getElements();

  /**
   * Returns the class of the field.
   *
   * @param int|null $row_index
   *   (optional) Index of the row element. If NULL the last row is used.
   *   Defaults to NULL.
   */
  public function elementClasses($row_index = NULL);

  /**
   * Replaces a value with tokens from the last field.
   *
   * This function actually figures out which field was last and uses its
   * tokens so they will all be available.
   *
   * @param string $value
   *   The raw string.
   * @param int|null $row_index
   *   (optional) Index of the row element. If NULL the last row is used.
   *   Defaults to NULL.
   */
  public function tokenizeValue($value, $row_index = NULL);

  /**
   * Returns the class of the field's label.
   *
   * @param int|null $row_index
   *   (optional) Index of the row element. If NULL the last row is used.
   *   Defaults to NULL.
   */
  public function elementLabelClasses($row_index = NULL);

  /**
   * Returns the class of the field's wrapper.
   *
   * @param int|null $row_index
   *   (optional) Index of the row element. If NULL the last row is used.
   *   Defaults to NULL.
   */
  public function elementWrapperClasses($row_index = NULL);

  /**
   * Gets the entity matching the current row and relationship.
   *
   * @param \Drupal\views\ResultRow $values
   *   An object containing all retrieved values.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   Returns the entity matching the values or NULL if there is no matching
   *   entity.
   */
  public function getEntity(ResultRow $values);

  /**
   * Gets the value that's supposed to be rendered.
   *
   * This api exists so that other modules can easy set the values of the field
   * without having the need to change the render method as well.
   *
   * @param \Drupal\views\ResultRow $values
   *   An object containing all retrieved values.
   * @param string|null $field
   *   (optional) Index of the row element. If NULL the last row is used.
   *   Defaults to NULL.
   *
   * @return mixed
   *   The value to be rendered.
   */
  public function getValue(ResultRow $values, $field = NULL);

  /**
   * Determines if this field can be grouped in the results.
   *
   * @return bool
   *   TRUE if this field handler is groupable, otherwise FALSE.
   */
  public function useStringGroupBy();

  /**
   * Runs before any fields are rendered.
   *
   * This gives the handlers some time to set up before any handler has been
   * rendered.
   *
   * @param \Drupal\views\ResultRow[] $values
   *   An array of all ResultRow objects returned from the query.
   */
  public function preRender(&$values);

  /**
   * Renders the field.
   *
   * @param \Drupal\views\ResultRow $values
   *   The values retrieved from a single row of a view's query result.
   *
   * @return string|\Drupal\Component\Render\MarkupInterface
   *   The rendered output. If the output is safe it will be wrapped in an
   *   object that implements MarkupInterface. If it is empty or unsafe it
   *   will be a string.
   */
  public function render(ResultRow $values);

  /**
   * Runs after every field has been rendered.
   *
   * This is meant to be used mainly to deal with field handlers whose output
   * cannot be cached at row level but can be cached at display level. The
   * typical example is the row counter. For completely uncacheable field output
   * placeholders should be used.
   *
   * @param \Drupal\views\ResultRow $row
   *   An array of all ResultRow objects returned from the query.
   * @param $output
   *   The field rendered output.
   *
   * @return string[]
   *   An associative array of post-render token values keyed by placeholder.
   *
   * @see \Drupal\views\Plugin\views\field\UncacheableFieldHandlerTrait
   */
  public function postRender(ResultRow $row, $output);

  /**
   * Renders a field using advanced settings.
   *
   * This renders a field normally, then decides if render-as-link and
   * text-replacement rendering is necessary.
   *
   * @param \Drupal\views\ResultRow $values
   *   The values retrieved from a single row of a view's query result.
   *
   * @return \Drupal\Component\Render\MarkupInterface|string
   *   The advanced rendered output. If the output is safe, it will be wrapped
   *   in an object that implements MarkupInterface. If it is empty or unsafe,
   *   it will be a string.
   */
  public function advancedRender(ResultRow $values);

  /**
   * Checks if a field value is empty.
   *
   * @param $value
   *   The field value.
   * @param bool $empty_zero
   *   Whether or not this field is configured to consider 0 as empty.
   * @param bool $no_skip_empty
   *   (optional) Whether or not to use empty() to check the value. Defaults to
   *   TRUE.
   *
   * @return bool
   *   TRUE if the value is considered empty, FALSE otherwise.
   */
  public function isValueEmpty($value, $empty_zero, $no_skip_empty = TRUE);

  /**
   * Performs an advanced text render for the item.
   *
   * This is separated out as some fields may render lists, and this allows each
   * item to be handled individually.
   *
   * @param array $alter
   *   The alter array of options to use:
   *   - max_length: Maximum length of a string, the rest gets truncated.
   *   - word_boundary: Trim only on a word boundary.
   *   - ellipsis: Show an ellipsis at the end of the trimmed string.
   *   - html: Make sure that the HTML is correct.
   *
   * @return \Drupal\Component\Render\MarkupInterface|string
   *   The rendered output. If the output is safe it will be wrapped in an
   *   object that implements MarkupInterface. If it is empty or unsafe it will
   *   be a string.
   */
  public function renderText($alter);

  /**
   * Gets the 'render' tokens to use for advanced rendering.
   *
   * This runs through all of the fields and arguments that are available and
   * gets their values. This will then be used in one giant str_replace().
   *
   * @param mixed $item
   *   The item to render.
   *
   * @return array
   *   An array of available tokens
   */
  public function getRenderTokens($item);

  /**
   * Renders row values using $this->themeFunctions() as #theme.
   *
   * @param \Drupal\views\ResultRow $values
   *   Holds single row of a view's result set.
   *
   * @return \Drupal\Component\Render\MarkupInterface|string
   *   Returns rendered output of the given theme implementation. If the output
   *   is safe, it will be wrapped in an object that implements MarkupInterface.
   *   If it is empty or unsafe, it will be a string.
   */
  public function theme(ResultRow $values);

}
