<?php

/**
 * @file
 * Contains \Drupal\ban\Form\BanAdmin.
 */

namespace Drupal\ban\Form;

use Drupal\Core\Form\FormBase;
use Drupal\ban\BanIpManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displays banned IP addresses.
 */
class BanAdmin extends FormBase {

  /**
   * @var \Drupal\ban\BanIpManager
   */
  protected $ipManager;

  /**
   * Constructs a new BanAdmin object.
   *
   * @param \Drupal\ban\BanIpManager $ip_manager
   */
  public function __construct(BanIpManager $ip_manager) {
    $this->ipManager = $ip_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ban.ip_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ban_ip_form';
  }

  /**
   * {@inheritdoc}
   *
   * @param string $default_ip
   *   (optional) IP address to be passed on to drupal_get_form() for use as the
   *   default value of the IP address form field.
   */
  public function buildForm(array $form, array &$form_state, $default_ip = '') {
    $rows = array();
    $header = array($this->t('banned IP addresses'), $this->t('Operations'));
    $result = $this->ipManager->findAll();
    foreach ($result as $ip) {
      $row = array();
      $row[] = $ip->ip;
      $links = array();
      $links['delete'] = array(
        'title' => $this->t('delete'),
        'route_name' => 'ban.delete',
        'route_parameters' => array('ban_id' => $ip->iid),
      );
      $row[] = array(
        'data' => array(
          '#type' => 'operations',
          '#links' => $links,
        ),
      );
      $rows[] = $row;
    }

    $form['ip'] = array(
      '#title' => $this->t('IP address'),
      '#type' => 'textfield',
      '#size' => 48,
      '#maxlength' => 40,
      '#default_value' => $default_ip,
      '#description' => $this->t('Enter a valid IP address.'),
    );
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Add'),
    );

    $form['ban_ip_banning_table'] = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No blocked IP addresses available.'),
      '#weight' => 120,
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    $ip = trim($form_state['values']['ip']);
    if ($this->ipManager->isBanned($ip)) {
      $this->setFormError('ip', $form_state, $this->t('This IP address is already banned.'));
    }
    elseif ($ip == $this->getRequest()->getClientIP()) {
      $this->setFormError('ip', $form_state, $this->t('You may not ban your own IP address.'));
    }
    elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE) == FALSE) {
      $this->setFormError('ip', $form_state, $this->t('Enter a valid IP address.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $ip = trim($form_state['values']['ip']);
    $this->ipManager->banIp($ip);
    drupal_set_message($this->t('The IP address %ip has been banned.', array('%ip' => $ip)));
    $form_state['redirect_route']['route_name'] = 'ban.admin_page';
  }

}
