<?php $user = wp_get_current_user();?>
<iframe src="<?php print plugin_dir_url(__FILE__)?>../htmlmock/activation-screen/?name=<?php esc_attr_e($user->display_name);?>" width="100%" height="100%"><?php esc_html_e('This page requres iFrames. :(', 'better-angels');?></iframe>
