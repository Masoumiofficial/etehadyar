<?php
/**
 * Plugin Name:       اتحادیار — دستیار هوشمند مغزی
 * Plugin URI:        https://etehadyar.ir
 * Description:       دستیار گفت‌وگو و پاسخ‌گویی هوشمند برای سایت وردپرسی. ظاهر شماتیک مغز، چت متنی و صوتی، پاسخ بر اساس محتوای سایت (RAG) و اتصال به API گپ‌جی‌پی‌تی.
 * Version:           1.0.0
 * Author:            اتحاد وردپرس | سجاد معصومی
 * Author URI:        https://etehadwp.com
 * Text Domain:       etehadyar
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * License:           GPL-2.0-or-later
 *
 * @package Etehadyar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // دسترسی مستقیم ممنوع
}

define( 'ETEHADYAR_VERSION', '1.0.0' );
define( 'ETEHADYAR_FILE', __FILE__ );
define( 'ETEHADYAR_PATH', plugin_dir_path( __FILE__ ) );
define( 'ETEHADYAR_URL', plugin_dir_url( __FILE__ ) );

require_once ETEHADYAR_PATH . 'includes/class-settings.php';
require_once ETEHADYAR_PATH . 'includes/class-api.php';

/**
 * کلاس اصلی پلاگین.
 */
final class Etehadyar {

	/** @var Etehadyar|null */
	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_footer', array( $this, 'render_widget' ) );
		add_shortcode( 'etehadyar', array( $this, 'render_shortcode' ) );

		Etehadyar_Settings::instance();
		Etehadyar_API::instance();
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'etehadyar', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * بارگذاری CSS/JS در قسمت فرانت‌اند سایت.
	 */
	public function enqueue_assets() {
		$opts = Etehadyar_Settings::instance()->get_options();

		// فونت وزیرمتن برای ظاهر فارسی
		wp_enqueue_style(
			'vazirmatn',
			'https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css',
			array(),
			'33.003'
		);

		wp_enqueue_style(
			'etehadyar',
			ETEHADYAR_URL . 'assets/css/etehadyar.css',
			array( 'vazirmatn' ),
			ETEHADYAR_VERSION
		);

		wp_enqueue_script(
			'etehadyar',
			ETEHADYAR_URL . 'assets/js/etehadyar.js',
			array(),
			ETEHADYAR_VERSION,
			true
		);

		$config = array(
			'ajaxUrl'      => esc_url_raw( rest_url( 'etehadyar/v1/chat' ) ),
			'configUrl'    => esc_url_raw( rest_url( 'etehadyar/v1/config' ) ),
			'nonce'        => wp_create_nonce( 'wp_rest' ),
			'lang'         => isset( $opts['lang'] ) && in_array( $opts['lang'], array( 'fa', 'en' ), true ) ? $opts['lang'] : 'fa',
			'greeting'     => $opts['greeting'],
			'placeholder'  => $opts['placeholder'],
			'ttsEnabled'   => (bool) $opts['tts'],
			'sttEnabled'   => (bool) $opts['stt'],
			'siteName'     => get_bloginfo( 'name' ),
			'brainLabel'   => __( 'اتحادیار — مغز هوشمند', 'etehadyar' ),
			'brandUrl'     => 'https://etehadyar.ir',
			'brandBy'      => 'محصول اتحاد وردپرس',
			'brandByUrl'   => 'https://etehadwp.com',
			'credit'       => 'مدیر پروژه: سجاد معصومی',
			'logoUrl'      => ETEHADYAR_URL . 'assets/img/etehad-logo.png',
			'logoNeonUrl'  => ETEHADYAR_URL . 'assets/img/etehad-logo-neon.png',
		);

		wp_localize_script( 'etehadyar', 'ETEHADYAR', $config );
	}

	/**
	 * رندر ویجت شناور در پایین سایت.
	 */
	public function render_widget() {
		$opts = Etehadyar_Settings::instance()->get_options();
		if ( empty( $opts['widget_enabled'] ) ) {
			return;
		}
		echo '<div id="etehadyar-root" data-mode="widget" aria-live="polite"></div>';
	}

	/**
	 * شورت‌کد برای جاسازی داخلی صفحه: [etehadyar]
	 */
	public function render_shortcode() {
		return '<div id="etehadyar-root" data-mode="inline" aria-live="polite"></div>';
	}
}

Etehadyar::instance();
