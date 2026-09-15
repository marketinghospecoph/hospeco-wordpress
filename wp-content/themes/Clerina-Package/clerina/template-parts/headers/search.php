<li class="extra-menu-item menu-item-search">
    <a class="clerina-header-search" href="#">
        <i class="icon-magnifier extra-icon"></i>
    </a>
    <form class="header-search-content" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
        <div class="search-wrapper">
            <input type="text" name="s" class="search-field" autocomplete="off" placeholder="<?php esc_attr_e('Enter Keyword...', 'clerina'); ?>">
            <button type="submit" class="search-submit"><i class="icon-magnifier"></i></button>
        </div>
    </form>
</li>