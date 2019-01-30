<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License Version 3
	 * @since       1.0.3
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	/**
	 * @var array $VARS
	 * @var Freemius
	 */
	$fs = freemius( $VARS['id'] );

	$slug = $fs->get_slug();

	$open_addon_slug = fs_request_get( 'slug' );

	$open_addon = false;

	/**
	 * @var FS_Plugin[]
	 */
	$addons = $fs->get_addons();

	$has_addons = ( is_array( $addons ) && 0 < count( $addons ) );

    $account_addon_ids = $fs->get_updated_account_addons();

    $download_latest_text = fs_text_x_inline( 'Download Latest', 'as download latest version', 'download-latest', $slug );
    $view_details_text    = fs_text_inline( 'View details', 'view-details', $slug );

	$has_tabs = $fs->_add_tabs_before_content();
?>
	<div id="fs_addons" class="wrap fs-section">
		<?php if ( ! $has_tabs ) : ?>
		<h2><?php echo esc_html( sprintf( fs_text_inline( 'Add Ons for %s', 'add-ons-for-x', $slug ), $fs->get_plugin_name() ) ) ?></h2>
		<?php endif ?>

		<div id="poststuff">
			<?php if ( ! $has_addons ) : ?>
				<h3><?php echo esc_html( sprintf(
						'%s... %s',
						fs_text_x_inline( 'Oops', 'exclamation', 'oops', $slug ),
						fs_text_inline( 'We could\'nt load the add-ons list. It\'s probably an issue on our side, please try to come back in few minutes.', 'add-ons-missing', $slug )
					) ) ?></h3>
			<?php endif ?>
			<ul class="fs-cards-list">
				<?php if ( $has_addons ) : ?>
					<?php foreach ( $addons as $addon ) : ?>
						<?php
                        $is_addon_installed = $fs->is_addon_installed( $addon->id );
                        $is_addon_activated = $is_addon_installed ?
                            $fs->is_addon_activated( $addon->id ) :
                            false;

						$open_addon = ( $open_addon || ( $open_addon_slug === $addon->slug ) );

						$price     = 0;
						$has_trial = false;
						$has_free_plan = false;
						$has_paid_plan = false;

						$result    = $fs->get_api_plugin_scope()->get( $fs->add_show_pending( "/addons/{$addon->id}/pricing.json?type=visible" ) );
						if ( ! isset( $result->error ) ) {
							$plans = $result->plans;

							if ( is_array( $plans ) && 0 < count( $plans ) ) {
								foreach ( $plans as $plan ) {
									if ( ! isset( $plan->pricing ) ||
									     ! is_array( $plan->pricing ) ||
									     0 == count( $plan->pricing )
									) {
										// No pricing means a free plan.
										$has_free_plan = true;
										continue;
									}


									$has_paid_plan = true;
									$has_trial     = $has_trial || ( is_numeric( $plan->trial_period ) && ( $plan->trial_period > 0 ) );

									$min_price = 999999;
									foreach ( $plan->pricing as $pricing ) {
										if ( ! is_null( $pricing->annual_price ) && $pricing->annual_price > 0 ) {
											$min_price = min( $min_price, $pricing->annual_price );
										} else if ( ! is_null( $pricing->monthly_price ) && $pricing->monthly_price > 0 ) {
											$min_price = min( $min_price, 12 * $pricing->monthly_price );
										}
									}

									if ( $min_price < 999999 ) {
										$price = $min_price;
									}

								}
							}

							if ( ! $has_paid_plan && ! $has_free_plan ) {
							    continue;
                            }
						}
						?>
						<li class="fs-card fs-addon" data-slug="<?php echo $addon->slug ?>">
							<?php
								$view_details_link = sprintf( '<a href="%s" aria-label="%s" data-title="%s"',
									esc_url( network_admin_url( 'plugin-install.php?fs_allow_updater_and_dialog=true&tab=plugin-information&parent_plugin_id=' . $fs->get_id() . '&plugin=' . $addon->slug .
									                            '&TB_iframe=true&width=600&height=550' ) ),
									esc_attr( sprintf( fs_text_inline( 'More information about %s', 'more-information-about-x', $slug ), $addon->title ) ),
									esc_attr( $addon->title )
								) . ' class="thickbox%s">%s</a>';

								echo sprintf(
                                    $view_details_link,
                                    /**
                                     * Additional class.
                                     *
                                     * @author Leo Fajardo (@leorw)
                                     * @since 2.2.3.2
                                     */
                                    ' fs-overlay',
                                    /**
                                     * Set the view details link text to an empty string since it is an overlay that
                                     * doesn't really need a text and whose purpose is to open the details dialog when
                                     * the card is clicked.
                                     *
                                     * @author Leo Fajardo (@leorw)
                                     * @since 2.2.3.2
                                     */
                                    ''
                                );
							?>
							<?php
								if ( is_null( $addon->info ) ) {
									$addon->info = new stdClass();
								}
								if ( ! isset( $addon->info->card_banner_url ) ) {
									$addon->info->card_banner_url = '//dashboard.freemius.com/assets/img/marketing/blueprint-300x100.jpg';
								}
								if ( ! isset( $addon->info->short_description ) ) {
									$addon->info->short_description = 'What\'s the one thing your add-on does really, really well?';
								}
							?>
							<div class="fs-inner">
								<ul>
									<li class="fs-card-banner"
                                        style="background-image: url('<?php echo $addon->info->card_banner_url ?>');"><?php
                                        if ( $is_addon_activated || $is_addon_installed ) {
                                            echo sprintf(
                                                '<span class="fs-badge fs-installed-addon-badge">%s</span>',
                                                esc_html( $is_addon_activated ?
                                                    fs_text_x_inline( 'Active', 'active add-on', 'active-addon', $slug ) :
                                                    fs_text_x_inline( 'Installed', 'installed add-on', 'installed-addon', $slug )
                                                )
                                            );
                                        }
                                        ?></li>
									<!-- <li class="fs-tag"></li> -->
									<li class="fs-title"><?php echo $addon->title ?></li>
									<li class="fs-offer">
									<span
										class="fs-price"><?php
											$descriptors = array();

											if ($has_free_plan)
												$descriptors[] = fs_text_inline( 'Free', 'free', $slug );
											if ($has_paid_plan && $price > 0)
												$descriptors[] = '$' . number_format( $price, 2 );
											if ($has_trial)
												$descriptors[] = fs_text_x_inline( 'Trial', 'trial period',  'trial', $slug );

											echo implode(' - ', $descriptors) ?></span>
									</li>
									<li class="fs-description"><?php echo ! empty( $addon->info->short_description ) ? $addon->info->short_description : 'SHORT DESCRIPTION' ?></li>
                                    <?php if ( ! in_array( $addon->id, $account_addon_ids ) || $is_addon_installed ) : ?>
									<li class="fs-cta"><a class="button"><?php echo esc_html( $view_details_text ) ?></a></li>
                                    <?php else : ?>
                                        <?php
                                            $latest_download_local_url = $fs->_get_latest_download_local_url( $addon->id );
                                            $is_allowed_to_install     = $fs->is_allowed_to_install();
                                        ?>

                                        <li class="fs-cta fs-dropdown">
                                        <div class="button-group">
                                            <?php if ( $is_allowed_to_install ) : ?>
                                            <?php
                                                echo sprintf(
                                                    '<a class="button button-primary" href="%s">%s</a>',
                                                   wp_nonce_url( self_admin_url( 'update.php?fs_allow_updater_and_dialog=true&action=install-plugin&plugin=' . $addon->slug ), 'install-plugin_' . $addon->slug ),
                                                   fs_esc_html_inline( 'Install Now', 'install-now', $slug )
                                               );
                                            ?>
                                            <?php else : ?>
                                            <a target="_blank" class="button button-primary" href="<?php echo $latest_download_local_url ?>"><?php echo esc_html( $download_latest_text ) ?></a>
                                            <?php endif ?>
                                            <div class="button button-primary fs-dropdown-arrow-button"><span class="fs-dropdown-arrow"></span><ul class="fs-dropdown-list" style="display: none">
		                                            <?php if ( $is_allowed_to_install ) : ?>
			                                            <li><a target="_blank" href="<?php echo $latest_download_local_url ?>"><?php echo esc_html( $download_latest_text ) ?></a></li>
		                                            <?php endif ?>
		                                            <li><?php
				                                            echo sprintf(
					                                            $view_details_link,
					                                            /**
					                                             * No additional class.
					                                             *
					                                             * @author Leo Fajardo (@leorw)
					                                             * @since 2.2.3.2
					                                             */
					                                            '',
					                                            /**
					                                             * Set the view details link text to a non-empty string since it is an
					                                             * item in the dropdown list and the text should be visible.
					                                             *
					                                             * @author Leo Fajardo (@leorw)
					                                             * @since 2.2.3.2
					                                             */
					                                            esc_html( $view_details_text )
				                                            );
			                                            ?></li>
	                                            </ul></div>
                                        </div>
                                    </li>
                                    <?php endif ?>
								</ul>
							</div>
						</li>
					<?php endforeach ?>
				<?php endif ?>
			</ul>
		</div>
	</div>
	<script type="text/javascript">
		(function( $, undef ) {
			<?php if ( $open_addon ) : ?>

			var interval = setInterval(function () {
				// Open add-on information page.
				<?php
				/**
				 * @author Vova Feldman
				 *
				 * This code does NOT expose an XSS vulnerability because:
				 *  1. This page only renders for admins, so if an attacker manage to get
				 *     admin access, they can do more harm.
				 *  2. This code won't be rendered unless $open_addon_slug matches any of
				 *     the plugin's add-ons slugs.
				 */
				?>
				$('.fs-card[data-slug=<?php echo $open_addon_slug ?>] a').click();
				if ($('#TB_iframeContent').length > 0) {
					clearInterval(interval);
					interval = null;
				}
			}, 200);

			<?php else : ?>

			$( '.fs-card.fs-addon' )
				.mouseover(function() {
				    var $this = $( this );

					$this.find( '.fs-cta .button' ).addClass( 'button-primary' );

                    if ( 0 === $this.find( '.fs-dropdown-arrow-button.active' ).length ) {
                        /**
                         * When hovering over a card, close the dropdown on any other card.
                         *
                         * @author Leo Fajardo (@leorw)
                         * @since 2.2.3.2
                         */
                        toggleDropdown();
                    }
				}).mouseout(function( evt ) {
                    var $relatedTarget = $( evt.relatedTarget );

                    if ( 0 !== $relatedTarget.parents( '.fs-addon' ).length ) {
                        return true;
                    }

                    var $this = $( this );

                    /**
                     * Set the color of the "View details" button to "secondary".
                     *
                     * @author Leo Fajardo (@leorw)
                     * @since 2.2.3.2
                     */
					$this.find( '.fs-cta .button' ).filter(function() {
                        /**
                         * Keep the "primary" color of the dropdown arrow button, "Install Now" button, and
                         * "Download Latest" button.

                         * @author Leo Fajardo (@leorw)
                         * @since 2.2.3.2
                         */
					    return $( this ).parent().is( ':not(.button-group)' );
                    }).removeClass('button-primary');

					toggleDropdown( $this.find( '.fs-dropdown' ), false );
				}).find( 'a.thickbox, .button:not(.fs-dropdown-arrow-button)' ).click(function() {
                    toggleDropdown();
                });

			<?php endif ?>

            var $dropdowns = $( '.fs-dropdown' );
            if ( 0 !== $dropdowns.length ) {
                $dropdowns.find( '.fs-dropdown-arrow-button' ).click(function() {
                    var $this     = $( this ),
                        $dropdown = $this.parents( '.fs-dropdown' );

                    toggleDropdown( $dropdown, ! $dropdown.hasClass( 'active' ) );
                });
            }

            /**
             * Returns the default state of the dropdown arrow button and hides the dropdown list.
             *
             * @author Leo Fajardo (@leorw)
             * @since 2.2.3.2
             *
             * @param {(Object|undefined)}  [$dropdown]
             * @param {(Boolean|undefined)} [state]
             */
            function toggleDropdown( $dropdown, state ) {
                if ( undef === $dropdown ) {
                    var $activeDropdown = $dropdowns.find( '.active' );
                    if ( 0 !== $activeDropdown.length ) {
                        $dropdown = $activeDropdown;
                    }
                }

                if ( undef === $dropdown ) {
                    return;
                }

                if ( undef === state ) {
                    state = false;
                }

                $dropdown.toggleClass( 'active', state );
                $dropdown.find( '.fs-dropdown-list' ).toggle( state );
                $dropdown.find( '.fs-dropdown-arrow-button' ).toggleClass( 'active', state );
            }
		})( jQuery );
	</script>
<?php
	if ( $has_tabs ) {
		$fs->_add_tabs_after_content();
	}

	$params = array(
		'page'           => 'addons',
		'module_id'      => $fs->get_id(),
		'module_type'    => $fs->get_module_type(),
		'module_slug'    => $slug,
		'module_version' => $fs->get_plugin_version(),
	);
	fs_require_template( 'powered-by.php', $params );