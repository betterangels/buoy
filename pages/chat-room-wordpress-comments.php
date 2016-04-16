<div id="comments-chat">
    <iframe
        src="<?php print admin_url('admin-ajax.php');?>?action=<?php print esc_attr(self::$prefix.'_post_comments_chat');?>&amp;hash=<?php print esc_attr($alert->get_hash());?>#page-footer"
        name="<?php print esc_attr(self::$prefix);?>_post_comments_chat"
        width="100%"
        height="100%"
        seamless="seamless"
    >
        <?php esc_html_e('To access the incident chat room, inline frames must be supported by your browser.', 'buoy');?>
    </iframe>
<?php
add_filter('comments_open', '__return_true');
$submit_field  = '<div class="input-group">';
$submit_field .= '<input type="text" id="comment" name="comment"';
$submit_field .= ' class="form-control" aria-requred="true" required="required"';
$submit_field .= ' placeholder="'.$curr_user->display_name.'&hellip;" />';
$submit_field .= '<span class="input-group-btn">%1$s</span> %2$s';
$submit_field .= wp_nonce_field(self::$prefix.'_chat_comment', self::$prefix.'_chat_comment_nonce', true, false);
$submit_field .= '</div><!-- .input-group -->';
ob_start();
comment_form(array(
    'comment_field' => '', // use the submit_field instead,
    'label_submit' => esc_attr__('Send', 'buoy'),
    'class_submit' => 'btn btn-success',
    'id_submit' => 'submit-btn',
    'name_submit' => 'submit-btn',
    'submit_button' => '<button type="submit" class="%3$s" id="%2$s" name="%1$s">%4$s</button>',
    'submit_field' => $submit_field,
    'logged_in_as' => '',
    'title_reply' => '',
    'title_reply_before' => '',
    'cancel_reply_before' => '',
    'cancel_reply_link' => ' ',
), $alert->wp_post->ID);
$comment_form = ob_get_contents();
ob_end_clean();
print links_add_target($comment_form, self::$prefix.'_post_comments_chat', array('form'));
?>
</div>
