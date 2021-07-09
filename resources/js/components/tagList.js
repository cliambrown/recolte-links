window.tagList = function (tags, allTags) {
    return {
        tags: tags,
        all_tags: allTags,
        toggleTag(tagToToggle) {
            let tagToToggleSimple = simpleSearchStr(tagToToggle);
            let oldTagArr = this.tags.split(',');
            let newTagArr = [];
            let foundTag = false;
            while (oldTagArr.length) {
                let oldTag = oldTagArr.shift();
                let oldTagSimple = simpleSearchStr(oldTag);
                if (oldTagSimple === tagToToggleSimple) {
                    foundTag = true;
                } else if (oldTagSimple) {
                    newTagArr.push(oldTag.trim());
                }
            }
            if (!foundTag) newTagArr.push(tagToToggle.trim());
            this.tags = newTagArr.join(', ');
        },
    }
}