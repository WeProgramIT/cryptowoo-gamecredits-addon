<?php
/**
 * Plugin Name: CryptoWoo GameCredits Add-on
 * Plugin URI: https://github.com/WeProgramIT/cryptowoo-gamecredits-addon
 * Description: Accept GameCredits payments in WooCommerce. Requires CryptoWoo main plugin and CryptoWoo HD Wallet Add-on.
 * Version: 1.0.1
 * Author: We Program IT | legal company name: OS IT Programming AS | Company org nr: NO 921 074 077
 * Author URI: https://weprogram.it
 * License: GPLv2
 * Text Domain: cryptowoo-game-addon
 * Domain Path: /lang
 * WC tested up to: 3.5.4
 *
 * @package CryptoWoo GameCredits Addon
 */

// Make sure we don't expose any info if called directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( CW_GameCredits_Addon::class ) ) {

	/**
	 * Class CW_GameCredits_Addon
	 */
	class CW_GameCredits_Addon {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->init();
		}

		/** Get the currency name
		 *
		 * @return string
		 */
		private function get_currency_name() : string {
			return 'GameCredits';
		}

		/** Get the currency name
		 *
		 * @return string
		 */
		private function get_currency_short_name() : string {
			return 'game';
		}

		/** Get the currency name
		 *
		 * @return string
		 */
		private function get_currency_protocol_name() : string {
			return 'gamecredits';
		}

		/** Get the currency name
		 *
		 * @return string
		 */
		private function get_currency_code() : string {
			return 'GAME';
		}

		/**
		 * Initialize plugin
		 */
		public function init() {

			include_once ABSPATH . 'wp-admin/includes/plugin.php';

			if ( ! $this->plugin_is_installed( 'cryptowoo-hd-wallet-addon' ) ) {
				add_action( 'admin_notices', array( $this, 'cw_hd_addon_not_installed_notice' ) );
			} elseif ( ! $this->plugin_is_installed( 'cryptowoo' ) ) {
				add_action( 'admin_notices', array( $this, 'cw_not_installed_notice' ) );
			} elseif ( ! $this->plugin_is_activated( 'cryptowoo' ) ) {
				add_action( 'admin_notices', array( $this, 'cw_inactive_notice' ) );
			} elseif ( ! $this->plugin_is_activated( 'cryptowoo-hd-wallet-addon' ) ) {
				add_action( 'admin_notices', array( $this, 'cw_hd_addon_inactive_notice' ) );
			} else {
				$this->activate();
			}
		}

		/** Check if a plugin is installed
		 *
		 * @param string $plugin_id Plugin id name.
		 *
		 * @return bool
		 */
		private function plugin_is_installed( string $plugin_id ) : bool {
			return file_exists( WP_PLUGIN_DIR . '/' . $plugin_id );
		}

		/** Check if a plugin is activated
		 *
		 * @param string $plugin_id Plugin id name.
		 *
		 * @return bool
		 */
		private function plugin_is_activated( string $plugin_id ) : bool {
			return is_plugin_active( "$plugin_id/$plugin_id.php" );
		}

		/**
		 * Display CryptoWoo HD Wallet addon not installed notice
		 */
		public function cw_hd_addon_not_installed_notice() {
			$this->addon_not_installed_notice( 'CryptoWoo HD Wallet Add-on' );
		}

		/**
		 * Display CryptoWoo not installed notice
		 */
		public function cw_not_installed_notice() {
			$this->addon_not_installed_notice( 'CryptoWoo' );
		}

		/**
		 * Display CryptoWoo HD Wallet addon inactive notice
		 */
		public function cw_hd_addon_inactive_notice() {
			$this->addon_inactive_notice( 'CryptoWoo HD Wallet Add-on' );
		}

		/**
		 * Display CryptoWoo inactive notice
		 */
		public function cw_inactive_notice() {
			$this->addon_inactive_notice( 'CryptoWoo' );
		}

		/** Display addon inactive notice
		 *
		 * @param string $addon_name Addon name.
		 */
		private function addon_inactive_notice( string $addon_name ) {
			$addon_id = strtolower( str_replace( [ 'CryptoWoo', ' ' ], [ 'cw', '_' ], $addon_name ) );

			CW_Admin_Notice::generate( CW_Admin_Notice::NOTICE_ERROR )
			->add_message( "{$this->get_plugin_name()} " . __( 'error' ) )
			->add_message( "$addon_name " . __( 'plugin is inactive' ) )
			->add_message( __( 'Activate the addon and go to the CryptoWoo checkout settings to make sure the settings are correct.' ) )
			->add_button( __( 'Go to' ) . ' CryptoWoo ' . __( 'settings' ), __( 'Go to' ) . ' CryptoWoo ' . __( 'settings' ), 'cryptowoo' )
			->make_dismissible( "{$this->get_currency_short_name()}_{$addon_id}_not_installed" )
			->print();
		}


		/** Display CryptoWoo HD Wallet add-on not installed notice
		 *
		 * @param string $addon_name test Addon name.
		 * TODO: Add link to CryptoWoo and HD Wallet Addon.
		 */
		private function addon_not_installed_notice( string $addon_name ) {
			CW_Admin_Notice::generate( CW_Admin_Notice::NOTICE_ERROR )
			->add_message( "{$this->get_plugin_name()} " . __( 'error' ) )
			->add_message( "$addon_name " . __( 'plugin has not been installed' ) )
			->add_message( "{$this->get_plugin_name()} " . __( 'will only work in combination with' ) . " $addon_name." )
			->make_dismissible( "{$this->get_currency_short_name()}_hd_wallet_not_installed" )
			->print();
		}

		/**
		 * Activate plugin
		 */
		public function activate() {
			// Coin symbol and name.
			add_filter( 'woocommerce_currencies', array( $this, 'woocommerce_currencies' ), 10, 1 );
			add_filter( 'cw_get_currency_symbol', array( $this, 'get_currency_symbol' ), 10, 2 );
			add_filter( 'cw_get_enabled_currencies', array( $this, 'add_coin_identifier' ), 10, 1 );

			// BIP32 prefixes.
			add_filter( 'address_prefixes', array( $this, 'address_prefixes' ), 10, 1 );

			// Custom block explorer URL.
			add_filter( 'cw_link_to_address', array( $this, 'link_to_address' ), 10, 4 );

			// Options page validations.
			add_filter( 'validate_custom_api_genesis', array( $this, 'validate_custom_api_genesis' ), 10, 2 );
			add_filter( 'validate_custom_api_currency', array( $this, 'validate_custom_api_currency' ), 10, 2 );
			add_filter( 'cryptowoo_is_ready', array( $this, 'cryptowoo_is_ready' ), 10, 3 );
			add_filter( 'cw_misconfig_notice', array( $this, 'cw_misconfig_notice' ), 10, 2 );

			// HD wallet management.
			add_filter( 'index_key_ids', array( $this, 'index_key_ids' ), 10, 1 );
			add_filter( 'mpk_key_ids', array( $this, 'mpk_key_ids' ), 10, 1 );
			add_filter( 'get_mpk_data_mpk_key', array( $this, 'get_mpk_data_mpk_key' ), 10, 3 );
			add_filter( 'get_mpk_data_network', array( $this, 'get_mpk_data_network' ), 10, 3 );
			add_filter( 'cw_discovery_notice', array( $this, 'add_currency_to_array' ), 10, 1 );

			// Currency params.
			add_filter( 'cw_get_currency_params', array( $this, 'get_currency_params' ), 10, 2 );

			// Order sorting and prioritizing.
			add_filter( 'cw_sort_unpaid_addresses', array( $this, 'sort_unpaid_addresses' ), 10, 2 );
			add_filter( 'cw_prioritize_unpaid_addresses', array( $this, 'prioritize_unpaid_addresses' ), 10, 2 );
			add_filter( 'cw_filter_batch', array( $this, 'filter_batch' ), 10, 2 );

			// Exchange rates.
			add_filter( 'cw_force_update_exchange_rates', array( $this, 'force_update_exchange_rates' ), 10, 2 );
			add_filter( 'cw_cron_update_exchange_data', array( $this, 'cron_update_exchange_data' ), 10, 2 );

			// Insight API URL.
			add_filter( 'cw_prepare_insight_api', array( $this, 'override_insight_url' ), 10, 4 );

			// Wallet config.
			add_filter( 'wallet_config', array( $this, 'wallet_config' ), 10, 3 );
			add_filter( 'cw_get_processing_config', array( $this, 'processing_config' ), 10, 3 );

			// Options page.
			add_action( 'plugins_loaded', array( $this, 'add_fields' ), 10 );

			// get payment address.
			add_filter( "cw_create_payment_address_{$this->get_currency_code()}", array( $this, 'get_payment_address' ), 10, 3 );

			// Add gamecredits.network processing.
			add_filter( 'cw_update_tx_details', array( $this, 'cw_update_tx_details' ), 10, 5 );

			// Change currency icon color.
			add_action( 'wp_head', array( $this, 'coin_icon_color' ) );

			// Add to crypto store check.
			add_filter( 'is_cryptostore', array( $this, 'is_cryptostore' ), 10, 2 );
		}

		/**
		 * Get the plugin name
		 */
		private function get_plugin_name() : string {
			return "CryptoWoo {$this->get_currency_name()} Addon";
		}

		/**
		 * Get the plugin name
		 */
		private function get_plugin_domain() : string {
			return "cryptowoo-{$this->get_currency_short_name()}-addon";
		}

		/** Get the processing api id for CryptoWoo option
		 *
		 * @return string
		 */
		private function get_processing_api_id() : string {
			return "processing_api_{$this->get_currency_short_name()}";
		}

		/** Get the processing api id for CryptoWoo option
		 *
		 * @return string
		 */
		private function get_custom_processing_api_id() : string {
			return "custom_api_{$this->get_currency_short_name()}";
		}

		/** Get the processing api id for CryptoWoo option
		 *
		 * @return string
		 */
		private function get_preferred_block_explorer_api_id() : string {
			return "preferred_block_explorer_{$this->get_currency_short_name()}";
		}

		/** Get the processing api id for CryptoWoo option
		 *
		 * @return string
		 */
		private function get_custom_block_explorer_api_id() : string {
			return "custom_block_explorer_{$this->get_currency_short_name()}";
		}

		/** Get the processing fallback url id for CryptoWoo option
		 *
		 * @return string
		 */
		private function get_processing_fallback_url_id() : string {
			return "processing_fallback_url_{$this->get_currency_short_name()}";
		}

		/** Get the mpk id for CryptoWoo option.
		 *
		 * @return string
		 */
		private function get_mpk_id() : string {
			return "cryptowoo_{$this->get_currency_short_name()}_mpk";
		}

		/** Get the index id for CryptoWoo option.
		 *
		 * @return string
		 */
		private function get_index_id() : string {
			return "cryptowoo_{$this->get_currency_short_name()}_index";
		}

		/** Get the index id for CryptoWoo option.
		 *
		 * @return string
		 */
		private function get_multiplier_id() : string {
			return "multiplier_{$this->get_currency_short_name()}";
		}

		/**
		 * Override currency params in xpub validation
		 *
		 * @param array  $currency_params Currency parameters.
		 * @param string $field_id        Name of the master public key field.
		 *
		 * @return object
		 */
		public function get_currency_params( $currency_params, $field_id ) {
			if ( strcmp( $field_id, $this->get_mpk_id() ) === 0 ) {
				$currency_params            = new stdClass();
				$currency_params->currency  = $this->get_currency_code();
				$currency_params->index_key = $this->get_index_id();
			}

			return $currency_params;
		}

		/**
		 * Font color for aw-cryptocoins
		 * see cryptowoo/assets/fonts/aw-cryptocoins/cryptocoins-colors.css
		 */
		public function coin_icon_color() {
			?>
			<style type="text/css">
				i.cc.<?php echo esc_attr( $this->get_currency_code() ); ?>, i.cc.<?php echo esc_attr( "{$this->get_currency_code()}-alt" ); ?> {
					color: #98C01F;
				}
			</style>
			<?php
		}

		/** Add minimum confidence and "raw" zeroconf settings to processing config
		 *
		 * @param array  $pc_conf  Processing configuration.
		 * @param string $currency Currency code.
		 * @param array  $options  CryptoWoo options.
		 *
		 * @return array
		 */
		public function processing_config( $pc_conf, $currency, $options ) {
			if ( $this->get_currency_code() === $currency ) {
				$min_conf_id  = "cryptowoo_{$this->get_currency_short_name()}_min_conf";
				$zero_conf_id = "cryptowoo_{$this->get_currency_short_name()}_raw_zeroconf";
				// Maybe accept "raw" zeroconf.
				$pc_conf['min_confidence'] = isset( $options[ $min_conf_id ] ) && 0 === (int) $options[ $min_conf_id ] && isset( $options[ $zero_conf_id ] ) && (bool) $options[ $zero_conf_id ] ? 0 : $pc_conf['min_confidence'];
			}

			return $pc_conf;
		}

		/**
		 * Processing API configuration error
		 *
		 * @param array $enabled Array of enabled cryptocurrencies.
		 * @param array $options CryptoWoo options.
		 *
		 * @return mixed
		 */
		public function cw_misconfig_notice( $enabled, $options ) {
			$enabled[ $this->get_currency_code() ] = 'disabled' === $options[ $this->get_processing_api_id() ] && ( (bool) CW_Validate::check_if_unset( $this->get_mpk_id(), $options ) );

			return $enabled;
		}

		/**
		 * Add currency name
		 *
		 * @param array $currencies Array of Woocommerce currencies.
		 *
		 * @return mixed
		 */
		public function woocommerce_currencies( $currencies ) {
			$currencies[ $this->get_currency_code() ] = $this->get_currency_name();

			return $currencies;
		}


		/** Add currency symbol
		 *
		 * @param string $currency_symbol Currency symbol.
		 * @param string $currency Currency code.
		 *
		 * @return string
		 */
		public function get_currency_symbol( $currency_symbol, $currency ) {
			return $currency === $this->get_currency_code() ? $this->get_currency_code() : $currency_symbol;
		}


		/** Add coin identifier
		 *
		 * @param array $coin_identifiers currency codes.
		 *
		 * @return array
		 */
		public function add_coin_identifier( $coin_identifiers ) {
			$coin_identifiers[ $this->get_currency_code() ] = $this->get_currency_short_name();

			return $coin_identifiers;
		}


		/** Add address prefix
		 *
		 * @param array $prefixes Cryptocurrency address prefixes.
		 *
		 * @return array
		 */
		public function address_prefixes( $prefixes ) {
			$prefixes[ $this->get_currency_code() ]               = '26';
			$prefixes[ $this->get_currency_code() . '_MULTISIG' ] = 'a6';

			return $prefixes;
		}


		/**
		 * Add wallet config
		 *
		 * @param array  $wallet_config Cryptocurrency wallet configuration.
		 * @param string $currency      Currency name.
		 * @param array  $options       CryptoWoo options.
		 *
		 * @return array
		 */
		public function wallet_config( $wallet_config, $currency, $options ) {
			if ( $this->get_currency_code() === $currency ) {
				$wallet_config                     = array(
					'coin_client'  => $this->get_currency_protocol_name(),
					'request_coin' => $this->get_currency_code(),
					'multiplier'   => (float) $options[ $this->get_multiplier_id() ],
					'safe_address' => false,
					'decimals'     => 4,
				);
				$wallet_config['hdwallet']         = CW_Validate::check_if_unset( $this->get_mpk_id(), $options, false );
				$wallet_config['coin_protocols'][] = $this->get_currency_protocol_name();
				$wallet_config['fwd_addr_key']     = false;
			}

			return $wallet_config;
		}

		/** Override links to payment addresses
		 *
		 * @param string $url      URL.
		 * @param string $address  Crypto address.
		 * @param string $currency Currency code.
		 * @param array  $options  CryptoWoo options.
		 *
		 * @return string
		 */
		public function link_to_address( $url, $address, $currency, $options ) {
			if ( $this->get_currency_code() === $currency ) {
				$api_url = $options [ $this->get_preferred_block_explorer_api_id() ] ?: 'autoselect';

				if ( 'autoselect' === $api_url ) {
					$api_url = $options[ $this->get_processing_api_id() ];
				}

				$api_path = 'blockexplorer.gamecredits.org' === $api_url ? 'addresses' : 'address';
				$url      = "https://$api_url/$api_path/$address";

				if ( 'custom' === $api_url && isset( $options[ $this->get_custom_block_explorer_api_id() ] ) ) {
					$api_url = $options[ $this->get_custom_block_explorer_api_id() ];
					$url     = preg_replace( '/{{ADDRESS}}/', $address, $api_url );
					if ( ! wp_http_validate_url( $url ) ) {
						$url = '#';
					}
				}
			}

			return $url;
		}

		/** Do api processing
		 *
		 * @param array      $batch_data Current API data result.
		 * @param string     $batch_currency Currency code.
		 * @param WC_Order[] $orders Orders to update.
		 * @param stdclass   $processing CryptoWoo Processing API options.
		 * @param array      $options CryptoWoo options.
		 *
		 * @return array
		 */
		public function cw_update_tx_details( $batch_data, $batch_currency, $orders, $processing, $options ) {
			if ( $this->get_currency_code() === $batch_currency ) {
				$chain_height                  = $this->processing_api_get_block_height( $processing->tx_update_api );
				$batch                         = $orders[0]->address;
				$batch_data[ $batch_currency ] = $this->processing_api_get_txs( $batch, $processing->tx_update_api, $chain_height );
				usleep( 333333 ); // Max ~3 requests/second TODO remove when we have proper rate limiting.


				// Check if data is valid. There is only an incoming payment if address exist.
				if ( ! isset( $batch_data[ $this->get_currency_code() ] ) || ! is_object( $batch_data[ $this->get_currency_code() ] ) && 'address not found.' !== $batch_data[ $this->get_currency_code() ] ) {
					// TODO: Change to new CryptoWoo logging function (in an upcoming update).
				    file_put_contents( CW_LOG_DIR . 'cryptowoo-tx-update.log', date( 'Y-m-d H:i:s' ) . " {$processing->tx_update_api} full address error_invalid_result\r\n", FILE_APPEND );
					return array();
				}

				// Convert to correct format for insight_tx_analysis.
				$data = $batch_data[ $this->get_currency_code() ];
				if ( isset( $data->transactions ) && ! empty( $data->transactions ) ) {
					$batch_data[ $batch ] = $data->transactions;
					unset( $batch_data[ $this->get_currency_code() ] );
				}

				$batch_data = CW_Insight::insight_tx_analysis( $orders, $batch_data, $options, $chain_height, true );
			}

			return $batch_data;
		}


		/** Get the current block height
		 *
		 * @param string $api_url Processing API URL.
		 *
		 * @return int
		 */
		public function processing_api_get_block_height( $api_url ) {
			$currency = $this->get_currency_code();

			// Return block height if we have it in transient.
			$bh_transient = sprintf( 'block-height-%s', $currency );
			$block_height = get_transient( $bh_transient );
			if ( false !== $block_height ) {
				return (int) $block_height;
			}

			// Get block height data.
			$api_path = 'blockexplorer.gamecredits.org' === $api_url ? 'api/network/info' : 'api/getblockcount';
			$url      = "http://{$api_url}/$api_path";
			$result   = wp_safe_remote_get( $url );

			$api_validation = $this->validate_processing_api_result( $result, $api_url );
			if ( true !== $api_validation ) {
				return $api_validation;
			}

			$result = json_decode( $result['body'] );

			if ( isset( $result ) && is_integer( $result ) ) {
				$block_height = $result;
				set_transient( $bh_transient, $block_height, 180 ); // Cache for 3 minutes.
			} else {
				$block_height = 0;
			}

			return (int) $block_height;
		}

		/** Get address details using api.
		 *
		 * @param string $address Payment address.
		 * @param string $api_url Base url.
		 * @param int    $chain_height Current block number.
		 *
		 * @return array|bool|mixed|object|string|WP_Error
		 */
		public function processing_api_get_txs( $address, $api_url, $chain_height ) {
			// Get rate limit transient.
			$limit_transient = get_transient( 'cryptowoo_limit_rates' );

			// Get data for an address from bock explorer api.
			$api_path = 'blockexplorer.gamecredits.org' === $api_url ? 'api/addresses' : 'ext/getaddress';
			$url      = "http://{$api_url}/$api_path/$address";
			$result   = wp_safe_remote_get( $url );

			// Check that api response is valid.
			// Log errors and return error message if not valid.
			$api_validation = $this->validate_processing_api_result( $result, $api_url, $limit_transient );
			if ( true !== $api_validation ) {
				return $api_validation;
			}

			$result = json_decode( $result['body'] );

			// Get transactions confirmations and format data.
			if ( isset( $result->last_txs ) ) {
				$result->transactions = $result->last_txs;
				unset( $result->last_txs );
			}

			if ( ! isset( $result->transactions ) ) {
				return 'Could not find transaction data from block explorer api';
			}

			// Get extra transaction information and format transaction data.
			foreach ( $result->transactions as & $transaction ) {
				// Get tx confirmations.
				if ( 'blockexplorer.gamecredits.org' === $api_url ) {
					$url      = 'http://blockexplorer.gamecredits.org/api/transactions/confirmations';
					$response = wp_remote_post( $url, array(
						'timeout'     => 60,
						'redirection' => 5,
						'blocking'    => true,
						'headers'     => array(
							'Content-Type' => 'application/json',
						),
						'body'        => wp_json_encode( [ 'transactions' => [ $result->transactions[0]->txid ] ] ),
					) );

					$api_validation = $this->validate_processing_api_result( $response, $api_url, $limit_transient );
					if ( true !== $api_validation ) {
						return $api_validation;
					}

					// Format data.
					$response = json_decode( $response['body'] );
					if ( isset( $response[0]->confirmations ) ) {
						$transaction->confirmations = $response[0]->confirmations;
					} elseif ( isset( $transaction->blocktime ) && $transaction->blocktime && $chain_height ) {
						$transaction->confirmations = $chain_height - $transaction->blocktime;
					} else {
						return 'Could not find tx confirmations from block explorer api result';
					}
					unset( $response[0]->confirmations );

					if ( isset( $transaction->vout ) ) {
						foreach ( $transaction->vout as & $vout ) {
							$vout->scriptPubKey            = new stdClass();
							$vout->scriptPubKey->addresses = $vout->addresses;
							unset( $vout->addresses );
						}
					} else {
						return 'Could not find transaction outputs data from block explorer api';
					}
				} elseif ( 'gamecredits.network' === $api_url ) {
					// Get data for transaction from bock explorer api.
					$txid     = $transaction->addresses;
					$api_path = 'api/getrawtransaction';
					$url      = "http://{$api_url}/$api_path?txid=$txid&decrypt=1";

					$tx_result   = wp_safe_remote_get( $url );
					$transaction = json_decode( $tx_result['body'] );
				}

				// Format or add timestamp.
				isset( $transaction->time ) ?: $transaction->time = isset( $transaction->blocktime ) ? $transaction->blocktime : time();
			}

			if ( empty( $result->transactions ) ) {
				return 'Could not find transaction data from block explorer api';
			}

			// Delete rate limit transient if the last call was successful.
			if ( false !== $limit_transient ) {
				delete_transient( 'cryptowoo_limit_rates' );
			}

			return $result;
		}

		/** Validate the result from payment processing api.
		 *
		 * @param array|WP_Error $result Processing API result data.
		 * @param string         $api_url Processing API URL.
		 * @param bool           $limit_transient Processing API Limit transient.
		 *
		 * @return bool
		 */
		private function validate_processing_api_result( $result, $api_url, $limit_transient = false ) {
			if ( ! is_wp_error( $result ) && is_array( $result ) ) {
				return true;
			}

			$currency_code = $this->get_currency_code();
			$error         = $result->get_error_message();

			// Get rate limit transient.
			if ( ! $limit_transient ) {
				$limit_transient = get_transient( 'cryptowoo_limit_rates' );
			}

			$error = $error . $api_url;

			// Action hook for API error.
			do_action( 'cryptowoo_api_error', 'API error: ' . $error );

			// Update rate limit transient.
			if ( isset( $limit_transient[ $currency_code ]['count'] ) ) {
				$limit_transient[ $currency_code ] = array(
					'count' => (int) $limit_transient[ $currency_code ]['count'] + 1,
					'api'   => $api_url,
				);
			} else {
				$limit_transient[ $currency_code ] = array(
					'count' => 1,
					'api'   => $api_url,
				);
			}

			// Keep error data until the next full hour (rate limits refresh every full hour). We'll try again after that time.
			set_transient( 'cryptowoo_limit_rates', $limit_transient, CW_AdminMain::seconds_to_next_hour() );
			file_put_contents( CW_LOG_DIR . 'cryptowoo-tx-update.log', date( 'Y-m-d H:i:s' ) . " Insight full address error {$error}\r\n", FILE_APPEND );

			return $error;
		}

		/** Override genesis block
		 *
		 * @param string $genesis Genesis block id.
		 * @param string $field_id Processing api field.
		 *
		 * @return string
		 */
		public function validate_custom_api_genesis( $genesis, $field_id ) {
			if ( in_array( $field_id, array( $this->get_custom_processing_api_id(), $this->get_processing_fallback_url_id() ), true ) ) {
				$genesis = '91ec5f25ee9a0ffa1af7d4da4db9a552228dd2dc77cdb15b738be4e1f55f30ee';
			}

			return $genesis;
		}


		/** Override custom API currency
		 *
		 * @param string $currency Currency code.
		 * @param string $field_id Processing API ID.
		 *
		 * @return string
		 */
		public function validate_custom_api_currency( $currency, $field_id ) {
			if ( in_array( $field_id, array( $this->get_custom_processing_api_id(), $this->get_processing_fallback_url_id() ), true ) ) {
				$currency = $this->get_currency_code();
			}

			return $currency;
		}


		/** Add currency to cryptowoo_is_ready
		 *
		 * @param array $enabled Currencies that are enabled.
		 * @param array $options CryptoWoo options.
		 * @param array $changed_values Changed values from transient.
		 *
		 * @return array
		 */
		public function cryptowoo_is_ready( $enabled, $options, $changed_values ) {
			$enabled[ "{$this->get_currency_code()}_mpk" ]           = (bool) CW_Validate::check_if_unset( $this->get_mpk_id(), $options, false );
			$enabled[ "{$this->get_currency_code()}_mpk_transient" ] = (bool) CW_Validate::check_if_unset( $this->get_mpk_id(), $changed_values, false );

			return $enabled;
		}


		/** Add currency to is_cryptostore check
		 *
		 * @param bool   $cryptostore If the Woocoommerce store currency is a cryptocurrency.
		 * @param string $woocommerce_currency Woocommerce store currency code.
		 *
		 * @return bool
		 */
		public function is_cryptostore( $cryptostore, $woocommerce_currency ) {
			return (bool) $cryptostore ?: $woocommerce_currency === $this->get_currency_code();
		}


		/** Add HD index key id for currency
		 *
		 * @param array $index_key_ids HD Wallet index key ids.
		 *
		 * @return array
		 */
		public function index_key_ids( $index_key_ids ) {
			$index_key_ids[ $this->get_currency_code() ] = $this->get_index_id();

			return $index_key_ids;
		}


		/** Add HD mpk key id for currency
		 *
		 * @param array $mpk_key_ids HD Wallet master public key ids.
		 *
		 * @return array
		 */
		public function mpk_key_ids( $mpk_key_ids ) {
			$mpk_key_ids[ $this->get_currency_code() ] = $this->get_mpk_id();

			return $mpk_key_ids;
		}


		/** Override mpk_key
		 *
		 * @param string $mpk_key Master public key options id.
		 * @param string $currency Currency code.
		 * @param array  $options CryptoWoo options.
		 *
		 * @return string
		 */
		public function get_mpk_data_mpk_key( $mpk_key, $currency, $options ) {
			if ( $currency === $this->get_currency_code() ) {
				$mpk_key = $this->get_mpk_id();
			}

			return $mpk_key;
		}


		/** Override mpk_data->network
		 *
		 * @param stdClass $mpk_data Master public key data.
		 * @param string   $currency Currency code.
		 * @param array    $options CryptoWoo options.
		 *
		 * @return object
		 * @throws Exception BitWasp exception.
		 */
		public function get_mpk_data_network( $mpk_data, $currency, $options ) {
			if ( $currency === $this->get_currency_code() ) {
				require_once 'bitwasp/class-game.php';
				require_once 'bitwasp/class-game-network-factory.php';
				$mpk_data->network        = BitWasp\Bitcoin\Network\GAME_Network_Factory::GAME();
				$mpk_data->network_config = new \BitWasp\Bitcoin\Key\Deterministic\HdPrefix\NetworkConfig( $mpk_data->network, [
					$mpk_data->slip132->p2pkh( $mpk_data->bitcoinPrefixes ),
				] );
			}

			return $mpk_data;
		}

		/** Add currency force exchange rate update button
		 *
		 * @param array $results Exchange rates api result.
		 *
		 * @return array
		 */
		public function force_update_exchange_rates( $results ) {
			$results[ $this->get_currency_code() ] = CW_ExchangeRates::update_altcoin_fiat_rates( $this->get_currency_code(), false, true );

			return $results;
		}

		/** Add currency to background exchange rate update
		 *
		 * @param array $data Exchange rates api result data.
		 * @param array $options CryptoWoo options.
		 *
		 * @return array
		 */
		public function cron_update_exchange_data( $data, $options ) {
			$gamecredits = CW_ExchangeRates::update_altcoin_fiat_rates( $this->get_currency_code(), $options );

			// Maybe log exchange rate updates.
			if ( (bool) $options['logging']['rates'] ) {
				if ( 'not updated' !== $gamecredits['status'] || strpos( $gamecredits['status'], 'disabled' ) ) {
					$data[ $this->get_currency_code() ] = strpos( $gamecredits['status'], 'disabled' ) ? $gamecredits['status'] : $gamecredits['last_update'];
				} else {
					$data[ $this->get_currency_code() ] = $gamecredits;
				}
			}

			return $data;
		}

		/** Add currency to currencies array
		 *
		 * @param string[] $currencies Currency codes.
		 *
		 * @return array
		 */
		public function add_currency_to_array( $currencies ) {
			$currencies[] = $this->get_currency_code();

			return $currencies;
		}

		/**
		 * Add addresses to sort unpaid addresses
		 *
		 * @param array    $top_n Sorting levels.
		 * @param stdClass $address Address data.
		 *
		 * @return array
		 */
		public function sort_unpaid_addresses( $top_n, $address ) {
			if ( strcmp( $address->payment_currency, $this->get_currency_code() ) === 0 ) {
				$top_n[3][ $this->get_currency_code() ][] = $address;
			}

			return $top_n;
		}

		/**
		 * Add addresses to prioritize unpaid addresses
		 *
		 * @param array    $top_n Sorting levels.
		 * @param stdClass $address Address data.
		 *
		 * @return array
		 */
		public function prioritize_unpaid_addresses( $top_n, $address ) {
			if ( strcmp( $address->payment_currency, $this->get_currency_code() ) === 0 ) {
				$top_n[3][] = $address;
			}

			return $top_n;
		}

		/**
		 * Add addresses to address_batch
		 *
		 * @param array    $address_batch Addresses for processing.
		 * @param stdClass $address Address data.
		 *
		 * @return array
		 */
		public function filter_batch( $address_batch, $address ) {
			if ( strcmp( $address->payment_currency, $this->get_currency_code() ) === 0 ) {
				$address_batch[ $this->get_currency_code() ][] = $address->address;
			}

			return $address_batch;
		}

		/** Override Insight API URL if no URL is found in the settings
		 *
		 * @param stdClass $insight Insight api options.
		 * @param string   $endpoint Insight endpoint URL.
		 * @param string   $currency Currency code.
		 * @param array    $options CryptoWoo options.
		 *
		 * @return mixed
		 */
		public function override_insight_url( $insight, $endpoint, $currency, $options ) {
			if ( $currency === $this->get_currency_code() && isset( $options[ $this->get_processing_fallback_url_id() ] ) && wp_http_validate_url( $options[ $this->get_processing_fallback_url_id() ] ) ) {
				$fallback_url = $options[ $this->get_processing_fallback_url_id() ];
				$urls         = $endpoint ? CW_Formatting::format_insight_api_url( $fallback_url, $endpoint ) : CW_Formatting::format_insight_api_url( $fallback_url, '' );
				$insight->url = $urls['surl'];
			}

			return $insight;
		}

		/**
		 * Add Redux options
		 */
		public function add_fields() {
			$woocommerce_currency = get_option( 'woocommerce_currency' );

			/** Payment processing section start */

			/*
			 * Required confirmations with blockexplorer.gamecredits.org.
			 */
			Redux::setField( 'cryptowoo_payments', array(
				'section_id' => 'processing-confirmations',
				'id'         => "cryptowoo_{$this->get_currency_short_name()}_min_conf",
				'type'       => 'spinner',
				'title'      => sprintf( __( '%s Minimum Confirmations', 'cryptowoo' ), $this->get_currency_code() ),
				'desc'       => sprintf( __( 'Minimum number of confirmations for <strong>%s</strong> transactions - %s Confirmation Threshold', 'cryptowoo' ), $this->get_currency_name(), $this->get_currency_code() ),
				'default'    => 1,
				'min'        => 0,
				'step'       => 1,
				'max'        => 100,
			) );

			// Enable raw zeroconf.
			Redux::setField( 'cryptowoo_payments', array(
				'section_id' => 'processing-confirmations',
				'id'         => "cryptowoo_{$this->get_currency_short_name()}_raw_zeroconf",
				'type'       => 'switch',
				'title'      => $this->get_currency_code() . __( ' "Raw" Zeroconf', 'cryptowoo' ),
				'subtitle'   => __( 'Accept unconfirmed transactions as soon as they are seen on the network.', 'cryptowoo' ),
				'desc'       => sprintf( __( '%sThis practice is generally not recommended. Only enable this if you know what you are doing!%s', 'cryptowoo' ), '<strong>', '</strong>' ),
				'default'    => false,
				'required'   => array(
					array( "cryptowoo_{$this->get_currency_short_name()}_min_conf", '=', 0 ),
				),
			) );

			// Zeroconf order amount threshold.
			Redux::setField( 'cryptowoo_payments', array(
				'section_id' => 'processing-zeroconf',
				'id'         => "cryptowoo_max_unconfirmed_{$this->get_currency_short_name()}",
				'type'       => 'slider',
				'title'      => sprintf( __( '%s zeroconf threshold (%s)', 'cryptowoo' ), $this->get_currency_name(), $woocommerce_currency ),
				'desc'       => '',
				'required'   => array( "cryptowoo_{$this->get_currency_short_name()}_min_conf", '<', 1 ),
				'default'    => 100,
				'min'        => 0,
				'step'       => 10,
				'max'        => 500,
			) );

			Redux::setField( 'cryptowoo_payments', array(
				'section_id' => 'processing-zeroconf',
				'id'         => "cryptowoo_{$this->get_currency_short_name()}_zconf_notice",
				'type'       => 'info',
				'style'      => 'info',
				'notice'     => false,
				'required'   => array( "cryptowoo_{$this->get_currency_short_name()}_min_conf", '>', 0 ),
				'icon'       => 'fa fa-info-circle',
				'title'      => sprintf( __( '%s Zeroconf Threshold Disabled', 'cryptowoo' ), $this->get_currency_name() ),
				'desc'       => sprintf( __( 'This option is disabled because you do not accept unconfirmed %s payments.', 'cryptowoo' ), $this->get_currency_name() ),
			) );

			/*
			 * Processing API
			 */
			Redux::setField( 'cryptowoo_payments', array(
				'section_id'        => 'processing-api',
				'id'                => $this->get_processing_api_id(),
				'type'              => 'select',
				'title'             => sprintf( __( '%s Processing API', 'cryptowoo' ), $this->get_currency_name() ),
				'subtitle'          => sprintf( __( 'Choose the API provider you want to use to look up %s payments.', 'cryptowoo' ), $this->get_currency_code() ),
				'options'           => array(
					'blockexplorer.gamecredits.org' => 'blockexplorer.gamecredits.org',
					'gamecredits.network'           => 'gamecredits.network',
					'custom'                        => 'Custom (insight)',
					'disabled'                      => 'Disabled',
				),
				'desc'              => '',
				'default'           => 'disabled',
				'ajax_save'         => false, // Force page load when this changes.
				'validate_callback' => 'redux_validate_processing_api',
				'select2'           => array( 'allowClear' => false ),
			) );


			/*
			 * Processing API custom URL warning
			 */
			Redux::setField( 'cryptowoo_payments', array(
				'section_id' => 'processing-api',
				'id'         => "processing_api_{$this->get_currency_short_name()}_info",
				'type'       => 'info',
				'style'      => 'critical',
				'icon'       => 'el el-warning-sign',
				'required'   => array(
					array( $this->get_processing_api_id(), 'equals', 'custom' ),
					array( $this->get_custom_processing_api_id(), 'equals', '' ),
				),
				'desc'       => sprintf( __( 'Please enter a valid URL in the field below to use a custom %s processing API', 'cryptowoo' ), $this->get_currency_name() ),
			) );

			/*
			 * Custom processing API URL
			 */
			Redux::setField( 'cryptowoo_payments', array(
				'section_id'        => 'processing-api',
				'id'                => $this->get_custom_processing_api_id(),
				'type'              => 'text',
				'title'             => sprintf( __( '%s Insight API URL', 'cryptowoo' ), $this->get_currency_name() ),
				'subtitle'          => sprintf( __( 'Connect to any %sInsight API%s instance.', 'cryptowoo' ), '<a href="https://github.com/bitpay/insight-api/" title="Insight API" target="_blank">', '</a>' ),
				'desc'              => sprintf( __( 'The root URL of the API instance:%sLink to address:%sinsight.bitpay.com/ext/getaddress/%sRoot URL: %sinsight.bitpay.com%s', $this->get_plugin_domain() ), '<p>', '<code>', '</code><br>', '<code>', '</code></p>' ),
				'placeholder'       => 'gamecredits.network',
				'required'          => array( $this->get_processing_api_id(), 'equals', 'custom' ),
				'validate_callback' => 'redux_validate_custom_api',
				'ajax_save'         => false,
				'msg'               => __( 'Invalid', 'cryptowoo' ) . " {$this->get_currency_code()} Insight API URL",
				'default'           => '',
				'text_hint'         => array(
					'title'   => 'Please Note:',
					'content' => __( 'Make sure the root URL of the API has a trailing slash ( / ).', 'cryptowoo' ),
				),
			) );

			// Re-add blockcypher token field (to make sure it is last).
			$field = Redux::getField( 'cryptowoo_payments', 'blockcypher_token' );
			Redux::removeField( 'cryptowoo_payments', 'blockcypher_token' );
			unset( $field['priority'] );
			Redux::setField( 'cryptowoo_payments', $field );

			// API Resource control information.
			Redux::setField( 'cryptowoo_payments', array(
				'section_id'        => 'processing-api-resources',
				'id'                => $this->get_processing_fallback_url_id(),
				'type'              => 'text',
				'title'             => sprintf( '%s ' . __( 'API Fallback', 'cryptowoo' ), $this->get_currency_code() ),
				'subtitle'          => sprintf( __( 'Fallback to any %sInsight API%s instance in case the gamecredits.network API fails. Retry upon beginning of the next hour. Leave empty to disable.', 'cryptowoo' ), '<a href="https://github.com/bitpay/insight-api/" title="Insight API" target="_blank">', '</a>' ),
				'desc'              => sprintf( __( 'The root URL of the API instance:%sLink to address:%sinsight.bitpay.com/ext/getaddress/XtuVUju4Baaj7YXShQu4QbLLR7X2aw9Gc8%sRoot URL: %sinsight.bitpay.com%s', $this->get_plugin_domain() ), '<p>', '<code>', '</code><br>', '<code>', '</code></p>' ),
				'placeholder'       => 'gamecredits.network',
				'required'          => array( $this->get_processing_api_id(), 'equals', 'blockcypher' ),
				'validate_callback' => 'redux_validate_custom_api',
				'ajax_save'         => false,
				'msg'               => __( 'Invalid', 'cryptowoo' ) . " {$this->get_currency_code()} Insight API URL",
				'default'           => 'gamecredits.network',
				'text_hint'         => array(
					'title'   => 'Please Note:',
					'content' => __( 'Make sure the root URL of the API has a trailing slash ( / ).', 'cryptowoo' ),
				),
			) );

			/** Payment processing section end */


			/** Pricing section start */

			/*
			 * Preferred exchange rate provider
			 */
			Redux::setField( 'cryptowoo_payments', array(
				'section_id'        => 'rates-exchange',
				'id'                => "preferred_exchange_{$this->get_currency_short_name()}",
				'type'              => 'select',
				'title'             => "{$this->get_currency_name()} Exchange ({$this->get_currency_code()}/BTC)",
				'subtitle'          => sprintf( __( "Choose the exchange you prefer to use to calculate the %s{$this->get_currency_name()} to Bitcoin exchange rate%s", 'cryptowoo' ), '<strong>', '</strong>.' ),
				'desc'              => sprintf( __( 'Cross-calculated via BTC/%s', 'cryptowoo' ), $woocommerce_currency ),
				'options'           => array(
					'poloniex' => 'Poloniex',
					'bittrex'  => 'Bittrex',
				),
				'default'           => 'poloniex',
				'ajax_save'         => false, // Force page load when this changes.
				'validate_callback' => 'redux_validate_exchange_api',
				'select2'           => array( 'allowClear' => false ),
			) );

			/*
			 * Exchange rate multiplier
			 */
			Redux::setField( 'cryptowoo_payments', array(
				'section_id'    => 'rates-multiplier',
				'id'            => $this->get_multiplier_id(),
				'type'          => 'slider',
				'title'         => sprintf( '%s' . __( 'exchange rate multiplier', 'cryptowoo' ), $this->get_currency_code() ),
				'subtitle'      => sprintf( __( 'Extra multiplier to apply when calculating prices for', 'cryptowoo' ) . '%s.', $this->get_currency_code() ),
				'desc'          => '',
				'default'       => 1,
				'min'           => .01,
				'step'          => .01,
				'max'           => 2,
				'resolution'    => 0.01,
				'validate'      => 'comma_numeric',
				'display_value' => 'text',
			) );

			/*
			 * Preferred blockexplorer
			 */
			Redux::setField( 'cryptowoo_payments', array(
				'section_id' => 'rewriting',
				'id'         => $this->get_preferred_block_explorer_api_id(),
				'type'       => 'select',
				'title'      => sprintf( '%s ' . __( 'Block Explorer', 'cryptowoo' ), $this->get_currency_name() ),
				'subtitle'   => __( 'Choose the block explorer you want to use for links to the blockchain.', 'cryptowoo' ),
				'desc'       => '',
				'options'    => array(
					'autoselect'                    => __( 'Autoselect by processing API', 'cryptowoo' ),
					'blockexplorer.gamecredits.org' => 'blockexplorer.gamecredits.org',
					'gamecredits.network'           => 'gamecredits.network',
					'custom'                        => __( 'Custom (enter URL below)' ),
				),
				'default'    => 'autoselect',
				'select2'    => array( 'allowClear' => false ),
			) );

			Redux::setField( 'cryptowoo_payments', array(
				'section_id' => 'rewriting',
				'id'         => "preferred_block_explorer_{$this->get_currency_short_name()}_info",
				'type'       => 'info',
				'style'      => 'critical',
				'icon'       => 'el el-warning-sign',
				'required'   => array(
					array( $this->get_preferred_block_explorer_api_id(), '=', 'custom' ),
					array( $this->get_custom_block_explorer_api_id(), '=', '' ),
				),
				'desc'       => sprintf( __( 'Please enter a valid URL in the field below to use a custom %s block explorer', 'cryptowoo' ), $this->get_currency_code() ),
			) );

			Redux::setField( 'cryptowoo_payments', array(
				'section_id'        => 'rewriting',
				'id'                => $this->get_custom_block_explorer_api_id(),
				'type'              => 'text',
				'title'             => sprintf( __( 'Custom %s Block Explorer URL', 'cryptowoo' ), $this->get_currency_name() ),
				'subtitle'          => __( 'Link to a block explorer of your choice.', 'cryptowoo' ),
				'desc'              => sprintf( __( 'The URL to the page that displays the information for a single address.%sPlease add %s{{ADDRESS}}%s as placeholder for the cryptocurrency address in the URL.%s', 'cryptowoo' ), '<br><strong>', '<code>', '</code>', '</strong>' ),
				'placeholder'       => 'gamecredits.network/ext/getaddress/{$address}',
				'required'          => array( $this->get_preferred_block_explorer_api_id(), '=', 'custom' ),
				'validate_callback' => 'redux_validate_custom_blockexplorer',
				'ajax_save'         => false,
				'msg'               => __( 'Invalid custom block explorer URL', 'cryptowoo' ),
				'default'           => '',
			) );

			/** Pricing section end */


			/** Display settings section start */

			/*
			 * Currency Switcher plugin decimals
			 */
			Redux::setField( 'cryptowoo_payments', array(
				'section_id' => 'rewriting-switcher',
				'id'         => "decimals_{$this->get_currency_code()}",
				'type'       => 'select',
				'title'      => sprintf( __( '%s amount decimals', 'cryptowoo' ), $this->get_currency_code() ),
				'subtitle'   => '',
				'desc'       => __( 'This option overrides the decimals option of the WooCommerce Currency Switcher plugin.', 'cryptowoo' ),
				'required'   => array( 'add_currencies_to_woocs', '=', true ),
				'options'    => array(
					2 => '2',
					4 => '4',
					6 => '6',
					8 => '8',
				),
				'default'    => 4,
				'select2'    => array( 'allowClear' => false ),
			) );

			/** Display settings section end */


			/** HD wallet section start */

			Redux::setField( 'cryptowoo_payments', array(
				'section_id' => 'wallets-hdwallet',
				'id'         => "wallets-hdwallet-{$this->get_currency_short_name()}",
				'type'       => 'section',
				'title'      => $this->get_currency_name(),
				'icon'       => "cc-{$this->get_currency_code()}",
				'indent'     => true,
			) );

			/*
			 * Extended public key
			 */
			Redux::setField( 'cryptowoo_payments', array(
				'section_id'        => 'wallets-hdwallet',
				'id'                => $this->get_mpk_id(),
				'type'              => 'text',
				'ajax_save'         => false,
				'username'          => false,
				'title'             => sprintf( __( '%sprefix%s', 'cryptowoo-hd-wallet-addon' ), '<b>' . $this->get_currency_name() . ' "xpub..." ', '</b>' ),
				'desc'              => "{$this->get_currency_name()} HD Wallet Extended Public Key (xpub...)",
				'validate_callback' => 'redux_validate_mpk',
				'placeholder'       => 'xpub...',
				// xpub format.
				'text_hint'         => array(
					'title'   => 'Please Note:',
					'content' => sprintf( __( 'If you enter a used key you will have to run the address discovery process after saving this setting.%sUse a dedicated HD wallet (or at least a dedicated xpub) for your store payments to prevent address reuse.', 'cryptowoo-hd-wallet-addon' ), '<br>' ),
				),
			) );

			Redux::setField( 'cryptowoo_payments', array(
				'section_id'        => 'wallets-hdwallet',
				'id'                => "derivation_path_{$this->get_currency_short_name()}",
				'type'              => 'select',
				'subtitle'          => '',
				'title'             => sprintf( __( '%s Derivation Path', 'cryptowoo-hd-wallet-addon' ), $this->get_currency_code() ),
				'desc'              => __( 'Change the derivation path to match the derivation path of your wallet client.', 'cryptowoo-hd-wallet-addon' ),
				'validate_callback' => 'redux_validate_derivation_path',
				'options'           => array(
					'0/' => __( 'm/0/i (e.g. Electrum Standard Wallet)', 'cryptowoo-hd-wallet-addon' ),
					'm'  => __( 'm/i (BIP44 Account)', 'cryptowoo-hd-wallet-addon' ),
				),
				'default'           => '0/',
				'select2'           => array( 'allowClear' => false ),
			) );

			// Re-add Bitcoin testnet section (to make sure it is last).
			$section = Redux::getField( 'cryptowoo_payments', 'wallets-hdwallet-testnet' );
			$field1  = Redux::getField( 'cryptowoo_payments', 'cryptowoo_btc_test_mpk' );
			$field2  = Redux::getField( 'cryptowoo_payments', 'derivation_path_btctest' );
			unset( $section['priority'] );
			unset( $field1['priority'] );
			unset( $field2['priority'] );
			Redux::removeField( 'cryptowoo_payments', 'wallets-hdwallet-testnet' );
			Redux::removeField( 'cryptowoo_payments', 'wallets-cryptowoo_btc_test_mpk-testnet' );
			Redux::removeField( 'cryptowoo_payments', 'wallets-hdwallet-derivation_path_btctest' );
			Redux::setField( 'cryptowoo_payments', $section );
			Redux::setField( 'cryptowoo_payments', $field1 );
			Redux::setField( 'cryptowoo_payments', $field2 );

			/** HD wallet section end */
		}
	}

	new CW_GameCredits_Addon();
}
