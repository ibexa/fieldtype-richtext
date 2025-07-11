import { Plugin, Widget, AttributeCommand } from 'ckeditor5';

import IbexaCustomStyleInlineCommand from './custom-style-inline-command';

class IbexaCustomStyleInlineEditing extends Plugin {
    static get requires() {
        return [Widget];
    }

    constructor(props) {
        super(props);

        this.customStyleName = '';
    }

    defineConverters() {
        this.editor.model.schema.extend('$text', { allowAttributes: this.customStyleName });

        this.editor.conversion.for('editingDowncast').attributeToElement({
            model: this.customStyleName,
            view: (customStyleValue, { writer: downcastWriter }) =>
                downcastWriter.createAttributeElement(
                    'span',
                    {
                        'data-ezelement': 'eztemplateinline',
                        'data-eztype': 'style',
                        'data-ezname': this.customStyleName,
                    },
                    { priority: 5 },
                ),
        });

        this.editor.conversion.for('dataDowncast').attributeToElement({
            model: this.customStyleName,
            view: (customStyleValue, { writer: downcastWriter }) =>
                downcastWriter.createAttributeElement(
                    'span',
                    {
                        'data-ezelement': 'eztemplateinline',
                        'data-eztype': 'style',
                        'data-ezname': this.customStyleName,
                    },
                    { priority: 5 },
                ),
        });

        this.editor.conversion.for('upcast').elementToAttribute({
            view: {
                name: 'span',
                attributes: {
                    'data-ezelement': 'eztemplateinline',
                    'data-eztype': 'style',
                    'data-ezname': this.customStyleName,
                },
            },
            model: {
                key: this.customStyleName,
                value: this.customStyleName,
            },
        });
    }

    init() {
        this.defineConverters();

        this.editor.commands.add(this.customStyleName, new AttributeCommand(this.editor, this.customStyleName));
        this.editor.commands.add('ibexaCustomStyleInline', new IbexaCustomStyleInlineCommand(this.editor));
    }
}

export default IbexaCustomStyleInlineEditing;
