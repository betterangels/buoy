<?php
$posts = get_posts(array(
    'post_type' => 'buoy_team',
    'post_status' => 'any',
    'meta_key' => 'sms_email_bridge_enabled',
    'meta_value' => true
));
$selected_team = (empty($_POST['team'])) ? false : $_POST['team'];
?>
<h1><?php esc_html_e('Check Team SMS/txt email', 'buoy');?></h1>
<form
    method="POST"
    action="<?php print admin_url('admin.php?page=buoy_sms_email_bridge_tool');?>"
>
<?php wp_nonce_field('sms_email_bridge_check', 'buoy_nonce');?>
<table class="form-table">
    <tbody>
        <tr>
            <th>
                <label for="team">
                    <?php esc_html_e('Team', 'buoy');?>
                </label>
            </th>
            <td>
                <select id="team" name="team"><?php foreach ($posts as $post) {
                    print '<option value="'.esc_attr($post->ID).'" '.selected($post->ID, $selected_team, false).'>' . esc_html($post->post_title) . '</option>';
                } ?></select>
            </td>
        </tr>
    </tbody>
</table>
<input type="submit" class="button-large button-primary"
    value="<?php esc_attr_e('Check email', 'buoy');?>"
/>
</form>
<h2><?php esc_html_e('Results', 'buoy');?></h2>
<pre>
<?php
if (isset($_POST['buoy_nonce']) && wp_verify_nonce($_POST['buoy_nonce'], 'sms_email_bridge_check')) {
    WP_Buoy_SMS_Email_Bridge::run(absint($selected_team));
}
?>
</pre>
<table>
    <tbody>
        <tr>
            <th>
            </th>
            <td>
            </td>
        </tr>
    </tbody>
<table>
