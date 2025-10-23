import Plugin from '@ckeditor/ckeditor5-core/src/plugin';

import { addPredefinedClassToConfig } from '../custom-attributes/helpers/config-helper';

class IbexaTable extends Plugin {
    constructor(props) {
        super(props);

        addPredefinedClassToConfig('table', 'table');
    }
}

export default IbexaTable;
