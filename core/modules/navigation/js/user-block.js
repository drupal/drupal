/**
 * @file
 *
 * Customizes the user block to use the current username without affecting
 * caching.
 */

((Drupal, drupalSettings, once) => {
  /**
   * Replaces the generic 'My Account' text with the actual username.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Sets the username.
   */
  Drupal.behaviors.navigationUsername = {
    attach: (context, settings) => {
      if (!settings?.navigation?.user) {
        return;
      }
      once('user-block', '[data-user-block]', context).forEach((userBlock) => {
        userBlock
          .querySelectorAll(
            '.toolbar-button--icon--navigation-user-links-user-wrapper, .toolbar-popover__header',
          )
          .forEach((button) => {
            const buttonLabel = button.querySelector('[data-toolbar-text]');
            button.dataset.indexText = settings.navigation.user.charAt(0);
            button.dataset.iconText = settings.navigation.user.substring(0, 2);
            if (buttonLabel) {
              buttonLabel.textContent = settings.navigation.user;
            }
          });
      });
    },
  };
})(Drupal, drupalSettings, once);
