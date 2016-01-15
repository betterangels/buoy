window.addEventListener('DOMContentLoaded', function () {
    // Just go to the bottom of the page on each refresh.
    window.location = window.location + '#page-footer';
    // Reset the comment form, too.
    if (document.body.classList.contains('do_form_reset')) {
        parent.document.getElementById('commentform').reset();
    }
});
