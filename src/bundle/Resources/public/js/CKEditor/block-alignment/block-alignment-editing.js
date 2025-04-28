import { Plugin, Widget } from 'ckeditor5';

import IbexaBlockAlignmentCommand from './block-alignment-command';

class IbexaCustomAttributesEditing extends Plugin {
    static get requires() {
        return [Widget];
    }

    defineConverters() {
        const { conversion } = this.editor;

        conversion.attributeToAttribute({
            model: {
                key: 'data-ezalign',
            },
            view: {
                key: 'data-ezalign',
            },
        });
    }

    init() {
        const { model } = this.editor;

        model.schema.extend('embedImage', { allowAttributes: 'data-ezalign' });
        model.schema.extend('customTag', { allowAttributes: 'data-ezalign' });

        this.defineConverters();

        this.editor.commands.add('addBlockAlignment', new IbexaBlockAlignmentCommand(this.editor));
    }
}

export default IbexaCustomAttributesEditing;
