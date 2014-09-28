<?php

/**
 * @file
 * Contains \Drupal\rest\Plugin\views\style\Serializer.
 */

namespace Drupal\rest\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * The style plugin for serialized output formats.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "serializer",
 *   title = @Translation("Serializer"),
 *   help = @Translation("Serializes views row data using the Serializer component."),
 *   display_types = {"data"}
 * )
 */
class Serializer extends StylePluginBase {

  /**
   * Overrides \Drupal\views\Plugin\views\style\StylePluginBase::$usesRowPlugin.
   */
  protected $usesRowPlugin = TRUE;

  /**
   * Overrides Drupal\views\Plugin\views\style\StylePluginBase::$usesFields.
   */
  protected $usesGrouping = FALSE;

  /**
   * The serializer which serializes the views result.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * The available serialization formats.
   *
   * @var array
   */
  protected $formats = array();

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('serializer'),
      $container->getParameter('serializer.formats')
    );
  }

  /**
   * Constructs a Plugin object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SerializerInterface $serializer, array $serializer_formats) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->definition = $plugin_definition + $configuration;
    $this->serializer = $serializer;
    $this->formats = $serializer_formats;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['formats'] = array('default' => array());

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['formats'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Accepted request formats'),
      '#description' => $this->t('Request formats that will be allowed in responses. If none are selected all formats will be allowed.'),
      '#options' => array_combine($this->formats, $this->formats),
      '#default_value' => $this->options['formats'],
    );
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    parent::submitOptionsForm($form, $form_state);

    $formats = $form_state->getValue(array('style_options', 'formats'));
    $form_state->setValue(array('style_options', 'formats'), array_filter($formats));
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $rows = array();
    // If the Data Entity row plugin is used, this will be an array of entities
    // which will pass through Serializer to one of the registered Normalizers,
    // which will transform it to arrays/scalars. If the Data field row plugin
    // is used, $rows will not contain objects and will pass directly to the
    // Encoder.
    foreach ($this->view->result as $row) {
      $rows[] = $this->view->rowPlugin->render($row);
    }

    $content_type = $this->displayHandler->getContentType();
    if (!empty($this->view->live_preview)) {
      $content_type = $this->options['formats'] ? reset($this->options['formats']) : 'json';
    }

    return $this->serializer->serialize($rows, $content_type);
  }

  /**
   * Gets a list of all available formats that can be requested.
   *
   * This will return the configured formats, or all formats if none have been
   * selected.
   *
   * @return array
   *   An array of formats.
   */
  public function getFormats() {
    if (!empty($this->options['formats'])) {
      return $this->options['formats'];
    }

    return $this->formats;
  }

}
