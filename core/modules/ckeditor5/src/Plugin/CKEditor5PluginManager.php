<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin;

use Drupal\ckeditor5\Annotation\CKEditor5Plugin;
use Drupal\ckeditor5\HTMLRestrictions;
use Drupal\Component\Annotation\Plugin\Discovery\AnnotationBridgeDecorator;
use Drupal\Component\Assertion\Inspector;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Discovery\YamlDiscoveryDecorator;
use Drupal\editor\EditorInterface;
use Drupal\filter\FilterPluginCollection;

/**
 * Provides a CKEditor5 plugin manager.
 *
 * @see \Drupal\ckeditor5\Plugin\CKEditor5PluginInterface
 * @see \Drupal\ckeditor5\Plugin\CKEditor5PluginBase
 * @see \Drupal\ckeditor5\Annotation\CKEditor5Plugin
 * @see plugin_api
 *
 * @internal
 *   CKEditor 5 is currently experimental and should only be leveraged by
 *   experimental modules and development releases of contributed modules.
 *   See https://www.drupal.org/core/experimental for more information.
 */
class CKEditor5PluginManager extends DefaultPluginManager implements CKEditor5PluginManagerInterface {

  /**
   * Constructs a CKEditor5PluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/CKEditor5Plugin', $namespaces, $module_handler, CKEditor5PluginInterface::class, CKEditor5Plugin::class);

    $this->alterInfo('ckeditor5_plugin_info');
    $this->setCacheBackend($cache_backend, 'ckeditor5_plugins');
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    if (!$this->discovery) {
      $discovery = new AnnotatedClassDiscovery($this->subdir, $this->namespaces, $this->pluginDefinitionAnnotationName, $this->additionalAnnotationNamespaces);
      $discovery = new YamlDiscoveryDecorator($discovery, 'ckeditor5', $this->moduleHandler->getModuleDirectories());
      // Note: adding translatable properties here is impossible because it only
      // supports top-level properties.
      // @see \Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition::label()
      $discovery = new AnnotationBridgeDecorator($discovery, $this->pluginDefinitionAnnotationName);
      $this->discovery = $discovery;
    }
    return $this->discovery;
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugin(string $plugin_id, ?EditorInterface $editor): CKEditor5PluginInterface {
    $configuration = $editor
      ? self::getPluginConfiguration($editor, $plugin_id)
      : [];
    return $this->createInstance($plugin_id, $configuration);
  }

  /**
   * Gets the plugin configuration (if any) from a text editor config entity.
   *
   * @param \Drupal\editor\EditorInterface $editor
   *   A text editor config entity that is using CKEditor 5.
   * @param string $plugin_id
   *   A CKEditor 5 plugin ID.
   *
   * @return array
   *   The CKEditor 5 plugin configuration, if any.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the method is called with any other text editor than CKEditor 5.
   */
  protected static function getPluginConfiguration(EditorInterface $editor, string $plugin_id): array {
    if ($editor->getEditor() !== 'ckeditor5') {
      throw new \InvalidArgumentException('This method should only be called on text editor config entities using CKEditor 5.');
    }
    return $editor->getSettings()['plugins'][$plugin_id] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getToolbarItems(): array {
    return $this->mergeDefinitionValues('getToolbarItems', $this->getDefinitions());
  }

  /**
   * {@inheritdoc}
   */
  public function getAdminLibraries(): array {
    $list = $this->mergeDefinitionValues('getAdminLibrary', $this->getDefinitions());
    // Include main admin library.
    array_unshift($list, 'ckeditor5/admin');
    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function getEnabledLibraries(EditorInterface $editor): array {
    $list = $this->mergeDefinitionValues('getLibrary', $this->getEnabledDefinitions($editor));
    $list = array_unique($list);
    // Include main library.
    array_unshift($list, 'ckeditor5/drupal.ckeditor5');
    sort($list);
    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function getEnabledDefinitions(EditorInterface $editor): array {
    $definitions = $this->getDefinitions();
    ksort($definitions);

    $definitions_with_plugins_condition = [];

    foreach ($definitions as $plugin_id => $definition) {
      // Remove definition when plugin has conditions and they are not met.
      if ($definition->hasConditions()) {
        $plugin = $this->getPlugin($plugin_id, $editor);
        if ($this->isPluginDisabled($plugin, $editor)) {
          unset($definitions[$plugin_id]);
        }
        else {
          // The `plugins` condition can only be evaluated at the end of
          // gathering enabled definitions. ::isPluginDisabled() did not yet
          // evaluate that condition.
          if (array_key_exists('plugins', $definition->getConditions())) {
            $definitions_with_plugins_condition[$plugin_id] = $definition;
          }
        }
      }
      // Otherwise, only remove the definition if the plugin has buttons and
      // none of its buttons are active.
      elseif ($definition->hasToolbarItems()) {
        if (empty(array_intersect($editor->getSettings()['toolbar']['items'], array_keys($definition->getToolbarItems())))) {
          unset($definitions[$plugin_id]);
        }
      }
    }

    // Only enable the arbitrary HTML Support plugin on text formats with no
    // HTML restrictions.
    // @see https://ckeditor.com/docs/ckeditor5/latest/api/html-support.html
    // @see https://github.com/ckeditor/ckeditor5/issues/9856
    if ($editor->getFilterFormat()->getHtmlRestrictions() !== FALSE) {
      unset($definitions['ckeditor5_arbitraryHtmlSupport']);
    }

    // Evaluate `plugins` condition.
    foreach ($definitions_with_plugins_condition as $plugin_id => $definition) {
      if (!empty(array_diff($definition->getConditions()['plugins'], array_keys($definitions)))) {
        unset($definitions[$plugin_id]);
      }
    }

    if (!isset($definitions['ckeditor5_arbitraryHtmlSupport'])) {
      $restrictions = new HTMLRestrictions($this->getProvidedElements(array_keys($definitions), $editor, FALSE));
      if ($restrictions->getWildcardSubset()->allowsNothing()) {
        // This is only reached if arbitrary HTML is not enabled. If wildcard
        // tags (such as $text-container) are present, they need to
        // be resolved via the wildcardHtmlSupport plugin.
        // @see \Drupal\ckeditor5\Plugin\CKEditor5PluginManager::getCKEditor5PluginConfig()
        unset($definitions['ckeditor5_wildcardHtmlSupport']);
      }
    }
    // When arbitrary HTML is already supported, there is no need to support
    // wildcard tags.
    else {
      unset($definitions['ckeditor5_wildcardHtmlSupport']);
    }

    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function findPluginSupportingElement(string $tag): ?string {
    // This will contain the element config for a plugin found to support $tag,
    // so it can be compared to additional plugins that support $tag so the
    // plugin with the most permissive config can be the id returned.
    $selected_provided_elements = [];
    $plugin_id = NULL;

    foreach ($this->getDefinitions() as $id => $definition) {
      $provided_elements = $this->getProvidedElements([$id]);

      // Multiple plugins may support the $tag being searched for.
      if (array_key_exists($tag, $provided_elements)) {
        // Skip plugins with conditions as those plugins can't be guaranteed to
        // provide a given tag without additional criteria being met. In the
        // future we could possibly add support for automatically enabling
        // filters or other similar requirements a plugin might need in order to
        // be enabled and provide the tag it supports. For now, we assume such
        // configuration cannot be modified programmatically.
        if ($definition->hasConditions()) {
          continue;
        }

        // True if a plugin has already been selected. If another plugin
        // supports $tag, it will be compared against this one. Whichever
        // provides broader support for $tag will be the plugin id returned by
        // this method.
        $selected_plugin = isset($selected_provided_elements[$tag]);
        $selected_config = $selected_provided_elements[$tag] ?? FALSE;

        // True if a plugin supporting $tag has been selected but does not allow
        // any attributes while the plugin currently being checked does support
        // attributes.
        $adds_attribute_config = is_array($provided_elements[$tag]) && $selected_plugin && !is_array($selected_config);
        $broader_attribute_config = FALSE;

        // If the selected plugin and the plugin being checked both have arrays
        // for $tag configuration, they both have attribute configuration. Check
        // which attribute configuration is more permissive.
        if ($selected_plugin && is_array($selected_config) && is_array($provided_elements[$tag])) {
          $selected_plugin_full_attributes = array_filter($selected_config, function ($attribute_config) {
            return !is_array($attribute_config);
          });
          $being_checked_plugin_full_attributes = array_filter($provided_elements[$tag], function ($attribute_config) {
            return !is_array($attribute_config);
          });
          if (count($being_checked_plugin_full_attributes) > count($selected_plugin_full_attributes)) {
            $broader_attribute_config = TRUE;
          }
        }

        if (empty($selected_provided_elements) || $broader_attribute_config || $adds_attribute_config) {
          $selected_provided_elements = $provided_elements;
          $plugin_id = $id;
        }
      }
    }

    return $plugin_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getCKEditor5PluginConfig(EditorInterface $editor): array {
    $definitions = $this->getEnabledDefinitions($editor);

    // Allow plugin to modify config, such as loading dynamic values.
    $config = [];
    foreach ($definitions as $plugin_id => $definition) {
      $plugin = $this->getPlugin($plugin_id, $editor);
      $config[$plugin_id] = $plugin->getDynamicPluginConfig($definition->getCKEditor5Config(), $editor);
    }

    // CKEditor 5 interprets wildcards from a "CKEditor 5 model element"
    // perspective, Drupal interprets wildcards from a "HTML element"
    // perspective. GHS is used to reconcile those two perspectives, to ensure
    // all expected HTML elements truly are supported.
    // The `ckeditor5_wildcardHtmlSupport` is automatically enabled when
    // necessary, and only when necessary.
    // @see \Drupal\ckeditor5\Plugin\CKEditor5PluginManager::getEnabledDefinitions()
    if (isset($definitions['ckeditor5_wildcardHtmlSupport'])) {
      $allowed_elements = new HTMLRestrictions($this->getProvidedElements(array_keys($definitions), $editor, FALSE));
      // Compute the net new elements that the wildcard tags resolve into.
      $concrete_allowed_elements = $allowed_elements->getConcreteSubset();
      $net_new_elements = $allowed_elements->diff($concrete_allowed_elements);
      $config['ckeditor5_wildcardHtmlSupport'] = [
        'htmlSupport' => [
          'allow' => $net_new_elements->toGeneralHtmlSupportConfig(),
        ],
      ];
    }

    return [
      'plugins' => $this->mergeDefinitionValues('getCKEditor5Plugins', $definitions),
      'config' => NestedArray::mergeDeepArray($config),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getProvidedElements(array $plugin_ids = [], EditorInterface $editor = NULL, bool $resolve_wildcards = TRUE, bool $creatable_elements_only = FALSE): array {
    $plugins = $this->getDefinitions();
    if (!empty($plugin_ids)) {
      $plugins = array_intersect_key($plugins, array_flip($plugin_ids));
    }
    $elements = HTMLRestrictions::emptySet();

    foreach ($plugins as $id => $definition) {
      // Some CKEditor 5 plugins only provide functionality, not additional
      // elements.
      if (!$definition->hasElements()) {
        continue;
      }

      $defined_elements = $definition->getElements();
      if (is_a($definition->getClass(), CKEditor5PluginElementsSubsetInterface::class, TRUE)) {
        // ckeditor5_sourceEditing is the edge case here: it is the only plugin
        // that is allowed to return a superset. It's a special case because it
        // is through configuring this particular plugin that additional HTML
        // tags can be allowed.
        // The list of tags it supports is generated dynamically. In its default
        // configuration it does support any HTML tags.
        if ($id === 'ckeditor5_sourceEditing') {
          $defined_elements = !isset($editor) ? [] : $this->getPlugin($id, $editor)->getElementsSubset();
        }
        // The default case: all other plugins that implement this interface are
        // explicitly checked for compliance: only subsets are allowed. This is
        // essential for \Drupal\ckeditor5\SmartDefaultSettings to be able to
        // work: otherwise it would not be able to know which plugins to enable.
        elseif (isset($editor)) {
          $subset = $this->getPlugin($id, $editor)->getElementsSubset();
          $subset_restrictions = HTMLRestrictions::fromString(implode($subset));
          $defined_restrictions = HTMLRestrictions::fromString(implode($defined_elements));
          $subset_violations = $subset_restrictions->diff($defined_restrictions)->toCKEditor5ElementsArray();
          if (!empty($subset_violations)) {
            throw new \LogicException(sprintf('The "%s" CKEditor 5 plugin implements ::getElementsSubset() and did not return a subset, the following tags are absent from the plugin definition: "%s".', $id, implode(' ', $subset_violations)));
          }
          // Also detect what is technically a valid subset, but has lost the
          // ability to create tags that are still in the subset. This points to
          // a bug in the plugin's ::getElementsSubset() logic.
          $defined_creatable = HTMLRestrictions::fromString(implode($definition->getCreatableElements()));
          $subset_creatable_actual = HTMLRestrictions::fromString(implode(array_filter($subset, [CKEditor5PluginDefinition::class, 'isCreatableElement'])));
          $subset_creatable_needed = $subset_restrictions->extractPlainTagsSubset()
            ->intersect($defined_creatable);
          $missing_creatable_for_subset = $subset_creatable_needed->diff($subset_creatable_actual);
          if (!$missing_creatable_for_subset->allowsNothing()) {
            throw new \LogicException(sprintf('The "%s" CKEditor 5 plugin implements ::getElementsSubset() and did return a subset ("%s") but the following tags can no longer be created: "%s".', $id, implode($subset_restrictions->toCKEditor5ElementsArray()), implode($missing_creatable_for_subset->toCKEditor5ElementsArray())));
          }
          $defined_elements = $subset;
        }
      }
      assert(Inspector::assertAllStrings($defined_elements));
      if ($creatable_elements_only) {
        // @see \Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition::getCreatableElements()
        $defined_elements = array_filter($defined_elements, [CKEditor5PluginDefinition::class, 'isCreatableElement']);
      }
      foreach ($defined_elements as $element) {
        $additional_elements = HTMLRestrictions::fromString($element);
        $elements = $elements->merge($additional_elements);
      }
    }

    return $elements->getAllowedElements($resolve_wildcards);
  }

  /**
   * Returns array of merged values for the given plugin definitions.
   *
   * @param string $get_method
   *   Which CKEditor5PluginDefinition getter to call to get values to merge.
   * @param \Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition[] $definitions
   *   The plugin definitions whose values to merge.
   *
   * @return array
   *   List of merged values for the given plugin definition method.
   */
  protected function mergeDefinitionValues(string $get_method, array $definitions): array {
    assert(method_exists(CKEditor5PluginDefinition::class, $get_method));
    $has_method = 'has' . substr($get_method, 3);
    assert(method_exists(CKEditor5PluginDefinition::class, $has_method));
    $per_plugin = array_filter(array_map(function (CKEditor5PluginDefinition $definition) use ($get_method, $has_method) {
      if ($definition->$has_method()) {
        return $definition->$get_method();
      }
    }, $definitions));
    return array_reduce($per_plugin, function (array $result, $current): array {
      return is_array($current) && is_array(reset($current))
        // Merge nested arrays using their keys.
        ? $result + $current
        // Merge everything else by appending.
        : array_merge($result, (array) $current);
    }, []);
  }

  /**
   * Checks whether a plugin must be disabled due to unmet conditions.
   *
   * @param \Drupal\ckeditor5\Plugin\CKEditor5PluginInterface $plugin
   *   A CKEditor 5 plugin instance.
   * @param \Drupal\editor\EditorInterface $editor
   *   A configured text editor object.
   *
   * @return bool
   *   Whether the plugin is disabled due to unmet conditions.
   */
  protected function isPluginDisabled(CKEditor5PluginInterface $plugin, EditorInterface $editor): bool {
    assert($plugin->getPluginDefinition()->hasConditions());
    foreach ($plugin->getPluginDefinition()->getConditions() as $condition_type => $required_value) {
      switch ($condition_type) {
        case 'toolbarItem':
          if (!in_array($required_value, $editor->getSettings()['toolbar']['items'])) {
            return TRUE;
          }
          break;

        case 'imageUploadStatus':
          $image_upload_status = $editor->getImageUploadSettings()['status'] ?? FALSE;
          if (!$image_upload_status) {
            return TRUE;
          }
          break;

        case 'filter':
          $filters = $editor->getFilterFormat()->filters();
          assert($filters instanceof FilterPluginCollection);
          if (!$filters->has($required_value) || !$filters->get($required_value)->status) {
            return TRUE;
          }
          break;

        case 'requiresConfiguration':
          $intersection = array_intersect($plugin->getConfiguration(), $required_value);
          return $intersection !== $required_value;

        case 'plugins':
          // Tricky: this cannot yet be evaluated here. It will evaluated later.
          // @see \Drupal\ckeditor5\Plugin\CKEditor5PluginManager::getEnabledDefinitions()
          return FALSE;
      }
    }

    return FALSE;
  }

}
