<div id="topbar_mobile" class="hidden-lg topbar topbar-mobile">
    <div class="<?php echo esc_attr(clerina_header_container()); ?>">
        <div class="topbar-mobile-content">
	        <?php if ( is_active_sidebar( 'topbar-mobile' ) ) {
		        dynamic_sidebar( 'topbar-mobile' );
	        } ?>

        </div>
    </div>
</div>