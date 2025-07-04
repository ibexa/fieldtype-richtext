import { Plugin } from 'ckeditor5';

import IbexaCustomAttributesUI from './custom-attributes-ui';
import IbexaCustomAttributesEditing from './custom-attributes-editing';

class IbexaCustomAttributes extends Plugin {
    static get requires() {
        return [IbexaCustomAttributesUI, IbexaCustomAttributesEditing];
    }
}

export default IbexaCustomAttributes;
