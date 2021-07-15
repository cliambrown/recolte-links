const { default: axios } = require("axios");

window.urlMetaScraper = function (url, title, description) {
    return {
        url: url,
        is_valid_url: false,
        title: title,
        description: description,
        loading: false,
        checkUrl(url) {
            this.is_valid_url = isValidHttpUrl(url);
        },
        fetchUrlMetadata() {
            this.loading = true;
            axios.post('/api/get_url_metadata', {
                url: this.url
            }).then(response => {
                this.title = _.get(response, 'data.title');
                let description = _.get(response, 'data.description');
                if (description.trim()) {
                    this.description = '"' + description + '"';
                }
            }).catch(error => {
                alert(getReadableAxiosError(error));
            }).finally(() => {
                this.loading = false;
            });
        },
    }
}