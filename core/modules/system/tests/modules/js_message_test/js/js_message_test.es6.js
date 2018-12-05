/**
 * @file
 *  Testing behavior for JSMessageTest.
 */

(($, { behaviors }, { testMessages }) => {
  // Message types.
  const indexes = {};
  testMessages.types.forEach(type => {
    indexes[type] = [];
  });

  // Message storage.
  const messageObjects = {
    default: {
      zone: new Drupal.Message(),
      indexes,
    },
    multiple: [],
  };

  testMessages.selectors.filter(Boolean).forEach(selector => {
    messageObjects[selector] = {
      zone: new Drupal.Message(document.querySelector(selector)),
      indexes,
    };
  });

  /**
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Add click listeners that show and remove links with context and type.
   */
  behaviors.js_message_test = {
    attach() {
      $('[data-drupal-messages-area]')
        .once('messages-details')
        .on('click', '[data-action]', e => {
          const $target = $(e.currentTarget);
          const type = $target.attr('data-type');
          const area =
            $target
              .closest('[data-drupal-messages-area]')
              .attr('data-drupal-messages-area') || 'default';
          const message = messageObjects[area].zone;
          const action = $target.attr('data-action');

          if (action === 'add') {
            messageObjects[area].indexes[type].push(
              message.add(
                `This is a message of the type, ${type}. You be the the judge of its importance.`,
                { type },
              ),
            );
          } else if (action === 'remove') {
            message.remove(messageObjects[area].indexes[type].pop());
          }
        });
      $('[data-action="add-multiple"]')
        .once('add-multiple')
        .on('click', () => {
          /**
           * Add several of different types to make sure message type doesn't
           * cause issues in the API.
           */
          [0, 1, 2, 3, 4, 5].forEach(i => {
            messageObjects.multiple.push(
              messageObjects.default.zone.add(
                `This is message number ${i} of the type, ${
                  testMessages.types[i % testMessages.types.length]
                }. You be the the judge of its importance.`,
                { type: testMessages.types[i % testMessages.types.length] },
              ),
            );
          });
        });
      $('[data-action="remove-multiple"]')
        .once('remove-multiple')
        .on('click', () => {
          messageObjects.multiple.forEach(messageIndex =>
            messageObjects.default.zone.remove(messageIndex),
          );
          messageObjects.multiple = [];
        });
      $('[data-action="add-multiple-error"]')
        .once('add-multiple-error')
        .on('click', () => {
          // Use the same number of elements to facilitate things on the PHP side.
          [0, 1, 2, 3, 4, 5].forEach(i =>
            messageObjects.default.zone.add(`Msg-${i}`, { type: 'error' }),
          );
          messageObjects.default.zone.add(
            `Msg-${testMessages.types.length * 2}`,
            { type: 'status' },
          );
        });
      $('[data-action="remove-type"]')
        .once('remove-type')
        .on('click', () => {
          Array.prototype.map
            .call(
              document.querySelectorAll('[data-drupal-message-id^="error"]'),
              element => element.getAttribute('data-drupal-message-id'),
            )
            .forEach(id => messageObjects.default.zone.remove(id));
        });
      $('[data-action="clear-all"]')
        .once('clear-all')
        .on('click', () => {
          messageObjects.default.zone.clear();
        });
      $('[data-action="id-no-status"]')
        .once('id-no-status')
        .on('click', () => {
          messageObjects.default.zone.add('Msg-id-no-status', {
            id: 'my-special-id',
          });
        });
    },
  };
})(jQuery, Drupal, drupalSettings);
