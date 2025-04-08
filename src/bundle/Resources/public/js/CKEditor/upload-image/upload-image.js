import Plugin from '@ckeditor/ckeditor5-core/src/plugin';

import IbexaUploadImageEditing from './upload-image-editing';
import IbexaUploadImageUI from './upload-image-ui';

class IbexaUploadImage extends Plugin {
    static get requires() {
        return [IbexaUploadImageEditing, IbexaUploadImageUI];
    }
}

export default IbexaUploadImage;
