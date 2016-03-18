<?php
// TODO: Add some help texts?

/*if ( current_user_can( 'switch_themes' ) && isset($_GET['action'] ) ) {
    if ( 'activate' == $_GET['action'] ) {
        check_admin_referer('switch-theme_' . $_GET['stylesheet']);
        $theme = wp_get_theme( $_GET['stylesheet'] );

        if ( ! $theme->exists() || ! $theme->is_allowed() ) {
            wp_die(
                '<h1>' . __( 'Cheatin&#8217; uh?' ) . '</h1>' .
                '<p>' . __( 'The requested theme does not exist.' ) . '</p>',
                403
            );
        }

        switch_theme( $theme->get_stylesheet() );
        wp_redirect( admin_url('themes.php?activated=true') );
        exit;
    } elseif ( 'delete' == $_GET['action'] ) {
        check_admin_referer('delete-theme_' . $_GET['stylesheet']);
        $theme = wp_get_theme( $_GET['stylesheet'] );

        if ( ! current_user_can( 'delete_themes' ) ) {
            wp_die(
                '<h1>' . __( 'Cheatin&#8217; uh?' ) . '</h1>' .
                '<p>' . __( 'You are not allowed to delete this item.' ) . '</p>',
                403
            );
        }

        if ( ! $theme->exists() ) {
            wp_die(
                '<h1>' . __( 'Cheatin&#8217; uh?' ) . '</h1>' .
                '<p>' . __( 'The requested theme does not exist.' ) . '</p>',
                403
            );
        }

        $active = wp_get_theme();
        if ( $active->get( 'Template' ) == $_GET['stylesheet'] ) {
            wp_redirect( admin_url( 'themes.php?delete-active-child=true' ) );
        } else {
            delete_theme( $_GET['stylesheet'] );
            wp_redirect( admin_url( 'themes.php?deleted=true' ) );
        }
        exit;
    }
}

// Help tab: Overview
if ( current_user_can( 'switch_themes' ) ) {
    $help_overview  = '<p>' . __( 'This screen is used for managing your installed themes. Aside from the default theme(s) included with your WordPress installation, themes are designed and developed by third parties.' ) . '</p>' .
        '<p>' . __( 'From this screen you can:' ) . '</p>' .
        '<ul><li>' . __( 'Hover or tap to see Activate and Live Preview buttons' ) . '</li>' .
        '<li>' . __( 'Click on the theme to see the theme name, version, author, description, tags, and the Delete link' ) . '</li>' .
        '<li>' . __( 'Click Customize for the current theme or Live Preview for any other theme to see a live preview' ) . '</li></ul>' .
        '<p>' . __( 'The current theme is displayed highlighted as the first theme.' ) . '</p>' .
        '<p>' . __( 'The search for installed themes will search for terms in their name, description, author, or tag.' ) . ' <span id="live-search-desc">' . __( 'The search results will be updated as you type.' ) . '</span></p>';

    get_current_screen()->add_help_tab( array(
        'id'      => 'overview',
        'title'   => __( 'Overview' ),
        'content' => $help_overview
    ) );
} // switch_themes

// Help tab: Adding Themes
if ( current_user_can( 'install_themes' ) ) {
    if ( is_multisite() ) {
        $help_install = '<p>' . __('Installing themes on Multisite can only be done from the Network Admin section.') . '</p>';
    } else {
        $help_install = '<p>' . sprintf( __('If you would like to see more themes to choose from, click on the &#8220;Add New&#8221; button and you will be able to browse or search for additional themes from the <a href="%s" target="_blank">WordPress.org Theme Directory</a>. Themes in the WordPress.org Theme Directory are designed and developed by third parties, and are compatible with the license WordPress uses. Oh, and they&#8217;re free!'), 'https://wordpress.org/themes/' ) . '</p>';
    }

    get_current_screen()->add_help_tab( array(
        'id'      => 'adding-themes',
        'title'   => __('Adding Themes'),
        'content' => $help_install
    ) );
} // install_themes

// Help tab: Previewing and Customizing
if ( current_user_can( 'edit_theme_options' ) && current_user_can( 'customize' ) ) {
    $help_customize =
        '<p>' . __( 'Tap or hover on any theme then click the Live Preview button to see a live preview of that theme and change theme options in a separate, full-screen view. You can also find a Live Preview button at the bottom of the theme details screen. Any installed theme can be previewed and customized in this way.' ) . '</p>'.
        '<p>' . __( 'The theme being previewed is fully interactive &mdash; navigate to different pages to see how the theme handles posts, archives, and other page templates. The settings may differ depending on what theme features the theme being previewed supports. To accept the new settings and activate the theme all in one step, click the Save &amp; Activate button above the menu.' ) . '</p>' .
        '<p>' . __( 'When previewing on smaller monitors, you can use the collapse icon at the bottom of the left-hand pane. This will hide the pane, giving you more room to preview your site in the new theme. To bring the pane back, click on the collapse icon again.' ) . '</p>';

    get_current_screen()->add_help_tab( array(
        'id'        => 'customize-preview-themes',
        'title'     => __( 'Previewing and Customizing' ),
        'content'   => $help_customize
    ) );
} // edit_theme_options && customize

get_current_screen()->set_help_sidebar(
    '<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
    '<p>' . __( '<a href="https://codex.wordpress.org/Using_Themes" target="_blank">Documentation on Using Themes</a>' ) . '</p>' .
    '<p>' . __( '<a href="https://wordpress.org/support/" target="_blank">Support Forums</a>' ) . '</p>'
);*/


