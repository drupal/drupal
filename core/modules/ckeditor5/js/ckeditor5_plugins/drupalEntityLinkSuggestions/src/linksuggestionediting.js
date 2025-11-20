/* eslint-disable import/no-extraneous-dependencies */
// cspell:ignore linksuggestioncommand

import { Plugin } from 'ckeditor5/src/core';
import { findAttributeRange } from 'ckeditor5/src/typing';
import DrupalEntityLinkSuggestionMetadataCommand from './linksuggestioncommand';
import { getCurrentLinkRange, extractTextFromLinkRange } from './utils';

export default class DrupalEntityLinkSuggestionsEditing extends Plugin {
  init() {
    const { editor } = this;
    editor.commands.add(
      'linkSuggestionMetadata',
      new DrupalEntityLinkSuggestionMetadataCommand(editor),
    );

    this.attrs = [
      'data-entity-type',
      'data-entity-uuid',
      'data-entity-metadata',
    ];
    this.blockLinkAttrs = [
      'data-link-entity-type',
      'data-link-entity-uuid',
      'data-link-entity-metadata',
    ];
    this.blockLinkAttrsToModel = {
      'data-link-entity-type': 'drupalLinkEntityType',
      'data-link-entity-uuid': 'drupalLinkEntityUuid',
      'data-link-entity-metadata': 'drupalLinkEntityMetadata',
    };
    this._allowAndConvertExtraAttributes();
    this._removeExtraAttributesOnUnlinkCommandExecute();
    this._refreshExtraAttributeValues();
    this._addExtraAttributesOnLinkCommandExecute();
  }

  _allowAndConvertExtraAttributes() {
    const { editor } = this;
    editor.model.schema.extend('$text', { allowAttributes: this.attrs });

    this.attrs.forEach((attribute) => {
      editor.conversion.for('downcast').attributeToElement({
        model: attribute,
        view: (value, { writer }) => {
          const viewAttributes = {};
          viewAttributes[attribute] = value;
          const linkViewElement = writer.createAttributeElement(
            'a',
            viewAttributes,
            { priority: 5 },
          );

          // Without it the isLinkElement() will not recognize the link and the UI will not show up
          // when the user clicks a link.
          writer.setCustomProperty('link', true, linkViewElement);

          return linkViewElement;
        },
      });

      editor.conversion.for('upcast').elementToAttribute({
        view: {
          name: 'a',
          attributes: {
            [attribute]: true,
          },
        },
        model: {
          key: attribute,
          value: (viewElement) => viewElement.getAttribute(attribute),
        },
      });
    });
  }

