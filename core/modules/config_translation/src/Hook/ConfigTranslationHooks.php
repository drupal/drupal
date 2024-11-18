<?php

namespace Drupal\config_translation\Hook;

use Drupal\field\FieldConfigInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for config_translation.
 */
class ConfigTranslationHooks {

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    switch ($route_name) {
      case 'help.page.config_translation':
        $output = '';
        $output .= '<h2>' . t('About') . '</h2>';
        $output .= '<p>' . t('The Configuration Translation module allows you to translate configuration text; for example, the site name, vocabularies, menus, or date formats. Together with the modules <a href=":language">Language</a>, <a href=":content-translation">Content Translation</a>, and <a href=":locale">Interface Translation</a>, it allows you to build multilingual websites. For more information, see the <a href=":doc_url">online documentation for the Configuration Translation module</a>.', [
          ':doc_url' => 'https://www.drupal.org/documentation/modules/config_translation',
          ':config' => Url::fromRoute('help.page', [
            'name' => 'config',
          ])->toString(),
          ':language' => Url::fromRoute('help.page', [
            'name' => 'language',
          ])->toString(),
          ':locale' => Url::fromRoute('help.page', [
            'name' => 'locale',
          ])->toString(),
          ':content-translation' => \Drupal::moduleHandler()->moduleExists('content_translation') ? Url::fromRoute('help.page', [
            'name' => 'content_translation',
          ])->toString() : '#',
        ]) . '</p>';
        $output .= '<h2>' . t('Uses') . '</h2>';
        $output .= '<dl>';
        $output .= '<dt>' . t('Enabling translation') . '</dt>';
        $output .= '<dd>' . t('In order to translate configuration, the website must have at least two <a href=":url">languages</a>.', [
          ':url' => Url::fromRoute('entity.configurable_language.collection')->toString(),
        ]) . '</dd>';
        $output .= '<dt>' . t('Translating configuration text') . '</dt>';
        $output .= '<dd>' . t('Users with the <em>Translate user edited configuration</em> permission can access the configuration translation overview, and manage translations for specific languages. The <a href=":translation-page">Configuration translation</a> page shows a list of all configuration text that can be translated, either as individual items or as lists. After you click on <em>Translate</em>, you are provided with a list of all languages. You can <em>add</em> or <em>edit</em> a translation for a specific language. Users with specific configuration permissions can also <em>edit</em> the text for the site\'s default language. For some configuration text items (for example for the site information), the specific translation pages can also be accessed directly from their configuration pages.', [
          ':translation-page' => Url::fromRoute('config_translation.mapper_list')->toString(),
        ]) . '</dd>';
        $output .= '<dt>' . t('Translating date formats') . '</dt>';
        $output .= '<dd>' . t('You can choose to translate date formats on the <a href=":translation-page">Configuration translation</a> page. This allows you not only to translate the label text, but also to set a language-specific <em>PHP date format</em>.', [
          ':translation-page' => Url::fromRoute('config_translation.mapper_list')->toString(),
        ]) . '</dd>';
        $output .= '</dl>';
        return $output;

      case 'config_translation.mapper_list':
        $output = '<p>' . t('This page lists all configuration items on your site that have translatable text, like your site name, role names, etc.') . '</p>';
        return $output;
    }
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme() : array {
    return [
      'config_translation_manage_form_element' => [
        'render element' => 'element',
        'template' => 'config_translation_manage_form_element',
      ],
    ];
  }

  /**
   * Implements hook_themes_installed().
   */
  #[Hook('themes_installed')]
  public function themesInstalled() {
    // Themes can provide *.config_translation.yml declarations.
    // @todo Make ThemeHandler trigger an event instead and make
    //   ConfigMapperManager plugin manager subscribe to it.
    // @see https://www.drupal.org/node/2206347
    \Drupal::service('plugin.manager.config_translation.mapper')->clearCachedDefinitions();
  }

  /**
   * Implements hook_themes_uninstalled().
   */
  #[Hook('themes_uninstalled')]
  public function themesUninstalled() {
    // Themes can provide *.config_translation.yml declarations.
    // @todo Make ThemeHandler trigger an event instead and make
    //   ConfigMapperManager plugin manager subscribe to it.
    // @see https://www.drupal.org/node/2206347
    \Drupal::service('plugin.manager.config_translation.mapper')->clearCachedDefinitions();
  }

