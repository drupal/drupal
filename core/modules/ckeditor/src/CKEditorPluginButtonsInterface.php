<?php

/**
 * @file
 * Contains \Drupal\ckeditor\CKEditorPluginButtonsInterface.
 */

namespace Drupal\ckeditor;

/**
 * Defines an interface for CKEditor plugins with buttons.
 *
 * This allows a CKEditor plugin to define which buttons it provides, so that
 * users can configure a CKEditor toolbar instance via the toolbar builder UI.
 * If at least one button that this plugin provides is added to the toolbar via
 * the toolbar builder UI, then this plugin will be enabled automatically.
 *
 * If a CKEditor plugin implements this interface, it can still also implement
 * CKEditorPluginContextualInterface if it wants a button to conditionally be
 * added as well. The downside of conditionally adding buttons is that the user
 * cannot see these buttons in the toolbar builder UI.
 *
 * @see \Drupal\ckeditor\CKEditorPluginInterface
 * @see \Drupal\ckeditor\CKEditorPluginContextualInterface
 * @see \Drupal\ckeditor\CKEditorPluginConfigurableInterface
 * @see \Drupal\ckeditor\CKEditorPluginBase
 * @see \Drupal\ckeditor\CKEditorPluginManager
 * @see \Drupal\ckeditor\Annotation\CKEditorPlugin
 * @see plugin_api
 */
interface CKEditorPluginButtonsInterface extends CKEditorPluginInterface {

  /**
   * Returns the buttons that this plugin provides, along with metadata.
   *
   * The metadata is used by the CKEditor module to generate a visual CKEditor
   * toolbar builder UI.
   *
   * @return array
   *   An array of buttons that are provided by this plugin. This will
   *   only be used in the administrative section for assembling the toolbar.
   *   Each button should by keyed by its CKEditor button name, and should
   *   contain an array of button properties, including:
   *   - label: A human-readable, translated button name.
   *   - image: An image for the button to be used in the toolbar.
   *   - image_rtl: If the image needs to have a right-to-left version, specify
   *     an alternative file that will be used in RTL editors.
   *   - image_alternative: If this button does not render as an image, specify
   *     an HTML string representing the contents of this button.
   *   - image_alternative_rtl: Similar to image_alternative, but a
   *     right-to-left version.
   *   - attributes: An array of HTML attributes which should be added to this
   *     button when rendering the button in the administrative section for
   *     assembling the toolbar.
   *   - multiple: Boolean value indicating if this button may be added multiple
   *     times to the toolbar. This typically is only applicable for dividers
   *     and group indicators.
   */
  public function getButtons();

}
