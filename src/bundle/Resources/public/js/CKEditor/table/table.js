import { Plugin } from 'ckeditor5';

import { addPredefinedClassToConfig } from '../custom-attributes/helpers/config-helper';

class IbexaTable extends Plugin {
    constructor(props) {
        super(props);

        addPredefinedClassToConfig('table', 'table');
    }
}

export default IbexaTable;
