import { Plugin, Widget } from 'ckeditor5';

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
            const anchorUpcastConverter = (event, data, conversionApi) => {
                if (!data.modelRange) {
                    Object.assign(data, conversionApi.convertChildren(data.viewItem, data.modelCursor));
                }

                const listParent = data.viewItem;
                const id = listParent.getAttribute('id');

                for (const listItem of data.modelRange.getItems({ shallow: true })) {
                    conversionApi.writer.setAttribute('anchor', id, listItem);
                }
            };

            dispatcher.on('element:ul', anchorUpcastConverter);
            dispatcher.on('element:ol', anchorUpcastConverter);
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
