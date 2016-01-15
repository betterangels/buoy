<!DOCTYPE html>
<html <?php language_attributes();?> class="no-js">
<head>
    <meta http-equiv="refresh" content="5" />
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width" />
	<!--[if lt IE 9]>
	<script src="<?php echo esc_url( get_template_directory_uri() ); ?>/js/html5.js"></script>
	<![endif]-->
	<?php wp_head();?>
</head>
<body <?php body_class();?>>
    <div id="page" class="h-feed">
        <header id="masthead" class="site-header" role="banner">
            <h1 class="site-title"><?php esc_html_e('Buoy Alert: Chat room', 'buoy');?></h1>
        </header>
    </div>

    <ul>
    <?php
    $buoy_chat_room->wp_list_comments();
    ?>
    </ul>
</body>
