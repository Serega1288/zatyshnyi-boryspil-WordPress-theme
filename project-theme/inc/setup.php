<?php
/**
 * Theme supports and navigation locations.
 *
 * @package ProjectTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function project_theme_setup(): void {
	load_theme_textdomain( 'project-theme', get_template_directory() . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support(
		'html5',
		array( 'comment-list', 'comment-form', 'search-form', 'gallery', 'caption', 'style', 'script' )
	);

	register_nav_menus(
		array(
			'header-menu' => __( 'Головне меню', 'project-theme' ),
			'footer-menu' => __( 'Меню у футері', 'project-theme' ),
		)
	);
}
add_action( 'after_setup_theme', 'project_theme_setup' );

/**
 * Keep the bundled brand mark as a favicon until an administrator sets a Site Icon.
 */
function project_theme_favicon(): void {
	if ( has_site_icon() ) {
		return;
	}
	?>
	<link rel="icon" href="<?php echo esc_url( get_template_directory_uri() . '/assets/zatyshnyi-logo.svg' ); ?>" type="image/svg+xml">
	<?php
}
add_action( 'wp_head', 'project_theme_favicon', 2 );

/**
 * Browser chrome colour from the supplied design.
 */
function project_theme_theme_colour(): void {
	echo '<meta name="theme-color" content="#163d29">' . "\n";
}
add_action( 'wp_head', 'project_theme_theme_colour', 2 );



//****************


/**
 * Dev mode toggle in admin bar + CSS hiding for non-dev mode
 */


add_action( 'admin_bar_menu', 'lux_dev_mode_admin_bar_button', 100 );
function lux_dev_mode_admin_bar_button( $wp_admin_bar ) {
    if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( ! is_admin_bar_showing() ) {
        return;
    }

    $args = array(
            'id'    => 'lux-dev-mode-toggle',
            'title' => '<span class="ab-icon dashicons-hammer"></span><span class="ab-label">Developer mode</span>',
            'href'  => '#',
            'meta'  => array(
                    'class' => 'lux-dev-mode-toggle dev-off',
                    'title' => 'Увімкнути / вимкнути режим розробника',
            ),
    );

    $wp_admin_bar->add_node( $args );
}
add_action( 'admin_head', 'lux_dev_mode_head_assets' );
add_action( 'wp_head', 'lux_dev_mode_head_assets' );
function lux_dev_mode_head_assets() {
    if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <style>



        #wp-admin-bar-lux-dev-mode-toggle.dev-off > .ab-item {
            background: #666666 !important;
            color: #ffffff !important;
            font-weight: 600;
        }

        #wp-admin-bar-lux-dev-mode-toggle > .ab-item * {
            color: #ffffff !important;
        }


        #wp-admin-bar-lux-dev-mode-toggle.dev-on > .ab-item {
            background: #f5627c !important;
            color: #ffffff !important;
            font-weight: 700;
            box-shadow: 0 0 10px rgba(255,0,119,0.5);
        }


        body #wp-admin-bar-lux-dev-mode-toggle > .ab-item:after {
            content: '●';
            display: inline-block;
            margin-left: 6px;
            color: #fff !important;
        }

        body.mod-dev-on #wp-admin-bar-lux-dev-mode-toggle > .ab-item:after {
            content: '●';
            display: inline-block;
            margin-left: 6px;
            color: #00ff40;
        }


        /*menu list */

        body:not(.mod-dev-on) #toplevel_page_loco,
        body:not(.mod-dev-on) #toplevel_page_rrrlgvwr,
        body:not(.mod-dev-on) #toplevel_page_custom-twitter-feeds,
        body:not(.mod-dev-on) #toplevel_page_wpconsent,
        body:not(.mod-dev-on) #toplevel_page_edit-post_type-acf-field-group,
        body:not(.mod-dev-on) #toplevel_page_ai1wm_export,
        body:not(.mod-dev-on) #menu-tools,
        body:not(.mod-dev-on) #menu-plugins,
        body:not(.mod-dev-on) #menu-appearance,
        body:not(.mod-dev-on) #toplevel_page_tinvwl,
        body:not(.mod-dev-on) #toplevel_page_berocket_account,
        body:not(.mod-dev-on) #toplevel_page_spotlight-instagram,
        body:not(.mod-dev-on) #toplevel_page_wpcf7,
        body:not(.mod-dev-on) #toplevel_page_wpclever,
        body:not(.mod-dev-on) #menu-settings,
        body:not(.mod-dev-on) #menu-posts,
        body:not(.mod-dev-on) #menu-comments,
        body:not(.mod-dev-on) #toplevel_page_debug-log-viewer,
        body:not(.mod-dev-on) #toplevel_page_mlang,
        body:not(.mod-dev-on) #menu-dashboard .wp-submenu.wp-submenu-wrap

        {
            display: none !important;
        }

        /*notice*/

        body:not(.mod-dev-on) .berocket_admin_notice
        {
            display: none !important;
        }


    </style>

    <script>
        (function() {
            var STORAGE_KEY = 'lux_dev_mode_state'; // '1' = dev ON, '0' = dev OFF

            function readState() {
                try {
                    var v = localStorage.getItem(STORAGE_KEY);
                    if (v === null) {
                        // за замовчуванням: dev OFF (тобто мод для недевелоперів: модулі сховані)
                        return false;
                    }
                    return v === '1';
                } catch (e) {
                    return false;
                }
            }

            function saveState(on) {
                try {
                    localStorage.setItem(STORAGE_KEY, on ? '1' : '0');
                } catch (e) {}
            }

            function applyState(isDevOn) {
                if (!document.body) { return; }

                // Класи на <body>
                document.body.classList.toggle('mod-dev-on',  !!isDevOn);
                document.body.classList.toggle('mod-dev-off', !isDevOn);

                // Класи на кнопці в адмін-барі
                var node = document.getElementById('wp-admin-bar-lux-dev-mode-toggle');
                if (node) {
                    node.classList.toggle('dev-on',  !!isDevOn);
                    node.classList.toggle('dev-off', !isDevOn);
                }
            }

            function init() {
                var isDevOn = readState();
                applyState(isDevOn);

                var node = document.getElementById('wp-admin-bar-lux-dev-mode-toggle');
                if (!node) { return; }

                node.addEventListener('click', function(e) {
                    e.preventDefault();
                    var current = document.body.classList.contains('mod-dev-on');
                    var next = !current; // інвертуємо
                    applyState(next);
                    saveState(next);
                });
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init);
            } else {
                init();
            }
        })();
    </script>
    <?php
}