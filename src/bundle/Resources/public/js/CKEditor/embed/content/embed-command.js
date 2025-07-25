import { Command } from 'ckeditor5';

class IbexaEmbedContentCommand extends Command {
    execute(contentData) {
        this.editor.model.change((writer) => {
            writer.setSelection(this.editor.model.document.selection.getFirstPosition().parent, 'end');

            this.editor.model.insertContent(this.createEmbed(writer, contentData));
        });
    }

    createEmbed(writer, { contentId, contentName, locationId, languageCodes }) {
        return writer.createElement('embed', { contentId, contentName, locationId, languageCodes });
    }
}

export default IbexaEmbedContentCommand;
