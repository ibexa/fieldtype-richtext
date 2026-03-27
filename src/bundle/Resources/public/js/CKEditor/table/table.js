import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import ContextualBalloon from '@ckeditor/ckeditor5-ui/src/panel/balloon/contextualballoon';
import BalloonPanelView from '@ckeditor/ckeditor5-ui/src/panel/balloon/balloonpanelview';

import { addPredefinedClassToConfig } from '../custom-attributes/helpers/config-helper';

class IbexaTable extends Plugin {
    constructor(props) {
        super(props);

        addPredefinedClassToConfig('table', 'table');
    }

    afterInit() {
        const FIXED_BOTTOM_MARGIN = 40;
        const FIXED_TOP_MARGIN = 12;
        const editor = this.editor;
        const contextualBalloon = editor.plugins.get(ContextualBalloon);
        const toolbarPanel = editor.ui.view.panel;

        const fixBalloonOverlap = () => {
            if (!toolbarPanel.isVisible || !contextualBalloon.visibleView) {
                return;
            }

            const currentPosition = contextualBalloon._visibleStack.get(contextualBalloon.visibleView).position;
            const isTableWidget = currentPosition.target.nodeName === 'FIGURE' && currentPosition.target.classList.contains('table');

            if (!isTableWidget) {
                return;
            }

            const targetRect = currentPosition.target.getBoundingClientRect();
            const balloonElRect = contextualBalloon.view.element.getBoundingClientRect();
            const fitsBelow = targetRect.bottom + balloonElRect.height + FIXED_BOTTOM_MARGIN <= window.innerHeight;

            if (fitsBelow) {
                contextualBalloon.updatePosition({
                    ...currentPosition,
                    positions: [BalloonPanelView.defaultPositions.southArrowNorth],
                });
            } else {
                const stickyTop = (targetRect) => {
                    const toolbarBottom = toolbarPanel.element.getBoundingClientRect().bottom;

                    return {
                        top: Math.max(toolbarBottom, targetRect.top) + FIXED_TOP_MARGIN,
                        left: balloonElRect.left,
                        name: 'arrow_n',
                    };
                };

                contextualBalloon.updatePosition({
                    ...currentPosition,
                    positions: [stickyTop],
                });
            }
        };

        editor.ui.on('update', fixBalloonOverlap, { priority: 'low' });

        document.defaultView.addEventListener('scroll', fixBalloonOverlap, true);
    }
}

export default IbexaTable;