$themes = WpakThemes::get_available_themes( true );

foreach ( $themes as $slug => $theme ) {
    $prepared_themes[ $slug ] = array(
        'id'           => $slug,
        'name'         => $theme['Name'],
        'screenshot'   => array(), // TODO: define how theme screenshot should be retrieved
        'description'  => $theme['Description'],
        'author'       => $theme['Author'],
        'authorAndUri' => $theme['Author'],
        'version'      => $theme['Version'],
        'tags'         => '',
        'hasUpdate'    => false, // TODO: implement theme update feature
        'update'       => false, // TODO: implement theme update feature
        'apps_ids'     => WpakThemes::get_apps_for_theme( $slug ),
        'actions'      => array(
            // TODO: define what are the available actions and capabilities associated to them
            // 'delete'   => current_user_can( 'delete_themes' ) ? wp_nonce_url( admin_url( 'themes.php?action=delete&amp;stylesheet=' . $encoded_slug ), 'delete-theme_' . $slug ) : null,
        ),
    );
}

// TODO:
//  - add "apply_filters"?
//  - encapsulate in a separate function?
$themes = array_filter( array_values( $prepared_themes ) );

wp_reset_vars( array( 'theme', 'search' ) );

// TODO: duplicate 'theme' script to update and fit WP-AppKit needs
wp_localize_script( 'theme', '_wpThemeSettings', array(
    'themes'   => $themes,
    'settings' => array(
        'canInstall'    => ( ! is_multisite() && current_user_can( 'install_themes' ) ),
        'installURI'    => ( ! is_multisite() && current_user_can( 'install_themes' ) ) ? admin_url( 'theme-install.php' ) : null,
        'confirmDelete' => __( "Are you sure you want to delete this theme?\n\nClick 'Cancel' to go back, 'OK' to confirm the delete." ),
        'adminUrl'      => parse_url( admin_url(), PHP_URL_PATH ),
    ),
    'l10n' => array(
        'addNew'            => __( 'Add New Theme' ),
        'search'            => __( 'Search Installed Themes' ),
        'searchPlaceholder' => __( 'Search installed themes...' ), // placeholder (no ellipsis)
        'themesFound'       => __( 'Number of Themes found: %d' ),
        'noThemesFound'     => __( 'No themes found. Try a different search.' ),
    ),
) );

add_thickbox();
wp_enqueue_script( 'theme' );
?>

