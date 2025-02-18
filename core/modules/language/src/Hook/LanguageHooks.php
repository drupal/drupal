<?php

namespace Drupal\language\Hook;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrlFallback;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUI;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Entity\ContentEntityFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Installer\InstallerKernel;
use Drupal\language\Entity\ContentLanguageSettings;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for language.
 */
class LanguageHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match): ?string {
    switch ($route_name) {
      case 'help.page.language':
        $output = '';
        $output .= '<h2>' . $this->t('About') . '</h2>';
        $output .= '<p>' . $this->t('The Language module allows you to configure the languages used on your site, and provides information for the <a href=":content">Content Translation</a>, <a href=":interface">Interface Translation</a>, and <a href=":configuration">Configuration Translation</a> modules, if they are installed. For more information, see the <a href=":doc_url">online documentation for the Language module</a>.', [
          ':doc_url' => 'https://www.drupal.org/documentation/modules/language',
          ':content' => \Drupal::moduleHandler()->moduleExists('content_translation') ? Url::fromRoute('help.page', [
            'name' => 'content_translation',
          ])->toString() : '#',
          ':interface' => \Drupal::moduleHandler()->moduleExists('locale') ? Url::fromRoute('help.page', [
            'name' => 'locale',
          ])->toString() : '#',
          ':configuration' => \Drupal::moduleHandler()->moduleExists('config_translation') ? Url::fromRoute('help.page', [
            'name' => 'config_translation',
          ])->toString() : '#',
        ]) . '</p>';
        $output .= '<h2>' . $this->t('Uses') . '</h2>';
        $output .= '<dl>';
        $output .= '<dt>' . $this->t('Adding languages') . '</dt>';
        $output .= '<dd>' . $this->t('You can add languages on the <a href=":language_list">Languages</a> page by selecting <em>Add language</em> and choosing a language from the drop-down menu. This language is then displayed in the languages list, where it can be configured further. If the <a href=":interface">Interface translation module</a> is installed, and the <em>translation server</em> is set as a translation source, then the interface translation for this language is automatically downloaded as well.', [
          ':language_list' => Url::fromRoute('entity.configurable_language.collection')->toString(),
          ':interface' => \Drupal::moduleHandler()->moduleExists('locale') ? Url::fromRoute('help.page', [
            'name' => 'locale',
          ])->toString() : '#',
        ]) . '</dd>';
        $output .= '<dt>' . $this->t('Adding custom languages') . '</dt>';
        $output .= '<dd>' . $this->t('You can add a language that is not provided in the drop-down list by choosing <em>Custom language</em> at the end of the list. You then have to configure its language code, name, and direction in the form provided.') . '</dd>';
        $output .= '<dt>' . $this->t('Configuring content languages') . '</dt>';
        $output .= '<dd>' . $this->t('By default, content is created in the site\'s default language and no language selector is displayed on content creation pages. On the <a href=":content_language">Content language</a> page you can customize the language configuration for any supported content entity on your site (for example for content types or menu links). After choosing an entity, you are provided with a drop-down menu to set the default language and a check-box to display language selectors.', [
          ':content_language' => Url::fromRoute('language.content_settings_page')->toString(),
        ]) . '</dd>';
        $output .= '<dt>' . $this->t('Adding a language switcher block') . '</dt>';
        $output .= '<dd>' . $this->t('If the Block module is installed, then you can add a language switcher block on the <a href=":blocks">Block layout</a> page to allow users to switch between languages.', [
          ':blocks' => \Drupal::moduleHandler()->moduleExists('block') ? Url::fromRoute('block.admin_display')->toString() : '#',
        ]) . '</dd>';
        $output .= '<dt>' . $this->t('Making a block visible per language') . '</dt>';
        $output .= '<dd>' . $this->t('If the Block module is installed, then the Language module allows you to set the visibility of a block based on selected languages on the <a href=":blocks">Block layout</a> page.', [
          ':blocks' => \Drupal::moduleHandler()->moduleExists('block') ? Url::fromRoute('block.admin_display')->toString() : '#',
        ]) . '</dd>';
        $output .= '<dt>' . $this->t('Choosing user languages') . '</dt>';
        $output .= '<dd>' . $this->t("Users can choose a <em>Site language</em> on their profile page. This language is used for email messages, and can be used by modules to determine a user's language. It can also be used for interface text, if the <em>User</em> method is enabled as a <em>Detection and selection</em> method (see below). Administrative users can choose a separate <em>Administration pages language</em> for the interface text on administration pages. This configuration is only available on the user's profile page if the <em>Account administration pages</em> method is enabled (see below).") . '</dd>';
        $output .= '<dt>' . $this->t('Language detection and selection') . '</dt>';
        $output .= '<dd>' . $this->t('The <a href=":detection">Detection and selection</a> page provides several methods for deciding which language is used for displaying interface text. When a method detects and selects an interface language, then the following methods in the list are not applied. You can order them by importance, with your preferred method at the top of the list, followed by one or several fall-back methods.', [':detection' => Url::fromRoute('language.negotiation')->toString()]);
        $output .= '<ul><li>' . $this->t('<em>URL</em> sets the interface language based on a path prefix or domain (for example specifying <em>de</em> for German would result in URLs like <em>example.com/de/contact</em>). The default language does not require a path prefix, but can have one assigned as well. If the language detection is done by domain name, a domain needs to be specified for each language.') . '</li>';
        $output .= '<li>' . $this->t('<em>Session</em> determines the interface language from a request or session parameter (for example <em>example.com?language=de</em> would set the interface language to German based on the use of <em>de</em> as the <em>language</em> parameter).') . '</li>';
        $output .= '<li>' . $this->t("<em>User</em> follows the language configuration set on the user's profile page.") . '</li>';
        $output .= '<li>' . $this->t('<em>Browser</em> sets the interface language based on the browser\'s language settings. Since browsers use different language codes to refer to the same languages, you can add and edit languages codes to map the browser language codes to the <a href=":language_list">language codes</a> used on your site.', [
          ':language_list' => Url::fromRoute('entity.configurable_language.collection')->toString(),
        ]) . '</li>';
        $output .= '<li>' . $this->t('<em>Account administration pages</em> follows the configuration set as <em>Administration pages language</em> on the profile page of an administrative user. This method is similar to the <em>User</em> method, but only sets the interface text language on administration pages, independent of the interface text language on other pages.') . '</li>';
        $output .= '<li>' . $this->t("<em>Selected language</em> allows you to specify the site's default language or a specific language as the fall-back language. This method should be listed last.") . '</li></ul></dd>';
        $output .= '</dl>';
        return $output;

      case 'entity.configurable_language.collection':
        $output = '<p>' . $this->t('Reorder the configured languages to set their order in the language switcher block and, when editing content, in the list of selectable languages. This ordering does not impact <a href=":detection">detection and selection</a>.', [':detection' => Url::fromRoute('language.negotiation')->toString()]) . '</p>';
        $output .= '<p>' . $this->t('The site default language can also be set. It is not recommended to change the default language on a working site. <a href=":language-detection">Configure the Selected language</a> setting on the detection and selection page to change the fallback language for language selection.', [
          ':language-detection' => Url::fromRoute('language.negotiation')->toString(),
        ]) . '</p>';
        return $output;

      case 'language.add':
        return '<p>' . $this->t('Add a language to be supported by your site. If your desired language is not available, pick <em>Custom language...</em> at the end and provide a language code and other details manually.') . '</p>';

      case 'language.negotiation':
        $output = '<p>' . $this->t('Define how to decide which language is used to display page elements (primarily text provided by modules, such as field labels and help text). This decision is made by evaluating a series of detection methods for languages; the first detection method that gets a result will determine which language is used for that type of text. Be aware that some language detection methods are unreliable under certain conditions, such as browser detection when page-caching is enabled and a user is not currently logged in. Define the order of evaluation of language detection methods on this page. The default language can be changed in the <a href=":admin-change-language">list of languages</a>.', [
          ':admin-change-language' => Url::fromRoute('entity.configurable_language.collection')->toString(),
        ]) . '</p>';
        return $output;

      case 'language.negotiation_session':
        $output = '<p>' . $this->t('Determine the language from a request/session parameter. Example: "http://example.com?language=de" sets language to German based on the use of "de" within the "language" parameter.') . '</p>';
        return $output;

      case 'language.negotiation_browser':
        $output = '<p>' . $this->t('Browsers use different language codes to refer to the same languages. Internally, a best effort is made to determine the correct language based on the code that the browser sends. You can add and edit additional mappings from browser language codes to <a href=":configure-languages">site languages</a>.', [
          ':configure-languages' => Url::fromRoute('entity.configurable_language.collection')->toString(),
        ]) . '</p>';
        return $output;

      case 'language.negotiation_selected':
        $output = '<p>' . $this->t('Changing the selected language here (and leaving this option as the last among the detection and selection options) is the easiest way to change the fallback language for the website, if you need to change how your site works by default (e.g., when using an empty path prefix or using the default domain). <a href=":admin-change-language">Changing the site\'s default language</a> itself might have other undesired side effects.', [
          ':admin-change-language' => Url::fromRoute('entity.configurable_language.collection')->toString(),
        ]) . '</p>';
        return $output;

      case 'entity.block.edit_form':
        if (($block = $route_match->getParameter('block')) && $block->getPluginId() == 'language_block:language_interface') {
          return '<p>' . $this->t('With multiple languages configured, registered users can select their preferred language and authors can assign a specific language to content.') . '</p>';
        }
        break;

      case 'block.admin_add':
        if ($route_match->getParameter('plugin_id') == 'language_block:language_interface') {
          return '<p>' . $this->t('With multiple languages configured, registered users can select their preferred language and authors can assign a specific language to content.') . '</p>';
        }
        break;

      case 'language.content_settings_page':
        return '<p>' . $this->t("Change language settings for <em>content types</em>, <em>taxonomy vocabularies</em>, <em>user profiles</em>, or any other supported element on your site. By default, language settings hide the language selector and the language is the site's default language.") . '</p>';
    }
    return NULL;
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme() : array {
    return [
      'language_negotiation_configure_form' => [
        'render element' => 'form',
        'file' => 'language.admin.inc',
      ],
      'language_content_settings_table' => [
        'render element' => 'element',
        'file' => 'language.admin.inc',
      ],
    ];
  }

