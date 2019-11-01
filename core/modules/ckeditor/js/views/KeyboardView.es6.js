/**
 * @file
 * Backbone View providing the aural view of CKEditor keyboard UX configuration.
 */

(function($, Drupal, Backbone, _) {
  Drupal.ckeditor.KeyboardView = Backbone.View.extend(
    /** @lends Drupal.ckeditor.KeyboardView# */ {
      /**
       * Backbone View for CKEditor toolbar configuration; keyboard UX.
       *
       * @constructs
       *
       * @augments Backbone.View
       */
      initialize() {
        // Add keyboard arrow support.
        this.$el.on(
          'keydown.ckeditor',
          '.ckeditor-buttons a, .ckeditor-multiple-buttons a',
          this.onPressButton.bind(this),
        );
        this.$el.on(
          'keydown.ckeditor',
          '[data-drupal-ckeditor-type="group"]',
          this.onPressGroup.bind(this),
        );
      },

      /**
       * @inheritdoc
       */
      render() {},

      /**
       * Handles keypresses on a CKEditor configuration button.
       *
       * @param {jQuery.Event} event
       *   The keypress event triggered.
       */
      onPressButton(event) {
        const upDownKeys = [
          38, // Up arrow.
          63232, // Safari up arrow.
          40, // Down arrow.
          63233, // Safari down arrow.
        ];
        const leftRightKeys = [
          37, // Left arrow.
          63234, // Safari left arrow.
          39, // Right arrow.
          63235, // Safari right arrow.
        ];

        // Respond to an enter key press. Prevent the bubbling of the enter key
        // press to the button group parent element.
        if (event.keyCode === 13) {
          event.stopPropagation();
        }

        // Only take action when a direction key is pressed.
        if (_.indexOf(_.union(upDownKeys, leftRightKeys), event.keyCode) > -1) {
          let view = this;
          let $target = $(event.currentTarget);
          let $button = $target.parent();
          const $container = $button.parent();
          let $group = $button.closest('.ckeditor-toolbar-group');
          let $row;
          const containerType = $container.data(
            'drupal-ckeditor-button-sorting',
          );
          const $availableButtons = this.$el.find(
            '[data-drupal-ckeditor-button-sorting="source"]',
          );
          const $activeButtons = this.$el.find('.ckeditor-toolbar-active');
          // The current location of the button, just in case it needs to be put
          // back.
          const $originalGroup = $group;
          let dir;

          // Move available buttons between their container and the active
          // toolbar.
          if (containerType === 'source') {
            // Move the button to the active toolbar configuration when the down
            // or up keys are pressed.
            if (_.indexOf([40, 63233], event.keyCode) > -1) {
              // Move the button to the first row, first button group index
              // position.
              $activeButtons
                .find('.ckeditor-toolbar-group-buttons')
                .eq(0)
                .prepend($button);
            }
          } else if (containerType === 'target') {
            // Move buttons between sibling buttons in a group and between groups.
            if (_.indexOf(leftRightKeys, event.keyCode) > -1) {
              // Move left.
              const $siblings = $container.children();
              const index = $siblings.index($button);
              if (_.indexOf([37, 63234], event.keyCode) > -1) {
                // Move between sibling buttons.
                if (index > 0) {
                  $button.insertBefore($container.children().eq(index - 1));
                }
                // Move between button groups and rows.
                else {
                  // Move between button groups.
                  $group = $container.parent().prev();
                  if ($group.length > 0) {
                    $group
                      .find('.ckeditor-toolbar-group-buttons')
                      .append($button);
                  }
                  // Wrap between rows.
                  else {
                    $container
                      .closest('.ckeditor-row')
                      .prev()
                      .find('.ckeditor-toolbar-group')
                      .not('.placeholder')
                      .find('.ckeditor-toolbar-group-buttons')
                      .eq(-1)
                      .append($button);
                  }
                }
              }
              // Move right.
              else if (_.indexOf([39, 63235], event.keyCode) > -1) {
                // Move between sibling buttons.
                if (index < $siblings.length - 1) {
                  $button.insertAfter($container.children().eq(index + 1));
                }
                // Move between button groups. Moving right at the end of a row
                // will create a new group.
                else {
                  $container
                    .parent()
                    .next()
                    .find('.ckeditor-toolbar-group-buttons')
                    .prepend($button);
                }
              }
            }
            // Move buttons between rows and the available button set.
            else if (_.indexOf(upDownKeys, event.keyCode) > -1) {
              dir =
                _.indexOf([38, 63232], event.keyCode) > -1 ? 'prev' : 'next';
              $row = $container.closest('.ckeditor-row')[dir]();
              // Move the button back into the available button set.
              if (dir === 'prev' && $row.length === 0) {
                // If this is a divider, just destroy it.
                if ($button.data('drupal-ckeditor-type') === 'separator') {
                  $button.off().remove();
                  // Focus on the first button in the active toolbar.
                  $activeButtons
                    .find('.ckeditor-toolbar-group-buttons')
                    .eq(0)
                    .children()
                    .eq(0)
                    .children()
                    .trigger('focus');
                }
                // Otherwise, move it.
                else {
                  $availableButtons.prepend($button);
                }
              } else {
                $row
                  .find('.ckeditor-toolbar-group-buttons')
                  .eq(0)
                  .prepend($button);
              }
            }
          }
          // Move dividers between their container and the active toolbar.
          else if (containerType === 'dividers') {
            // Move the button to the active toolbar configuration when the down
            // or up keys are pressed.
            if (_.indexOf([40, 63233], event.keyCode) > -1) {
              // Move the button to the first row, first button group index
              // position.
              $button = $button.clone(true);
              $activeButtons
                .find('.ckeditor-toolbar-group-buttons')
                .eq(0)
                .prepend($button);
              $target = $button.children();
            }
          }

          view = this;
          // Attempt to move the button to the new toolbar position.
          Drupal.ckeditor.registerButtonMove(this, $button, result => {
            // Put the button back if the registration failed.
            // If the button was in a row, then it was in the active toolbar
            // configuration. The button was probably placed in a new group, but
            // that action was canceled.
            if (!result && $originalGroup) {
              $originalGroup.find('.ckeditor-buttons').append($button);
            }
            // Refocus the target button so that the user can continue from a
            // known place.
            $target.trigger('focus');
          });

          event.preventDefault();
          event.stopPropagation();
        }
      },

      /**
       * Handles keypresses on a CKEditor configuration group.
       *
       * @param {jQuery.Event} event
       *   The keypress event triggered.
       */
      onPressGroup(event) {
        const upDownKeys = [
          38, // Up arrow.
          63232, // Safari up arrow.
          40, // Down arrow.
          63233, // Safari down arrow.
        ];
        const leftRightKeys = [
          37, // Left arrow.
          63234, // Safari left arrow.
          39, // Right arrow.
          63235, // Safari right arrow.
        ];

        // Respond to an enter key press.
        if (event.keyCode === 13) {
          const view = this;
          // Open the group renaming dialog in the next evaluation cycle so that
          // this event can be cancelled and the bubbling wiped out. Otherwise,
          // Firefox has issues because the page focus is shifted to the dialog
          // along with the keydown event.
          window.setTimeout(() => {
            Drupal.ckeditor.openGroupNameDialog(view, $(event.currentTarget));
          }, 0);
          event.preventDefault();
          event.stopPropagation();
        }

        // Respond to direction key presses.
        if (_.indexOf(_.union(upDownKeys, leftRightKeys), event.keyCode) > -1) {
          const $group = $(event.currentTarget);
          const $container = $group.parent();
          const $siblings = $container.children();
          let index;
          let dir;
          // Move groups between sibling groups.
          if (_.indexOf(leftRightKeys, event.keyCode) > -1) {
            index = $siblings.index($group);
            // Move left between sibling groups.
            if (_.indexOf([37, 63234], event.keyCode) > -1) {
              if (index > 0) {
                $group.insertBefore($siblings.eq(index - 1));
              }
              // Wrap between rows. Insert the group before the placeholder group
              // at the end of the previous row.
              else {
                const $rowChildElement = $container
                  .closest('.ckeditor-row')
                  .prev()
                  .find('.ckeditor-toolbar-groups')
                  .children()
                  .eq(-1);
                $group.insertBefore($rowChildElement);
              }
            }
            // Move right between sibling groups.
            else if (_.indexOf([39, 63235], event.keyCode) > -1) {
              // Move to the right if the next group is not a placeholder.
              if (!$siblings.eq(index + 1).hasClass('placeholder')) {
                $group.insertAfter($container.children().eq(index + 1));
              }
              // Wrap group between rows.
              else {
                $container
                  .closest('.ckeditor-row')
                  .next()
                  .find('.ckeditor-toolbar-groups')
                  .prepend($group);
              }
            }
          }
          // Move groups between rows.
          else if (_.indexOf(upDownKeys, event.keyCode) > -1) {
            dir = _.indexOf([38, 63232], event.keyCode) > -1 ? 'prev' : 'next';
            $group
              .closest('.ckeditor-row')
              [dir]()
              .find('.ckeditor-toolbar-groups')
              .eq(0)
              .prepend($group);
          }

          Drupal.ckeditor.registerGroupMove(this, $group);
          $group.trigger('focus');
          event.preventDefault();
          event.stopPropagation();
        }
      },
    },
  );
})(jQuery, Drupal, Backbone, _);
