/**
 * @file
 * Customization of messages.
 */

((Drupal, once) => {
  /**
   * Adds a close button to the message.
   *
   * @param {object} message
   *   The message object.
   */
  const closeMessage = (message) => {
    const messageContainer = message.querySelector(
      '[data-drupal-selector="messages-container"]',
    );
    if (!messageContainer.querySelector('.messages__button')) {
      const closeBtnWrapper = document.createElement('div');
      closeBtnWrapper.setAttribute('class', 'messages__button');

      const closeBtn = document.createElement('button');
      closeBtn.setAttribute('type', 'button');
      closeBtn.setAttribute('class', 'messages__close');

      const closeBtnText = document.createElement('span');
      closeBtnText.setAttribute('class', 'visually-hidden');
      closeBtnText.innerText = Drupal.t('Close message');

      messageContainer.appendChild(closeBtnWrapper);
      closeBtnWrapper.appendChild(closeBtn);
      closeBtn.appendChild(closeBtnText);

      closeBtn.addEventListener('click', () => {
        message.classList.add('hidden');
      });
    }
  };

  /**
   * Get messages from context.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the close button behavior for messages.
   */
  Drupal.behaviors.messages = {
    attach(context) {
      once('messages', '[data-drupal-selector="messages"]', context).forEach(
        closeMessage,
      );
    },
  };

  Drupal.olivero.closeMessage = closeMessage;
})(Drupal, once);
