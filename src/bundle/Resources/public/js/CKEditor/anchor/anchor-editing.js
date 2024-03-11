import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import Widget from '@ckeditor/ckeditor5-widget/src/widget';

class IbexaAnchorEditing extends Plugin {
    static get requires() {
        return [Widget];
    }

    defineConverters() {
        const { conversion } = this.editor;

        conversion.attributeToAttribute({
            model: {
                key: 'anchor',
            },
            view: {
                key: 'id',
            },
        });

        this.editor.conversion.for('dataDowncast').add((dispatcher) => {
            dispatcher.on('attribute:anchor:listItem', (event, data, conversionApi) => {
                if (data.attributeKey !== 'anchor') {
                    return;
                }

                const viewItem = conversionApi.mapper.toViewElement(data.item);
                const previousElement = viewItem.parent.previousSibling;

                if (data.attributeNewValue) {
                    conversionApi.writer.setAttribute('id', data.attributeNewValue, viewItem.parent);
                } else {
                    conversionApi.writer.removeAttribute('id', viewItem.parent);
                }

                conversionApi.writer.removeAttribute('id', viewItem);

                if (previousElement?.name === viewItem.parent.name) {
                    conversionApi.writer.mergeContainers(conversionApi.writer.createPositionAfter(previousElement));
                }
            });
        });

        this.editor.conversion.for('editingDowncast').add((dispatcher) => {
            dispatcher.on('attribute:anchor:listItem', (event, data, conversionApi) => {
                if (data.attributeKey !== 'anchor') {
                    return;
                }

                const viewItem = conversionApi.mapper.toViewElement(data.item);
                const previousElement = viewItem.parent.previousSibling;
                const nextElement = viewItem.parent.nextSibling;

                if (data.attributeNewValue) {
                    conversionApi.writer.setAttribute('id', data.attributeNewValue, viewItem.parent);
                } else {
                    conversionApi.writer.removeAttribute('id', viewItem.parent);
                }

                conversionApi.writer.removeAttribute('id', viewItem);

                if (previousElement?.name === viewItem.parent.name) {
                    conversionApi.writer.mergeContainers(conversionApi.writer.createPositionAfter(previousElement));
                }

                if (nextElement?.name === viewItem.parent.name) {
                    conversionApi.writer.mergeContainers(conversionApi.writer.createPositionBefore(nextElement));
                }
            });
        });

        this.editor.conversion.for('upcast').add((dispatcher) => {
            dispatcher.on('element:li', (event, data, conversionApi) => {
                const listParent = data.viewItem.parent;
                const listItem = data.modelRange.start.nodeAfter ?? data.modelRange.end.nodeBefore;
                const id = listParent.getAttribute('id');

                conversionApi.writer.setAttribute('anchor', id, listItem);
            });
        });
    }

    init() {
        const { commands, model } = this.editor;

        model.schema.extend('$block', { allowAttributes: 'anchor' });
        model.schema.extend('embed', { allowAttributes: 'anchor' });
        model.schema.extend('embedInline', { allowAttributes: 'anchor' });
        model.schema.extend('embedImage', { allowAttributes: 'anchor' });
        model.schema.extend('customTag', { allowAttributes: 'anchor' });
        model.schema.extend('formatted', { allowAttributes: 'anchor' });

        commands.get('enter').on('afterExecute', () => {
            const blocks = model.document.selection.getSelectedBlocks();

            for (const block of blocks) {
                model.change((writer) => {
                    writer.removeAttribute('anchor', block);
                });
            }
        });

        this.defineConverters();
    }
}

export default IbexaAnchorEditing;
