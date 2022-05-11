<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Drupal\filter\FilterPluginManager;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * CKEditor 5 Media plugin in sync with the filter settings validator.
 *
 * @internal
 */
class CKEditor5MediaAndFilterSettingsInSyncConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  use PluginManagerDependentValidatorTrait;
  use TextEditorObjectDependentValidatorTrait;
  use StringTranslationTrait;

  /**
   * The filter plugin manager service.
   *
   * @var \Drupal\filter\FilterPluginManager
   */
  protected $filterPluginManager;

  /**
   * The typed config manager service.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfigManager;

  /**
   * Constructs a new CKEditor5MediaAndFilterSettingsInSyncConstraintValidator.
   *
   * @param \Drupal\filter\FilterPluginManager $filter_plugin_manager
   *   The filter plugin manager service.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager
   *   The typed config manager service.
   */
  public function __construct(FilterPluginManager $filter_plugin_manager, TypedConfigManagerInterface $typed_config_manager) {
    $this->filterPluginManager = $filter_plugin_manager;
    $this->typedConfigManager = $typed_config_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.filter'),
      $container->get('config.typed'),
    );
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\Validator\Exception\UnexpectedTypeException
   *   Thrown when the given constraint is not supported by this validator.
   */
  public function validate($toolbar_item, Constraint $constraint) {
    if (!$constraint instanceof CKEditor5MediaAndFilterSettingsInSyncConstraint) {
      throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '\CKEditor5MediaAndFilterSettingsInSync');
    }
    $text_editor = $this->createTextEditorObjectFromContext();

    if (isset($text_editor->getSettings()['plugins']['media_media'])) {
      $cke5_plugin_overrides_allowed = $text_editor->getSettings()['plugins']['media_media']['allow_view_mode_override'];
      $filter_allowed_view_modes = $text_editor->getFilterFormat()->filters('media_embed')->getConfiguration()['settings']['allowed_view_modes'];
      $filter_media_plugin_label = $this->filterPluginManager->getDefinition('media_embed')['title']->render();
      $filter_media_allowed_view_modes_label = $this->typedConfigManager->getDefinition('filter_settings.media_embed')['mapping']['allowed_view_modes']['label'];

      // Whenever the CKEditor 5 plugin is configured to allow overrides, the
      // filter must be configured to allow 2 or more view modes.
      if ($cke5_plugin_overrides_allowed && count($filter_allowed_view_modes) < 2) {
        $this->context->addViolation($constraint->message, [
          '%cke5_media_plugin_label' => $this->t('Media'),
          '%cke5_allow_view_mode_override_label' => $this->t('Allow the user to override the default view mode'),
          '%filter_media_plugin_label' => $filter_media_plugin_label,
          '%filter_media_allowed_view_modes_label' => $filter_media_allowed_view_modes_label,
        ]);
      }
    }
  }

}
