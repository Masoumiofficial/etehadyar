<?php
/**
 * Plugin Name:       اتحادیار | Etehadyar — دستیار هوشمند اتحاد وردپرس
 * Plugin URI:        https://etehadyar.ir
 * Description:       اتحادیار 6.7.3 — نسخه فول واقعی: کارخانه درست + ویدیو بدون ارور + فروش‌یار مرتب + نگهبان دقیق + پشتیبان بامزه با FAQ و طراحی شیک + گزارش شماتیک + تلگرام پیشرفته با پروکسی + کلیدها با تست — etehadyar.ir — سجاد معصومی
 * Version:           6.7.3
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            سجاد معصومی — اتحاد وردپرس
 * Author URI:        https://etehadwp.com
 * License:           GPL v2 or later
 * Text Domain:       etehadwp-ai-writer
 * Domain Path:       /languages
 */

defined('ABSPATH') || exit;

define('EAIW_VERSION', '6.7.3');
define('EAIW_DB_VERSION', '673');
define('EAIW_BRAND_NAME', 'اتحادیار');
define('EAIW_BRAND_EN', 'Etehadyar');
define('EAIW_BRAND_URL', 'https://etehadyar.ir');
define('EAIW_BRAND_POWERED', 'قدرت گرفته از اتحاد وردپرس — سجاد معصومی');
define('EAIW_FILE', __FILE__);
define('EAIW_PATH', plugin_dir_path(__FILE__));
define('EAIW_URL', plugin_dir_url(__FILE__));
define('EAIW_BASENAME', plugin_basename(__FILE__));

// حداقل پیش‌نیازها
if (version_compare(PHP_VERSION, '7.4', '<')) {
    add_action('admin_notices', function(){
        echo '<div class="notice notice-error"><p><b>اتحادیار</b> نیاز به PHP 7.4+ دارد. نسخه فعلی: '.PHP_VERSION.'</p></div>';
    });
    return;
}
if (version_compare($GLOBALS['wp_version'], '6.0', '<')) {
    add_action('admin_notices', function(){
        echo '<div class="notice notice-error"><p><b>اتحادیار</b> نیاز به وردپرس 6.0+ دارد.</p></div>';
    });
    return;
}

// Autoloader سبک — بدون Composer
spl_autoload_register(function($class){
    if (strpos($class, 'EAIW_') !== 0) return;
    $map = [
        'EAIW_Plugin'              => 'includes/Core/class-plugin.php',
        'EAIW_Activator'           => 'includes/Core/class-activator.php',
        'EAIW_Deactivator'         => 'includes/Core/class-deactivator.php',
        'EAIW_Vault'               => 'includes/Security/class-vault.php',
        'EAIW_Admin_Menu'          => 'includes/Admin/class-admin-menu.php',
        'EAIW_Site_Brain'          => 'includes/Brain/class-site-brain.php',
        'EAIW_Vector_Store'        => 'includes/Brain/class-vector-store.php',
        'EAIW_RAG'                 => 'includes/Brain/class-rag.php',
        'EAIW_Agent_Manager'       => 'includes/Agents/class-agent-manager.php',
        'EAIW_Agent_SEO_Watcher'   => 'includes/Agents/Types/class-agent-seo-watcher.php',
        'EAIW_Agent_Gardener'      => 'includes/Agents/Types/class-agent-gardener.php',
        'EAIW_Agent_Link_Weaver'   => 'includes/Agents/Types/class-agent-link-weaver.php',
        'EAIW_Agent_Trend_Hunter'  => 'includes/Agents/Types/class-agent-trend-hunter.php',
        'EAIW_AI_Client'           => 'includes/Providers/class-ai-client.php',
        'EAIW_Omnichannel_Factory' => 'includes/Factory/class-omnichannel-factory.php',
        'EAIW_Vision_Studio'       => 'includes/Vision/class-vision-studio.php',
        'EAIW_Flux_Client'         => 'includes/Vision/class-flux-client.php',
        'EAIW_Video_Studio_Pro'    => 'includes/Video/class-video-studio-pro.php',
        'EAIW_Automation_Engine'   => 'includes/Nexus/class-automation-engine.php',
        'EAIW_Reports'             => 'includes/Reports/class-reports.php',
        'EAIW_FPDF'                => 'includes/Reports/class-reports.php',
        'EAIW_TTS'                 => 'includes/Voice/class-tts.php',
        'EAIW_Telegram'            => 'includes/Social/class-telegram.php',
        'EAIW_Instagram'           => 'includes/Social/class-instagram.php',
        'EAIW_Woo_Autopilot'       => 'includes/Woo/class-woo-autopilot.php',
        'EAIW_Architect'           => 'includes/Architect/class-architect.php',
        'EAIW_Woo_GodMode'         => 'includes/Woo/class-woo-godmode.php',
        'EAIW_Oracle'              => 'includes/Oracle/class-oracle.php',
        'EAIW_Guardian'            => 'includes/Guardian/class-guardian.php',
        'EAIW_ChatSoul'            => 'includes/Soul/class-chatsoul.php',
        'EAIW_Nexus'               => 'includes/Nexus/class-nexus.php',
        'EAIW_Health'              => 'includes/Core/class-health.php',
        'EAIW_Logger'              => 'includes/Core/class-logger.php',
    ];
    if (isset($map[$class]) && file_exists(EAIW_PATH . $map[$class])) {
        require_once EAIW_PATH . $map[$class];
    }
});

require_once EAIW_PATH . 'includes/Core/class-activator.php';
require_once EAIW_PATH . 'includes/Core/class-deactivator.php';

register_activation_hook(__FILE__, ['EAIW_Activator', 'activate']);
register_deactivation_hook(__FILE__, ['EAIW_Deactivator', 'deactivate']);

add_action('plugins_loaded', function(){
    load_plugin_textdomain('etehadwp-ai-writer', false, dirname(EAIW_BASENAME) . '/languages');
    load_plugin_textdomain('etehadyar', false, dirname(EAIW_BASENAME) . '/languages');
    if (class_exists('EAIW_Plugin')) {
        $GLOBALS['eaiw_plugin'] = EAIW_Plugin::instance();
    }
});

function eaiw() {
    return $GLOBALS['eaiw_plugin'] ?? null;
}
function etehadyar() { return eaiw(); }
