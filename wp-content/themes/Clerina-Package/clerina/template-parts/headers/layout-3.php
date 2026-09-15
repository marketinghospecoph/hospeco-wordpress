<div class="header-main-wapper">
    <div class="container">
        <div class="row header-row">
            <div class="navbar-toggle col-xs-3"><?php clerina_menu_icon(); ?></div>
            <div class="col-lg-3 col-xs-6 logo">
				<?php get_template_part( 'template-parts/headers/logo' ); ?>
            </div>
            <div class="hidden-lg col-xs-3"><?php clerina_extra_menu(); ?></div>
            <div class="col-lg-9 hidden-md hidden-sm hidden-xs">
				<?php clerina_header_sidebar(); ?>
            </div>
        </div>
    </div>
</div>
<div class="main-menu hidden-md hidden-sm hidden-xs">
    <div class="container">
        <div class="header-row header-menu-wrapper">
			<?php
			clerina_header_menu();
			clerina_extra_menu();
			?>
        </div>
    </div>
</div>
