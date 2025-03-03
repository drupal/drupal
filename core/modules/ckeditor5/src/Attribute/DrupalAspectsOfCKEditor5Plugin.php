<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Attribute;

use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Define a Drupal aspects of CKEditor5 plugin.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class DrupalAspectsOfCKEditor5Plugin extends Plugin {

  /**
   * Constructs a DrupalAspectsOfCKEditor5Plugin attribute.
   *
   * @param string|\Drupal\Core\StringTranslation\TranslatableMarkup|null $label
   *   (optional) The human-readable name of the CKEditor plugin. Required
   *   unless set by deriver.
   * @param class-string $class
   *   (optional) The CKEditor 5 plugin class.If not specified, the
   *   CKEditor5PluginDefault class is used.
   * @param string|false $library
   *   (optional) The library this plugin requires.
   * @param string|false $admin_library
   *   (optional) The admin library this plugin provides.
   * @param string[]|false|null $elements
   *   (optional) List of elements and attributes provided. An array of strings,
   *   or false if no elements are provided. Required unless set by deriver.
   *   Syntax for each array value:
   *   - <element> only allows that HTML element with no attributes
   *   - <element attrA attrB> only allows that HTML element with attributes
   *     attrA and attrB, and any value for those attributes.
   *   - <element attrA="foo bar baz" attrB="qux-*"> only allows that HTML
   *     element with attributes attrA (if attrA contains one of the three
   *     listed values) and attrB (if its value has the provided prefix).
   *   - <element data-*> only allows that HTML element with any attribute that
   *     has the given prefix.
   *   Note that <element> means such an element (tag) can be created, whereas
   *   <element attrA attrB> means that `attrA` and `attrB` can be created on
   *   the tag. If a plugin supports both creating the element as well as
   *   setting some attributes or attribute values on it, it should have
   *   distinct entries in the list.
   *   For example, for a link plugin: `<a>` and `<a href>`. The first indicates
   *   the plugin can create such tags, the second indicates it can set the
   *   `href` attribute on it. If the first were omitted, the Drupal CKEditor 5
   *   module would interpret that as "this plugin cannot create `<a>`, it can
   *   only set the `href` attribute on it".
   * @param array $toolbar_items
   *   (optional) List of toolbar items the plugin provides.
   * @param array|false $conditions
   *   (optional) List of conditions to enable this plugin.
   * @param class-string|null $deriver
   *   (optional) The deriver class.
   *
   * @see \Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition::getCreatableElements()
   */
  public function __construct(
    public readonly string|TranslatableMarkup|null $label = NULL,
    public string $class = CKEditor5PluginDefault::class,
    public readonly string|false $library = FALSE,
    public readonly string|false $admin_library = FALSE,
    public readonly array|false|null $elements = NULL,
    public readonly array $toolbar_items = [],
    public readonly array|false $conditions = FALSE,
    public readonly ?string $deriver = NULL,
  ) {}

}
