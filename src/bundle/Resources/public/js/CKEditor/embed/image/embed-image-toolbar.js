import { Plugin, WidgetToolbarRepository, BalloonPanelView } from 'ckeditor5';

const { defaultPositions } = BalloonPanelView;

class IbexaEmbedImageToolbar extends Plugin {
    static get requires() {
        return [WidgetToolbarRepository];
    }

    getSelectedEmbedImageWidget(selection) {
        const viewElement = selection.getSelectedElement();
        const isEmbedImage = viewElement?.hasClass('ibexa-embed-type-image');

        return isEmbedImage ? viewElement : null;
    }

    afterInit() {
        const { editor } = this;
        const widgetToolbarRepository = editor.plugins.get(WidgetToolbarRepository);
        const balloon = editor.plugins.get('ContextualBalloon');

        widgetToolbarRepository.register('embedImage', {
            ariaLabel: editor.t('Embed Image toolbar'),
            items: editor.config.get('embedImage.toolbar') || [],
            getRelatedElement: this.getSelectedEmbedImageWidget,
        });

        const toolbarDefinition = widgetToolbarRepository._toolbarDefinitions.get('embedImage');

        editor.ui.on('update', () => {
            if (balloon.visibleView !== toolbarDefinition?.view) {
                return;
            }

            const relatedElement = this.getSelectedEmbedImageWidget(editor.editing.view.document.selection);

            if (!relatedElement) {
                return;
            }

            const domElement = editor.editing.view.domConverter.mapViewToDom(relatedElement);
            const editorSourceElementRect = editor.sourceElement.getBoundingClientRect();
            const balloonRect = balloon.view.element.getBoundingClientRect();
            const isOverlapped = balloonRect.top < editorSourceElementRect.top;

            if (isOverlapped) {
                balloon.updatePosition({
                    target: domElement,
                    positions: [
                        defaultPositions.southArrowNorth,
                        defaultPositions.southArrowNorthWest,
                        defaultPositions.southArrowNorthEast,
                    ],
                });
            }
        });
    }
}

export default IbexaEmbedImageToolbar;
