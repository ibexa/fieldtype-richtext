import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import { rawSnippetTextToViewDocumentFragment } from '@ckeditor/ckeditor5-code-block/src/utils';
import UpcastWriter from '@ckeditor/ckeditor5-engine/src/view/upcastwriter';
import Widget from '@ckeditor/ckeditor5-widget/src/widget';

import IbexaFormattedCommand from './formatted-command';

class IbexaFormattedEditing extends Plugin {
    static get requires() {
        return [Widget];
    }

    defineSchema() {
        const { schema } = this.editor.model;

        schema.register('formatted', {
            isBlock: true,
            allowIn: '$root',
            allowChildren: '$text',
        });

        schema.extend('$text', {
            allowIn: 'formatted',
        });
    }

    defineConverters() {
        const { conversion, model } = this.editor;

        conversion.for('editingDowncast').elementToElement({
            model: 'formatted',
            view: (modelElement, { writer: downcastWriter }) => downcastWriter.createContainerElement('pre'),
        });

        conversion.for('dataDowncast').elementToElement({
            model: 'formatted',
            view: (modelElement, { writer: downcastWriter }) => downcastWriter.createContainerElement('pre'),
        });

        this.editor.data.downcastDispatcher.on(
            'insert:softBreak',
            (event, data, conversionApi) => {
                if (data.item.parent.name !== 'formatted') {
                    return;
                }

                const { writer, mapper, consumable } = conversionApi;

                if (!consumable.consume(data.item, 'insert')) {
                    return;
                }

                const position = mapper.toViewPosition(model.createPositionBefore(data.item));

                writer.insert(position, writer.createText('\n'));
            },
            { priority: 'high' },
        );

        conversion.for('upcast').elementToElement({
            model: 'formatted',
            view: {
                name: 'pre',
            },
        });

        this.listenTo(this.editor.editing.view.document, 'clipboardInput', (event, data) => {
            const modelSelection = model.document.selection;

            if (!modelSelection.anchor.parent.is('element', 'formatted')) {
                return;
            }

            const text = data.dataTransfer.getData('text/plain');
            const writer = new UpcastWriter(this.editor.editing.view.document);

            data.content = rawSnippetTextToViewDocumentFragment(writer, text);
        });
    }

    init() {
        this.defineSchema();
        this.defineConverters();

        this.editor.commands.add('insertIbexaFormatted', new IbexaFormattedCommand(this.editor));
    }
}

export default IbexaFormattedEditing;
