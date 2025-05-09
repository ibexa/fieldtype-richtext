import { Plugin, Widget } from 'ckeditor5';

import IbexaUploadImageCommand from './upload-image-command';

class IbexaUploadImageEditing extends Plugin {
    static get requires() {
        return [Widget];
    }

    addListeners() {
        this.listenTo(this.editor.editing.view.document, 'drop', (event, data) => {
            if (data.dataTransfer.effectAllowed === 'copyMove') {
                return;
            }

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
