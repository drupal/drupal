<?php

namespace Drupal\media\Form;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\EditorInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\editor\Ajax\EditorDialogSave;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\filter\Plugin\FilterInterface;
use Drupal\image\Plugin\Field\FieldType\ImageItem;
use Drupal\media\MediaInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;

/**
 * Provides a media embed dialog for text editors.
 *
 * Depending on the configuration of the filters associated with the text
 * editor, this dialog allows users to set the alt text, alignment, and
 * captioning status for embedded media items.
 *
 * @internal
 *   This is an internal part of the media system in Drupal core and may be
 *   subject to change in minor releases. This class should not be
 *   instantiated or extended by external code.
 */
class EditorMediaDialog extends FormBase {

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Constructs a EditorMediaDialog object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityDisplayRepositoryInterface $entity_display_repository) {
    $this->entityRepository = $entity_repository;
    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'editor_media_dialog';
  }

  /**
   * {@inheritdoc}
   *
   * @param array $form
   *   A nested array form elements comprising the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\editor\EditorInterface $editor
   *   The text editor to which this dialog corresponds.
   */
  public function buildForm(array $form, FormStateInterface $form_state, EditorInterface $editor = NULL) {
    // This form is special, in that the default values do not come from the
    // server side, but from the client side, from a text editor. We must cache
    // this data in form state, because when the form is rebuilt, we will be
    // receiving values from the form, instead of the values from the text
    // editor. If we don't cache it, this data will be lost. By convention,
    // the data that the text editor sends to any dialog is in the
    // 'editor_object' key.
    if (isset($form_state->getUserInput()['editor_object'])) {
      $editor_object = $form_state->getUserInput()['editor_object'];
      // The data that the text editor sends to any dialog is in
      // the 'editor_object' key.
      $media_embed_element = $editor_object['attributes'];
      $form_state->set('media_embed_element', $media_embed_element);
      $has_caption = $editor_object['hasCaption'];
      $form_state
        ->set('hasCaption', $has_caption)
        ->setCached(TRUE);
    }
    else {
      // Retrieve the user input from form state.
      $media_embed_element = $form_state->get('media_embed_element');
      $has_caption = $form_state->get('hasCaption');
    }

    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'editor/drupal.editor.dialog';
    $form['#prefix'] = '<div id="editor-media-dialog-form">';
    $form['#suffix'] = '</div>';

    $filters = $editor->getFilterFormat()->filters();
    $filter_html = $filters->get('filter_html');
    $filter_align = $filters->get('filter_align');
    $filter_caption = $filters->get('filter_caption');
    $media_embed_filter = $filters->get('media_embed');

    $allowed_attributes = [];
    if ($filter_html->status) {
      $restrictions = $filter_html->getHTMLRestrictions();
      $allowed_attributes = $restrictions['allowed']['drupal-media'];
    }

    $media = $this->entityRepository->loadEntityByUuid('media', $media_embed_element['data-entity-uuid']);

    if ($image_field_name = $this->getMediaImageSourceFieldName($media)) {
      // We'll want the alt text from the same language as the host.
      if (!empty($editor_object['hostEntityLangcode']) && $media->hasTranslation($editor_object['hostEntityLangcode'])) {
        $media = $media->getTranslation($editor_object['hostEntityLangcode']);
      }
      $settings = $media->{$image_field_name}->getItemDefinition()->getSettings();
      $alt = $media_embed_element['alt'] ?? NULL;
      $form['alt'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Alternate text'),
        '#default_value' => $alt,
        '#description' => $this->t('Short description of the image used by screen readers and displayed when the image is not loaded. This is important for accessibility.'),
        '#required_error' => $this->t('Alternative text is required.<br />(Only in rare cases should this be left empty. To create empty alternative text, enter <code>""</code> — two double quotes without any content).'),
        '#maxlength' => 2048,
        '#placeholder' => $media->{$image_field_name}->alt,
        '#parents' => ['attributes', 'alt'],
        '#access' => !empty($settings['alt_field']) && ($filter_html->status === FALSE || !empty($allowed_attributes['alt'])),
      ];
    }

    // When Drupal core's filter_align is being used, the text editor offers the
    // ability to change the alignment.
    $form['align'] = [
      '#title' => $this->t('Align'),
      '#type' => 'radios',
      '#options' => [
        'none' => $this->t('None'),
        'left' => $this->t('Left'),
        'center' => $this->t('Center'),
        'right' => $this->t('Right'),
      ],
      '#default_value' => empty($media_embed_element['data-align']) ? 'none' : $media_embed_element['data-align'],
      '#attributes' => ['class' => ['container-inline']],
      '#parents' => ['attributes', 'data-align'],
      '#access' => $filter_align->status && ($filter_html->status === FALSE || !empty($allowed_attributes['data-align'])),
    ];

    // When Drupal core's filter_caption is being used, the text editor offers
    // the ability to in-place edit the media's caption: show a toggle.
    $form['caption'] = [
      '#title' => $this->t('Caption'),
      '#type' => 'checkbox',
      '#default_value' => $has_caption === 'true',
      '#parents' => ['hasCaption'],
      '#access' => $filter_caption->status && ($filter_html->status === FALSE || !empty($allowed_attributes['data-caption'])),
    ];

    $view_mode_options = array_intersect_key($this->entityDisplayRepository->getViewModeOptionsByBundle('media', $media->bundle()), $media_embed_filter->settings['allowed_view_modes']);
    $default_view_mode = static::getViewModeDefaultValue($view_mode_options, $media_embed_filter, $media_embed_element['data-view-mode'] ?? NULL);

    $form['view_mode'] = [
      '#title' => $this->t("Display"),
      '#type' => 'select',
      '#options' => $view_mode_options,
      '#default_value' => $default_view_mode,
      '#parents' => ['attributes', 'data-view-mode'],
      '#access' => count($view_mode_options) >= 2,
    ];

    // Store the default from the MediaEmbed filter, so that if the selected
    // view mode matches the default, we can drop the 'data-view-mode'
    // attribute.
    $form_state->set('filter_default_view_mode', $media_embed_filter->settings['default_view_mode']);

    if ((empty($form['alt']) || $form['alt']['#access'] === FALSE) && $form['align']['#access'] === FALSE && $form['caption']['#access'] === FALSE && $form['view_mode']['#access'] === FALSE) {
      $format = $editor->getFilterFormat();
      $warning = $this->t('There is nothing to configure for this media.');
      $form['no_access_notice'] = ['#markup' => $warning];
      if ($format->access('update')) {
        $tparams = [
          '@warning' => $warning,
          '@edit_url' => $format->toUrl('edit-form')->toString(),
          '%format' => $format->label(),
        ];
        $form['no_access_notice']['#markup'] = $this->t('@warning <a href="@edit_url">Edit the text format %format</a> to modify the attributes that can be overridden.', $tparams);
      }
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['save_modal'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      // No regular submit-handler. This form only works via JavaScript.
      '#submit' => [],
      '#ajax' => [
        'callback' => '::submitForm',
        'event' => 'click',
      ],
      // Prevent this hidden element from being tabbable.
      '#attributes' => [
        'tabindex' => -1,
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // When the `alt` attribute is set to two double quotes, transform it to the
    // empty string: two double quotes signify "empty alt attribute". See above.
    if (trim($form_state->getValue(['attributes', 'alt'], '')) === '""') {
      $form_state->setValue(['attributes', 'alt'], '""');
    }

    // The `alt` attribute is optional: if it isn't set, the default value
    // simply will not be overridden. It's important to set it to FALSE
    // instead of unsetting the value.  This way we explicitly inform
    // the client side about the new value.
    if ($form_state->hasValue(['attributes', 'alt']) && trim($form_state->getValue(['attributes', 'alt'])) === '') {
      $form_state->setValue(['attributes', 'alt'], FALSE);
    }

    // If the selected view mode matches the default on the filter, remove the
    // attribute.
    if (!empty($form_state->get('filter_default_view_mode')) && $form_state->getValue(['attributes', 'data-view-mode']) === $form_state->get('filter_default_view_mode')) {
      $form_state->setValue(['attributes', 'data-view-mode'], FALSE);
    }

    if ($form_state->getErrors()) {
      unset($form['#prefix'], $form['#suffix']);
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
      ];
      $response->addCommand(new HtmlCommand('#editor-media-dialog-form', $form));
    }
    else {
      // Only send back the relevant values.
      $values = [
        'hasCaption' => $form_state->getValue('hasCaption'),
        'attributes' => $form_state->getValue('attributes'),
      ];
      $response->addCommand(new EditorDialogSave($values));
      $response->addCommand(new CloseModalDialogCommand());
    }

    return $response;
  }

  /**
   * Gets the default value for the view mode form element.
   *
   * @param array $view_mode_options
   *   The array of options for the view mode form element.
   * @param \Drupal\filter\Plugin\FilterInterface $media_embed_filter
   *   The media embed filter.
   * @param string $media_element_view_mode_attribute
   *   The data-view-mode attribute on the <drupal-media> element.
   *
   * @return string|null
   *   The default value for the view mode form element.
   */
  public static function getViewModeDefaultValue(array $view_mode_options, FilterInterface $media_embed_filter, $media_element_view_mode_attribute) {
    // The select element won't display without at least two options, so if
    // that's the case, just return NULL.
    if (count($view_mode_options) < 2) {
      return NULL;
    }

    $filter_default_view_mode = $media_embed_filter->settings['default_view_mode'];

    // If the current media embed ($media_embed_element) has a set view mode,
    // we want to use that as the default in the select form element,
    // otherwise we'll want to use the default for all embedded media.
    if (!empty($media_element_view_mode_attribute) && array_key_exists($media_element_view_mode_attribute, $view_mode_options)) {
      return $media_element_view_mode_attribute;
    }
    elseif (array_key_exists($filter_default_view_mode, $view_mode_options)) {
      return $filter_default_view_mode;
    }

    return NULL;
  }

  /**
   * Gets the name of an image media item's source field.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media item being embedded.
   *
   * @return string|null
   *   The name of the image source field configured for the media item, or
   *   NULL if the source field is not an image field.
   */
  protected function getMediaImageSourceFieldName(MediaInterface $media) {
    $field_definition = $media->getSource()
      ->getSourceFieldDefinition($media->bundle->entity);
    $item_class = $field_definition->getItemDefinition()->getClass();
    if (is_a($item_class, ImageItem::class, TRUE)) {
      return $field_definition->getName();
    }
    return NULL;
  }

}
