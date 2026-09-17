<div class="header-main-wapper">
    <div class="<?php echo esc_attr( clerina_header_container() ); ?>">
        <div class="header-row">
            <div class="navbar-toggle hidden-lg col-xs-3"><?php clerina_menu_icon(); ?></div>
            <div class="col-xs-6 col-header-logo">
				<?php get_template_part( 'template-parts/headers/logo' ); ?>
            </div>
            <div class="hidden-lg col-xs-3"><?php clerina_extra_menu(); ?></div>
            <div class="hidden-xs hidden-sm hidden-md col-header-menu container">
				<?php
				clerina_header_menu();
				clerina_extra_menu();
				?>
            </div>
            <?php clerina_header_booking(); ?>
        </div>
    </div>
</div>
