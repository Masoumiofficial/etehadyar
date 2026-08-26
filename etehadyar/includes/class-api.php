<?php
/**
 * اندپوینت‌های REST و اتصال به API گپ‌جی‌پی‌تی (اتحادیار).
 *
 * @package Etehadyar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * کلاس API.
 */
final class Etehadyar_API {

	/** @var Etehadyar_API|null */
	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes() {
		register_rest_route( 'etehadyar/v1', '/chat', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'handle_chat' ),
			'permission_callback' => array( $this, 'public_permission' ),
		) );

		register_rest_route( 'etehadyar/v1', '/config', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'handle_config' ),
			'permission_callback' => array( $this, 'public_permission' ),
		) );
	}

	public function public_permission() {
		// ویجت برای عموم باز است؛ محدودیت در rate-limit و key انجام می‌شود.
		return true;
	}

	/**
	 * محدودسازی نرخ درخواست بر اساس IP با استفاده از transient.
	 */
	private function rate_limited() {
		$opts  = Etehadyar_Settings::instance()->get_options();
		$limit = (int) $opts['rate_limit'];
		if ( $limit <= 0 ) {
			return false;
		}
		$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		$key = 'etehadyar_rl_' . md5( $ip );
		$val = (int) get_transient( $key );
		if ( $val >= $limit ) {
			return true;
		}
		set_transient( $key, $val + 1, MINUTE_IN_SECONDS );
		return false;
	}

	/**
	 * اندپوینت پیکربندی برای جاوااسکریپت.
	 * شامل: زبان فعال، پیام‌ها، نمونه‌سؤال‌های پیشنهادی (از دیتای وردپرس) و مسیر لوگو.
	 */
	public function handle_config( $request ) {
		$opts = Etehadyar_Settings::instance()->get_options();
		$lang = isset( $request['lang'] ) ? sanitize_key( $request['lang'] ) : 'fa';
		$lang = in_array( $lang, array( 'fa', 'en' ), true ) ? $lang : 'fa';

		return rest_ensure_response( array(
			'model'              => $opts['model'],
			'lang'               => $lang,
			'greeting'           => $opts['greeting'],
			'placeholder'        => $opts['placeholder'],
			'tts'                => (bool) $opts['tts'],
			'stt'                => (bool) $opts['stt'],
			'siteName'           => get_bloginfo( 'name' ),
			'knowledge'          => (bool) $opts['knowledge'],
			'accent'             => $opts['accent'],
			'suggested'          => $this->suggested_questions( $lang ),
			'logoUrl'            => ETEHADYAR_URL . 'assets/img/etehad-logo.png',
			'logoNeonUrl'        => ETEHADYAR_URL . 'assets/img/etehad-logo-neon.png',
		) );
	}

	/**
	 * ساخت نمونه‌سؤال‌های پیشنهادی؛ ترکیبی از سؤال‌های رایج و
	 * عنوان پست‌ها/صفحه‌های سایت (به زبان فعال).
	 */
	private function suggested_questions( $lang = 'fa' ) {
		$opts  = Etehadyar_Settings::instance()->get_options();
		$items = array();

		// سؤال‌های رایج (Intent) — بومی‌سازی
		if ( 'en' === $lang ) {
			$items[] = 'How can I contact you?';
			$items[] = 'Do you offer consulting services?';
			$items[] = 'What are your prices?';
			$items[] = 'Where should I start?';
		} else {
			$items[] = 'چطور می‌تونم با شما تماس بگیرم؟';
			$items[] = 'آیا خدمات مشاوره هم دارید؟';
			$items[] = 'هزینه خدمات شما چقدر است؟';
			$items[] = 'از کجا باید شروع کنم؟';
		}

		// ساخت از محتوای سایت (پست/صفحه)
		$q = new WP_Query( array(
			'post_type'           => array( 'post', 'page' ),
			'post_status'         => 'publish',
			'posts_per_page'      => 6,
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
		) );

		if ( $q->have_posts() ) {
			foreach ( $q->posts as $p ) {
				$title = get_the_title( $p );
				if ( 'en' === $lang ) {
					$items[] = 'Tell me more about "' . $title . '"';
				} else {
					$items[] = 'درباره‌ی «' . $title . '» بیشتر توضیح بده';
				}
			}
		}
		wp_reset_postdata();

		// حذف تکراری و محدودکردن به ۸ مورد
		$items = array_values( array_unique( array_filter( array_map( 'trim', $items ) ) ) );
		return array_slice( $items, 0, 8 );
	}

	/**
	 * جستجوی محتوای مرتبط در وردپرس برای RAG.
	 */
	private function get_site_context( $query ) {
		$opts = Etehadyar_Settings::instance()->get_options();

		$terms  = $this->tokenize( $query );
		$search = implode( ' ', array_slice( $terms, 0, 6 ) );

		$args = array(
			'post_type'           => array( 'post', 'page' ),
			'post_status'         => 'publish',
			'posts_per_page'      => (int) $opts['knowledge_posts'],
			's'                   => $search ? $search : $query,
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
		);

		$q    = new WP_Query( $args );
		$docs = array();

		if ( $q->have_posts() ) {
			foreach ( $q->posts as $post ) {
				$excerpt = has_excerpt( $post ) ? get_the_excerpt( $post ) : '';
				if ( ! $excerpt ) {
					$excerpt = wp_trim_words( strip_shortcodes( $post->post_content ), 80, '…' );
				}
				$docs[] = sprintf(
					"- عنوان: %s\n  آدرس: %s\n  محتوا: %s",
					get_the_title( $post ),
					get_permalink( $post ),
					wp_strip_all_tags( $excerpt )
				);
			}
		}
		wp_reset_postdata();

		if ( empty( $docs ) ) {
			return '';
		}

		return "اطلاعات زیر از سایت استخراج شده‌اند. اگر سؤال کاربر به این‌ها مربوط است، فقط بر اساس همین‌ها پاسخ بده و به لینک‌ها اشاره کن. اگر مرتبط نبود، نادیده بگیر:\n\n" . implode( "\n\n", $docs );
	}

	/**
	 * جدا کردن کلمات برای جستجو.
	 */
	private function tokenize( $text ) {
		$text  = mb_strtolower( wp_strip_all_tags( $text ), 'UTF-8' );
		$text  = preg_replace( '/[^\p{L}\p{N}\s]+/u', ' ', $text );
		$words = preg_split( '/\s+/u', trim( $text ) );
		$words = array_values( array_filter( $words, function ( $w ) {
			return mb_strlen( $w, 'UTF-8' ) > 2;
		} ) );
		return array_slice( $words, 0, 12 );
	}

	/**
	 * پردازش پیام و پاسخ‌گویی.
	 */
	public function handle_chat( $request ) {
		$opts = Etehadyar_Settings::instance()->get_options();

		if ( empty( $opts['api_key'] ) ) {
			return new WP_Error( 'etehadyar_no_key', __( 'کلید API تنظیم نشده است. با مدیر سایت تماس بگیرید.', 'etehadyar' ), array( 'status' => 500 ) );
		}

		if ( $this->rate_limited() ) {
			return new WP_Error( 'etehadyar_rate', __( 'درخواست‌های زیادی ارسال کردید. کمی صبر کنید.', 'etehadyar' ), array( 'status' => 429 ) );
		}

		$params   = $request->get_json_params();
		$messages = isset( $params['messages'] ) && is_array( $params['messages'] ) ? array_map( 'sanitize_textarea_field', wp_list_pluck( $params['messages'], 'content' ) ) : array();
		$roles    = isset( $params['messages'] ) && is_array( $params['messages'] ) ? wp_list_pluck( $params['messages'], 'role' ) : array();
		$lang     = isset( $params['lang'] ) ? sanitize_key( $params['lang'] ) : 'fa';
		$lang     = in_array( $lang, array( 'fa', 'en' ), true ) ? $lang : 'fa';

		if ( empty( $messages ) || empty( $roles ) ) {
			return new WP_Error( 'etehadyar_empty', __( 'پیام خالی است.', 'etehadyar' ), array( 'status' => 400 ) );
		}

		$system = $opts['system_prompt'];
		if ( 'en' === $lang ) {
			$system .= "\n\nRespond to the user in English.";
		} else {
			$system .= "\n\nپاسخ را به زبان فارسی بده.";
		}
		$out = array( array( 'role' => 'system', 'content' => $system ) );

		if ( ! empty( $opts['knowledge'] ) ) {
			$last = end( $messages );
			$ctx  = $this->get_site_context( $last );
			if ( $ctx ) {
				$out[] = array( 'role' => 'system', 'content' => $ctx );
			}
		}

		foreach ( $roles as $i => $role ) {
			$r      = ( 'assistant' === $role ) ? 'assistant' : 'user';
			$out[]  = array( 'role' => $r, 'content' => $messages[ $i ] );
		}

		$payload = array(
			'model'       => $opts['model'],
			'messages'    => $out,
			'max_tokens'  => (int) $opts['max_tokens'],
			'temperature' => (float) $opts['temperature'],
		);

		$response = wp_remote_post(
			trailingslashit( $opts['base_url'] ) . 'chat/completions',
			array(
				'timeout' => 60,
				'headers' => array(
					'Authorization' => 'Bearer ' . $opts['api_key'],
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( $payload ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'etehadyar_remote', __( 'خطا در ارتباط با سرور هوش مصنوعی.', 'etehadyar' ), array( 'status' => 502 ) );
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( $code >= 400 || ! isset( $data['choices'][0]['message']['content'] ) ) {
			$msg = isset( $data['error']['message'] ) ? sanitize_text_field( $data['error']['message'] ) : __( 'پاسخ دریافت نشد.', 'etehadyar' );
			return new WP_Error( 'etehadyar_model', $msg, array( 'status' => 502 ) );
		}

		return rest_ensure_response( array(
			'content' => $data['choices'][0]['message']['content'],
		) );
	}
}
