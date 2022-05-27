import Plugin from '@ckeditor/ckeditor5-core/src/plugin';

import IbexaButtonView from '../common/button-view/button-view';

const { ibexa, Translator } = window;

class IbexaUploadImageUI extends Plugin {
    constructor(props) {
        super(props);

        this.openImageSelector = this.openImageSelector.bind(this);
    }

    createFileSelector() {
        const fileSelector = document.createElement('input');

        fileSelector.setAttribute('type', 'file');
        fileSelector.setAttribute('accept', 'image/*');

        return fileSelector;
    }

    openImageSelector() {
        const fileSelector = this.createFileSelector();

        fileSelector.addEventListener(
            'change',
            ({ currentTarget }) => this.editor.execute('insertIbexaUploadImage', { file: currentTarget.files[0] }),
            false,
        );

        fileSelector.click();
    }

    init() {
        this.editor.ui.componentFactory.add('ibexaUploadImage', (locale) => {
            const buttonView = new IbexaButtonView(locale);

            buttonView.set({
                label: Translator.trans(/*@Desc("Upload image")*/ 'upload_image_btn.label', {}, 'ck_editor'),
                icon: ibexa.helpers.icon.getIconPath('upload-image'),
                tooltip: true,
            });

            this.listenTo(buttonView, 'execute', this.openImageSelector);

            return buttonView;
        });
    }
}

export default IbexaUploadImageUI;
