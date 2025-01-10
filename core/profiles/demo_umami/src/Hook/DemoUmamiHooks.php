<?php

namespace Drupal\demo_umami\Hook;

use Drupal\contact\Entity\ContactForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;

/**
 * Hook implementations for demo_umami.
 */
class DemoUmamiHooks {

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match): string {
    $output = '';
    switch ($route_name) {
      case 'help.page.demo_umami':
        $output .= '<h2>' . t('About') . '</h2>';
        $output .= '<p>' . t('Umami is an example food magazine website that demonstrates some of the features of Drupal core. It is intended to be used as an example site, rather than as a foundation for building your own site. For more information, see the <a href=":demo_umami">online documentation for the Umami installation profile</a>.', [':demo_umami' => 'https://www.drupal.org/node/2941833']) . '</p>';
        $output .= '<h2>' . t('Uses') . '</h2>';
        $output .= '<h3>' . t('Demonstrating Drupal core functionality') . '</h3>';
        $output .= '<p>' . t('You can look around the site to get ideas for what kinds of features Drupal is capable of, and to see how an actual site can be built using Drupal core.') . '</p>';
        $output .= '<h3>' . t('Sample content') . '</h3>';
        $output .= '<p>' . t('The Umami profile is very handy if you are developing a feature and need some sample content.') . '</p>';
        $output .= '<h2>' . t('What to do when you are ready to build your Drupal website') . '</h2>';
        $output .= '<p>' . t("Once you've tried Drupal using Umami and want to build your own site, simply reinstall Drupal and select a different installation profile (such as Standard) from the install screen.") . '</p>';
    }

    return $output;
  }

  /**
   * Implements hook_form_FORM_ID_alter() for install_configure_form().
   *
   * Allows the profile to alter the site configuration form.
   */
  #[Hook('form_install_configure_form_alter')]
  public function formInstallConfigureFormAlter(&$form, FormStateInterface $form_state): void {
    $form['site_information']['site_name']['#default_value'] = 'Umami Food Magazine';
    $form['#submit'][] = [$this, 'installConfigureSubmit'];
  }

  /**
   * Submission handler to sync the contact.form.feedback recipient.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   */
  public function installConfigureSubmit(array $form, FormStateInterface $form_state): void {
    $site_mail = $form_state->getValue('site_mail');
    ContactForm::load('feedback')->setRecipients([$site_mail])->trustData()->save();

    $password = $form_state->getValue('account')['pass'];
    $this->setUserPasswords($password);
  }

  /**
   * Implements hook_toolbar().
   */
  #[Hook('toolbar')]
  public function toolbar(): array {
    // Add a warning about using an experimental profile.
    // @todo This can be removed once a generic warning for experimental profiles
    //   has been introduced. https://www.drupal.org/project/drupal/issues/2934374
    $items['experimental-profile-warning'] = [
      '#weight' => 3400,
      '#cache' => [
        'contexts' => ['route'],
      ],
    ];

    // Show warning only on administration pages.
    $admin_context = \Drupal::service('router.admin_context');
    if ($admin_context->isAdminRoute()) {
      $link_to_help_page = \Drupal::moduleHandler()->moduleExists('help') && \Drupal::currentUser()->hasPermission('access help pages');
      $items['experimental-profile-warning']['#type'] = 'toolbar_item';
      $items['experimental-profile-warning']['tab'] = [
        '#type' => 'inline_template',
        '#template' => '<a class="toolbar-warning" href="{{ more_info_link }}">This site is intended for demonstration purposes.</a>',
        '#context' => [
          // Link directly to the drupal.org documentation if the help pages
          // aren't available.
          'more_info_link' => $link_to_help_page ? Url::fromRoute('help.page', ['name' => 'demo_umami'])
            : 'https://www.drupal.org/node/2941833',
        ],
        '#attached' => [
          'library' => ['demo_umami/toolbar-warning'],
        ],
      ];
    }
    return $items;
  }

  /**
   * Sets the password of admin to be the password for all users.
   */
  public function setUserPasswords(#[\SensitiveParameter] $admin_password): void {
    // Collect the IDs of all users with roles editor or author.
    $ids = \Drupal::entityQuery('user')
      ->accessCheck(FALSE)
      ->condition('roles', ['author', 'editor'], 'IN')
      ->execute();

    $users = \Drupal::entityTypeManager()->getStorage('user')->loadMultiple($ids);

    foreach ($users as $user) {
      $user->setPassword($admin_password);
      $user->save();
    }
  }

}
