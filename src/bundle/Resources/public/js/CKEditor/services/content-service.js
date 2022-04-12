export const findContent = ({ token, siteaccess, contentId, limit = 1, offset = 0 }, callback) => {
    const body = JSON.stringify({
        ViewInput: {
            identifier: `find-content-${contentId}`,
            public: false,
            ContentQuery: {
                FacetBuilders: {},
                SortClauses: {},
                Filter: { ContentIdCriterion: `${contentId}` },
                limit,
                offset,
            },
        },
    });
    const request = new Request('/api/ibexa/v2/views', {
        method: 'POST',
        headers: {
            Accept: 'application/vnd.ibexa.api.View+json; version=1.1',
            'Content-Type': 'application/vnd.ibexa.api.ViewInput+json; version=1.1',
            'X-Siteaccess': siteaccess,
            'X-CSRF-Token': token,
        },
        body,
        mode: 'same-origin',
        credentials: 'same-origin',
    });

    fetch(request)
        .then(window.ibexa.helpers.request.getJsonFromResponse)
        .then((response) => {
            const items = response.View.Result.searchHits.searchHit.map((searchHit) => searchHit.value.Content);

            callback(items);
        })
        .catch(window.ibexa.helpers.notification.showErrorNotification);
};
