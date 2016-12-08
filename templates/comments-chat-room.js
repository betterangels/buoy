/**
 * Buoy Chat Room front-end
 *
 * @license GPL-3.0
 *
 * @copyright Copyright (c) 2016 by Meitar "maymay" Moscoviz
 */

/**
 * A module for the built-in chat room functionality.
 *
 * @todo Consider using the WP-API JavaScript client instead of using
 *       custom methods defined ourselves. See its documentation:
 *       http://v2.wp-api.org/extending/javascript-client/
 */
var BUOY_CHAT_ROOM = (function () {

    /**
     * Base request URI for WP-API.
     *
     * Used for traditional polling when `EventSource` is not available.
     * If we can use HTML5 Server-Sent Events, we just make a single
     * request to WordPress's `admin-ajax.php` endpoint.
     *
     * @type {string}
     */
    var api_base = buoy_chat_room_vars.api_base;

    /**
     * WordPress's admin-ajax.php endpoint.
     *
     * WordPress does not offer the global `ajaxurl` to JavaScript if
     * the current page is rendered on the "front-end," which this JS
     * still qualifies as. So we make our own `ajaxurl`.
     *
     * @type {string}
     */
    var ajaxurl = ajaxurl || buoy_chat_room_vars.ajaxurl;

    /**
     * Gets the post ID of the current chat room.
     *
     * @return {number}
     */
    var getPostId = function () {
        return jQuery('#chat-room').data('post-id');
    };

    /**
     * How many comments in this chat room?
     *
     * This becomes the `offset` value used to retrieve new comments.
     *
     * @type {Number}
     */
    var getCommentCount = function () {
        return jQuery('.buoy-chat-message').length;
    };

    /**
     * Asks the Buoy server for new comments using the WP REST API.
     */
    var pollForNewComments = function () {
        var url = api_base + '/comments&post=' + getPostId() + '&offset=' + getCommentCount()
            + '&_wpnonce=' + wpApiSettings.nonce + '&order=asc';
        jQuery.get(url, function (response) {
            if (response.length) {
                appendComments(response);
                showNewCommentsNotice();
            }
        });
    };

    /**
     * Sets up a new HTML5 SSE listener.
     *
     * If the browser supports it, we minimize load on the WordPress
     * backend by using the HTML5 specification's `EventSource` API.
     * Only Microsoft's browsers don't yet support this part of the
     * spec, but we fallback to Ajax polling in those cases already.
     *
     * @see {@link https://developer.mozilla.org/en-US/docs/Web/API/Server-sent_events/Using_server-sent_events}
     *
     * @param {string} url
     */
    var connectSource = function (url) {
        var es = new EventSource(url + '&offset=' + getCommentCount());
        es.addEventListener('updated', function (e) {
            appendComments(JSON.parse(e.data));
            showNewCommentsNotice();
        });
        // TODO: Fancier error handling?
        es.onerror = function (e) {
            es.close()
            connectSource(getEventSourceUrl());
        };
    };

    /**
     * Constructs the chat room's `event-stream` endpoint URL.
     *
     * @return {string}
     */
    var getEventSourceUrl = function () {
        return ajaxurl + '?action=buoy_chat_event_stream&post_id=' + getPostId();
    };

    /**
     * Shows a notice that there are new comments.
     */
    var showNewCommentsNotice = function () {
        jQuery('#new-comments-notice').show();
    };

    /**
     * Scrolls to bottom and hides the notice.
     */
    var handleNewCommentsNotice = function () {
        jQuery(this).hide();
        jQuery('html, body').animate({
            'scrollTop': jQuery('#page-footer').offset().top
        }, 500);
    };

    /**
     * Appends new comments to the chat room.
     *
     * @param {Array} comments
     */
    var appendComments = function (comments) {
        var message_list = jQuery('#chat-room .media-list');
        jQuery(comments).each(function () {
            message_list.append(commentHtml(this));
        });
    };

    /**
     * Gets the HTML representation of a comment.
     *
     * @typedef {Object} WP_Comment
     * @property {Number} id
     * @property {Number} post
     * @property {Number} parent
     * @property {Number} author
     * @property {string} author_name
     * @property {string} author_url
     * @property {Object} author_avatar_urls
     * @property {string} author_avatar_urls.24
     * @property {string} author_avatar_urls.48
     * @property {string} author_avatar_urls.96
     * @property {string} date
     * @property {string} date_gmt
     * @property {Object} content
     * @property {string} content.rendered
     * @property {string} link
     * @property {string} status
     * @property {string} type
     *
     * @param {WP_Comment} comment
     *
     * @return {string}
     */
    var commentHtml = function (comment) {
        // TODO: Is there some way to define a template that both the PHP
        //       and this JS can use?
        var html = '<li id="comment-' + comment.id + '" class="media media-on-left buoy-chat-message">';
        html += '<div class="media-left media-bottom vcard">';
        html += '<span class="comment-author fn">' + comment.author_name + '</span>';
        html += '<a href="">';
        html += '<img src="' + comment.author_avatar_urls['48'] + '" alt="" class="avatar avatar-48 photo media-object" height="48" width="48" />';
        html += '</a>';
        html += '</div>';
        html += '<div class="media-body">';
        html += comment.content.rendered;
        html += '<footer><time datetime="' + comment.date + '">' + comment.date + '</time></footer>';
        html += '</div>';
        html += '</li>';
        return html;
    };

    /**
     * Runs on "page load."
     */
    var init = function () {
        jQuery('window').scrollTop(jQuery('#page-footer').offset().top);
        resetCommentForm();

        if (window.EventSource) {
            connectSource(getEventSourceUrl());
        } else {
            setInterval(pollForNewComments, 5000);
        }

        // Attach handlers.
        jQuery('#new-comments-notice.notice').on('click', handleNewCommentsNotice);
    };

    /**
     * Resets the comment form used to send a new message.
     */
    var resetCommentForm = function () {
        if (document.body.classList.contains('do_form_reset')) {
            parent.document.getElementById('commentform').reset();
        }
    };

    return {
        'init': init
    }

})();

window.addEventListener('DOMContentLoaded', BUOY_CHAT_ROOM.init);
