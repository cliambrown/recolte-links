window.resizeTextarea = function (el) {
    el.style.height = '80px';
    el.style.height = (Math.max(el.scrollHeight, 60) + 20) + 'px';
}

window.simpleSearchStr = function (str) {
    if (!str || typeof str !== 'string') return str;
    // Replace all diacritics (see above)
    return str.normalize('NFD').replace(/[\u0300-\u036f\(\)\[\]\<\>\"\"]/g, '').replace('œ', 'oe')
        // Remove any funny/curly quotes
        .replace(/[\u2018\u2019]/g, "'")
        .replace(/[\u201C\u201D]/g, '"')
        // Replace any hyphens/dashes with spaces
        .replace(/[\u2014\-]/g, ' ')
        // Remove any duplicate whitespace
        .replace(/\s+/g, ' ')
        .toLowerCase().trim();
}

window.getReadableAxiosError = function (error) {
    console.log(error ? error.toString() : 'getReadableAxiosError — unknown error');
    let message = "Sorry, an error occurred: \n";
    if (!error || !error.response) {
        message += 'Unknown error.';
    } else if (error.response.status) {
        switch (error.response.status) {
            case 400:
                message += 'The server understood the request, but the request content was invalid.';
                break;
            case 401:
                message += 'Unauthorized access.';
                break;
            case 403:
                message += 'Unauthorized action.';
                if (error.response.data.message) message += "\n" + error.response.data.message;
                break;
            case 404:
                message += 'Page not found.';
                break;
            case 422:
                if (error.response.data.errors) {
                    _.forOwn(error.response.data.errors, (errItem, key) => {
                        if (Array.isArray(errItem)) {
                            errItem.forEach(errMsg => {
                                if (_.isString(errMsg)) message += errMsg + "\n";
                            });
                        } else if (_.isString(errItem)) {
                            message += errItem + "\n";
                        }
                    });
                } else if (error.response.data.message) {
                    message += error.response.data.message;
                } else {
                    message += 'Invalid data.';
                }
                break;
            case 500:
                message += 'Internal server error.';
                break;
            case 503:
                message += 'Service unavailable.';
                break;
            default:
                message += 'Unknown error.';
        }
    } else if (error.request) {
        // The request was made but no response was received
        // `error.request` is an instance of XMLHttpRequest in the browser
        if (error.request.readyState == 4) {
            message += error.request.statusText;
        }
        else if (error.request.readyState == 0) {
            message += 'You may not be connected to the internet.';
        }
        else {
            message += 'Unknown error.';
        }
    } else if (error.message) {
        message += error.message;
    } else {
        message += 'Unknown error.';
    }
    return message;
}

window.isValidHttpUrl = function (string) {
    let url;
    try {
        url = new URL(string);
    } catch (_) {
        return false;
    }
    return url.protocol === "http:" || url.protocol === "https:";
}