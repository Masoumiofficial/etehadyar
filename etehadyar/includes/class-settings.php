<?php
/**
 * مدیریت تنظیمات پلاگین اتحادیار (منو و ذخیره گزینه‌ها).
 *
 * @package Etehadyar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * کلاس تنظیمات.
 */
final class Etehadyar_Settings {

	/** @var string */
	private $option_key = 'etehadyar_options';

	/** @var array */
	private $options = array();

	/** @var Etehadyar_Settings|null */
	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->options = get_option( $this->option_key, array() );
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
	}

	/**
	 * مقادیر پیش‌فرض.
	 */
	public function defaults() {
		return array(
			'api_key'          => '',
			'base_url'         => 'https://api.gapgpt.app/v1',
			'model'            => 'gpt-4o',
			'system_prompt'    => 'تو اتحادیار، دستیار هوشمند سایت هستی. به زبان فارسی، دوستانه و حرفه‌ای پاسخ بده. پاسخ‌ها را کوتاه، دقیق و منظم بنویس. اگر اطلاعاتی درباره سؤال کاربر نداشتی، صادقانه بگو.',
			'knowledge'        => 1,   // پاسخ بر اساس محتوای سایت
			'knowledge_posts'  => 5,   // تعداد پست برای زمینه
			'tts'              => 1,   // خروجی صوتی
			'stt'              => 1,   // ورودی صوتی
			'widget_enabled'   => 1,   // ویجت شناور
			'max_tokens'       => 800,
			'temperature'      => 0.7,
			'rate_limit'       => 10,  // درخواست در دقیقه به ازای هر IP
			'greeting'         => 'سلام! 👋 من اتحادیار، دستیار هوشمند این سایتم؛ هر سؤالی داری بپرس.',
			'placeholder'      => 'سؤالت رو بنویس یا روی میکروفون بزن…',
			'launcher_text'    => 'اتحادیار',
			'lang'             => 'fa', // زبان پیش‌فرض رابط
			'accent'           => '#22d3ee', // رنگ اصلی (سایان)
		);
	}

	/**
	 * برمی‌گرداند گزینه‌ها با مقادیر پیش‌فرض.
	 */
	public function get_options() {
		return wp_parse_args( $this->options, $this->defaults() );
	}

	public function add_menu() {
		add_menu_page(
			__( 'اتحادیار — دستیار هوشمند', 'etehadyar' ),
			__( 'اتحادیار', 'etehadyar' ),
			'manage_options',
			'etehadyar',
			array( $this, 'render_page' ),
			'dashicons-art',
			58
		);
	}

	public function register_settings() {
		register_setting( 'etehadyar_group', $this->option_key, array(
			'type'              => 'array',
			'sanitize_callback' => array( $this, 'sanitize' ),
			'default'           => $this->defaults(),
		) );
	}

	/**
	 * پاک‌سازی ورودی.
	 */
	public function sanitize( $input ) {
		$d   = $this->defaults();
		$out = array();

		$out['api_key']       = isset( $input['api_key'] ) ? sanitize_text_field( $input['api_key'] ) : $d['api_key'];
		$out['base_url']      = isset( $input['base_url'] ) ? esc_url_raw( $input['base_url'] ) : $d['base_url'];
		$out['model']         = isset( $input['model'] ) ? sanitize_text_field( $input['model'] ) : $d['model'];
		$out['system_prompt'] = isset( $input['system_prompt'] ) ? sanitize_textarea_field( $input['system_prompt'] ) : $d['system_prompt'];
		$out['max_tokens']    = isset( $input['max_tokens'] ) ? max( 100, (int) $input['max_tokens'] ) : $d['max_tokens'];
		$out['temperature']   = isset( $input['temperature'] ) ? min( 2, max( 0, (float) $input['temperature'] ) ) : $d['temperature'];
		$out['rate_limit']    = isset( $input['rate_limit'] ) ? max( 0, (int) $input['rate_limit'] ) : $d['rate_limit'];
		$out['knowledge_posts'] = isset( $input['knowledge_posts'] ) ? max( 1, (int) $input['knowledge_posts'] ) : $d['knowledge_posts'];
		$out['greeting']      = isset( $input['greeting'] ) ? sanitize_text_field( $input['greeting'] ) : $d['greeting'];
		$out['placeholder']   = isset( $input['placeholder'] ) ? sanitize_text_field( $input['placeholder'] ) : $d['placeholder'];
		$out['launcher_text'] = isset( $input['launcher_text'] ) ? sanitize_text_field( $input['launcher_text'] ) : $d['launcher_text'];
		$out['lang']          = isset( $input['lang'] ) && in_array( $input['lang'], array( 'fa', 'en' ), true ) ? $input['lang'] : 'fa';
		$out['accent']        = isset( $input['accent'] ) ? sanitize_hex_color( $input['accent'] ) : $d['accent'];

		foreach ( array( 'knowledge', 'tts', 'stt', 'widget_enabled' ) as $b ) {
			$out[ $b ] = ! empty( $input[ $b ] ) ? 1 : 0;
		}

		return $out;
	}

	public function admin_assets( $hook ) {
		if ( 'toplevel_page_etehadyar' !== $hook ) {
			return;
		}
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
	}

	public function render_page() {
		$opts = $this->get_options();
		?>
		<div class="wrap">
			<h1>🧠 <?php esc_html_e( 'اتحادیار — دستیار هوشمند', 'etehadyar' ); ?></h1>
			<p style="max-width:720px">
				محصول <strong>اتحاد وردپرس</strong> — مدیر پروژه: <strong>سجاد معصومی</strong>.
				برای اتصال به هوش مصنوعی یک کلید API از
				<a href="https://gapgpt.app/platform-v2/tokens" target="_blank" rel="noopener">گپ‌جی‌پی‌تی</a>
				بگیر و اینجا وارد کن. کلید فقط روی سرور ذخیره می‌شود و هرگز به مرورگر کاربران نمی‌رود.
			</p>

			<form method="post" action="options.php">
				<?php settings_fields( 'etehadyar_group' ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="api_key">کلید API (GAPGPT)</label></th>
						<td>
							<input type="password" class="regular-text" id="api_key" name="etehadyar_options[api_key]"
								value="<?php echo esc_attr( $opts['api_key'] ); ?>" autocomplete="off" />
							<p class="description">مثال: <code>sk-…</code></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="base_url">آدرس پایه API</label></th>
						<td>
							<input type="url" class="regular-text" id="base_url" name="etehadyar_options[base_url]"
								value="<?php echo esc_attr( $opts['base_url'] ); ?>" />
							<p class="description">گپ‌جی‌پی‌تی: <code>https://api.gapgpt.app/v1</code> — نسخه CDN خارجی: <code>https://api.gapapi.com/v1</code></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="model">مدل</label></th>
						<td>
							<input type="text" class="regular-text" id="model" name="etehadyar_options[model]"
								value="<?php echo esc_attr( $opts['model'] ); ?>" list="etehadyar-models" />
							<datalist id="etehadyar-models">
								<option value="gpt-4o"></option>
								<option value="gpt-4o-mini"></option>
								<option value="gpt-5.6"></option>
								<option value="claude-3-5-sonnet"></option>
								<option value="gemini-2.5-pro"></option>
								<option value="gemini-2.5-flash"></option>
							</datalist>
							<p class="description">لیست کامل مدل‌ها: <a href="https://gapgpt.app/platform-v2/pricing" target="_blank" rel="noopener">صفحه قیمت‌ها</a></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="system_prompt">دستور سیستم (System Prompt)</label></th>
						<td>
							<textarea class="large-text code" id="system_prompt" rows="4"
								name="etehadyar_options[system_prompt]"><?php echo esc_textarea( $opts['system_prompt'] ); ?></textarea>
						</td>
					</tr>
					<tr>
						<th scope="row">پاسخ بر اساس محتوای سایت (RAG)</th>
						<td>
							<label>
								<input type="checkbox" name="etehadyar_options[knowledge]" value="1"
									<?php checked( 1, $opts['knowledge'] ); ?> />
								هنگام پاسخ‌گویی، محتوای مرتبط از پست‌ها/صفحه‌ها جستجو و به مدل داده شود
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="knowledge_posts">تعداد پست برای زمینه</label></th>
						<td>
							<input type="number" min="1" max="15" id="knowledge_posts" name="etehadyar_options[knowledge_posts]"
								value="<?php echo (int) $opts['knowledge_posts']; ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row">قابلیت‌های صوتی</th>
						<td>
							<label><input type="checkbox" name="etehadyar_options[stt]" value="1" <?php checked( 1, $opts['stt'] ); ?> /> ورودی صوتی (میکروفون) — در مرورگر کاربر</label><br />
							<label><input type="checkbox" name="etehadyar_options[tts]" value="1" <?php checked( 1, $opts['tts'] ); ?> /> خروجی صوتی (خواندن پاسخ با صدای مرورگر)</label>
						</td>
					</tr>
					<tr>
						<th scope="row">ویجت شناور</th>
						<td>
							<label>
								<input type="checkbox" name="etehadyar_options[widget_enabled]" value="1"
									<?php checked( 1, $opts['widget_enabled'] ); ?> />
								نمایش دستیار به‌صورت شناور در گوشه پایین سایت
							</label>
							<p class="description">برای جاسازی داخل صفحه، شورت‌کد <code>[etehadyar]</code> را بگذارید.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="greeting">پیام خوش‌آمد</label></th>
						<td><input type="text" class="regular-text" id="greeting" name="etehadyar_options[greeting]" value="<?php echo esc_attr( $opts['greeting'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="placeholder">متن داخل باکس ورودی</label></th>
						<td><input type="text" class="regular-text" id="placeholder" name="etehadyar_options[placeholder]" value="<?php echo esc_attr( $opts['placeholder'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="launcher_text">متن دکمه شناور</label></th>
						<td><input type="text" class="regular-text" id="launcher_text" name="etehadyar_options[launcher_text]" value="<?php echo esc_attr( $opts['launcher_text'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="lang">زبان رابط</label></th>
						<td>
							<select id="lang" name="etehadyar_options[lang]">
								<option value="fa" <?php selected( 'fa', $opts['lang'] ); ?>>فارسی (پیش‌فرض)</option>
								<option value="en" <?php selected( 'en', $opts['lang'] ); ?>>English</option>
							</select>
							<p class="description">زبان پیش‌فرض دستیار. کاربران می‌توانند از داخل ویجت هم زبان را تغییر دهند.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="accent">رنگ اصلی</label></th>
						<td><input type="text" class="etehadyar-color" id="accent" name="etehadyar_options[accent]" value="<?php echo esc_attr( $opts['accent'] ); ?>" data-default-color="#22d3ee" /></td>
					</tr>
					<tr>
						<th scope="row">محدودیت فراخوانی</th>
						<td>
							<input type="number" min="0" id="rate_limit" name="etehadyar_options[rate_limit]" value="<?php echo (int) $opts['rate_limit']; ?>" />
							<span class="description">درخواست در دقیقه به‌ازای هر IP (۰ = بدون محدودیت)</span>
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>

			<hr />
			<h2>راهنمای نصب</h2>
			<ol>
				<li>پوشه <code>etehadyar</code> را در <code>wp-content/plugins</code> آپلود و فعال کنید.</li>
				<li>کلید API گپ‌جی‌پی‌تی را وارد و مدل دلخواه را انتخاب کنید.</li>
				<li>اگر می‌خواهید دستیار داخل یک صفحه باشد، شورت‌کد <code>[etehadyar]</code> را در آن صفحه بگذارید؛ وگرنه ویجت شناور خودکار ظاهر می‌شود.</li>
			</ol>

			<hr />
			<p style="color:#667">اتحادیار — محصول <a href="https://etehadwp.com" target="_blank" rel="noopener">اتحاد وردپرس</a> | <a href="https://etehadyar.ir" target="_blank" rel="noopener">etehadyar.ir</a> | مدیر پروژه: سجاد معصومی</p>
		</div>
		<script>
		jQuery(function($){ if($.fn.wpColorPicker){ $('#accent').wpColorPicker(); } });
		</script>
		<?php
	}
}
