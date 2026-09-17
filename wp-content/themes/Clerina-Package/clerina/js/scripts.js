(function ($) {
    'use strict';

    var clerina = clerina || {};
    clerina.init = function () {
        clerina.$body = $(document.body),
            clerina.$window = $(window),
            clerina.$header = $('#site-header');
        // Portfolio category filter
        this.portfolioFilter();
        this.portfolioLoadingAjax();
        this.serviceLoadingAjax();
        this.testimonialMasonryLayout();
        this.testimonialLoadingAjax();
        this.shopPagination();

        // Page Header
        this.pageHeader();

        // Mobile menu
        this.mobileMenu();
        this.footerDropdown();

        // Header
        this.toggleSearch();

        //newsletter
        this.newLetterPopup();

        //newsletter
        this.scrollTop();

        // Product
        this.productQuantity();
    };

    clerina.toggleSearch = function () {
        // Search toggle
        $('.clerina-header-search').on('click', function (e) {
            e.preventDefault();
            $(this).closest('.menu-item-search').toggleClass('show-search');
        });
    };

    // Mobile Menu
    clerina.mobileMenu = function () {
        var $mobileMenu = $('#primary-mobile-nav');
        clerina.$header.on('click', '#clerina-navbar-toggle', function (e) {
            e.preventDefault();
            $mobileMenu.toggleClass('open');
            clerina.$body.toggleClass('open-canvas-panel');
        });

        $mobileMenu.find('.menu .menu-item-has-children > a').prepend('<span class="toggle-menu-children"></span>');

        $mobileMenu.on('click', '.menu-item-has-children > a, .menu-item-has-children > span', function (e) {
            e.preventDefault();
            openSubMenus($(this));
        });

        $mobileMenu.on('click', '.close-canvas-mobile-panel', function (e) {
            e.preventDefault();
            clerina.$body.removeClass('open-canvas-panel');
            $('#primary-mobile-nav').removeClass('open');
        });

        $('#off-canvas-layer').on('click', function (e) {
            e.preventDefault();
            clerina.$body.removeClass('open-canvas-panel');
            $('#primary-mobile-nav').removeClass('open');
        });

        clerina.$window.on('resize', function () {
            if (clerina.$window.width() > 1200) {
                if ($mobileMenu.hasClass('open')) {
                    $mobileMenu.removeClass('open');
                    clerina.$body.removeClass('open-canvas-panel');
                }
            }
        });

        function openSubMenus($menu) {
            $menu.closest('li').siblings().find('ul').slideUp();
            $menu.closest('li').siblings().removeClass('active');
            $menu.closest('li').siblings().find('li').removeClass('active');

            $menu.closest('li').children('ul').slideToggle();
            $menu.closest('li').toggleClass('active');
        }
    };

    // Footer Dropdown
    clerina.footerDropdown = function () {
        clerina.$window.on('resize', function () {
            var $dropdown = $('#footer-widgets'),
                $title = $('#footer-widgets').find('.widget-title');
            if (clerina.$window.width() < 992) {
                $title.next('div,ul,form').addClass('clicked');
                $dropdown.find('select').addClass('clicked');
            } else {
                $title.next('div,ul,form').removeClass('clicked').removeAttr('style');
                $dropdown.find('select').removeClass('clicked').removeAttr('style');
            }

            $dropdown.find('.screen-reader-text').remove();

            $dropdown.on('click', '.widget-title', function (e) {
                e.preventDefault();
                $(this).next('.clicked').stop().slideToggle();
                $(this).toggleClass('active');

            });
        }).trigger('resize');
    };

    clerina.portfolioFilter = function () {
        var $portfolioList = $('#clerina_portfolio_grid'),
            options = {
                itemSelector: '.portfolio-wrapper',
                layoutMode: 'fitRows',
                fitRows: {
                    gutter: 0
                }

            };

        clerina.$window.on('load', function () {
            $portfolioList.isotope(options);
        });


        $('#portfolio-cats-filters').on('click', '.button', function () {
            var filterValue = $(this).attr('data-filter');

            $portfolioList.isotope({filter: filterValue});
        });

        $('.button-group').each(function (i, buttonGroup) {
            var $buttonGroup = $(buttonGroup);
            $buttonGroup.on('click', 'span', function () {
                $buttonGroup.find('.active').removeClass('active');
                $(this).addClass('active');
            });
        });

        if (clerina.$body.hasClass('portfolio_full-width')) {
            $('#filters').addClass('container');
        }
    };

    /**
     * Portfolio ajax
     */
    clerina.portfolioLoadingAjax = function () {

        if (!clerina.$body.hasClass('clerina-portfolio')) {
            return;
        }
        // Blog page
        clerina.$body.on('click', '.pag-2 .next', function (e) {

            e.preventDefault();

            if ($(this).data('requestRunning')) {
                return;
            }

            $(this).data('requestRunning', true);

            var $postList = $('#clerina_portfolio_grid'),
                $pagination = $(this).parents('.pagination'),
                $parent = $(this).parent();

            $parent.addClass('loader');

            $.get(
                $(this).attr('href'),
                function (response) {
                    var $content = $(response).find('#clerina_portfolio_grid').children('.portfolio-wrapper'),
                        pagination_html = $(response).find('.pagination').html();

                    $pagination.html(pagination_html);

                    $postList.append($content);

                    $content.imagesLoaded(function () {
                        $postList.isotope('appended', $content);
                        $pagination.find('.next').data('requestRunning', false);
                        $parent.removeClass('loader');
                    });
                }
            );
        });
    };
    /**
     * Service ajax
     */
    clerina.serviceLoadingAjax = function () {

        if (!clerina.$body.hasClass('clerina-service')) {
            return;
        }
        // Blog page
        clerina.$body.on('click', '.pag-2 .next', function (e) {

            e.preventDefault();

            if ($(this).data('requestRunning')) {
                return;
            }

            $(this).data('requestRunning', true);

            var $postList = $('.site-main'),
                $pagination = $(this).parents('.pagination'),
                $parent = $(this).parent();

            $parent.addClass('loader');

            $.get(
                $(this).attr('href'),
                function (response) {
                    var $content = $(response).find('.site-main').children('.service-wrapper'),
                        pagination_html = $(response).find('.pagination').html();

                    $pagination.html(pagination_html);

                    $postList.append($content);

                    $content.imagesLoaded(function () {
                        $pagination.find('.next').data('requestRunning', false);
                        $parent.removeClass('loader');
                    });
                }
            );
        });
    };

    // testimonial Masonry Layout
    clerina.testimonialMasonryLayout = function () {
        var $listTesti = '#list-testimonial';
        $($listTesti).imagesLoaded(function () {
            $($listTesti).isotope({
                itemSelector: '.testimonial-item'
            });
        });

    };

    /**
     * testimonial ajax
     */
    clerina.testimonialLoadingAjax = function () {

        if (!clerina.$body.hasClass('clerina-testimonial')) {
            return;
        }
        // testimonial page
        clerina.$body.on('click', '.pag-2 .next', function (e) {

            e.preventDefault();

            if ($(this).data('requestRunning')) {
                return;
            }

            $(this).data('requestRunning', true);
            var $listTesti = '#list-testimonial';
            var $postList = $($listTesti),
                $pagination = $(this).parents('.pagination'),
                $parent = $(this).parent();

            $parent.addClass('loader');

            $.get(
                $(this).attr('href'),
                function (response) {
                    var $content = $(response).find($listTesti).children('.testimonial-item'),
                        pagination_html = $(response).find('.pagination').html();

                    $pagination.html(pagination_html);

                    $content.imagesLoaded(function () {
                        $postList.append($content).isotope('insert', $content);
                        $pagination.find('.next').data('requestRunning', false);
                        $parent.removeClass('loader');
                    });
                }
            );
        });
    };


    clerina.shopPagination = function () {


        var $next_button = $('.woocommerce-pagination .page-numbers .next'),
            $prev_button = $('.woocommerce-pagination .prev');
        $next_button.empty();
        $prev_button.empty();
        $next_button.append('<span class="elegant">5</span>');
        $prev_button.append('<span class="elegant">4</span>');

    };

    // Page header
    clerina.pageHeader = function () {
        $('.page-header.parallax').find('.featured-image').parallax('50%', 0.6);
    };

    // Newsletter popup

    clerina.newLetterPopup = function () {
        var $modal = $('#cl-newsletter-popup'),
            days = parseInt(clerinaData.nl_days),
            seconds = parseInt(clerinaData.nl_seconds);

        if ($modal.length < 1) {
            return;
        }

        clerina.$window.on('load', function () {
            setTimeout(function () {
                $modal.addClass('open');
            }, seconds * 1000);
        });

        $modal.on('click', '.close-modal', function (e) {
            e.preventDefault();
            closeNewsLetter(days);
            $modal.removeClass('open');
            $modal.fadeOut();
        });

        $modal.on('click', '.n-close', function (e) {
            e.preventDefault();
            closeNewsLetter(30);
            $modal.removeClass('open');
            $modal.fadeOut();
        });

        $modal.find('.mc4wp-form').submit(function () {
            closeNewsLetter(days);
        });

        function closeNewsLetter(days) {
            var date = new Date(),
                value = date.getTime();

            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));

            document.cookie = 'cl_newletter=' + value + ';expires=' + date.toGMTString() + ';path=/';
        }
    };

    // Scroll Top
    clerina.scrollTop = function () {
        var $scrollTop = $('#scroll-top');
        clerina.$window.scroll(function () {
            if (clerina.$window.scrollTop() > clerina.$window.height()) {
                $scrollTop.addClass('show-scroll');
            } else {
                $scrollTop.removeClass('show-scroll');
            }
        });

        // Scroll effect button top
        $scrollTop.on('click', function (event) {
            event.preventDefault();
            $('html, body').stop().animate({
                    scrollTop: 0
                },
                800
            );
        });
    };

    /**
     * Change product quantity
     */
    clerina.productQuantity = function() {
        clerina.$body.on('click', '.quantity .increase, .quantity .decrease', function(e) {
            e.preventDefault();

            var $this = $(this),
                $qty = $this.siblings('.qty'),
                current = parseInt($qty.val(), 10),
                min = parseInt($qty.attr('min'), 10),
                max = parseInt($qty.attr('max'), 10);

            min = min ? min : 1;
            max = max ? max : current + 1;

            if ( $this.hasClass('decrease') && current > min ) {
                $qty.val(current - 1);
                $qty.trigger('change');
            }
            if ( $this.hasClass('increase') && current < max ) {
                $qty.val(current + 1);
                $qty.trigger('change');
            }
        });
    };

    /**
     * Document ready
     */
    $(function () {
        clerina.init();
    });

})(jQuery);