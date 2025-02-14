/**
 * @file
 * Message template overrides.
 */

((Drupal) => {
  customElements.define(
    'drupal-umami-messages',
    class extends HTMLElement {
      constructor() {
        super();
        const template = document.getElementById('umami-messages-template');
        const templateContent = template.content;

        const shadowRoot = this.attachShadow({ mode: 'open' });
        shadowRoot.appendChild(templateContent.cloneNode(true));
      }
    },
  );

  /**
   * Overrides message theme function.
   *
   * @param {object} message
   *   The message object.
   * @param {string} message.text
   *   The message text.
   * @param {object} options
   *   The message context.
   * @param {string} options.type
   *   The message type.
   * @param {string} options.id
   *   ID of the message, for reference.
   *
   * @return {HTMLElement}
   *   A DOM Node.
   */
  Drupal.theme.message = ({ text }, { type, id }) => {
    const messagesTypes = Drupal.Message.getMessageTypeLabels();
    const messageWrapper = document.createElement('drupal-umami-messages');

    messageWrapper.setAttribute('class', `messages messages--${type}`);
    messageWrapper.setAttribute(
      'role',
      type === 'error' || type === 'warning' ? 'alert' : 'status',
    );
    messageWrapper.setAttribute('data-drupal-message-id', id);
    messageWrapper.setAttribute('data-drupal-message-type', type);

    messageWrapper.innerHTML = `
    <span slot="title">
      ${messagesTypes[type]}
    </span>
    <span class="messages__item" slot="content">
      ${text}
    </span>
  `;

    return messageWrapper;
  };
})(Drupal);
