<?php

/**
 * @file
 * Documentation related to CKEditor 5.
 */

use Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition;

/**
 * @defgroup ckeditor5_architecture CKEditor 5 architecture
 * @{
 *
 * @section overview Overview
 * The CKEditor 5 module integrates CKEditor 5 with Drupal's filtering and text
 * editor APIs.
 *
 * Where possible, it uses upstream CKEditor plugins, but it also relies on
 * Drupal-specific CKEditor plugins to ensure a consistent user experience.
 *
 * @see https://ckeditor.com/ckeditor-5/
 *
 * @section data_models Data models
 * Drupal and CKEditor 5 have very different data models.
 *
 * Drupal stores blobs of HTML that remains manageable thanks to the use of
 * filters and granular HTML restrictions — crucially this remains manageable
 * thanks to those restrictions but also because Drupal does not need to
 * process, render, understand or otherwise interact with it.
 *
 * @see \Drupal\text\Plugin\Field\FieldType\TextItemBase
 * @see \Drupal\filter\Plugin\Filter\FilterInterface::getHTMLRestrictions()
 *
 * On the other hand, CKEditor 5 must not only be able to render these
 * blobs, but also allow editing and creating it. This requires a much deeper
 * understanding of that HTML.
 *
 * CKEditor 5 (in contrast with CKEditor 4) therefore has its own data model to
 * represent this information — that data model is explicitly not HTML.
 *
 * Therefore all interactions between Drupal and CKEditor 5 need to translate
 * between these different data models.
 *
 * @see https://ckeditor.com/docs/ckeditor5/latest/framework/guides/architecture/editing-engine.html#element-types-and-custom-data
 *
 * @section plugins CKEditor 5 Plugins
 * CKEditor 5 plugins may use either YAML or a PHP annotation for their
 * definitions. A PHP class does not need an annotation if it is defined in yml.
 *
 * To be discovered, YAML definition files must be named
 * {module_name}.ckeditor5.yml.
 *
 * @see ckeditor5.ckeditor5.yml for many examples of CKEditor 5 plugin configuration as YAML.
 *
 * The minimally required metadata: the CKEditor 5 plugins to load, the label
 * and the HTML elements it can generate — here's an example for a module
 * providing a Marquee plugin, both in yml or Annotation form:
 *
 * Declared in the yml file:
 * @code
 * # In the MODULE_NAME.ckeditor5.yml file.
 *
 * MODULE_NAME_marquee:
 *   ckeditor5:
 *     plugins: [PACKAGE.CLASS]
 *   drupal:
 *     label: Marquee
 *     library: MODULE_NAME/ckeditor5.marquee
 *     elements:
 *       - <marquee>
 *       - <marquee behavior>
 * @endcode
 *
 * Declared as an Annotation:
 * @code
 * # In a scr/Plugin/CKEditor5Plugin/Marquee.php file.
 * /**
 *  * @CKEditor5Plugin(
 *  *   id = "MODULE_NAME_marquee",
 *  *   ckeditor5 = @CKEditor5AspectsOfCKEditor5Plugin(
 *  *     plugins = { "PACKAGE.CLASS" },
 *  *   ),
 *  *   drupal = @DrupalAspectsOfCKEditor5Plugin(
 *  *     label = @Translation("Marquee"),
 *  *     library = "MODULE_NAME/ckeditor5.marquee"
 *  *     elements = { "<marquee>", "<marquee behavior>" },
 *  *   )
 *  * )
 *  * /
 * @endcode
 *
 * The metadata relating strictly to the CKEditor 5 plugin's JS code is stored
 * in the 'ckeditor5' key; all other metadata is stored in the 'drupal' key.
 *
 * If the plugin has a dependency on another module, adding the 'provider' key
 * will prevent the plugin from being loaded if that module is not installed.
 *
 * All of these can be defined in YAML or annotations. A given plugin should
 * choose one or the other, as a definition can't parse both at once.
 *
 * Overview of all available plugin definition properties:
 *
 * - provider: Allows a plugin to have a dependency on another module. If it has
 *   a value, a module with a machine name matching that value must be installed
 *   for the configured plugin to load.
 * - ckeditor5.plugins: A list CKEditor 5 JavaScript plugins to load, as
 *   '{package.Class}' , such as 'drupalMedia.DrupalMedia'.
 * - ckeditor5.config: A keyed array of additional values for the constructor of
 *   the CKEditor 5 JavaScript plugins being loaded. i.e. this becomes the
 *   CKEditor 5 plugin configuration settings (see
 *   https://ckeditor.com/docs/ckeditor5/latest/builds/guides/integration/configuration.html)
 *   for a given plugin.
 * - drupal.label: Human-readable name of the CKEditor 5 plugin.
 * - drupal.library: A Drupal asset library to load with the plugin.
 * - drupal.admin_library: A Drupal asset library that will load in the text
 *   format admin UI when the plugin is available.
 * - drupal.class: Optional PHP class that makes it possible for the plugin to
 *   provide dynamic values, or a configuration UI. The value should be
 *   formatted as '\Drupal\{module_name}\Plugin\CKEditor5Plugin\{class_name}' to
 *   make it discoverable.
 * - drupal.elements: A list of elements and attributes the plugin allows use of
 *   within CKEditor 5. This uses the same syntax as the 'filter_html' plugin
 *   with an additional special keyword: '<$text-container>' . Using
 *   '<$text-container [attribute(s)]>` will permit the provided
 *   attributes in all CKEditor 5's `$block` text container tags that are
 *   explicitly enabled in any plugin. i.e. if only '<p>', '<h3>' and '<h2>'
 *   tags are allowed, then '<$text-container data-something>' will allow the
 *   'data-something' attribute for '<p>', '<h3>' and '<h2>' tags.
 *   Note that while the syntax is the same, some extra nuance is needed:
 *   although this syntax can be used to create an attribute on an element, f.e.
 *   (['<marquee behavior>']) creating the `behavior` attribute on `<marquee>`,
 *   the tag itself must be creatable as well (['<marquee>']). If a plugin wants
 *   the tag and attribute to be created, list both:
 *   (['<marquee>', '<marquee behavior>']). Validation logic ensures that a
 *   plugin supporting only the creation of attributes cannot be enabled if the
 *   tag cannot be created via itself or through another CKEditor 5 plugin.
 * - drupal.toolbar_items: List of toolbar items the plugin provides. Keyed by a
 *   machine name and the value being a pair defining the label:
 *   @code
 *   toolbar_items:
 *     indent:
 *       label: Indent
 *     outdent:
 *       label: Outdent
 *   @encode
 * - drupal.conditions: Conditions required for the plugin to load (other than
 *   module dependencies, which are defined by the 'provider' property).
 *   Conditions can check for five different things:
 *   - 'toolbarItem': a toolbar item that must be enabled
 *   - 'filter': a filter that must be enabled
 *   - 'imageUploadStatus': TRUE if image upload must be enabled, FALSE if it
 *      must not be enabled
 *   - 'requiresConfiguration': a subset of the configuration for this plugin
 *      that must match (exactly)
 *   - 'plugins': a list of CKEditor 5 Drupal plugin IDs that must be enabled
 *   Plugins requiring more complex conditions, such as requiring multiple
 *   toolbar items or multiple filters, have not yet been identified. If this
 *   need arises, see
 *   https://www.drupal.org/docs/drupal-apis/ckeditor-5-api/overview#conditions.
 *
 * All of these can be defined in YAML or annotations. A given plugin should
 * choose one or the other, as a definition can't parse both at once.
 *
 * If the CKEditor 5 plugin contains translation they can be automatically
 * loaded by Drupal by adding the dependency to the core/ckeditor5.translations
 * library to the CKEditor 5 plugin library definition:
 *
 * @code
 * # In the MODULE_NAME.libraries.yml file.
 *
 * marquee:
 *  js:
 *    assets/ckeditor5/marquee/marquee.js: { minified: true }
 *  dependencies:
 *    - core/ckeditor5
 *    - core/ckeditor5.translations
 * @endcode
 *
 * The translations for CKEditor 5 are located in a translations/ subdirectory,
 * Drupal will load the corresponding translation when necessary, located in
 * assets/ckeditor5/marquee/translations/* in this example.
 *
 *
 * @see \Drupal\ckeditor5\Annotation\CKEditor5Plugin
 * @see \Drupal\ckeditor5\Annotation\CKEditor5AspectsOfCKEditor5Plugin
 * @see \Drupal\ckeditor5\Annotation\DrupalAspectsOfCKEditor5Plugin
 *
 * @section upgrade_path Upgrade path
 *
 * Modules can provide upgrade paths similar to the built-in upgrade path for
 * Drupal core's CKEditor 4 to CKEditor 5, by providing a CKEditor4To5Upgrade
 * plugin. This plugin type allows:
 * - mapping a CKEditor 4 button to an equivalent CKEditor 5 toolbar item
 * - mapping CKEditor 4 plugin settings to equivalent CKEditor 5 plugin
 *   configuration.
 * The supported CKEditor 4 buttons and/or CKEditor 4 plugin settings must be
 * specified in the annotation.
 * See Drupal core's implementation for an example.
 *
 * @see \Drupal\ckeditor5\Annotation\CKEditor4To5Upgrade
 * @see \Drupal\ckeditor5\Plugin\CKEditor4To5UpgradePluginInterface
 * @see \Drupal\ckeditor5\Plugin\CKEditor4To5Upgrade\Core
 *
 * @section public_api Public API
 *
 * The CKEditor 5 module provides no public API, other than:
 * - the annotations and interfaces mentioned above;
 * - to help implement CKEditor 5 plugins:
 *   \Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableTrait and
 *   \Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
 * - \Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition, which is used to
 *   interact with plugin definitions in hook_ckeditor5_plugin_info_alter();
 * - to help contributed modules write tests:
 *   \Drupal\Tests\ckeditor5\Kernel\CKEditor5ValidationTestTrait and
 *   \Drupal\Tests\ckeditor5\Traits\CKEditor5TestTrait;
 * - to help contributed modules write configuration schemas for configurable
 *   plugins, the data types in config/schema/ckeditor5.data_types.yml are
 *   likely to be useful. They automatically get validation constraints applied;
 * - to help contributed modules write validation constraints for configurable
 *   plugins, it is strongly recommended to subclass
 *   \Drupal\Tests\ckeditor5\Kernel\ValidatorsTest. For very complex validation
 *   constraints that need to access text editor and/or format, use
 *   \Drupal\ckeditor5\Plugin\Validation\Constraint\TextEditorObjectDependentValidatorTrait.
 *
 * @}
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Modify the list of available CKEditor 5 plugins.
 *
 * This hook may be used to modify plugin properties after they have been
 * specified by other modules.
 *
 * @param array $plugin_definitions
 *   An array of all the existing plugin definitions, passed by reference.
 *
 * @see \Drupal\ckeditor5\Plugin\CKEditor5PluginManager
 */
