window.link = function (id, unread, liked, likesCount) {
    return {
        id: id,
        unread: unread,
        liked: liked,
        likes_count: likesCount,
        loading: false,
        toggleUnread() {
            this.loading = true;
            axios.post(`/api/links/${id}/update`, {
                unread: (!this.unread)
            }).then(response => {
                this.unread = !!_.get(response, 'data.unread');
            }).catch(error => {
                alert(getReadableAxiosError(error));
            }).finally(() => {
                this.loading = false;
            });
        },
        toggleLiked() {
            this.loading = true;
            axios.post(`/api/links/${id}/update`, {
                liked: (!this.liked)
            }).then(response => {
                this.liked = !!_.get(response, 'data.liked');
                this.likes_count = parseInt(_.get(response, 'data.likes_count'));
                console.log(_.get(response, 'data.likes_count'));
            }).catch(error => {
                alert(getReadableAxiosError(error));
            }).finally(() => {
                this.loading = false;
            });
        }
    }
}