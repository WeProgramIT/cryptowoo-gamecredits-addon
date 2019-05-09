<?php

/**
 * Class for displaying admin notices.
 *
 * @package CryptoWoo
 * @subpackage Admin
 */
class CW_Admin_Notice {
	/** The id (required for dismissible)
	 *
	 * @var string
	 */
	private $id;
	/** Array of messages to be printed in the notice
	 *
	 * @var array
	 */
	private $messages;
	/** The class(es) to use for the output
	 *
	 * @var string
	 */
	private $class;
	/** If the notice can be dismissed or not
	 *
	 * @var bool
	 */
	private $is_dismissible;
	/** Extra buttons in the notice.
	 *
	 * @var string
	 */
	private $extra_buttons;

	const NOTICE_ERROR   = 'notice-error';
	const NOTICE_WARNING = 'notice-warning';
	const NOTICE_SUCCESS = 'notice-success';
	const NOTICE_INFO    = 'notice-info';

	/**
	 * CW_Admin_Notice generator.
	 *
	 * @param string $class The class to use (for example 'notice_error'). Use constants.
	 *
	 * @return CW_Admin_Notice
	 */
	public static function generate( string $class ) {
		return new self( $class );
	}

	/**
	 * CW_Admin_Notice constructor.
	 *
	 * @param string $class The class to use (for example 'notice_error').
	 */
	private function __construct( string $class ) {
		$this->class          = "notice $class";
		$this->is_dismissible = false;
		$this->extra_buttons  = '';
	}

	/**
	 * Make the notice dismissible.
	 *
	 * @param string $id The notice id/name, f.ex 'license_inactive'.
	 *
	 * @return CW_Admin_Notice
	 */
	public function make_dismissible( string $id ) {
		$this->id             = $id;
		$this->is_dismissible = true;
		return $this;
	}

	/**
	 * Make the notice dismissible.
	 *
	 * @param string $message A message to add.
	 *
	 * @return CW_Admin_Notice
	 */
	public function add_message( string $message ) {
		$this->messages[] = $message;
		return $this;
	}

	/**
	 * Add extra button to the notice linking to a page
	 *
	 * @param string $label       Label for the button (button text).
	 * @param string $hint        The hint for the button (on mouse hover text).
	 * @param string $target_page The target page when clicking the button.
	 *
	 * @return CW_Admin_Notice
	 */
	public function add_button_menu_link( string $label, string $hint, string $target_page ) {
		// Only add button if menu link exists.
		if ( empty( $GLOBALS['admin_page_hooks'][ $target_page ] ) ) {
			return $this;
		}

		return $this->add_button( $label, $hint, 'admin.php?page=', $target_page, "link-page_$target_page" );
	}

	/**
	 * Add extra button to the notice for activation of a plugin
	 *
	 * @param string $label     Label for the button (button text).
	 * @param string $hint      The hint for the button (on mouse hover text).
	 * @param string $dir_name  The directory name of the plugin to activate when clicking the button.
	 * @param string $file_name The name of the plugin to activate when clicking the button.
	 *
	 * @return CW_Admin_Notice
	 */
	public function add_button_plugin_activate( string $label, string $hint, string $dir_name, $file_name = null ) {
		isset( $file_name ) ?: $file_name = $dir_name;
		$target                           = "$dir_name/$file_name.php";

		// Do not add button if plugin is already active.
		if ( is_plugin_active( $target ) ) {
			return $this;
		}

		return $this->add_button( $label, $hint, 'plugins.php?action=activate&plugin=', $target, "activate-plugin_$target" );
	}

	/**
	 * Add extra button to the notice
	 *
	 * @param string $label       Label for the button (button text).
	 * @param string $hint        The hint for the button (on mouse hover text).
	 * @param string $target_base The target base url when clicking the button (e.g. 'admin.php?page=').
	 * @param string $target      The target when clicking the button (e.g. 'cryptowoo').
	 * @param string $nonce       The nonce to add to the url.
	 *
	 * @return CW_Admin_Notice
	 */
	public function add_button( string $label, string $hint, string $target_base, string $target, string $nonce ) {
		$path = wp_nonce_url( admin_url( $target_base . rawurlencode( $target ) ), $nonce );

		$this->extra_buttons .= '<p><a class="button" href="' . $path . '" title="' . esc_attr__( $hint ) . '" target="_parent">' . esc_html( $label ) . '</a></p>';

		return $this;
	}

	/**
	 * Print the admin notice.
	 */
	public function print() {

		if ( 'dismissed' === get_option( 'cryptowoo_' . $this->id . '_notice' ) ) {
			return;
		}

		$message = '';

		foreach ( $this->messages as $msg ) {
			$message .= '<p>' . esc_html( $msg ) . '</p>';
		}

		printf( '<div class="%1$s">%2$s', esc_attr( $this->class ), $message );

		if ( $this->extra_buttons ) {
			echo $this->extra_buttons;
		}

		if ( $this->is_dismissible ) {
			$this->print_dismissible();
		}

		echo '</div>';
	}

	/**
	 *  Print dismissible button if not already dismissed
	 */
	private function print_dismissible() {
		$option_name = 'dismiss_' . $this->id . '_notice';

		if ( isset( $_POST[ $option_name ] ) ) {
			update_option( 'cryptowoo_' . $this->id . '_notice', 'dismissed' );
		} else {
			?>
			<p>
			<form id="<?php esc_attr_e( $option_name ); ?>" action="" method="post">
				<fieldset>
					<input type="hidden" name="<?php esc_attr_e( $option_name ); ?>" value="<?php esc_attr_e( $option_name ); ?>"/>
					<input id="<?php esc_attr_e( $option_name ); ?>" type="submit" name="submit" class="button"
					       value="<?php esc_html_e( 'Dismiss', 'cryptowoo' ); ?>" onClick=""/>
				</fieldset>
			</form>
			</p>
			<?php
		}
	}

}