  /**
   * Implements hook_element_info_alter().
   *
   * @see \Drupal\Core\Render\Element\LanguageSelect
   * @see \Drupal\Core\Render\Element\Select
   */
  #[Hook('element_info_alter')]
  public function elementInfoAlter(&$type): void {
    // Alter the language_select element so that it will be rendered like a
    // select field.
    if (isset($type['language_select'])) {
      if (!isset($type['language_select']['#process'])) {
        $type['language_select']['#process'] = [];
      }
      if (!isset($type['language_select']['#theme_wrappers'])) {
        $type['language_select']['#theme_wrappers'] = [];
      }
      $type['language_select']['#process'] = array_merge($type['language_select']['#process'], [
        'language_process_language_select',
            [
              'Drupal\Core\Render\Element\Select',
              'processSelect',
            ],
            [
              'Drupal\Core\Render\Element\RenderElementBase',
              'processAjaxForm',
            ],
      ]);
      $type['language_select']['#theme'] = 'select';
      $type['language_select']['#theme_wrappers'] = array_merge($type['language_select']['#theme_wrappers'], ['form_element']);
      $type['language_select']['#languages'] = LanguageInterface::STATE_CONFIGURABLE;
      $type['language_select']['#multiple'] = FALSE;
    }
  }

  /**
   * Implements hook_entity_base_field_info_alter().
   */
  #[Hook('entity_base_field_info_alter')]
  public function entityBaseFieldInfoAlter(&$fields): void {
    foreach ($fields as $definition) {
      // Set configurable form display for language fields with display options.
      if ($definition->getType() == 'language') {
        foreach (['form', 'view'] as $type) {
          if ($definition->getDisplayOptions($type)) {
            // The related configurations will be purged manually on Language
            // module uninstallation. @see language_modules_uninstalled().
            $definition->setDisplayConfigurable($type, TRUE);
          }
        }
      }
    }
  }

