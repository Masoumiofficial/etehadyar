<?php
defined('ABSPATH') || exit;

class EAIW_Admin_Menu {
    public function __construct(){
        add_action('admin_menu', [$this, 'register']);
        add_action('admin_bar_menu', [$this, 'admin_bar'], 99);
    }
    public function register(){
        $cap = 'edit_posts';
        if (current_user_can('manage_options')) $cap = 'manage_options';

        // برند جدید: اتحادیار — etehadyar.ir — قدرت گرفته از اتحاد وردپرس
        add_menu_page(
            'اتحادیار — دستیار هوشمند',
            'اتحادیار',
            $cap,
            'eaiw-nebula',
            [$this, 'render_nebula'],
            'data:image/svg+xml;base64,'.base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#6d28ff"/><stop offset="100%" stop-color="#00e5ff"/></linearGradient></defs><circle cx="12" cy="12" r="9" stroke="url(#g)" stroke-width="1.6" fill="none"/><circle cx="12" cy="12" r="4.2" fill="url(#g)"/><circle cx="12" cy="12" r="1.6" fill="white"/></svg>'),
            30
        );
        add_submenu_page('eaiw-nebula', 'اتاق فرمان اتحادیار', '🏠 اتاق فرمان', $cap, 'eaiw-nebula', [$this, 'render_nebula']);
        add_submenu_page('eaiw-nebula', 'حافظه هوشمند', '🧠 حافظه هوشمند', $cap, 'eaiw-brain', [$this, 'render_brain']);
        add_submenu_page('eaiw-nebula', 'دستیاران خودکار', '🤖 دستیاران خودکار', $cap, 'eaiw-agents', [$this, 'render_agents']);
        add_submenu_page('eaiw-nebula', 'کارخانه محتوا', '🏭 کارخانه محتوا', $cap, 'eaiw-factory', [$this, 'render_factory']);
        add_submenu_page('eaiw-nebula', 'ویدیو ساز', '🎬 ویدیو ساز', $cap, 'eaiw-video', [$this, 'render_video']);
        add_submenu_page('eaiw-nebula', 'تصویرساز', '🎨 تصویرساز', $cap, 'eaiw-vision', [$this, 'render_vision']);
        add_submenu_page('eaiw-nebula', 'صفحه‌ساز', '🧱 صفحه‌ساز', $cap, 'eaiw-architect', [$this, 'render_architect']);
        add_submenu_page('eaiw-nebula', 'فروش یار', '🛒 فروش یار', $cap, 'eaiw-woo', [$this, 'render_woo']);
        add_submenu_page('eaiw-nebula', 'پیش‌بینی سئو', '🔮 پیش‌بینی سئو', $cap, 'eaiw-oracle', [$this, 'render_oracle']);
        add_submenu_page('eaiw-nebula', 'نگهبان سایت', '🛡️ نگهبان سایت', $cap, 'eaiw-guardian', [$this, 'render_guardian']);
        $soul_name = get_option('eaiw_chatsoul_name', 'پشتیبان هوشمند');
        $soul_name = $soul_name ?: 'پشتیبان هوشمند';
        add_submenu_page('eaiw-nebula', $soul_name, '💬 '.$soul_name, $cap, 'eaiw-soul', [$this, 'render_soul']);
        add_submenu_page('eaiw-nebula', 'اتوماسیون', '⚡ اتوماسیون', $cap, 'eaiw-nexus', [$this, 'render_nexus']);
        add_submenu_page('eaiw-nebula', 'گزارش مدیر', '📊 گزارش مدیر', $cap, 'eaiw-reports', [$this, 'render_reports']);
        add_submenu_page('eaiw-nebula', 'تلگرام پیشرفته', '📣 تلگرام پیشرفته', $cap, 'eaiw-telegram', [$this, 'render_telegram']);
        // Legacy مخفی شد در 6.6 — فقط با ?show_legacy=1 نمایش داده ይሆናል
        if(isset($_GET['show_legacy']) || defined('EAIW_SHOW_LEGACY')){
            add_submenu_page('eaiw-nebula', 'استودیو نویسنده', '✍️ استودیو نویسنده', $cap, 'eaiw-studio-legacy', [$this, 'render_legacy']);
        }
        add_submenu_page('eaiw-nebula', 'تنظیمات اتحادیار', '⚙️ تنظیمات', 'manage_options', 'eaiw-settings', [$this, 'render_settings']);
        // لینک خارجی etehadyar.ir
        add_submenu_page('eaiw-nebula', 'اتحادیار', '🌐 etehadyar.ir ↗', 'read', 'eaiw-external', function(){ echo '<script>window.open("https://etehadyar.ir","_blank"); window.location.href="'.admin_url('admin.php?page=eaiw-nebula').'";</script>'; });
    }

    public function admin_bar($bar){
        if (!current_user_can('edit_posts')) return;
        $bar->add_menu([
            'id' => 'eaiw-nebula-bar',
            'title' => '🤝 اتحادیار',
            'href' => admin_url('admin.php?page=eaiw-nebula'),
            'meta' => ['title' => 'اتحادیار — etehadyar.ir | قدرت گرفته از اتحاد وردپرس']
        ]);
    }

    public function render_nebula(){ $this->load('nebula-dashboard'); }
    public function render_brain(){ $this->load('site-brain'); }
    public function render_agents(){ $this->load('agents'); }
    public function render_factory(){ $this->load('factory'); }
    public function render_video(){ $this->load('video-pro'); }
    public function render_vision(){ $this->load('vision'); }
    public function render_architect(){ $this->load('architect'); }
    public function render_woo(){ $this->load('woo-autopilot'); }
    public function render_oracle(){ $this->load('oracle'); }
    public function render_guardian(){ $this->load('guardian'); }
    public function render_soul(){ $this->load('soul'); }
    public function render_nexus(){ $this->load('nexus'); }
    public function render_reports(){ $this->load('reports'); }
    public function render_telegram(){ $this->load('telegram'); }
    public function render_legacy(){ $this->load('legacy'); }
    public function render_settings(){ $this->load('settings'); }

    private function load($tpl){
        $file = EAIW_PATH . "templates/admin/{$tpl}.php";
        if (file_exists($file)) include $file;
        else echo '<div class="wrap"><h1>قالب یافت نشد: '.$tpl.'</h1></div>';
    }
}
