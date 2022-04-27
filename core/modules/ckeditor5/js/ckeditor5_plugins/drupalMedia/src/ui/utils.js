/* eslint-disable import/no-extraneous-dependencies */

import { BalloonPanelView } from 'ckeditor5/src/ui';
import { getClosestSelectedDrupalMediaWidget } from '../utils';

/**
 * Returns the positioning options that control the geometry of the contextual
 * balloon with respect to the selected element in the editor content.
 *
 * @param {module:core/editor/editor~Editor} editor
 *   The editor instance.
 * @return {Object}
 *   The options.
 *
 * @private
 */
export function getBalloonPositionData(editor) {
  const editingView = editor.editing.view;
  const defaultPositions = BalloonPanelView.defaultPositions;

  return {
    target: editingView.domConverter.viewToDom(
      editingView.document.selection.getSelectedElement(),
    ),
    positions: [
      defaultPositions.northArrowSouth,
      defaultPositions.northArrowSouthWest,
      defaultPositions.northArrowSouthEast,
      defaultPositions.southArrowNorth,
      defaultPositions.southArrowNorthWest,
      defaultPositions.southArrowNorthEast,
    ],
  };
}

/**
 * A helper utility that positions the contextual balloon instance with respect
 * to the image in the editor content, if one is selected.
 *
 * @param {module:core/editor/editor~Editor} editor
 *   The editor instance.
 *
 * @private
 */
export function repositionContextualBalloon(editor) {
  const balloon = editor.plugins.get('ContextualBalloon');

  if (
    getClosestSelectedDrupalMediaWidget(editor.editing.view.document.selection)
  ) {
    const position = getBalloonPositionData(editor);

    balloon.updatePosition(position);
  }
}
