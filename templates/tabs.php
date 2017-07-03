<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
	 * @since       1.2.2.7
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	/**
	 * @var array    $VARS
	 * @var Freemius $fs
	 */
	$fs = freemius( $VARS['id'] );

	$slug = $fs->get_slug();

	$menu_items = $fs->get_menu_items();

	$tabs = array();
	foreach ( $menu_items as $priority => $items ) {
		foreach ( $items as $item ) {
			$url   = $fs->_get_admin_page_url( $item['menu_slug'] );
			$title = $item['menu_title'];

			$tab = array(
				'label' => $title,
				'href'  => $url,
				'slug'  => $item['menu_slug'],
			);

			if ( 'pricing' === $item['menu_slug'] && $fs->is_in_trial_promotion() ) {
				$tab['href'] .= '&trial=true';
			}

			$tabs[] = $tab;
		}
	}
?>
<script type="text/javascript">
	(function ($) {
		$(document).ready(function () {
			var $wrap        = $('.wrap'),
			    $tabsWrapper = $('.nav-tab-wrapper'),
			    $tabs        = $tabsWrapper.find('.nav-tab'),
			    $tab         = null;

			if (0 < $tabs.length) {
				// Tries to set $tab to the first inactive tab.
				for (var i = 0; i < $tabs.length; i++) {
					$tab = $($tabs[i]);

					if (!$tab.hasClass('nav-tab-active')) {
						break;
					}
				}
			}

			if (null == $tab) {
				// No tabs found, therefore, create new tabs section if required.
				<?php if (0 < count( $tabs )) : ?>
				var $h1 = $wrap.find('h1');

				$tabsWrapper = $('<h2 class="nav-tab-wrapper"></h2>');

				<?php foreach ($tabs as $tab) : ?>
				// @todo What happens when one of the pages is loaded and there's no tabs.
				// @todo What about the Billing and account tabs?
				$('<a href="<?php echo $tab['href'] ?>" class="nav-tab"><?php echo esc_html( $tab['label'] ) ?></a>')
					.appendTo($tabsWrapper);
				<?php endforeach ?>

				if (0 < $h1.length) {
					$tabsWrapper.insertAfter($h1);
				} else if (0 < $wrap.length) {
					$wrap.prepend($tabsWrapper);
				}
				<?php endif ?>
			} else {
				// Create a clone.
				$tab = $tab.clone();
				// Open in current page.
				$tab.removeAttr('target');
				$tab.removeClass('nav-tab-active');
				$tab.addClass('fs-tab');
				$tab.addClass('<?php echo $fs->get_unique_affix() ?>');

				var $tabClone = null;

				<?php $freemius_context_page = null ?>

				<?php foreach ($tabs as $tab) : ?>
				<?php $is_support_tab = ( 'wp-support-forum' == $tab['slug'] ) ?>
				// Add the Freemius tabs.
				$tabClone = $tab.clone();
				$tabClone.html(<?php echo json_encode( $tab['label'] ) ?>)
					.attr('href', '<?php echo $is_support_tab ? $fs->get_support_forum_url() : $tab['href'] ?>')
					.appendTo($tabsWrapper)
					// Remove any custom click events.
					.off('click', '**')
					.addClass('<?php echo $tab['slug'] ?>')
					// Avoid tab click triggering parent events.
					.click(function (e) {
						e.stopPropagation();
					});

				<?php if ($is_support_tab) : ?>
				// Open support in a new tab/page.
				$tabClone.attr('target', '_blank');
				<?php endif ?>

				<?php if ($fs->is_admin_page( $tab['slug'] )) : ?>
				<?php $freemius_context_page = $tab['slug'] ?>
				// Select the relevant Freemius tab.
				$tabs.removeClass('nav-tab-active');
				$tabClone.addClass('nav-tab-active');

				<?php if (in_array( $freemius_context_page, array( 'pricing', 'contact', 'checkout' ) )) : ?>
				// Add AJAX loader.
				$tabClone.prepend('<i class="fs-ajax-spinner"></i>');
				// Hide loader after content fully loaded.
				$('.wrap i' + 'frame').load(function () {
					$(".fs-ajax-spinner").hide();
				});
				<?php endif ?>

				// Fix URLs that are starting with a hashtag.
				$tabs.each(function (j, tab) {
					if (0 === $(tab).attr('href').indexOf('#')) {
						$(tab).attr('href', '<?php echo esc_js( $fs->main_menu_url() ) ?>' + $(tab).attr('href'));
					}
				});
				<?php endif ?>
				<?php endforeach ?>

				var selectTab = function ($tab) {
					$(window).load(function () {
						$tab.click();

						// Scroll to the top since the browser will auto scroll to the anchor.
						document.body.scrollTop = 0;
						document.body.scrollLeft = 0;
//						window.scrollTo(0,0);
					});
				};

				// If the URL is loaded with a hashtag, find the context tab and select it.
				if (window.location.hash) {
					for (var j = 0; j < $tabs.length; j++) {
						if (window.location.hash === $($tabs[j]).attr('href')) {
							selectTab($($tabs[j]));
							break;
						}
					}
				}

				<?php if (is_string( $freemius_context_page ) && in_array( $freemius_context_page, array(
				'pricing',
				'contact',
				'checkout'
			) )) : ?>
				// Add margin to the upper section of the tabs to give extra space for the HTTPS header.
				// @todo This code assumes that the wrapper style is fully loaded, if there's a stylesheet that is not loaded via the HTML head, it may cause unpredicted margin-top.
				var $tabsWrap = $tabsWrapper.parents('.wrap');
				$tabsWrap.css('marginTop', (parseInt($tabsWrap.css('marginTop'), 10) + 30) + 'px');
				<?php endif ?>
			}
		});
	})(jQuery);
</script>