  /**
   * Implements hook_entity_bundle_delete().
   */
  #[Hook('entity_bundle_delete')]
  public function entityBundleDelete($entity_type_id, $bundle): void {
    // Remove the content language settings associated with the bundle.
    $settings = ContentLanguageSettings::loadByEntityTypeBundle($entity_type_id, $bundle);
    if (!$settings->isNew()) {
      $settings->delete();
    }
  }

  /**
   * Implements hook_modules_installed().
   *
   * Implements hook_modules_uninstalled().
   */
  #[Hook('modules_installed')]
  #[Hook('modules_uninstalled')]
  public function modulesInstalled($modules, $is_syncing): void {
    if ($is_syncing) {
      return;
    }
    if (!in_array('language', $modules)) {
      if (InstallerKernel::installationAttempted() && ($profile = \Drupal::installProfile())) {
        // If the install profile provides its own language.types configuration
        // do not overwrite it.
        $profile_directory = \Drupal::service('extension.list.profile')->getPath($profile);
        $profile_storages = [
          new FileStorage($profile_directory . '/' . InstallStorage::CONFIG_INSTALL_DIRECTORY),
          new FileStorage($profile_directory . '/' . InstallStorage::CONFIG_OPTIONAL_DIRECTORY),
        ];
        foreach ($profile_storages as $storage) {
          if ($storage->exists('language.types')) {
            return;
          }
        }
      }
      // Since newly (un)installed modules may change the default settings for
      // non-locked language types (e.g. content language), we need to resave
      // the language type configuration.
      /** @var \Drupal\language\LanguageNegotiatorInterface $negotiator */
      $negotiator = \Drupal::service('language_negotiator');
      $configurable = \Drupal::config('language.types')->get('configurable');
      $negotiator->updateConfiguration($configurable);
      $negotiator->purgeConfiguration();
    }
    else {
      // In language_entity_base_field_info_alter() we are altering view/form
      // display definitions to make language fields display configurable. Since
      // this is not a hard dependency, and thus is not detected by the config
      // system, we have to clean up the related values manually.
      foreach (['entity_view_display', 'entity_form_display'] as $key) {
        $displays = \Drupal::entityTypeManager()->getStorage($key)->loadMultiple();
        /** @var \Drupal\Core\Entity\Display\EntityDisplayInterface $display */
        foreach ($displays as $display) {
          $display->save();
        }
      }
    }
  }

