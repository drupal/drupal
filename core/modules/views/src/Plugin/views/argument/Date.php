<?php

namespace Drupal\views\Plugin\views\argument;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Argument handler for dates.
 *
 * Adds an option to set a default argument based on the current date.
 *
 * Definitions terms:
 * - many to one: If true, the "many to one" helper will be used.
 * - invalid input: A string to give to the user for obviously invalid input.
 *                  This is deprecated in favor of argument validators.
 *
 * @see \Drupal\views\ManyToOneHelper
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("date")
 */
class Date extends Formula implements ContainerFactoryPluginInterface {

  /**
   * The date format used in the title.
   *
   * @var string
   */
  protected $format;

  /**
   * The date format used in the query.
   *
   * @var string
   */
  protected $argFormat = 'Y-m-d';

  public $option_name = 'default_argument_date';

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a new Date instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match, DateFormatterInterface $date_formatter) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->routeMatch = $route_match;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('date.formatter')
    );
  }

  /**
   * Add an option to set the default value to the current date.
   */
  public function defaultArgumentForm(&$form, FormStateInterface $form_state) {
    parent::defaultArgumentForm($form, $form_state);
    $form['default_argument_type']['#options'] += ['date' => $this->t('Current date')];
    $form['default_argument_type']['#options'] += ['node_created' => $this->t("Current node's creation time")];
    $form['default_argument_type']['#options'] += ['node_changed' => $this->t("Current node's update time")];
  }

  /**
   * Set the empty argument value to the current date,
   * formatted appropriately for this argument.
   */
  public function getDefaultArgument($raw = FALSE) {
    if (!$raw && $this->options['default_argument_type'] == 'date') {
      return date($this->argFormat, REQUEST_TIME);
    }
    elseif (!$raw && in_array($this->options['default_argument_type'], ['node_created', 'node_changed'])) {
      $node = $this->routeMatch->getParameter('node');

      if (!($node instanceof NodeInterface)) {
        return parent::getDefaultArgument();
      }
      elseif ($this->options['default_argument_type'] == 'node_created') {
        return date($this->argFormat, $node->getCreatedTime());
      }
      elseif ($this->options['default_argument_type'] == 'node_changed') {
        return date($this->argFormat, $node->getChangedTime());
      }
    }

    return parent::getDefaultArgument();
  }

  /**
   * {@inheritdoc}
   */
  public function getSortName() {
    return $this->t('Date', [], ['context' => 'Sort order']);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormula() {
    $this->formula = $this->getDateFormat($this->argFormat);
    return parent::getFormula();
  }

}
