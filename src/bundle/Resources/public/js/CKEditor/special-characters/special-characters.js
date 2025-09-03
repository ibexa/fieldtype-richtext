import { SpecialCharacters } from 'ckeditor5';

export default class IbexaSpecialCharacters extends SpecialCharacters {
    _updateGrid(currentGroupName, gridView) {
        super._updateGrid(currentGroupName, gridView);

        gridView.tiles.forEach((tile) => {
            tile.on('mouseover', (event) => {
                event.source.element.removeAttribute('data-original-title');
                event.source.element.removeAttribute('data-bs-original-title');
            });
        });
    }
}