  /**
   * Implements hook_entity_type_alter().
   */
  #[Hook('entity_type_alter')]
  public function entityTypeAlter(array &$entity_types) : void {
    /** @var \Drupal\Core\Entity\EntityTypeInterface[] $entity_types */
    foreach ($entity_types as $entity_type_id => $entity_type) {
      if ($entity_type->entityClassImplements(ConfigEntityInterface::class)) {
        if ($entity_type_id == 'block') {
          $class = 'Drupal\config_translation\Controller\ConfigTranslationBlockListBuilder';
        }
        elseif ($entity_type_id == 'field_config') {
          $class = 'Drupal\config_translation\Controller\ConfigTranslationFieldListBuilder';
          // Will be filled in dynamically, see \Drupal\field\Entity\FieldConfig::linkTemplates().
          $entity_type->setLinkTemplate('config-translation-overview', $entity_type->getLinkTemplate('edit-form') . '/translate');
        }
        else {
          $class = 'Drupal\config_translation\Controller\ConfigTranslationEntityListBuilder';
        }
        $entity_type->setHandlerClass('config_translation_list', $class);
        if ($entity_type->hasLinkTemplate('edit-form')) {
          $entity_type->setLinkTemplate('config-translation-overview', $entity_type->getLinkTemplate('edit-form') . '/translate');
        }
      }
    }
  }

  /**
   * Implements hook_config_translation_info().
   */
  #[Hook('config_translation_info')]
  public function configTranslationInfo(&$info) {
    $entity_type_manager = \Drupal::entityTypeManager();
    // If field UI is not enabled, the base routes of the type
    // "entity.field_config.{$entity_type}_field_edit_form" are not defined.
    if (\Drupal::moduleHandler()->moduleExists('field_ui')) {
      // Add fields entity mappers to all fieldable entity types defined.
      foreach ($entity_type_manager->getDefinitions() as $entity_type_id => $entity_type) {
        // Make sure entity type has field UI enabled and has a base route.
        if ($entity_type->get('field_ui_base_route')) {
          $info[$entity_type_id . '_fields'] = [
            'base_route_name' => "entity.field_config.{$entity_type_id}_field_edit_form",
            'entity_type' => 'field_config',
            'class' => '\Drupal\config_translation\ConfigFieldMapper',
            'base_entity_type' => $entity_type_id,
            'weight' => 10,
          ];
        }
      }
    }
    // Discover configuration entities automatically.
    foreach ($entity_type_manager->getDefinitions() as $entity_type_id => $entity_type) {
      // Determine base path for entities automatically if provided via the
      // configuration entity.
      if (!$entity_type->entityClassImplements(ConfigEntityInterface::class) || !$entity_type->hasLinkTemplate('edit-form')) {
        // Do not record this entity mapper if the entity type does not
        // provide a base route. We'll surely not be able to do anything with
        // it anyway. Configuration entities with a dynamic base path, such as
        // fields, need special treatment. See above.
        continue;
      }
      // Use the entity type as the plugin ID.
      $base_route_name = "entity.{$entity_type_id}.edit_form";
      $info[$entity_type_id] = [
        'class' => '\Drupal\config_translation\ConfigEntityMapper',
        'base_route_name' => $base_route_name,
        'title' => $entity_type->getSingularLabel(),
        'names' => [],
        'entity_type' => $entity_type_id,
        'weight' => 10,
      ];
    }
  }

  /**
   * Implements hook_entity_operation().
   */
  #[Hook('entity_operation')]
  public function entityOperation(EntityInterface $entity) {
    $operations = [];
    $entity_type = $entity->getEntityType();
    if ($entity_type->entityClassImplements(ConfigEntityInterface::class) && $entity->hasLinkTemplate('config-translation-overview') && \Drupal::currentUser()->hasPermission('translate configuration')) {
      $link_template = 'config-translation-overview';
      if ($entity instanceof FieldConfigInterface) {
        $link_template = "config-translation-overview.{$entity->getTargetEntityTypeId()}";
      }
      $operations['translate'] = [
        'title' => t('Translate'),
        'weight' => 50,
        'url' => $entity->toUrl($link_template),
      ];
    }
    return $operations;
  }

  /**
   * Implements hook_config_schema_info_alter().
   */
  #[Hook('config_schema_info_alter')]
  public function configSchemaInfoAlter(&$definitions): void {
    $map = [
      'label' => '\Drupal\config_translation\FormElement\Textfield',
      'text' => '\Drupal\config_translation\FormElement\Textarea',
      'date_format' => '\Drupal\config_translation\FormElement\DateFormat',
      'text_format' => '\Drupal\config_translation\FormElement\TextFormat',
      'mapping' => '\Drupal\config_translation\FormElement\ListElement',
      'sequence' => '\Drupal\config_translation\FormElement\ListElement',
      'plural_label' => '\Drupal\config_translation\FormElement\PluralVariants',
    ];
    // Enhance the text and date type definitions with classes to generate proper
    // form elements in ConfigTranslationFormBase. Other translatable types will
    // appear as a one line textfield.
    foreach ($definitions as $type => &$definition) {
      if (isset($map[$type]) && !isset($definition['form_element_class'])) {
        $definition['form_element_class'] = $map[$type];
      }
    }
  }

}
