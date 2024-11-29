<?php

declare(strict_types=1);

namespace Drupal\Core\Theme\Icon\Plugin;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;
use Drupal\Core\Plugin\Factory\ContainerFactory;
use Drupal\Core\Theme\Icon\Exception\IconPackConfigErrorException;
use Drupal\Core\Theme\Icon\IconCollector;
use Drupal\Core\Theme\Icon\IconDefinitionInterface;
use Drupal\Core\Theme\Icon\IconExtractorPluginManager;
use JsonSchema\Constraints\Constraint;
use JsonSchema\Validator;

/**
 * Defines an icon pack plugin manager to deal with icons.
 *
 * An extension can define an icon pack in an EXTENSION_NAME.icons.yml file
 * contained in the extension's base directory.
 * Each icon pack must have an `extractor` and `template` property. An optional
 * `config` property can be required based on the value of the `extractor`
 * property.
 * @code
 * example_pack:
 *   extractor: (string) Plugin ID of the IconExtractor. Provided extractors are
 *     `path`, `svg`, and `svg_sprite`. Contributed modules can provide more
 *     extractors for different use cases. These extractors will use the value
 *     of `config: sources` below to discover the icons and build the icon list
 *     for this icon pack.
 *   template: (string) Twig template to render the icon and the icon values
 *     available in the template:
 *     - `icon_id`: Icon ID based on filename or {icon_id} pattern.
 *     - `source`: Icon path or URL relative to the extension or to the Drupal
 *         root.
 *     - all specific values from the extractor plugin, extractor `svg` will
 *         provide a `content` variable with the SVG icon code without <svg>
 *         wrapper.
 *     - all `settings` values if set below based on keys.
 *   config:
 *     sources: (array) Mandatory for extractors: `path`, `svg`, `svg_sprite`.
 *      This list of paths or URLs is used to generate the list of icons for
 *      this pack. Extractors `path` and `svg_sprite` allow remote files, while
 *     `svg` does not allow remote files for security concerns, as the extractor
 *      generates a variable with the content of the SVG file. Examples of
 *      values:
 *       - path/to/relative/*.svg
 *       - path/to/relative/{icon_id}-suffix.svg # Extract icon id.
 *       - /path/relative/drupal/root/*.svg # For icons in /libraries.
 *       - http://www.my_domain.com/my_icon.png
 *       - ...
 *     # ... Other keys for specific or contributed extractor plugins.
 *
 *   # Recommended values:
 *   label: (string) The name of the icon pack for display
 *
 *   # Optional values:
 *   description: (string) The description of the icon pack for display.
 *   license:
 *     name: (string) A System Package Data Exchange (SPDX) license identifier
 *       such as "GPL-2.0-or-later" (see https://spdx.org/licenses/), or if
 *       not applicable, the human-readable name of the license.
 *     url: (string) The URL of the license information for the version
 *       of the library used.
 *     gpl-compatible: A Boolean for whether this library is GPL compatible.
 *   links: (array)
 *     - (string) The URL of a Documentation page.
 *     - ...
 *   version: (string) The version of the icon pack.
 *   enabled: (boolean) Set FALSE to disable the icon pack discovering process.
 *     Definition will not be populated with icons. Defaults to TRUE.
 *   preview: (string) Optional Twig template for previewing icons in the admin
 *     backend when the standard template does not support proper icon display.
 *     This is particularly useful for font-based icon packs that use a format
 *     like <i class="..">. By default, the admin preview relies on the
 *     <img src=""> template. This feature aids contrib module implementations
 *     that integrate icons with the Field API or CKEditor when a preview is
 *     necessary.
 *   library: (string) Drupal library machine name to include.
 *
 *   # Optional values for the template; they must follow JSON Schema and can
 *   # only be non-scalar primitives.
 *   # A specific class \Drupal\Core\Theme\Icon\IconExtractorSettingsForm
 *   # will transform these settings into Drupal Form API to be available as a
 *   # form for contributed modules implementing icons for FormElement, Field
 *   # API, Menu, CKEditor, or other Drupal APIs.
 *   # Constraints in the form are indicative and will not apply to the values
 *   # passed to the template; the implementation, for example, a FormElement
 *   # must enforce the constraints.
 *   settings: (array)
 *     FORM_KEY: (string) Name of the setting in the template.
 *       title : (string) Title of the setting.
 *       description : (string) Optional description of the setting.
 *       type : (string) Primitive type: string, number, integer, boolean.
 *       default: (mixed) Form default value, will not be used as default
 *         value in the template, template must use |default() twig filter.
 *       [...] Specific JSON Schema values like multipleOf, minimum, maximum...
 * @endcode
 * For example:
 * @code
 * my_icon_pack:
 *   label: "My icons"
 *   description: "My UI Icons pack to use everywhere."
 *   license:
 *     name: GPL3-or-later
 *     url: https://www.gnu.org/licenses/gpl-3.0.html
 *     gpl-compatible: true
 *   links:
 *     - https://homepage.com
 *     - https://homepage.com#usage
 *   version: 1.0.0
 *   enabled: true
 *   extractor: svg
 *   config:
 *     sources:
 *       - icons/{icon_id}.svg
 *       - icons_grouped/{group}/{icon_id}.svg
 *   settings:
 *     size:
 *       title: "Size"
 *       type: "integer"
 *       minimum: 24
 *       default: 32
 *   template: >
 *     <img src={{ source }} width="{{ size|default(32) }}" height="{{ size|default(32) }}"/>
 *   library: "my_theme/my_lib"
 * @endcode
 *
 * @see \Drupal\Core\Theme\Icon\IconExtractorInterface
 * @see \Drupal\Core\Theme\Icon\IconExtractorWithFinderInterface
 * @see \Drupal\Core\Theme\Icon\IconExtractorSettingsForm
 * @see plugin_api
 *
 * @internal
 *   The icon API is experimental and is not meant for production use.
 *   See https://www.drupal.org/core/experimental for more information.
 */
