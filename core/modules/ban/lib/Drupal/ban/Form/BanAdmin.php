<?php

/**
 * @file
 * Contains \Drupal\ban\Form\BanAdmin.
 */

namespace Drupal\ban\Form;

use Drupal\Core\Controller\ControllerInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\ban\BanIpManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displays banned IP addresses.
 */
class BanAdmin implements FormInterface, ControllerInterface {

  /**
   * @var \Drupal\ban\BanIpManager
   */
  protected $ipManager;

  /**
   * The request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

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
  public function getFormID() {
    return 'ban_ip_form';
  }

  /**
   * {@inheritdoc}
   *
   * @param string $default_ip
   *   (optional) IP address to be passed on to drupal_get_form() for use as the
   *   default value of the IP address form field.
   */
  public function buildForm(array $form, array &$form_state, Request $request = NULL, $default_ip = '') {
    $this->request = $request;
    $rows = array();
    $header = array(t('banned IP addresses'), t('Operations'));
    $result = $this->ipManager->findAll();
    foreach ($result as $ip) {
      $row = array();
      $row[] = $ip->ip;
      $links = array();
      $links['delete'] = array(
        'title' => t('delete'),
        'href' => "admin/config/people/ban/delete/$ip->iid",
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
      '#title' => t('IP address'),
      '#type' => 'textfield',
      '#size' => 48,
      '#maxlength' => 40,
      '#default_value' => $default_ip,
      '#description' => t('Enter a valid IP address.'),
    );
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Add'),
    );

    $form['ban_ip_banning_table'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => t('No blocked IP addresses available.'),
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
      form_set_error('ip', t('This IP address is already banned.'));
    }
    elseif ($ip == $this->request->getClientIP()) {
      form_set_error('ip', t('You may not ban your own IP address.'));
    }
    elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE) == FALSE) {
      form_set_error('ip', t('Enter a valid IP address.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $ip = trim($form_state['values']['ip']);
    $this->ipManager->banIp($ip);
    drupal_set_message(t('The IP address %ip has been banned.', array('%ip' => $ip)));
    $form_state['redirect'] = 'admin/config/people/ban';
  }

}