function hook_ckeditor5_plugin_info_alter(array &$plugin_definitions): void {
  assert($plugin_definitions['ckeditor5_link'] instanceof CKEditor5PluginDefinition);
  $link_plugin_definition = $plugin_definitions['ckeditor5_link']->toArray();
  $link_plugin_definition['ckeditor5']['config']['link']['decorators'][] = [
    'mode' => 'manual',
    'label' => t('Open in new window'),
    'attributes' => [
      'target' => '_blank',
    ],
  ];
  $plugin_definitions['ckeditor5_link'] = new CKEditor5PluginDefinition($link_plugin_definition);
}

/**
 * Modify the list of available CKEditor 4 to 5 Upgrade plugins.
 *
 * This hook may be used to modify plugin properties after they have been
 * specified by other modules. For example, to override a default upgrade path.
 *
 * @param array $plugin_definitions
 *   An array of all the existing plugin definitions, passed by reference.
 *
 * @see \Drupal\ckeditor5\Plugin\CKEditor4To5UpgradePluginManager
 */
function hook_ckeditor4to5upgrade_plugin_info_alter(array &$plugin_definitions): void {
  // Remove core's upgrade path for the "Maximize" button (which is: there is no
  // equivalent). This allows a different CKEditor4To5Upgrade plugin to define
  // this upgrade path instead.
  unset($plugin_definitions['core']['cke4_buttons']['Maximize']);
}

/**
 * @} End of "addtogroup hooks".
 */
