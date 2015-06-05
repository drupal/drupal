<?php

/**
 * @file
 * Contains \Drupal\filter\Plugin\FilterInterface.
 */

namespace Drupal\filter\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the interface for text processing filter plugins.
 *
 * User submitted content is passed through a group of filters before it is
 * output in HTML, in order to remove insecure or unwanted parts, correct or
 * enhance the formatting, transform special keywords, etc. A group of filters
 * is referred to as a "text format". Administrators can create as many text
 * formats as needed. Individual filters can be enabled and configured
 * differently for each text format.
 *
 * @see \Drupal\filter\Entity\FilterFormat
 *
 * Filtering is a two-step process. First, the content is 'prepared' by calling
 * the FilterInterface::prepare() method for every filter. The purpose is to
 * escape HTML-like structures. For example, imagine a filter which allows the
 * user to paste entire chunks of programming code without requiring manual
 * escaping of special HTML characters like < or &. If the programming code were
 * left untouched, then other filters could think it was HTML and change it. For
 * many filters, the prepare step is not necessary.
 *
 * The second step is the actual processing step. The result from passing the
 * text through all the filters' prepare steps gets passed to all the filters
 * again, this time to the FilterInterface::process() method. The process method
 * should then actually change the content: transform URLs into hyperlinks,
 * convert smileys into images, etc.
 *
 * @see filter_process_text()
 * @see check_markup()
 *
 * Typically, only text processing is applied, but in more advanced use cases,
 * filters may also:
 * - declare asset libraries to be loaded;
 * - declare cache tags that the resulting filtered text depends upon, so when
 *   either of those cache tags is invalidated, the render-cached HTML that the
 *   filtered text is part of should also be invalidated;
 * - create placeholders to apply uncacheable filtering, for example because it
 *   changes every few seconds.
 *
 * @see \Drupal\filter\Plugin\FilterInterface::process()
 *
 * Filters are discovered through annotations, which may contain the following
 * definition properties:
 * - title: (required) An administrative summary of what the filter does.
 *   - type: (required) A classification of the filter's purpose. This is one
 *     of the following:
 *     - FilterInterface::TYPE_HTML_RESTRICTOR: HTML tag and attribute
 *       restricting filters.
 *     - FilterInterface::TYPE_MARKUP_LANGUAGE: Non-HTML markup language filters
 *       that generate HTML.
 *     - FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE: Irreversible
 *       transformation filters.
 *     - FilterInterface::TYPE_TRANSFORM_REVERSIBLE: Reversible transformation
 *       filters.
 * - description: Additional administrative information about the filter's
 *   behavior, if needed for clarification.
 * - status: The default status for new instances of the filter. Defaults to
 *   FALSE.
 * - weight: A default weight for new instances of the filter. Defaults to 0.
 * - settings: An associative array containing default settings for new
 *   instances of the filter.
 *
 * Most implementations want to extend the generic basic implementation for
 * filter plugins.
 *
 * @see \Drupal\filter\Annotation\Filter
 * @see \Drupal\filter\FilterPluginManager
 * @see \Drupal\filter\Plugin\FilterBase
 * @see plugin_api
 */
interface FilterInterface extends ConfigurablePluginInterface, PluginInspectionInterface {

   /**
    * Non-HTML markup language filters that generate HTML.
    */
   const TYPE_MARKUP_LANGUAGE = 0;

   /**
    * HTML tag and attribute restricting filters to prevent XSS attacks.
    */
   const TYPE_HTML_RESTRICTOR = 1;

   /**
    * Reversible transformation filters.
    */
   const TYPE_TRANSFORM_REVERSIBLE = 2;

   /**
    * Irreversible transformation filters.
    */
   const TYPE_TRANSFORM_IRREVERSIBLE = 3;

  /**
   * Returns the processing type of this filter plugin.
   *
   * @return int
   *   One of:
   *   - FilterInterface::TYPE_MARKUP_LANGUAGE
   *   - FilterInterface::TYPE_HTML_RESTRICTOR
   *   - FilterInterface::TYPE_TRANSFORM_REVERSIBLE
   *   - FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE
   */
  public function getType();

  /**
   * Returns the administrative label for this filter plugin.
   *
   * @return string
   */
  public function getLabel();

  /**
   * Returns the administrative description for this filter plugin.
   *
   * @return string
   */
  public function getDescription();

  /**
   * Generates a filter's settings form.
   *
   * @param array $form
   *   A minimally prepopulated form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The state of the (entire) configuration form.
   *
   * @return array
   *   The $form array with additional form elements for the settings of this
   *   filter. The submitted form values should match $this->settings.
   */
  public function settingsForm(array $form, FormStateInterface $form_state);