  _addExtraAttributesOnLinkCommandExecute() {
    const { editor } = this;
    const linkCommand = editor.commands.get('link');
    let linkCommandExecuting = false;

    linkCommand.on(
      'execute',
      (evt, args) => {
        // Custom handling is only required if an extra attribute was passed into
        // editor.execute( 'link', ... ).
        if (linkCommandExecuting) {
          linkCommandExecuting = false;
          return;
        }

        // If the additional attribute was passed, we stop the default execution
        // of the LinkCommand. We're going to create Model#change() block for undo
        // and execute the LinkCommand together with setting the extra attribute.
        evt.stop();

        // Prevent infinite recursion by keeping records of when link command is
        // being executed by this function.
        linkCommandExecuting = true;
        let extraAttributeValues = [];
        // Per CKEditor v45, any decorators should be an object provided as
        // the [1] element to the execute params.
        // If no attributes are passed from the event (e.g., there wasn't a
        // change triggered from the autocomplete input), get values from state.
        if (args && args[1] && !args[1].entityLinkAttributes) {
          this.attrs.forEach((attribute) => {
            extraAttributeValues[attribute] = evt.source[attribute];
          });
          args[1].entityLinkAttributes = extraAttributeValues;
        } else {
          extraAttributeValues = args[1].entityLinkAttributes;
        }
        const { model } = editor;
        const { selection } = model.document;
        const displayedText = args[args.length - 1] || '';
        // This can update the Href value, so we need to know what the updated
        // value is to properly target ranges when the selection is collapsed.
        const currentHref = args[0];
        // Wrapping the original command execution in a model.change() block to
        // ensure there is a single undo step when the extra attribute is added.
        model.change((writer) => {
          const updateLinkTextIfNeeded = (range, displayedText) => {
            const linkText = extractTextFromLinkRange(range);
            if (!linkText) {
              return range;
            }
            // In a scenario where the displayedText is blank, fall back on the
            // linkText, and if that is empty, use the href from args[0].
            const newText = displayedText || linkText || args[0];
            const newRange = writer.createRange(
              range.start,
              range.start.getShiftedBy(newText.length),
            );
            return newRange;
          };

          editor.execute('link', ...args);

          this.attrs.forEach((attribute) => {
            if (selection.isCollapsed) {
              // The user has clicked somewhere within the link, so we need to
              // calculate the range of characters the attributes should apply
              // to.
              let range = getCurrentLinkRange(model, selection, currentHref);
              // In CKEditor v45, a new displayText input is present in the
              // link widget. So we need to recalculate the range in case the
              // text has changed.
              range = updateLinkTextIfNeeded(range, displayedText);

              if (extraAttributeValues[attribute]) {
                writer.setAttribute(
                  attribute,
                  extraAttributeValues[attribute],
                  range,
                );
              } else {
                writer.removeAttribute(attribute, range);
              }
              // The following is modeled after CKEditor5's collapseSelectionAtLinkEnd() method.
              writer.setSelection(range.end);
              const { plugins } = this.editor;
              if (plugins.has('TwoStepCaretMovement')) {
                // After replacing the text of the link, we need to move the caret to the end of the link,
                // override it's gravity to forward to prevent keeping e.g. bold attribute on the caret
                // which was previously inside the link.
                //
                // If the plugin is not available, the caret will be placed at the end of the link and the
                // bold attribute will be kept even if command moved caret outside the link.
                plugins.get('TwoStepCaretMovement')._handleForwardMovement();
              } else {
                // Remove any attributes to prevent link splitting.
                writer.removeSelectionAttribute(attribute);
              }
            } else {
              // The user has selected the entire link through highlighting it
              // so we don't need to do anything more to calculate the range.
              const ranges = model.schema.getValidRanges(
                selection.getRanges(),
                attribute,
              );

              // eslint-disable-next-line no-restricted-syntax
              for (const range of ranges) {
                if (extraAttributeValues[attribute]) {
                  writer.setAttribute(
                    attribute,
                    extraAttributeValues[attribute],
                    range,
                  );
                } else {
                  writer.removeAttribute(attribute, range);
                }
              }
            }
          });
          if (
            selection.getSelectedElement() &&
            ['imageBlock', 'drupalMedia'].includes(
              selection.getSelectedElement().name,
            )
          ) {
            const selectedElement = selection.getSelectedElement();

            this.blockLinkAttrs.forEach((attribute) => {
              if (extraAttributeValues[attribute]) {
                writer.setAttribute(
                  this.blockLinkAttrsToModel[attribute],
                  extraAttributeValues[attribute],
                  selectedElement,
                );
              } else {
                writer.removeAttribute(
                  this.blockLinkAttrsToModel[attribute],
                  selectedElement,
                );
              }
            });
          }
        });
      },
      { priority: 'high' },
    );
  }

  _removeExtraAttributesOnUnlinkCommandExecute() {
    const { editor } = this;
    const unlinkCommand = editor.commands.get('unlink');
    const { model } = editor;
    const { selection } = model.document;

    let isUnlinkingInProgress = false;

    // Make sure all changes are in a single undo step so cancel the original unlink first in the high priority.
    unlinkCommand.on(
      'execute',
      (evt) => {
        if (isUnlinkingInProgress) {
          return;
        }

        evt.stop();

        // This single block wraps all changes that should be in a single undo step.
        model.change(() => {
          // Now, in this single "undo block" let the unlink command flow naturally.
          isUnlinkingInProgress = true;

          // Do the unlinking within a single undo step.
          editor.execute('unlink');

          // Let's make sure the next unlinking will also be handled.
          isUnlinkingInProgress = false;

          // The actual integration that removes the extra attribute.
          model.change((writer) => {
            // Get ranges to unlink.
            let ranges;

            this.attrs.forEach((attribute) => {
              if (selection.isCollapsed) {
                ranges = [
                  findAttributeRange(
                    selection.getFirstPosition(),
                    attribute,
                    selection.getAttribute(attribute),
                    model,
                  ),
                ];
              } else {
                ranges = model.schema.getValidRanges(
                  selection.getRanges(),
                  attribute,
                );
              }

              // Remove the extra attribute from specified ranges.
              // eslint-disable-next-line no-restricted-syntax
              for (const range of ranges) {
                writer.removeAttribute(attribute, range);
              }
            });
          });
        });
      },
      { priority: 'high' },
    );
  }

  _refreshExtraAttributeValues() {
    const { editor } = this;
    const attributes = this.attrs;
    const linkCommand = editor.commands.get('link');
    const { model } = editor;
    const { selection } = model.document;

    attributes.forEach((attribute) => {
      linkCommand.set(attribute, null);
    });
    model.document.on('change', () => {
      attributes.forEach((attribute) => {
        linkCommand[attribute] = selection.getAttribute(attribute);
      });
    });
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'DrupalEntityLinkSuggestionsEditing';
  }
}
