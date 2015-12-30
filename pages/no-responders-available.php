<div class="notice error">
    <p><?php print sprintf(
        esc_html__('You have no team members. Before you can activate an alert, you must invite at least one other user to join one of %1$syour response teams%2$s and they must accept your invitation.', 'buoy'),
        '<a href="' . esc_attr(admin_url('edit.php?post_type=' . parent::$prefix . '_team')) . '">', '</a>'
    );?></p>
    <p>
        <a href="<?php print esc_attr(admin_url('edit.php?post_type=' . parent::$prefix . '_team'));?>"
           title="<?php esc_attr_e('Click here to choose members for your response teams.' , 'buoy');?>"
           class="btn btn-lg btn-primary btn-block"
        ><?php esc_html_e('Choose team members', 'buoy');?></a>
    </p>
</div>