class IconPackManager extends DefaultPluginManager implements IconPackManagerInterface {

  private const SCHEMA_VALIDATE = 'core/assets/schemas/v1/icon_pack.schema.json';

  /**
   * The schema validator.
   *
   * This property will only be set if the validator library is available.
   *
   * @var \JsonSchema\Validator|null
   */
  private ?Validator $validator = NULL;

  /**
   * Constructs the IconPackPluginManager object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   *   The theme handler.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   The cache backend.
   * @param \Drupal\Core\Theme\Icon\IconExtractorPluginManager $iconPackExtractorManager
   *   The icon plugin extractor service.
   * @param \Drupal\Core\Theme\Icon\IconCollector $iconCollector
   *   The icon cache collector service.
   * @param string $appRoot
   *   The application root.
   */
  public function __construct(
    ModuleHandlerInterface $module_handler,
    protected readonly ThemeHandlerInterface $themeHandler,
    CacheBackendInterface $cacheBackend,
    protected readonly IconExtractorPluginManager $iconPackExtractorManager,
    protected readonly IconCollector $iconCollector,
    protected string $appRoot,
  ) {
    $this->moduleHandler = $module_handler;
    $this->factory = new ContainerFactory($this);
    $this->alterInfo('icon_pack');
    $this->setCacheBackend($cacheBackend, 'icon_pack', ['icon_pack_plugin', 'icon_pack_collector']);
  }

