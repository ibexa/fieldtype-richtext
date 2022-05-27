import Command from '@ckeditor/ckeditor5-core/src/command';

const { ibexa, Routing, Translator } = window;
const { showErrorNotification } = ibexa.helpers.notification;

class IbexaUploadImageCommand extends Command {
    execute({ file }) {
        const languageCode = document.querySelector('meta[name="LanguageCode"]').content;
        const token = document.querySelector('meta[name="CSRF-Token"]').content;
        const assetCreateUri = Routing.generate('ibexa.asset.upload_image');
        const form = new FormData();

        form.append('languageCode', languageCode);
        form.append('file', file);

        const options = {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-CSRF-Token': token,
            },
            body: form,
            mode: 'same-origin',
            credentials: 'same-origin',
        };

        fetch(assetCreateUri, options)
            .then(ibexa.helpers.request.getJsonFromResponse)
            .then(ibexa.helpers.request.handleRequest)
            .then((response) => {
                this.editor.execute('insertIbexaEmbedImage', {
                    contentId: response.destinationContent.id,
                    size: 'medium',
                });
            })
            .catch((error) => {
                const message = Translator.trans(
                    /* @Desc("Error while creating Image: %error%") */ 'upload_image.message.error',
                    { error: error.message },
                    'ck_editor',
                );

                showErrorNotification(message);
            });
    }
}

export default IbexaUploadImageCommand;
