<div id="topbar" class="hidden-xs hidden-sm hidden-md topbar">
    <div class="<?php echo esc_attr(clerina_header_container()); ?>">
        <div class="topbar-flex">
            <div class="topbar-left topbar-sidebar">
				<?php if ( is_active_sidebar( 'topbar-left' ) ) {
					dynamic_sidebar( 'topbar-left' );
				} ?>
            </div>

            <div class="topbar-right topbar-sidebar">
				<?php if ( is_active_sidebar( 'topbar-right' ) ) {
					dynamic_sidebar( 'topbar-right' );
				} ?>
            </div>

        </div>
    </div>
</div>