  /**
   * Prepares the text for processing.
   *
   * Filters should not use the prepare method for anything other than escaping,
   * because that would short-circuit the control the user has over the order in
   * which filters are applied.
   *
   * @param string $text
   *   The text string to be filtered.
   * @param string $langcode
   *   The language code of the text to be filtered.
   *
   * @return string
   *   The prepared, escaped text.
   */
  public function prepare($text, $langcode);

  /**
   * Performs the filter processing.
   *
   * @param string $text
   *   The text string to be filtered.
   * @param string $langcode
   *   The language code of the text to be filtered.
   *
   * @return \Drupal\filter\FilterProcessResult
   *   The filtered text, wrapped in a FilterProcessResult object, and possibly
   *   with associated assets, cacheability metadata and placeholders.
   *
   * @see \Drupal\filter\FilterProcessResult
   */
  public function process($text, $langcode);

  /**
   * Returns HTML allowed by this filter's configuration.
   *
   * May be implemented by filters of the FilterInterface::TYPE_HTML_RESTRICTOR
   * type, this won't be used for filters of other types; they should just
   * return FALSE.
   *
   * This callback function is only necessary for filters that strip away HTML
   * tags (and possibly attributes) and allows other modules to gain insight in
   * a generic manner into which HTML tags and attributes are allowed by a
   * format.
   *
   * @return array|FALSE
   *   A nested array with *either* of the following keys:
   *     - 'allowed': (optional) the allowed tags as keys, and for each of those
   *       tags (keys) either of the following values:
   *       - TRUE to indicate any attribute is allowed
   *       - FALSE to indicate no attributes are allowed
   *       - an array to convey attribute restrictions: the keys must be
   *         attribute names (which may use a wildcard, e.g. "data-*"), the
   *         possible values are similar to the above:
   *           - TRUE to indicate any attribute value is allowed
   *           - FALSE to indicate the attribute is forbidden
   *           - an array to convey attribute value restrictions: the key must
   *             be attribute values (which may use a wildcard, e.g. "xsd:*"),
   *             the possible values are TRUE or FALSE: to mark the attribute
   *             value as allowed or forbidden, respectively
   *     -  'forbidden_tags': (optional) the forbidden tags
   *
   *   There is one special case: the "wildcard tag", "*": any attribute
   *   restrictions on that pseudotag apply to all tags.
   *
   *   If no restrictions apply, then FALSE must be returned.
   *
   *   Here is a concrete example, for a very granular filter:
   *     @code
   *     array(
   *       'allowed' => array(
   *         // Allows any attribute with any value on the <div> tag.
   *         'div' => TRUE,
   *         // Allows no attributes on the <p> tag.
   *         'p' => FALSE,
   *         // Allows the following attributes on the <a> tag:
   *         //  - 'href', with any value;
   *         //  - 'rel', with the value 'nofollow' value.
   *         'a' => array(
   *           'href' => TRUE,
   *           'rel' => array('nofollow' => TRUE),
   *         ),
   *         // Only allows the 'src' and 'alt' attributes on the <alt> tag,
   *         // with any value.
   *         'img' => array(
   *           'src' => TRUE,
   *           'alt' => TRUE,
   *         ),
   *         // Allow RDFa on <span> tags, using only the dc, foaf, xsd and sioc
   *         // vocabularies/namespaces.
   *         'span' => array(
   *           'property' => array('dc:*' => TRUE, 'foaf:*' => TRUE),
   *           'datatype' => array('xsd:*' => TRUE),
   *           'rel' => array('sioc:*' => TRUE),
   *         ),
   *         // Forbid the 'style' and 'on*' ('onClick' etc.) attributes on any
   *         // tag.
   *         '*' => array(
   *           'style' => FALSE,
   *           'on*' => FALSE,
   *         ),
   *       )
   *     )
   *     @endcode
   *
   *   A simpler example, for a very coarse filter:
   *     @code
   *     array(
   *       'forbidden_tags' => array('iframe', 'script')
   *     )
   *     @endcode
   *
   *   The simplest example possible: a filter that doesn't allow any HTML:
   *     @code
   *     array(
   *       'allowed' => array()
   *     )
   *     @endcode
   *
   *   And for a filter that applies no restrictions, i.e. allows any HTML:
   *     @code
   *     FALSE
   *     @endcode
   *
   * @see \Drupal\filter\Entity\FilterFormatInterface::getHtmlRestrictions()
   */
  public function getHTMLRestrictions();

  /**
   * Generates a filter's tip.
   *
   * A filter's tips should be informative and to the point. Short tips are
   * preferably one-liners.
   *
   * @param bool $long
   *   Whether this callback should return a short tip to display in a form
   *   (FALSE), or whether a more elaborate filter tips should be returned for
   *   template_preprocess_filter_tips() (TRUE).
   *
   * @return string|null
   *   Translated text to display as a tip, or NULL if this filter has no tip.
   *
   * @todo Split into getSummaryItem() and buildGuidelines().
   */
  public function tips($long = FALSE);

}
