<?php

namespace Drupal\Core\Theme;

use Drupal\Component\Assertion\Inspector;
use Drupal\Component\Discovery\YamlDirectoryDiscovery;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Factory\ContainerFactory;
use Drupal\Core\Theme\Component\ComponentValidator;
use Drupal\Core\Theme\Component\SchemaCompatibilityChecker;
use Drupal\Core\Plugin\Component;
use Drupal\Core\Render\Component\Exception\ComponentNotFoundException;
use Drupal\Core\Render\Component\Exception\IncompatibleComponentSchema;
use Drupal\Core\Plugin\Discovery\DirectoryWithMetadataPluginDiscovery;

/**
 * Defines a plugin manager to deal with components.
 *
 * Modules and themes can create components by adding a folder under
 * MODULENAME/components/my-component/my-component.component.yml.
 *
 * @see plugin_api
 */
class ComponentPluginManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  protected $defaults = [
    'class' => Component::class,
  ];

  /**
   * Constructs ComponentPluginManager object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   *   The theme handler.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $themeManager
   *   The theme manager.
   * @param \Drupal\Core\Theme\ComponentNegotiator $componentNegotiator
   *   The component negotiator.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system service.
   * @param \Drupal\Core\Theme\Component\SchemaCompatibilityChecker $compatibilityChecker
   *   The compatibility checker.
   * @param \Drupal\Core\Theme\Component\ComponentValidator $componentValidator
   *   The component validator.
   * @param string $appRoot
   *   The application root.
   */
  public function __construct(
    ModuleHandlerInterface $module_handler,
    protected ThemeHandlerInterface $themeHandler,
    CacheBackendInterface $cacheBackend,
    protected ConfigFactoryInterface $configFactory,
    protected ThemeManagerInterface $themeManager,
    protected ComponentNegotiator $componentNegotiator,
    protected FileSystemInterface $fileSystem,
    protected SchemaCompatibilityChecker $compatibilityChecker,
    protected ComponentValidator $componentValidator,
    protected string $appRoot,
  ) {
    // We are skipping the call to the parent constructor to avoid initializing
    // variables aimed for annotation discovery, that are unnecessary here.
    // Plugin managers using YAML discovery also skip the parent constructor,
    // like LinkRelationTypeManager.
    $this->moduleHandler = $module_handler;
    $this->factory = new ContainerFactory($this);
    $this->setCacheBackend($cacheBackend, 'component_plugins');
    // Note that we are intentionally skipping $this->alterInfo('component_info');
    // We want to ensure that everything related to a component is in the
    // single directory. If the alteration of a component is necessary,
    // component replacement is the preferred tool for that.
  }

  /**
   * Creates an instance.
   *
   * @throws \Drupal\Core\Render\Component\Exception\ComponentNotFoundException
   *
   * @internal
   */
  public function createInstance($plugin_id, array $configuration = []): Component {
    $configuration['app_root'] = $this->appRoot;
    $configuration['enforce_schemas'] = $this->shouldEnforceSchemas(
      $this->definitions[$plugin_id] ?? []
    );
    try {
      $instance = parent::createInstance($plugin_id, $configuration);
      if (!$instance instanceof Component) {
        throw new ComponentNotFoundException(sprintf(
          'Unable to find component "%s" in the component repository.',
          $plugin_id,
        ));
      }
      return $instance;
    }
    catch (PluginException $e) {
      // Cast the PluginNotFound to a more specific exception.
      $message = sprintf(
        'Unable to find component "%s" in the component repository. [%s]',
        $plugin_id,
        $e->getMessage()
      );
      throw new ComponentNotFoundException($message, $e->getCode(), $e);
    }
  }

  /**
   * Gets a component for rendering.
   *
   * @param string $component_id
   *   The component ID.
   *
   * @return \Drupal\Core\Plugin\Component
   *   The component.
   *
   * @throws \Drupal\Core\Render\Component\Exception\ComponentNotFoundException
   */
  public function find(string $component_id): Component {
    $definitions = $this->getDefinitions();
    if (empty($definitions)) {
      throw new ComponentNotFoundException('Unable to find any component definition.');
    }
    $negotiated_plugin_id = $this->componentNegotiator->negotiate($component_id, $definitions);
    return $this->createInstance($negotiated_plugin_id ?? $component_id);
  }

  /**
   * Gets all components.
   *
   * @return \Drupal\Core\Plugin\Component[]
   *   An array of Component objects.
   */
  public function getAllComponents(): array {
    $plugin_ids = array_keys($this->getDefinitions());
    return array_values(array_filter(array_map(
      [$this, 'createInstance'],
      $plugin_ids
    )));
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedDefinitions(): void {
    parent::clearCachedDefinitions();
    $this->componentNegotiator->clearCache();
  }

  /**
   * Creates the library declaration array from a component definition.
   *
   * @param array $definition
   *   The component definition.
   *
   * @return array
   *   The library for the Library API.
   */
  protected function libraryFromDefinition(array $definition): array {
    $metadata_path = $definition[YamlDirectoryDiscovery::FILE_KEY];
    $component_directory = $this->fileSystem->dirname($metadata_path);
    // Add the JS and CSS files.
    $library = [];
    $css_file = $this->findAsset(
      $component_directory,
      $definition['machineName'],
      'css',
      TRUE
    );
    if ($css_file) {
      $library['css']['component'][$css_file] = [];
    }
    $js_file = $this->findAsset(
      $component_directory,
      $definition['machineName'],
      'js',
      TRUE
    );
    if ($js_file) {
      $library['js'][$js_file] = [];
    }
    // We allow component authors to use library overrides to use files relative
    // to the component directory. So we need to fix the paths here.
    if (!empty($definition['libraryOverrides'])) {
      $overrides = $this->translateLibraryPaths(
        $definition['libraryOverrides'],
        $component_directory
      );
      // Apply library overrides.
      $library = array_merge(
        $library,
        $overrides
      );
      // Ensure that 'core/drupal' is always a dependency. This will ensure that
      // JS behaviors are attached.
      $library['dependencies'][] = 'core/drupal';
      $library['dependencies'] = array_unique($library['dependencies']);
    }

    return $library;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery(): DirectoryWithMetadataPluginDiscovery {
    if (!isset($this->discovery)) {
      $directories = $this->getScanDirectories();
      $this->discovery = new DirectoryWithMetadataPluginDiscovery($directories, 'component', $this->fileSystem);
    }
    return $this->discovery;
  }

  /**
   * {@inheritdoc}
   */
  protected function providerExists($provider) {
    return $this->moduleHandler->moduleExists($provider) || $this->themeHandler->themeExists($provider);
  }

  /**
   * {@inheritdoc}
   */
  protected function alterDefinitions(&$definitions) {
    // Save in the definition whether this is a module or a theme. This is
    // important because when creating the plugin instance (the Component
    // object) we'll need to negotiate based on the active theme.
    $definitions = array_map([$this, 'alterDefinition'], $definitions);
    // Validate the definition after alterations.
    assert(
      Inspector::assertAll(
        fn(array $definition) => $this->isValidDefinition($definition),
        $definitions
      )
    );
    parent::alterDefinitions($definitions);

    // Finally, validate replacements.
    $replacing_definitions = array_filter(
      $definitions,
      static fn(array $definition) => ($definition['replaces'] ?? NULL) && ($definitions[$definition['replaces']] ?? NULL)
    );
    $validation_errors = array_reduce($replacing_definitions, function (array $errors, array $new_definition) use ($definitions) {
      $original_definition = $definitions[$new_definition['replaces']];
      $original_schemas = $original_definition['props'] ?? NULL;
      $new_schemas = $new_definition['props'] ?? NULL;
      if (!$original_schemas || !$new_schemas) {
        return [
          sprintf(
            "Component \"%s\" is attempting to replace \"%s\", however component replacement requires both components to have schema definitions.",
            $new_definition['id'],
            $original_definition['id'],
          ),
        ];
      }
      try {
        $this->compatibilityChecker->isCompatible(
          $original_schemas,
          $new_schemas
        );
      }
      catch (IncompatibleComponentSchema $e) {
        $errors[] = sprintf(
          "\"%s\" is incompatible with the component is wants to replace \"%s\". Errors:\n%s",
          $new_definition['id'],
          $original_definition['id'],
          $e->getMessage()
        );
      }
      return $errors;
    }, []);
    if (!empty($validation_errors)) {
      throw new IncompatibleComponentSchema(implode("\n", $validation_errors));
    }
  }

  /**
   * Alters the plugin definition with computed properties.
   *
   * @param array $definition
   *   The definition.
   *
   * @return array
   *   The altered definition.
   */
  protected function alterDefinition(array $definition): array {
    $definition['extension_type'] = $this->moduleHandler->moduleExists($definition['provider'])
      ? ExtensionType::Module
      : ExtensionType::Theme;
    $metadata_path = $definition[YamlDirectoryDiscovery::FILE_KEY];
    $component_directory = $this->fileSystem->dirname($metadata_path);
    $definition['path'] = $component_directory;
    [, $machine_name] = explode(':', $definition['id']);
    $definition['machineName'] = $machine_name;
    $definition['library'] = $this->libraryFromDefinition($definition);
    // Discover the template.
    $template = $this->findAsset(
      $component_directory,
      $definition['machineName'],
      'twig'
    );
    $definition['template'] = basename($template);
    $definition['documentation'] = 'No documentation found. Add a README.md in your component directory.';
    $documentation_path = sprintf('%s/README.md', $this->fileSystem->dirname($metadata_path));
    if (file_exists($documentation_path)) {
      $definition['documentation'] = file_get_contents($documentation_path);
    }
    return $definition;
  }

  /**
   * Validates the metadata info.
   *
   * @param array $definition
   *   The component definition.
   *
   * @return bool
   *   TRUE if it's valid.
   *
   * @throws \Drupal\Core\Render\Component\Exception\InvalidComponentException
   */
  private function isValidDefinition(array $definition): bool {
    return $this->componentValidator->validateDefinition(
      $definition,
      $this->shouldEnforceSchemas($definition)
    );
  }

  /**
   * Get the list of directories to scan.
   *
   * @return string[]
   *   The directories.
   */
  private function getScanDirectories(): array {
    $extension_directories = [
      ...$this->moduleHandler->getModuleDirectories(),
      ...$this->themeHandler->getThemeDirectories(),
    ];
    return array_map(
      static fn(string $path) => rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'components',
      $extension_directories
    );
  }

  /**
   * Changes the library paths, so they can be used by the library system.
   *
   * We need this so we can let users apply overrides to JS and CSS files with
   * paths relative to the component.
   *
   * @param array $overrides
   *   The library overrides as provided by the component author.
   * @param string $component_directory
   *   The directory for the component.
   *
   * @return array
   *   The overrides with the fixed paths.
   */
  private function translateLibraryPaths(array $overrides, string $component_directory): array {
    // We only alter the keys of the CSS and JS entries.
    $altered_overrides = $overrides;
    unset($altered_overrides['css'], $altered_overrides['js']);
    $css = $overrides['css'] ?? [];
    $js = $overrides['js'] ?? [];
    foreach ($css as $dir => $css_info) {
      foreach ($css_info as $filename => $options) {
        if (!UrlHelper::isExternal($filename)) {
          $absolute_filename = sprintf('%s%s%s', $component_directory, DIRECTORY_SEPARATOR, $filename);
          $altered_filename = $this->makePathRelativeToLibraryRoot($absolute_filename);
          $altered_overrides['css'][$dir][$altered_filename] = $options;
        }
        else {
          $altered_overrides['css'][$dir][$filename] = $options;
        }
      }
    }
    foreach ($js as $filename => $options) {
      if (!UrlHelper::isExternal($filename)) {
        $absolute_filename = sprintf('%s%s%s', $component_directory, DIRECTORY_SEPARATOR, $filename);
        $altered_filename = $this->makePathRelativeToLibraryRoot($absolute_filename);
        $altered_overrides['js'][$altered_filename] = $options;
      }
      else {
        $altered_overrides['js'][$filename] = $options;
      }
    }
    return $altered_overrides;
  }

  /**
   * Assess whether schemas are mandatory for props.
   *
   * Schemas are always mandatory for component provided by modules. It depends
   * on a theme setting for theme components.
   *
   * @param array $definition
   *   The plugin definition.
   *
   * @return bool
   *   TRUE if schemas are mandatory.
   */
  private function shouldEnforceSchemas(array $definition): bool {
    $provider_type = $definition['extension_type'] ?? NULL;
    if ($provider_type !== ExtensionType::Theme) {
      return TRUE;
    }
    return $this->themeHandler
      ->getTheme($definition['provider'])
      ?->info['enforce_prop_schemas'] ?? FALSE;
  }

  /**
   * Finds assets related to the provided metadata file.
   *
   * @param string $component_directory
   *   The component directory for the plugin.
   * @param string $machine_name
   *   The component's machine name.
   * @param string $file_extension
   *   The file extension to detect.
   * @param bool $make_relative
   *   TRUE to make the filename relative to the core folder.
   *
   * @return string|null
   *   Filenames, maybe relative to the core folder.
   */
  private function findAsset(string $component_directory, string $machine_name, string $file_extension, bool $make_relative = FALSE): ?string {
    $absolute_path = sprintf('%s%s%s.%s', $component_directory, DIRECTORY_SEPARATOR, $machine_name, $file_extension);
    if (!file_exists($absolute_path)) {
      return NULL;
    }
    return $make_relative
      ? $this->makePathRelativeToLibraryRoot($absolute_path)
      : $absolute_path;
  }

  /**
   * Takes a path and makes it relative to the library provider.
   *
   * Drupal will take a path relative to the library provider in order to put
   * CSS and JS in the HTML page. Core is the provider for all the
   * auto-generated libraries for the components. This means that in order to
   * add <root>/themes/custom/my_theme/components/my-component/my-component.css
   * in the page, we need to crawl back up from <root>/core first:
   * ../themes/custom/my_theme/components/my-component/my-component.css.
   *
   * @param string $path
   *   The path to the file.
   *
   * @return string
   *   The path relative to the library provider root.
   */
  private function makePathRelativeToLibraryRoot(string $path): string {
    $path_from_root = str_starts_with($path, $this->appRoot)
      ? substr($path, strlen($this->appRoot) + 1)
      : $path;
    // Make sure this works seamlessly in every OS.
    $path_from_root = str_replace(DIRECTORY_SEPARATOR, '/', $path_from_root);
    // The library owner is in <root>/core, so we need to go one level up to
    // find the app root.
    return '../' . $path_from_root;
  }

}