<div class="wrap">
    <h1><?php echo get_admin_page_title(); ?>
        <span class="title-count theme-count"><?php echo count( $themes ); ?></span>
    </h1>
    <div class="theme-browser">
        <div class="themes">
<?php
/*
 * This PHP is synchronized with the tmpl-theme template below!
 */

foreach ( $themes as $theme ) :
    $aria_action = esc_attr( $theme['id'] . '-action' );
    $aria_name   = esc_attr( $theme['id'] . '-name' );
    ?>
<div class="theme" tabindex="0" aria-describedby="<?php echo $aria_action . ' ' . $aria_name; ?>">
    <?php if ( ! empty( $theme['screenshot'][0] ) ) { ?>
        <div class="theme-screenshot">
            <img src="<?php echo $theme['screenshot'][0]; ?>" alt="" />
        </div>
    <?php } else { ?>
        <div class="theme-screenshot blank"></div>
    <?php } ?>
    <span class="more-details" id="<?php echo $aria_action; ?>"><?php _e( 'Theme Details' ); ?></span>
    <div class="theme-author"><?php printf( __( 'By %s' ), $theme['author'] ); ?></div>

    <h2 class="theme-name" id="<?php echo $aria_name; ?>"><?php echo $theme['name']; ?></h2>

    <div class="theme-actions">
    </div>
</div>
<?php endforeach; ?>
    <br class="clear" />
    </div>
</div>
<div class="theme-overlay"></div>

<p class="no-themes"><?php _e( 'No themes found. Try a different search.' ); ?></p>

</div><!-- .wrap -->

<?php
/*
 * The tmpl-theme template is synchronized with PHP above!
 */
?>
<script id="tmpl-theme" type="text/template">
    <# if ( data.screenshot[0] ) { #>
        <div class="theme-screenshot">
            <img src="{{ data.screenshot[0] }}" alt="" />
        </div>
    <# } else { #>
        <div class="theme-screenshot blank"></div>
    <# } #>
    <span class="more-details" id="{{ data.id }}-action"><?php _e( 'Theme Details' ); ?></span>
    <div class="theme-author"><?php printf( __( 'By %s' ), '{{{ data.author }}}' ); ?></div>

    <h2 class="theme-name" id="{{ data.id }}-name">{{{ data.name }}}</h2>

    <div class="theme-actions">
    </div>
</script>

<script id="tmpl-theme-single" type="text/template">
    <div class="theme-backdrop"></div>
    <div class="theme-wrap">
        <div class="theme-header">
            <button class="left dashicons dashicons-no"><span class="screen-reader-text"><?php _e( 'Show previous theme' ); ?></span></button>
            <button class="right dashicons dashicons-no"><span class="screen-reader-text"><?php _e( 'Show next theme' ); ?></span></button>
            <button class="close dashicons dashicons-no"><span class="screen-reader-text"><?php _e( 'Close details dialog' ); ?></span></button>
        </div>
        <div class="theme-about">
            <div class="theme-screenshots">
            <# if ( data.screenshot[0] ) { #>
                <div class="screenshot"><img src="{{ data.screenshot[0] }}" alt="" /></div>
            <# } else { #>
                <div class="screenshot blank"></div>
            <# } #>
            </div>

            <div class="theme-info">
                <h2 class="theme-name">{{{ data.name }}}<span class="theme-version"><?php printf( __( 'Version: %s' ), '{{ data.version }}' ); ?></span></h2>
                <p class="theme-author"><?php printf( __( 'By %s' ), '{{{ data.authorAndUri }}}' ); ?></p>

                <p class="theme-description">{{{ data.description }}}</p>

                <# if ( data.tags ) { #>
                    <p class="theme-tags"><span><?php _e( 'Tags:' ); ?></span> {{{ data.tags }}}</p>
                <# } #>
            </div>
        </div>

        <div class="theme-actions">
            <# if ( ! data.active && data.actions['delete'] ) { #>
                <a href="{{{ data.actions['delete'] }}}" class="button button-secondary delete-theme"><?php _e( 'Delete' ); ?></a>
            <# } #>
        </div>
    </div>
</script>
