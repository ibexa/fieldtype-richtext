import Plugin from '@ckeditor/ckeditor5-core/src/plugin';

const { Translator } = window;
class IbexaElementsPath extends Plugin {
    constructor(props) {
        super(props);

        this.elementsPathWrapper = null;
        this.isTableCellSelected = false;

        this.updatePath = this.updatePath.bind(this);
    }

    findElementSiblings(element, siblingsName) {
        let iterator = element;
        const elementSiblings = [element];

        while (iterator?.previousSibling?.name === siblingsName) {
            elementSiblings.unshift(iterator.previousSibling);

            iterator = iterator.previousSibling;
        }

        iterator = element;

        while (iterator?.nextSibling?.name === siblingsName) {
            elementSiblings.push(iterator.nextSibling);

            iterator = iterator.nextSibling;
        }

        return elementSiblings;
    }

    addListItem(element, index) {
        const label = Translator.trans(/*@Desc("list")*/ 'elements_path.list.label', {}, 'ck_editor');
        const pathItem = `<li class="ibexa-elements-path__item">${label}</li>`;
        const container = document.createElement('ul');

        container.insertAdjacentHTML('beforeend', pathItem);

        const listItemNode = container.querySelector('li');

        listItemNode.addEventListener(
            'click',
            () => {
                const elementSiblings = this.findElementSiblings(element, 'listItem');
                const firstElement = elementSiblings.find((elementSibling) => elementSibling.getAttribute('listIndent') === index);
                const lastElement = elementSiblings.findLast((elementSibling) => elementSibling.getAttribute('listIndent') === index);

                const range = this.editor.model.createRange(
                    this.editor.model.createPositionBefore(firstElement),
                    this.editor.model.createPositionAfter(lastElement),
                );

                this.editor.isListSelected = true;
                this.editor.listIndent = index;

                this.editor.model.change((writer) => writer.setSelection(range));
                this.editor.focus();

                this.editor.model.document.selection.once('change', () => {
                    this.editor.isListSelected = false;

                    delete this.editor.listIndent;
                });
            },
            false,
        );

        this.elementsPathWrapper.append(listItemNode);
    }

    updatePath(element) {
        if (element.name === '$root') {
            return;
        }

        if (element.name === 'listItem') {
            const listIndent = element.getAttribute('listIndent');

            for (let i = 0; i <= listIndent; i++) {
                this.addListItem(element, i);
            }
        }

        const pathItem = `<li class="ibexa-elements-path__item">${element.name}</li>`;
        const container = document.createElement('ul');

        container.insertAdjacentHTML('beforeend', pathItem);

        const listItemNode = container.querySelector('li');

        listItemNode.addEventListener(
            'click',
            () => {
                this.isTableCellSelected = element.name === 'tableCell';

                const placement = this.isTableCellSelected ? 'on' : 'in';

                this.editor.model.change((writer) => writer.setSelection(element, placement));
                this.editor.focus();
            },
            false,
        );

        this.elementsPathWrapper.append(listItemNode);
    }

    init() {
        this.elementsPathWrapper = this.editor.sourceElement.parentElement.querySelector('.ibexa-elements-path');

        this.editor.model.document.selection.on('change:range', () => {
            this.elementsPathWrapper.innerHTML = '';

            this.editor.model.document.selection.getFirstPosition().getAncestors().forEach(this.updatePath);

            if (this.isTableCellSelected) {
                this.updatePath(this.editor.model.document.selection.getSelectedElement());

                this.isTableCellSelected = false;
            }
        });
    }
}

export default IbexaElementsPath;
