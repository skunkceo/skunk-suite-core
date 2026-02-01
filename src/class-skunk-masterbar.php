<?php
/**
 * Skunk Suite Masterbar - Shared header + tab navigation
 *
 * Provides a consistent masterbar across all Skunk plugins:
 *   [S] Skunk / ProductName       [avatar ▼]
 *   [Tab1] [Tab2] [Tab3] ...
 *
 * Usage from any plugin:
 *   Skunk_Masterbar::render( 'Forms', 'home', $tabs );
 *
 * @package SkunkSuiteCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'Skunk_Masterbar' ) ) {
	return;
}

class Skunk_Masterbar {

	/**
	 * Whether CSS/JS has already been output this page load
	 *
	 * @var bool
	 */
	private static $assets_rendered = false;

	/**
	 * Render the full masterbar (header + tabs + mobile menu)
	 *
	 * @param string $product_name  Product name shown after "Skunk / " (e.g. "Forms", "CRM", "Pages")
	 * @param string $active_tab    Key of the currently active tab
	 * @param array  $tabs          Associative array of tab_id => { label, slug }
	 * @param array  $options       Optional overrides: { profile_links: array, hide_tabs: bool }
	 */
	public static function render( $product_name, $active_tab, $tabs = array(), $options = array() ) {
		$current_user = wp_get_current_user();
		$avatar_url   = get_avatar_url( $current_user->ID, array( 'size' => 32 ) );

		$profile_links = isset( $options['profile_links'] ) ? $options['profile_links'] : array();
		$hide_tabs     = ! empty( $options['hide_tabs'] );
		?>
		<!-- Unified Skunk Navigation Wrapper -->
		<div class="skunk-unified-nav">
			<!-- Skunk Header -->
			<div class="skunk-header">
				<div class="skunk-header__left">
					<?php if ( ! empty( $tabs ) ) : ?>
					<!-- Mobile Menu Toggle -->
					<button class="skunk-mobile-menu-btn" aria-label="Open menu" aria-expanded="false">
						<svg class="skunk-mobile-menu-btn__icon skunk-mobile-menu-btn__icon--menu" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<line x1="3" y1="6" x2="21" y2="6"></line>
							<line x1="3" y1="12" x2="21" y2="12"></line>
							<line x1="3" y1="18" x2="21" y2="18"></line>
						</svg>
						<svg class="skunk-mobile-menu-btn__icon skunk-mobile-menu-btn__icon--close" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<line x1="18" y1="6" x2="6" y2="18"></line>
							<line x1="6" y1="6" x2="18" y2="18"></line>
						</svg>
					</button>
					<?php endif; ?>

					<a href="<?php echo esc_url( admin_url( 'admin.php?page=skunk-dashboard' ) ); ?>" class="skunk-header__logo">
						<div class="skunk-header__logo-icon">S</div>
						<span class="skunk-header__logo-text">Skunk</span>
					</a>
						<span class="skunk-header__separator">/</span>
						<span class="skunk-header__page-name"><?php echo esc_html( $product_name ); ?></span>
				</div>

				<!-- User Actions -->
				<div class="skunk-header__actions">
					<!-- App Grid — back to Suite Dashboard -->
					<div class="skunk-app-grid-wrap" style="position: relative;">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=skunk-dashboard' ) ); ?>" class="skunk-app-grid-btn" title="Skunk Suite">
							<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
								<rect x="2" y="2" width="4.5" height="4.5" rx="1" />
								<rect x="7.75" y="2" width="4.5" height="4.5" rx="1" />
								<rect x="13.5" y="2" width="4.5" height="4.5" rx="1" />
								<rect x="2" y="7.75" width="4.5" height="4.5" rx="1" />
								<rect x="7.75" y="7.75" width="4.5" height="4.5" rx="1" />
								<rect x="13.5" y="7.75" width="4.5" height="4.5" rx="1" />
								<rect x="2" y="13.5" width="4.5" height="4.5" rx="1" />
								<rect x="7.75" y="13.5" width="4.5" height="4.5" rx="1" />
								<rect x="13.5" y="13.5" width="4.5" height="4.5" rx="1" />
							</svg>
						</a>
					</div>

					<!-- Notifications mount point (React plugins can populate) -->
					<div id="skunk-masterbar-notifications" class="skunk-notifications-mount"></div>

					<!-- User Avatar Dropdown -->
					<div id="skunk-masterbar-profile" class="skunk-profile-mount">
						<div class="skunk-profile-wrapper" style="position: relative; z-index: 1001;">
							<button id="skunk-profile-btn" class="skunk-profile-btn" style="display: flex; align-items: center; background: none; border: none; cursor: pointer; padding: 4px; border-radius: 6px;">
								<img src="<?php echo esc_url( $avatar_url ); ?>" alt="<?php echo esc_attr( $current_user->display_name ); ?>" style="width: 32px; height: 32px; border-radius: 50%; margin-right: 8px; object-fit: cover; border: 2px solid #e5e7eb; background-color: #f3f4f6;">
								<span id="skunk-profile-arrow" style="font-size: 12px; color: #6b7280; transition: transform 0.15s ease;">▼</span>
							</button>
							<div id="skunk-profile-dropdown" class="skunk-profile-dropdown" style="display: none; position: absolute; top: 100%; right: 0; background-color: white; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); min-width: 200px; z-index: 10000; padding: 8px 0;">
								<div style="padding: 12px 16px; border-bottom: 1px solid #f3f4f6; margin-bottom: 8px;">
									<div style="font-weight: 500; color: #111827; margin-bottom: 2px;"><?php echo esc_html( $current_user->display_name ); ?></div>
									<div style="font-size: 12px; color: #6b7280;"><?php echo esc_html( $current_user->user_email ); ?></div>
								</div>
								<a href="<?php echo esc_url( admin_url( 'profile.php' ) ); ?>" class="skunk-profile-menu-item">
									<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
									My Profile
								</a>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=skunk-settings' ) ); ?>" class="skunk-profile-menu-item">
									<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 0 0-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 0 0-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 0 0-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 0 0-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 0 0 1.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><circle cx="12" cy="12" r="3"></circle></svg>
									Settings
								</a>
								<?php foreach ( $profile_links as $link ) : ?>
								<a href="<?php echo esc_url( $link['url'] ); ?>" class="skunk-profile-menu-item" <?php echo ! empty( $link['style'] ) ? 'style="' . esc_attr( $link['style'] ) . '"' : ''; ?> <?php echo ! empty( $link['id'] ) ? 'id="' . esc_attr( $link['id'] ) . '"' : ''; ?>>
									<?php if ( ! empty( $link['icon'] ) ) echo $link['icon']; ?>
									<?php echo esc_html( $link['label'] ); ?>
								</a>
								<?php endforeach; ?>
								<div style="border-top: 1px solid #f3f4f6; margin: 8px 0;"></div>
								<a href="<?php echo esc_url( wp_logout_url( admin_url() ) ); ?>" class="skunk-profile-menu-item" style="color: #dc2626;">
									<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
									Log Out
								</a>
							</div>
						</div>
					</div>
				</div>
			</div>

			<?php if ( ! empty( $tabs ) && ! $hide_tabs ) : ?>
			<!-- Tab Navigation (Desktop) -->
			<div class="skunk-tab-nav">
				<?php foreach ( $tabs as $tab_id => $tab ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $tab['slug'] ) ); ?>"
				   class="skunk-tab-nav__item <?php echo $active_tab === $tab_id ? 'skunk-tab-nav__item--active' : ''; ?>"
				   data-tab="<?php echo esc_attr( $tab_id ); ?>">
					<?php echo esc_html( $tab['label'] ); ?>
				</a>
				<?php endforeach; ?>
			</div>

			<!-- Mobile Menu Overlay -->
			<div class="skunk-mobile-menu">
				<div class="skunk-mobile-menu__backdrop"></div>
				<nav class="skunk-mobile-menu__nav">
					<div class="skunk-mobile-menu__header">
						<div class="skunk-header__logo-icon">S</div>
						<span class="skunk-header__logo-text">Skunk <?php echo esc_html( $product_name ); ?></span>
					</div>
					<div class="skunk-mobile-menu__items">
						<?php foreach ( $tabs as $tab_id => $tab ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $tab['slug'] ) ); ?>"
						   class="skunk-mobile-menu__item <?php echo $active_tab === $tab_id ? 'skunk-mobile-menu__item--active' : ''; ?>"
						   data-tab="<?php echo esc_attr( $tab_id ); ?>">
							<?php echo esc_html( $tab['label'] ); ?>
						</a>
						<?php endforeach; ?>
					</div>
					<div class="skunk-mobile-menu__footer">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=skunk-settings' ) ); ?>" class="skunk-mobile-menu__item">
							<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;">
								<path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 0 0-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 0 0-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 0 0-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 0 0-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 0 0 1.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
								<circle cx="12" cy="12" r="3"></circle>
							</svg>
							Settings
						</a>
					</div>
				</nav>
			</div>
			<?php endif; ?>
		</div>

		<?php self::render_assets(); ?>
		<?php
	}

	/**
	 * Render CSS + JS (once per page load)
	 */
	private static function render_assets() {
		if ( self::$assets_rendered ) {
			return;
		}
		self::$assets_rendered = true;
		?>
		<script>
		(function() {
			// Unified menu body class
			document.body.classList.add('skunk-unified-menu');

			// Profile dropdown
			var btn = document.getElementById('skunk-profile-btn');
			var dropdown = document.getElementById('skunk-profile-dropdown');
			var arrow = document.getElementById('skunk-profile-arrow');
			if (!btn || !dropdown) return;

			var isOpen = false;
			function toggle() {
				isOpen = !isOpen;
				dropdown.style.display = isOpen ? 'block' : 'none';
				if (arrow) arrow.style.transform = isOpen ? 'rotate(180deg)' : 'rotate(0deg)';
			}
			btn.addEventListener('click', function(e) { e.stopPropagation(); toggle(); });
			document.addEventListener('click', function(e) {
				if (isOpen && !dropdown.contains(e.target) && !btn.contains(e.target)) {
					isOpen = false;
					dropdown.style.display = 'none';
					if (arrow) arrow.style.transform = 'rotate(0deg)';
				}
			});
			btn.addEventListener('mouseenter', function() { btn.style.backgroundColor = '#f3f4f6'; });
			btn.addEventListener('mouseleave', function() { btn.style.backgroundColor = 'transparent'; });

			// Mobile menu
			var menuBtn = document.querySelector('.skunk-mobile-menu-btn');
			var mobileMenu = document.querySelector('.skunk-mobile-menu');
			var backdrop = document.querySelector('.skunk-mobile-menu__backdrop');
			if (menuBtn && mobileMenu) {
				function toggleMobile() {
					var open = mobileMenu.classList.toggle('is-open');
					menuBtn.setAttribute('aria-expanded', open);
					document.body.style.overflow = open ? 'hidden' : '';
				}
				menuBtn.addEventListener('click', toggleMobile);
				if (backdrop) backdrop.addEventListener('click', toggleMobile);
			}
		})();
		</script>

		<style>
			/* Remove WordPress default padding/margins on Skunk pages */
			#wpcontent { padding-left: 0 !important; }
			#wpbody-content { padding-bottom: 0 !important; }
			.skunk-unified-nav + .wrap,
			.skunk-unified-nav ~ .wrap,
			.skunk-unified-nav ~ * .wrap,
			.skunk-unified-nav ~ .skunk-page-content .wrap { margin: 0 !important; padding: 0 !important; max-width: none !important; }

			/* Unified navigation wrapper */
			.skunk-unified-nav { position: relative; z-index: 100; }

			/* Header */
			.skunk-header {
				background: #fff;
				padding: 16px 24px;
				display: flex;
				align-items: center;
				justify-content: space-between;
			}
			.skunk-header__left { display: flex; align-items: center; }
			.skunk-header__logo { display: flex; align-items: center; text-decoration: none; }
			.skunk-header__logo:hover { text-decoration: none; opacity: 0.8; }
			.skunk-header__logo:focus { outline: none; box-shadow: none; }
			.skunk-header__logo-icon {
				width: 32px; height: 32px; background: #000; border-radius: 4px;
				display: flex; align-items: center; justify-content: center;
				color: #fff; font-weight: 900; font-size: 16px; margin-right: 12px;
			}
			.skunk-header__logo-text {
				font-family: 'Work Sans', -apple-system, BlinkMacSystemFont, sans-serif;
				font-weight: 900; font-size: 24px; color: #000;
			}
			.skunk-header__separator { margin: 0 12px; color: #d1d5db; font-size: 20px; }
			.skunk-header__page-name { font-size: 16px; color: #6b7280; font-weight: 500; }

			/* Header Actions */
			.skunk-header__actions { display: flex; align-items: center; gap: 8px; }

			/* App Grid Button */
			.skunk-app-grid-btn {
				display: flex; align-items: center; justify-content: center;
				width: 36px; height: 36px; border-radius: 8px;
				color: #6b7280; text-decoration: none;
				transition: all 0.15s;
			}
			.skunk-app-grid-btn:hover {
				background: #f3f4f6; color: #111827;
			}
			.skunk-notifications-mount { display: flex; align-items: center; margin-right: 8px; }
			.skunk-notifications-mount:empty { display: none; }
			.skunk-profile-mount { display: flex; align-items: center; }

			/* Protect masterbar from global box-sizing resets (e.g. Tailwind) */
			.skunk-unified-nav, .skunk-unified-nav *, .skunk-unified-nav *::before, .skunk-unified-nav *::after {
				box-sizing: content-box;
			}
			.skunk-unified-nav button, .skunk-unified-nav input, .skunk-unified-nav select, .skunk-unified-nav textarea {
				box-sizing: border-box;
			}

			/* Profile dropdown menu items */
			.skunk-profile-menu-item {
				display: flex; align-items: center; gap: 8px;
				padding: 8px 16px; color: #374151; text-decoration: none;
				font-size: 14px; transition: background-color 0.15s ease;
			}
			.skunk-profile-menu-item:hover { background-color: #f9fafb; text-decoration: none; }
			.skunk-profile-menu-item svg { flex-shrink: 0; }

			/* Tab Navigation */
			.skunk-tab-nav {
				display: flex; gap: 0; background: #fff;
				border-bottom: 1px solid #e5e7eb; padding: 0 24px;
			}
			.skunk-tab-nav__item {
				padding: 12px 16px; text-decoration: none; color: #6b7280;
				font-size: 13px; font-weight: 500;
				border-bottom: 2px solid transparent; margin-bottom: -1px;
				transition: all 0.15s;
			}
			.skunk-tab-nav__item:hover { color: #111827; background: #f9fafb; }
			.skunk-tab-nav__item--active { color: #E50914; border-bottom-color: #E50914; }
			.skunk-tab-nav__item--active:hover { color: #E50914; background: transparent; }
			.skunk-tab-nav__item:focus { outline: none !important; box-shadow: none !important; }

			/* Mobile Menu Button */
			.skunk-mobile-menu-btn {
				display: none; align-items: center; justify-content: center;
				width: 44px; height: 44px; background: none; border: none;
				cursor: pointer; color: #374151; margin-right: 8px;
				border-radius: 8px; transition: background-color 0.15s;
			}
			.skunk-mobile-menu-btn:hover { background: #f3f4f6; }
			.skunk-mobile-menu-btn__icon--close { display: none; }
			.skunk-mobile-menu-btn[aria-expanded="true"] .skunk-mobile-menu-btn__icon--menu { display: none; }
			.skunk-mobile-menu-btn[aria-expanded="true"] .skunk-mobile-menu-btn__icon--close { display: block; }

			/* Mobile Menu */
			.skunk-mobile-menu {
				display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0;
				z-index: 99999; pointer-events: none;
			}
			.skunk-mobile-menu.is-open { pointer-events: auto; }
			.skunk-mobile-menu__backdrop {
				position: absolute; top: 0; left: 0; right: 0; bottom: 0;
				background: rgba(0,0,0,0.5); opacity: 0; transition: opacity 0.3s ease;
			}
			.skunk-mobile-menu.is-open .skunk-mobile-menu__backdrop { opacity: 1; }
			.skunk-mobile-menu__nav {
				position: absolute; top: 0; left: 0; bottom: 0; width: 280px;
				background: #fff; box-shadow: 2px 0 12px rgba(0,0,0,0.15);
				transform: translateX(-100%); transition: transform 0.3s ease;
				display: flex; flex-direction: column;
			}
			.skunk-mobile-menu.is-open .skunk-mobile-menu__nav { transform: translateX(0); }
			.skunk-mobile-menu__header {
				display: flex; align-items: center; gap: 12px;
				padding: 20px 24px; border-bottom: 1px solid #e5e7eb;
			}
			.skunk-mobile-menu__items { flex: 1; padding: 8px 0; overflow-y: auto; }
			.skunk-mobile-menu__item {
				display: flex; align-items: center; padding: 12px 24px;
				text-decoration: none; color: #374151; font-size: 15px;
				font-weight: 500; transition: background-color 0.15s;
			}
			.skunk-mobile-menu__item:hover { background: #f9fafb; }
			.skunk-mobile-menu__item--active { color: #E50914; background: #fef2f2; }
			.skunk-mobile-menu__footer {
				padding: 8px 0; border-top: 1px solid #e5e7eb;
			}

			/* Responsive */
			@media (max-width: 782px) {
				.skunk-mobile-menu-btn { display: flex; }
				.skunk-tab-nav { display: none; }
				.skunk-mobile-menu { display: block; }
				.skunk-header__separator, .skunk-header__page-name { display: none; }
			}
		</style>
		<?php
	}
}