  /**
   * Sets the validator service if available.
   *
   * @param \JsonSchema\Validator|null $validator
   *   The JSON Validator class.
   */
  public function setValidator(?Validator $validator = NULL): void {
    if (NULL !== $validator) {
      $this->validator = $validator;
      return;
    }
    if (class_exists(Validator::class)) {
      $this->validator = new Validator();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id): void {
    if (preg_match('@[^a-z0-9_]@', $plugin_id)) {
      throw new IconPackConfigErrorException(sprintf('Invalid icon pack id in: %s, name: %s must contain only lowercase letters, numbers, and underscores.', $definition['provider'], $plugin_id));
    }

    $this->validateDefinition($definition);

    // Do not include disabled definition with `enabled: false`.
    if (!($definition['enabled'] ?? TRUE)) {
      return;
    }

    if (!isset($definition['provider'])) {
      return;
    }

    // Provide path information for extractors.
    $relative_path = $this->moduleHandler->moduleExists($definition['provider'])
      ? $this->moduleHandler->getModule($definition['provider'])->getPath()
      : $this->themeHandler->getTheme($definition['provider'])->getPath();

    $definition['relative_path'] = $relative_path;
    // To avoid the need for appRoot in extractors.
    $definition['absolute_path'] = sprintf('%s/%s', $this->appRoot, $relative_path);

    // Load all discovered icon ids in the definition so they are cached.
    $definition['icons'] = $this->getIconsFromDefinition($definition);
  }

  /**
   * {@inheritdoc}
   */
  public function getIcons(array $allowed_icon_pack = []): array {
    $definitions = $this->getDefinitions();
    if (NULL === $definitions) {
      return [];
    }

    $icons = [];
    foreach ($definitions as $definition) {
      if ($allowed_icon_pack && !in_array($definition['id'], $allowed_icon_pack, TRUE)) {
        continue;
      }
      $icons = array_merge($icons, $definition['icons'] ?? []);
    }

    return $icons;
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(string $icon_full_id): ?IconDefinitionInterface {
    return $this->iconCollector->get($icon_full_id, $this->getDefinitions());
  }

  /**
   * {@inheritdoc}
   */
  public function getExtractorFormDefaults(string $pack_id): array {
    $icon_pack_definitions = $this->getDefinitions();

    if (!isset($icon_pack_definitions[$pack_id]) || !isset($icon_pack_definitions[$pack_id]['settings'])) {
      return [];
    }

    $default = [];
    foreach ($icon_pack_definitions[$pack_id]['settings'] as $name => $definition) {
      if (isset($definition['default'])) {
        $default[$name] = $definition['default'];
      }
    }

    return $default;
  }

  /**
   * {@inheritdoc}
   */
  public function getExtractorPluginForms(array &$form, FormStateInterface $form_state, array $default_settings = [], array $allowed_icon_pack = [], bool $wrap_details = FALSE): void {
    $icon_pack_definitions = $this->getDefinitions();

    if (NULL === $icon_pack_definitions) {
      return;
    }

    if (!empty($allowed_icon_pack)) {
      $icon_pack_definitions = array_intersect_key($icon_pack_definitions, $allowed_icon_pack);
    }

    $extractor_forms = $this->iconPackExtractorManager->getExtractorForms($icon_pack_definitions);
    if (empty($extractor_forms)) {
      return;
    }

    foreach ($icon_pack_definitions as $pack_id => $definition) {
      // Simply skip if no settings declared in definition.
      if (count($definition['settings'] ?? []) === 0) {
        continue;
      }

      // Create the container for each extractor settings used to have the
      // extractor form.
      $form[$pack_id] = [
        '#type' => $wrap_details ? 'details' : 'container',
        '#title' => $definition['label'] ?? $pack_id,
      ];

      // Create the extractor form and set settings so we can build with values.
      $subform_state = SubformState::createForSubform($form[$pack_id], $form, $form_state);
      $subform_state->getCompleteFormState()->setValue('saved_values', $default_settings[$pack_id] ?? []);
      if (is_a($extractor_forms[$pack_id], '\Drupal\Core\Plugin\PluginFormInterface')) {
        $form[$pack_id] += $extractor_forms[$pack_id]->buildConfigurationForm($form[$pack_id], $subform_state);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function listIconPackOptions(bool $include_description = FALSE): array {
    $icon_pack_definitions = $this->getDefinitions();

    if (NULL === $icon_pack_definitions) {
      return [];
    }

    $options = [];
    foreach ($icon_pack_definitions as $definition) {
      if (empty($definition['icons'])) {
        continue;
      }
      $label = $definition['label'] ?? $definition['id'];
      if ($include_description && isset($definition['description'])) {
        $label = sprintf('%s - %s', $label, $definition['description']);
      }
      $options[$definition['id']] = sprintf('%s (%u)', $label, count($definition['icons']));
    }

    natsort($options);
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery(): DiscoveryInterface {
    if (!$this->discovery) {
      $this->discovery = new YamlDiscovery('icons', $this->moduleHandler->getModuleDirectories() + $this->themeHandler->getThemeDirectories());
      $this->discovery
        ->addTranslatableProperty('label')
        ->addTranslatableProperty('description');
      $this->discovery = new ContainerDerivativeDiscoveryDecorator($this->discovery);
    }
    return $this->discovery;
  }

  /**
   * {@inheritdoc}
   */
  protected function providerExists(mixed $provider): bool {
    return $this->moduleHandler->moduleExists($provider) || $this->themeHandler->themeExists($provider);
  }

  /**
   * Discover list of icons from definition extractor.
   *
   * @param array $definition
   *   The definition.
   *
   * @return array
   *   Discovered icons.
   */
  private function getIconsFromDefinition(array $definition): array {
    if (!isset($definition['extractor'])) {
      return [];
    }

    /** @var \Drupal\Core\Theme\Icon\IconExtractorInterface $extractor */
    $extractor = $this->iconPackExtractorManager->createInstance($definition['extractor'], $definition);
    return $extractor->discoverIcons();
  }

  /**
   * Validates a definition against the JSON schema specification.
   *
   * @param array $definition
   *   The definition to alter.
   *
   * @return bool
   *   FALSE if the response failed validation, otherwise TRUE.
   *
   * @throws \Drupal\Core\Theme\Icon\Exception\IconPackConfigErrorException
   *   Thrown when the definition is not valid.
   */
  private function validateDefinition(array $definition): bool {
    // If the validator isn't set, then the validation library is not installed.
    if (!$this->validator) {
      return TRUE;
    }

    $schema_ref = sprintf(
      'file://%s/%s',
      $this->appRoot,
      self::SCHEMA_VALIDATE
    );
    $schema = (object) ['$ref' => $schema_ref];

    $definition_object = Validator::arrayToObjectRecursive($definition);

    $this->validator->validate($definition_object, $schema, Constraint::CHECK_MODE_COERCE_TYPES);

    if ($this->validator->isValid()) {
      return TRUE;
    }

    $message_parts = array_map(
      static fn (array $error): string => sprintf("[%s] %s", $error['property'], $error['message']),
      $this->validator->getErrors()
    );
    $message = implode(", ", $message_parts);

    throw new IconPackConfigErrorException(
      sprintf(
        '%s:%s Error in definition `%s`:%s',
        $definition['provider'],
        $definition['id'],
        $definition_object->id,
        $message
      )
    );
  }

}
