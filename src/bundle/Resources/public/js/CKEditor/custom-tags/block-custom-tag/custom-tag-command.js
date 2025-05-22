import { Command } from 'ckeditor5';

class IbexaCustomTagCommand extends Command {
    execute(customTagData) {
        this.editor.model.change((writer) => {
            writer.setSelection(this.editor.model.document.selection.getFirstPosition().parent, 'end');

            this.editor.model.insertContent(this.createCustomTag(writer, customTagData));
        });
    }

    createCustomTag(writer, { customTagName, values }) {
        const customTag = writer.createElement('customTag', { customTagName, values });
        const customTagContent = writer.createElement('customTagContent');

        writer.append(customTagContent, customTag);
        writer.appendElement('paragraph', customTagContent);

        return customTag;
    }
}

export default IbexaCustomTagCommand;
