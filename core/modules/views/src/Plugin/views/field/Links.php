<?php

namespace Drupal\views\Plugin\views\field;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Url as UrlObject;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * An abstract handler which provides a collection of links.
 *
 * @ingroup views_field_handlers
 */
abstract class Links extends FieldPluginBase {

  /**
   * Constructs a Links object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface|null $redirectDestination
   *   The redirect destination service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, protected ?RedirectDestinationInterface $redirectDestination = NULL) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    if ($redirectDestination === NULL) {
      $this->redirectDestination = \Drupal::service('redirect.destination');
      @trigger_error('Calling' . __METHOD__ . '() without the $redirectDestination argument is deprecated in drupal:10.1.0 and is required in drupal:11.0.0. See https://www.drupal.org/node/3343983', E_USER_DEPRECATED);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('redirect.destination')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $options = parent::defineOptions();

    $options['fields'] = ['default' => []];
    $options['destination'] = ['default' => TRUE];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    // Only show fields that precede this one.
    $field_options = $this->getPreviousFieldLabels();
    $form['fields'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Fields'),
      '#description' => $this->t('Fields to be included as links.'),
      '#options' => $field_options,
      '#default_value' => $this->options['fields'],
    ];
    $form['destination'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include destination'),
      '#description' => $this->t('Include a "destination" parameter in the link to return the user to the original view upon completing the link action.'),
      '#default_value' => $this->options['destination'],
    ];
  }

  /**
   * Gets the list of links used by this field.
   *
   * @return array
   *   The links which are used by the render function.
   */
  protected function getLinks() {
    $links = [];
    foreach ($this->options['fields'] as $field) {
      if (empty($this->view->field[$field]->last_render_text)) {
        continue;
      }
      $title = $this->view->field[$field]->last_render_text;
      $path = '';
      $url = NULL;
      if (!empty($this->view->field[$field]->options['alter']['path'])) {
        $path = $this->view->field[$field]->options['alter']['path'];
      }
      elseif (!empty($this->view->field[$field]->options['alter']['url']) && $this->view->field[$field]->options['alter']['url'] instanceof UrlObject) {
        $url = $this->view->field[$field]->options['alter']['url'];
      }
      // Make sure that tokens are replaced for this paths as well.
      $tokens = $this->getRenderTokens([]);
      $path = strip_tags(Html::decodeEntities($this->viewsTokenReplace($path, $tokens)));

      $links[$field] = [
        'url' => $path ? UrlObject::fromUri('internal:/' . $path) : $url,
        'title' => $title,
      ];
      if (!empty($this->options['destination'])) {
        $links[$field]['query'] = $this->redirectDestination->getAsArray();
      }
    }

    return $links;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
  }

}
