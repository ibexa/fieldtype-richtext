import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import Widget from '@ckeditor/ckeditor5-widget/src/widget';

import IbexaUploadImageCommand from './upload-image-command';

class IbexaUploadImageEditing extends Plugin {
    static get requires() {
        return [Widget];
    }

    addListeners() {
        this.listenTo(this.editor.editing.view.document, 'drop', (event, data) => {
            const { files } = data.dataTransfer;

            if (!files.length) {
                return;
            }

            this.editor.model.change((writer) => {
                writer.setSelection(this.editor.editing.mapper.toModelRange(data.dropRange));
            });

            files.forEach((file) => {
                if (file.type.includes('image')) {
                    this.editor.execute('insertIbexaUploadImage', { file });
                }
            });
        });
    }

    init() {
        this.addListeners();

        this.editor.commands.add('insertIbexaUploadImage', new IbexaUploadImageCommand(this.editor));
    }
}

export default IbexaUploadImageEditing;