  /**
   * Implements hook_form_alter().
   */
  #[Hook('form_alter')]
  public function formAlter(&$form, FormStateInterface $form_state) : void {
    // Content entity forms may have added a langcode field. But content
    // language configuration should decide if it should be exposed or not in
    // the forms.
    $form_object = $form_state->getFormObject();
    if ($form_object instanceof ContentEntityFormInterface && $form_object->getEntity()->getEntityType()->hasKey('langcode')) {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $entity = $form_object->getEntity();
      $entity_type = $entity->getEntityType();
      $langcode_key = $entity_type->getKey('langcode');
      if (isset($form[$langcode_key]) && $form[$langcode_key]['#access'] !== FALSE) {
        $language_configuration = ContentLanguageSettings::loadByEntityTypeBundle($entity->getEntityTypeId(), $entity->bundle());
        $form[$langcode_key]['#access'] = $language_configuration->isLanguageAlterable();
      }
    }
  }

  /**
   * Implements hook_field_info_alter().
   */
  #[Hook('field_info_alter')]
  public function fieldInfoAlter(&$info): void {
    // Change the default behavior of language field.
    $info['language']['class'] = '\Drupal\language\DefaultLanguageItem';
  }

  /**
   * Implements hook_entity_field_access().
   */
  #[Hook('entity_field_access')]
  public function entityFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, ?FieldItemListInterface $items = NULL): AccessResultInterface {
    // Only allow edit access on a langcode field if the entity it is attached
    // to is configured to have an alterable language. Also without items we can
    // not decide whether or not to allow access.
    if ($items && $operation == 'edit') {
      // Check if we are dealing with a langcode field.
      $langcode_key = $items->getEntity()->getEntityType()->getKey('langcode');
      if ($field_definition->getName() == $langcode_key) {
        // Grant access depending on whether the entity language can be altered.
        $entity = $items->getEntity();
        $config = ContentLanguageSettings::loadByEntityTypeBundle($entity->getEntityTypeId(), $entity->bundle());
        return AccessResult::forbiddenIf(!$config->isLanguageAlterable());
      }
    }
    return AccessResult::neutral();
  }

  /**
   * Implements hook_tour_tips_alter().
   */
  #[Hook('tour_tips_alter')]
  public function tourTipsAlter(array &$tour_tips, EntityInterface $entity): void {
    $module_extension_list = \Drupal::service('extension.list.module');
    foreach ($tour_tips as $tour_tip) {
      if ($tour_tip->get('id') == 'language-overview') {
        $additional_overview = '';
        if (\Drupal::service('module_handler')->moduleExists('locale')) {
          $additional_overview = $this->t("This page also provides an overview of how much of the site's interface has been translated for each configured language.");
        }
        else {
          $additional_overview = $this->t("If the Interface Translation module is installed, this page will provide an overview of how much of the site's interface has been translated for each configured language.");
        }
        $tour_tip->set('body', $tour_tip->get('body') . '<p>' . $additional_overview . '</p>');
      }
      elseif ($tour_tip->get('id') == 'language-continue') {
        $additional_continue = '';
        $additional_modules = [];
        if (!\Drupal::service('module_handler')->moduleExists('locale')) {
          $additional_modules[] = $module_extension_list->getName('locale');
        }
        if (!\Drupal::service('module_handler')->moduleExists('content_translation')) {
          $additional_modules[] = $module_extension_list->getName('content_translation');
        }
        if (!empty($additional_modules)) {
          $additional_continue = $this->t('Depending on your site features, additional modules that you might want to install are:') . '<ul>';
          foreach ($additional_modules as $additional_module) {
            $additional_continue .= '<li>' . $additional_module . '</li>';
          }
          $additional_continue .= '</ul>';
        }
        if (!empty($additional_continue)) {
          $tour_tip->set('body', $tour_tip->get('body') . '<p>' . $additional_continue . '</p>');
        }
      }
    }
  }

  /**
   * Implements hook_language_types_info_alter().
   *
   * We can't set the fixed properties in \Drupal\Core\Language\LanguageManager,
   * where the rest of the properties for the default language types are
   * defined. The LanguageNegation classes are only loaded when the language
   * module is enabled and we can't be sure of that in the LanguageManager.
   */
  #[Hook('language_types_info_alter')]
  public function languageTypesInfoAlter(array &$language_types): void {
    $language_types[LanguageInterface::TYPE_CONTENT]['fixed'] = [LanguageNegotiationUI::METHOD_ID];
    $language_types[LanguageInterface::TYPE_URL]['fixed'] = [
      LanguageNegotiationUrl::METHOD_ID,
      LanguageNegotiationUrlFallback::METHOD_ID,
    ];
  }

}
