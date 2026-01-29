<?php
/**
 * Skunk Suite Dashboard - Suite dashboard page + NUX overlay
 *
 * @package SkunkSuiteCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'Skunk_Dashboard' ) ) {
	return;
}

class Skunk_Dashboard {

	/**
	 * Render the suite dashboard page
	 */
	public static function render() {
		$products = Skunk_Product_Detect::get_active_map();
		$show_nux = current_user_can( 'manage_options' ) && ! get_option( 'skunk_suite_onboarded', false );
		?>
		<div class="wrap skunk-dashboard">
			<h1>Skunk Suite Dashboard</h1>

			<?php self::render_welcome( $products ); ?>

			<!-- Getting Started Checklist -->
			<div class="skunk-dashboard__checklist">
				<h3>Getting Started</h3>
				<?php self::render_checklist( $products ); ?>
			</div>

			<!-- Installed Products -->
			<div class="skunk-dashboard__products">
				<h3>Your Skunk Products</h3>
				<div class="skunk-dashboard__product-grid">
					<?php
					$all_products = Skunk_Product_Detect::get_all_products();
					foreach ( $all_products as $key => $info ) :
						$active = $products[ $key ];
					?>
					<div class="skunk-dashboard__product-card <?php echo $active ? 'is-active' : 'is-inactive'; ?>">
						<span class="skunk-dashboard__product-icon" style="display:inline-flex;width:32px;height:32px;border-radius:8px;align-items:center;justify-content:center;background:<?php echo esc_attr( $info['color'] ); ?>;">
							<?php echo Skunk_Icons::get( $info['icon'], 16 ); ?>
						</span>
						<div class="skunk-dashboard__product-info">
							<strong><?php echo esc_html( $info['name'] ); ?></strong>
							<span><?php echo esc_html( $info['desc'] ); ?></span>
						</div>
						<?php if ( $active ) : ?>
							<span class="skunk-dashboard__product-badge skunk-dashboard__product-badge--active">Active</span>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $info['menu_slug'] ) ); ?>" class="skunk-dashboard__product-link">Open →</a>
						<?php else : ?>
							<span class="skunk-dashboard__product-badge skunk-dashboard__product-badge--inactive">Not Installed</span>
						<?php endif; ?>
					</div>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- Quick Stats -->
			<?php
			$cards = apply_filters( 'skunk_dashboard_cards', array() );
			if ( ! empty( $cards ) ) :
			?>
			<div class="skunk-dashboard__stats-section">
				<h3>Quick Stats</h3>
				<div class="skunk-dashboard__cards">
					<?php foreach ( $cards as $plugin_id => $card ) : ?>
					<div class="skunk-dashboard__card">
						<h4><?php echo esc_html( $card['title'] ); ?></h4>
						<?php if ( ! empty( $card['stats'] ) ) : ?>
						<div class="skunk-dashboard__stats">
							<?php foreach ( $card['stats'] as $stat ) : ?>
							<div class="skunk-dashboard__stat">
								<span class="skunk-dashboard__stat-value"><?php echo esc_html( $stat['value'] ); ?></span>
								<span class="skunk-dashboard__stat-label"><?php echo esc_html( $stat['label'] ); ?></span>
							</div>
							<?php endforeach; ?>
						</div>
						<?php endif; ?>
						<?php if ( ! empty( $card['link'] ) ) : ?>
						<a href="<?php echo esc_url( $card['link'] ); ?>" class="skunk-dashboard__card-link">View <?php echo esc_html( $card['title'] ); ?> →</a>
						<?php endif; ?>
					</div>
					<?php endforeach; ?>
				</div>
			</div>
			<?php endif; ?>
		</div>

		<style>
			.skunk-dashboard { max-width: 960px; }
			.skunk-dashboard h3 { font-size: 16px; font-weight: 600; margin: 24px 0 12px; color: #111827; }
			.skunk-dashboard__welcome { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 24px; margin-bottom: 24px; }
			.skunk-dashboard__welcome h2 { margin: 0 0 8px; font-size: 20px; }
			.skunk-dashboard__welcome p { margin: 0 0 4px; color: #6b7280; }
			.skunk-dashboard__checklist { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px 24px; margin-bottom: 24px; }
			.skunk-dashboard__checklist ul { margin: 0; padding: 0; list-style: none; }
			.skunk-dashboard__checklist li { padding: 8px 0; display: flex; align-items: center; gap: 10px; font-size: 14px; color: #374151; border-bottom: 1px solid #f3f4f6; }
			.skunk-dashboard__checklist li:last-child { border-bottom: none; }
			.skunk-dashboard__products { margin-bottom: 24px; }
			.skunk-dashboard__product-grid { display: flex; flex-direction: column; gap: 8px; }
			.skunk-dashboard__product-card { display: flex; align-items: center; gap: 12px; background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 14px 18px; }
			.skunk-dashboard__product-card.is-inactive { opacity: 0.7; }
			.skunk-dashboard__product-icon { font-size: 24px; }
			.skunk-dashboard__product-info { flex: 1; display: flex; flex-direction: column; }
			.skunk-dashboard__product-info strong { font-size: 14px; color: #111827; }
			.skunk-dashboard__product-info span { font-size: 12px; color: #6b7280; }
			.skunk-dashboard__product-badge { font-size: 11px; font-weight: 600; text-transform: uppercase; padding: 3px 8px; border-radius: 4px; }
			.skunk-dashboard__product-badge--active { background: #dcfce7; color: #15803d; }
			.skunk-dashboard__product-badge--inactive { background: #f3f4f6; color: #6b7280; }
			.skunk-dashboard__product-link { font-size: 13px; font-weight: 500; color: #E50914; text-decoration: none; padding: 6px 12px; border: 1px solid #E50914; border-radius: 6px; }
			.skunk-dashboard__product-link:hover { background: #E50914; color: #fff; }
			.skunk-dashboard__stats-section { margin-bottom: 24px; }
			.skunk-dashboard__cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px; }
			.skunk-dashboard__card { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; }
			.skunk-dashboard__card h4 { margin: 0 0 12px; font-size: 15px; font-weight: 600; }
			.skunk-dashboard__stats { display: flex; gap: 20px; margin-bottom: 12px; }
			.skunk-dashboard__stat { display: flex; flex-direction: column; }
			.skunk-dashboard__stat-value { font-size: 24px; font-weight: 700; color: #111827; }
			.skunk-dashboard__stat-label { font-size: 12px; color: #6b7280; text-transform: uppercase; }
			.skunk-dashboard__card-link { display: inline-block; font-size: 13px; font-weight: 500; color: #E50914; text-decoration: none; }
			.skunk-dashboard__card-link:hover { text-decoration: underline; }
		</style>

		<?php if ( $show_nux ) : ?>
			<?php self::render_nux_overlay(); ?>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render adaptive welcome message
	 */
	private static function render_welcome( $products ) {
		$has_crm   = $products['crm'];
		$has_forms = $products['forms'];
		$has_pages = $products['pages'];

		echo '<div class="skunk-dashboard__welcome">';
		if ( $has_crm && $has_forms && $has_pages ) {
			echo '<h2>Welcome to Skunk Suite</h2>';
			echo '<p>Your complete business toolkit is ready. Pick a landing page template, it has forms built in, and leads go straight to your CRM.</p>';
		} elseif ( $has_crm && $has_forms ) {
			echo '<h2>Welcome to Skunk Suite</h2>';
			echo '<p>Your CRM and forms are connected. Form submissions automatically create CRM contacts.</p>';
		} elseif ( $has_crm ) {
			echo '<h2>Welcome to Skunk Suite</h2>';
			echo '<p>Your CRM is ready to manage contacts and track deals. Add more Skunk products to unlock the full suite.</p>';
		} elseif ( $has_forms ) {
			echo '<h2>Welcome to Skunk Suite</h2>';
			echo '<p>Your forms are ready to capture leads. Add more Skunk products to unlock the full suite.</p>';
		} else {
			echo '<h2>Welcome to Skunk Suite</h2>';
			echo '<p>Your business toolkit for WordPress.</p>';
		}
		echo '</div>';
	}

	/**
	 * Render getting started checklist
	 */
	private static function render_checklist( $products ) {
		$items = array();

		if ( $products['crm'] ) {
			$items[] = array( 'done' => true, 'text' => 'Install SkunkCRM' );
			$has_contacts = false;
			global $wpdb;
			$table = $wpdb->prefix . 'skunk_crm_contacts';
			if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) === $table ) {
				$has_contacts = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ) > 0;
			}
			$items[] = array(
				'done' => $has_contacts,
				'text' => $has_contacts ? 'Add your first contact' : '<a href="' . admin_url( 'admin.php?page=skunkcrm-add-contact' ) . '">Add your first contact</a>',
			);
		}

		if ( $products['forms'] ) {
			$items[] = array( 'done' => true, 'text' => 'Install Skunk Forms' );
			$forms_data = get_option( 'skunkforms_forms', array() );
			$has_forms = count( $forms_data ) > 0;
			$items[] = array(
				'done' => $has_forms,
				'text' => $has_forms ? 'Create your first form' : '<a href="' . admin_url( 'admin.php?page=skunkforms-templates' ) . '">Create your first form</a>',
			);
		}

		if ( $products['pages'] ) {
			$items[] = array( 'done' => true, 'text' => 'Install Skunk Pages' );
		}

		if ( $products['crm'] && $products['forms'] ) {
			$items[] = array( 'done' => true, 'text' => 'Connect forms to CRM (automatic!)' );
		}

		if ( ! $products['forms'] ) {
			$items[] = array( 'done' => false, 'text' => '<a href="' . admin_url( 'plugin-install.php?s=skunkforms&tab=search&type=term' ) . '">Install Skunk Forms</a> to capture leads' );
		}
		if ( ! $products['crm'] ) {
			$items[] = array( 'done' => false, 'text' => '<a href="' . admin_url( 'plugin-install.php?s=skunkcrm&tab=search&type=term' ) . '">Install SkunkCRM</a> to manage contacts' );
		}

		echo '<ul>';
		foreach ( $items as $item ) {
			if ( $item['done'] ) {
				$icon = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
			} else {
				$icon = '<span style="display:inline-block;width:16px;height:16px;border:2px solid #d1d5db;border-radius:3px;"></span>';
			}
			echo '<li>' . $icon . ' ' . wp_kses( $item['text'], array( 'a' => array( 'href' => array() ) ) ) . '</li>';
		}
		echo '</ul>';
	}

	/**
	 * Render the NUX (New User Experience) overlay
	 */
	public static function render_nux_overlay() {
		$products = array(
			'crm'   => Skunk_Product_Detect::detect( 'crm' ),
			'forms' => Skunk_Product_Detect::detect( 'forms' ),
			'pages' => Skunk_Product_Detect::detect( 'pages' ),
		);
		$nonce = wp_create_nonce( 'skunk_nux_nonce' );

		$product_names = array();
		if ( $products['crm']['state'] === 'active' ) $product_names[] = 'SkunkCRM';
		if ( $products['forms']['state'] === 'active' ) $product_names[] = 'Skunk Forms';
		if ( $products['pages']['state'] === 'active' ) $product_names[] = 'Skunk Pages';

		$product_list_text = '';
		if ( count( $product_names ) === 1 ) {
			$product_list_text = $product_names[0] . ' is';
		} elseif ( count( $product_names ) === 2 ) {
			$product_list_text = $product_names[0] . ' and ' . $product_names[1] . ' are';
		} elseif ( count( $product_names ) === 3 ) {
			$product_list_text = $product_names[0] . ', ' . $product_names[1] . ', and ' . $product_names[2] . ' are';
		} else {
			$product_list_text = 'Your products are';
		}

		$all_products = Skunk_Product_Detect::get_all_products();
		?>
		<style>
			#skunk-nux-overlay {
				position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 100000;
				background: #f9fafb; overflow-y: auto;
				font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
			}
			#skunk-nux-overlay * { box-sizing: border-box; }
			.skunk-nux-wrap { max-width: 600px; margin: 0 auto; padding: 60px 24px; }
			.skunk-nux-center { text-align: center; }
			.skunk-nux-logo {
				width: 56px; height: 56px; background: #111; border-radius: 14px;
				display: inline-flex; align-items: center; justify-content: center;
				font-size: 28px; font-weight: 700; color: #fff; margin-bottom: 20px;
			}
			.skunk-nux-h1 { font-size: 28px; font-weight: 700; color: #111; margin: 0 0 8px; }
			.skunk-nux-h2 { font-size: 24px; font-weight: 700; color: #111; margin: 0 0 6px; }
			.skunk-nux-sub { font-size: 15px; color: #6b7280; margin: 0; line-height: 1.5; }
			.skunk-nux-card {
				background: #fff; border: 1px solid #e5e7eb; border-radius: 10px;
				padding: 24px; margin-top: 28px;
			}
			.skunk-nux-label {
				font-size: 11px; text-transform: uppercase; letter-spacing: 0.8px;
				color: #9ca3af; font-weight: 600; margin: 0 0 16px;
			}
			.skunk-nux-row {
				display: flex; align-items: center; gap: 12px; padding: 12px 0;
				border-bottom: 1px solid #f3f4f6;
			}
			.skunk-nux-row:last-child { border-bottom: none; }
			.skunk-nux-row.is-inactive { opacity: 0.45; }
			.skunk-nux-icon {
				width: 36px; height: 36px; border-radius: 8px;
				display: flex; align-items: center; justify-content: center; flex-shrink: 0;
			}
			.skunk-nux-row-text { flex: 1; }
			.skunk-nux-row-text strong { font-size: 14px; color: #111; display: block; }
			.skunk-nux-row-text span { font-size: 12px; color: #6b7280; }
			.skunk-nux-badge {
				font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;
				padding: 3px 8px; border-radius: 4px;
			}
			.skunk-nux-badge--on { background: #dcfce7; color: #15803d; }
			.skunk-nux-badge--warn { background: #fef3c7; color: #92400e; }
			.skunk-nux-badge--warn:hover { background: #fde68a; }
			.skunk-nux-badge--off { background: #f3f4f6; color: #6b7280; }
			.skunk-nux-badge--off:hover { background: #e5e7eb; color: #374151; }
			.skunk-nux-flow {
				display: flex; align-items: center; gap: 8px; justify-content: center;
				padding: 4px 0;
			}
			.skunk-nux-flow-step { text-align: center; flex: 1; }
			.skunk-nux-flow-step .skunk-nux-icon { margin: 0 auto 6px; }
			.skunk-nux-flow-step strong { font-size: 13px; color: #111; display: block; }
			.skunk-nux-flow-step span { font-size: 11px; color: #6b7280; }
			.skunk-nux-flow-arrow { color: #d1d5db; flex-shrink: 0; }
			.skunk-nux-actions { text-align: center; margin-top: 28px; }
			.skunk-nux-btn {
				display: inline-block; padding: 12px 32px; font-size: 15px; font-weight: 600;
				border-radius: 8px; cursor: pointer; border: none; text-decoration: none;
			}
			.skunk-nux-btn--primary { background: #E50914; color: #fff; }
			.skunk-nux-btn--primary:hover { background: #c7080f; }
			.skunk-nux-btn--outline {
				background: transparent; color: #374151; border: 1.5px solid #d1d5db;
			}
			.skunk-nux-btn--outline:hover { border-color: #9ca3af; }
			.skunk-nux-skip {
				display: block; margin-top: 10px; color: #9ca3af; font-size: 13px;
				text-decoration: none; cursor: pointer;
			}
			.skunk-nux-skip:hover { color: #6b7280; }
			.skunk-nux-dots { text-align: center; margin-top: 28px; }
			.skunk-nux-dot {
				display: inline-block; width: 8px; height: 8px; border-radius: 50%;
				margin: 0 3px; background: #d1d5db;
			}
			.skunk-nux-dot.is-active { background: #E50914; }
			.skunk-nux-tip {
				background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px;
				padding: 16px; margin-top: 20px; font-size: 13px; color: #92400e; line-height: 1.5;
			}
			.skunk-nux-tip a { color: #92400e; font-weight: 600; }
		</style>

		<div id="skunk-nux-overlay">
			<div class="skunk-nux-wrap">

				<!-- Step 1: Welcome -->
				<div id="skunk-nux-step-1" style="display: block;">
					<div class="skunk-nux-center" style="margin-bottom: 36px;">
						<div class="skunk-nux-logo">S</div>
						<h1 class="skunk-nux-h1">Welcome to Skunk Suite</h1>
						<p class="skunk-nux-sub">Your business toolkit for WordPress.</p>
					</div>

					<div class="skunk-nux-card">
						<p class="skunk-nux-label">Your Products</p>
						<?php foreach ( $all_products as $key => $prod ) :
							$state = $products[ $key ]['state'];
							$url   = $products[ $key ]['url'];
						?>
						<div class="skunk-nux-row <?php echo $state !== 'active' ? 'is-inactive' : ''; ?>">
							<div class="skunk-nux-icon" style="background: <?php echo esc_attr( $prod['color'] ); ?>;">
								<?php echo Skunk_Icons::get( $prod['icon'], 18 ); ?>
							</div>
							<div class="skunk-nux-row-text">
								<strong><?php echo esc_html( $prod['name'] ); ?></strong>
								<span><?php echo esc_html( $prod['desc'] ); ?></span>
							</div>
							<?php if ( $state === 'active' ) : ?>
								<span class="skunk-nux-badge skunk-nux-badge--on">Active</span>
							<?php elseif ( $state === 'installed' ) : ?>
								<a href="<?php echo esc_url( $url ); ?>" class="skunk-nux-badge skunk-nux-badge--warn" style="text-decoration:none;">Activate</a>
							<?php else : ?>
								<a href="<?php echo esc_url( $url ); ?>" target="_blank" class="skunk-nux-badge skunk-nux-badge--off" style="text-decoration:none;">Get it</a>
							<?php endif; ?>
						</div>
						<?php endforeach; ?>
					</div>

					<div class="skunk-nux-card">
						<p class="skunk-nux-label">How It Works Together</p>
						<div class="skunk-nux-flow">
							<div class="skunk-nux-flow-step">
								<div class="skunk-nux-icon" style="background: #8B5CF6;"><?php echo Skunk_Icons::get( 'pages', 18 ); ?></div>
								<strong>Landing Page</strong>
								<span>Attract visitors</span>
							</div>
							<div class="skunk-nux-flow-arrow"><?php echo Skunk_Icons::get( 'arrow-right', 16, '#d1d5db' ); ?></div>
							<div class="skunk-nux-flow-step">
								<div class="skunk-nux-icon" style="background: #3B82F6;"><?php echo Skunk_Icons::get( 'forms', 18 ); ?></div>
								<strong>Form Capture</strong>
								<span>Collect leads</span>
							</div>
							<div class="skunk-nux-flow-arrow"><?php echo Skunk_Icons::get( 'arrow-right', 16, '#d1d5db' ); ?></div>
							<div class="skunk-nux-flow-step">
								<div class="skunk-nux-icon" style="background: #E50914;"><?php echo Skunk_Icons::get( 'users', 18 ); ?></div>
								<strong>CRM</strong>
								<span>Manage &amp; close</span>
							</div>
						</div>
					</div>

					<div class="skunk-nux-actions">
						<button onclick="skunkNuxNext(2)" class="skunk-nux-btn skunk-nux-btn--primary">Get Started</button>
						<a href="#" onclick="skunkNuxSkip(); return false;" class="skunk-nux-skip">Skip</a>
					</div>
				</div>

				<!-- Step 2: Orientation -->
				<div id="skunk-nux-step-2" style="display: none;">
					<div class="skunk-nux-center" style="margin-bottom: 28px;">
						<h2 class="skunk-nux-h2">Quick Orientation</h2>
						<p class="skunk-nux-sub"><?php echo esc_html( $product_list_text ); ?> ready. Here's where to find everything.</p>
					</div>

					<div class="skunk-nux-card">
						<div class="skunk-nux-row">
							<div class="skunk-nux-icon" style="background: #111; font-weight: 700; font-size: 15px; color: #fff;">S</div>
							<div class="skunk-nux-row-text">
								<strong>Skunk Menu</strong>
								<span>Everything lives under <b>Skunk</b> in your sidebar</span>
							</div>
						</div>
						<?php if ( $products['crm']['state'] === 'active' ) : ?>
						<div class="skunk-nux-row">
							<div class="skunk-nux-icon" style="background: #E50914;"><?php echo Skunk_Icons::get( 'users', 18 ); ?></div>
							<div class="skunk-nux-row-text">
								<strong>CRM</strong>
								<span>Add contacts, track deals, log activities</span>
							</div>
						</div>
						<?php endif; ?>
						<?php if ( $products['forms']['state'] === 'active' ) : ?>
						<div class="skunk-nux-row">
							<div class="skunk-nux-icon" style="background: #3B82F6;"><?php echo Skunk_Icons::get( 'forms', 18 ); ?></div>
							<div class="skunk-nux-row-text">
								<strong>Forms</strong>
								<span>Create forms from templates, view submissions<?php echo $products['crm']['state'] === 'active' ? ' &mdash; auto-synced to CRM' : ''; ?></span>
							</div>
						</div>
						<?php endif; ?>
						<?php if ( $products['pages']['state'] === 'active' ) : ?>
						<div class="skunk-nux-row">
							<div class="skunk-nux-icon" style="background: #8B5CF6;"><?php echo Skunk_Icons::get( 'pages', 18 ); ?></div>
							<div class="skunk-nux-row-text">
								<strong>Pages</strong>
								<span>Pick a template, customise in the editor, publish</span>
							</div>
						</div>
						<?php endif; ?>
					</div>

					<?php
					$missing = array();
					foreach ( array( 'crm' => 'SkunkCRM', 'forms' => 'Skunk Forms', 'pages' => 'Skunk Pages' ) as $k => $label ) {
						if ( $products[ $k ]['state'] !== 'active' ) {
							$missing[ $k ] = array( 'label' => $label, 'state' => $products[ $k ]['state'], 'url' => $products[ $k ]['url'] );
						}
					}
					if ( ! empty( $missing ) ) : ?>
					<div class="skunk-nux-tip">
						<?php if ( count( $missing ) === 1 ) : ?>
							<strong>Almost there.</strong>
						<?php else : ?>
							<strong>Tip:</strong> Install all three products for the complete experience.
						<?php endif; ?>
						<?php
						$parts = array();
						foreach ( $missing as $k => $m ) {
							if ( $m['state'] === 'installed' ) {
								$parts[] = '<a href="' . esc_url( $m['url'] ) . '">Activate ' . esc_html( $m['label'] ) . '</a>';
							} else {
								$parts[] = '<a href="' . esc_url( $m['url'] ) . '" target="_blank">Get ' . esc_html( $m['label'] ) . '</a>';
							}
						}
						echo implode( ' &middot; ', $parts );
						?>
					</div>
					<?php endif; ?>

					<div class="skunk-nux-actions" style="display: flex; gap: 10px; justify-content: center;">
						<button onclick="skunkNuxNext(1)" class="skunk-nux-btn skunk-nux-btn--outline">Back</button>
						<button onclick="skunkNuxComplete()" class="skunk-nux-btn skunk-nux-btn--primary">Go to Dashboard</button>
					</div>
				</div>

				<div class="skunk-nux-dots">
					<span id="skunk-nux-dot-1" class="skunk-nux-dot is-active"></span>
					<span id="skunk-nux-dot-2" class="skunk-nux-dot"></span>
				</div>
			</div>
		</div>

		<script>
		(function() {
			var cur = 1;
			window.skunkNuxNext = function(s) {
				document.getElementById('skunk-nux-step-' + cur).style.display = 'none';
				document.getElementById('skunk-nux-step-' + s).style.display = 'block';
				document.getElementById('skunk-nux-dot-' + cur).classList.remove('is-active');
				document.getElementById('skunk-nux-dot-' + s).classList.add('is-active');
				cur = s;
				document.getElementById('skunk-nux-overlay').scrollTop = 0;
			};
			window.skunkNuxComplete = function() {
				var el = document.getElementById('skunk-nux-overlay');
				el.style.transition = 'opacity 0.25s ease';
				el.style.opacity = '0';
				var xhr = new XMLHttpRequest();
				xhr.open('POST', ajaxurl);
				xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
				xhr.send('action=skunk_complete_nux&nonce=<?php echo esc_js( $nonce ); ?>');
				setTimeout(function(){ el.remove(); }, 260);
			};
			window.skunkNuxSkip = skunkNuxComplete;
		})();
		</script>
		<?php
	}

	/**
	 * AJAX handler for completing NUX
	 */
	public static function ajax_complete_nux() {
		check_ajax_referer( 'skunk_nux_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		update_option( 'skunk_suite_onboarded', true );
		wp_send_json_success();
	}

	/**
	 * Render settings page
	 *
	 * This is a placeholder that delegates to the appropriate plugin's settings.
	 * Each plugin can hook into this via filters.
	 */
	public static function render_settings() {
		$product = isset( $_GET['product'] ) ? sanitize_text_field( $_GET['product'] ) : '';

		// Try to delegate to a registered plugin's settings handler
		$handled = apply_filters( 'skunk_suite_render_settings', false, $product );

		if ( ! $handled ) {
			// Default settings page
			?>
			<div class="wrap">
				<h1>Skunk Suite Settings</h1>
				<p>Select a product to configure its settings.</p>
				<?php
				$plugins = Skunk_Menu::get_plugins();
				if ( ! empty( $plugins ) ) {
					echo '<ul>';
					foreach ( $plugins as $id => $config ) {
						echo '<li><a href="' . esc_url( admin_url( 'admin.php?page=skunk-settings&product=' . $id ) ) . '">' . esc_html( $config['name'] ) . ' Settings</a></li>';
					}
					echo '</ul>';
				}
				?>
			</div>
			<?php
		}
	}
}
