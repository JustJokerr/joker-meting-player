<?php
/*
Plugin Name: Joker Meting Player
Plugin URI: https://github.com/JustJoker/joker-meting-player
Description: 基于 APlayer 和 Meting API 的专业音乐播放器。支持自定义 API、夜间模式自动切换及深度 UI 自定义。
Version: 1.4.0 Professional
Author: Just.Joker
Author URI: https://github.com/JustJokerr
License: MIT
Text Domain: joker-meting-player
*/

if (!defined('ABSPATH')) exit;

/**
 * JokerMetingPlayer 核心类
 * v1.4.0 Professional 
 * 涵盖：跨站鉴权闭环、动态CSS引擎、四维空间锚定、移动端自适应适配、API性能缓存
 */
class JokerMetingPlayer {
    private $option_name = 'joker_player_options';
    
    // 默认设置项
    private $defaults = [
        // 核心设置
        'playlist_id'    => '2238058922',
        'server'         => 'netease', // netease, tencent, kugou, xiami, baidu
        'type'           => 'playlist',
        'play_order'     => 'list',    // list, single, random
        'default_volume' => '70',
        'custom_api'     => 'https://api.injahow.cn/meting/', 
        
        // 界面与布局
        'position'       => 'left-bottom', // left-bottom, left-top, right-bottom, right-top
        'lrc_size'       => '16',          // 歌词字体大小
        'lrc_pos'        => 'bottom',      // top, bottom
        
        // 夜间模式逻辑
        'night_mode'     => 'auto',        // auto (系统), time (时间), always, never
        'night_start'    => '20:00',
        'night_end'      => '06:00',
        
        // 白天模式颜色自定义 
        'c_primary'      => '#ff4081',
        'c_bg'           => '#ffffff',
        'c_list_bg'      => '#f7f7f7',
        'c_list_cur_bg'  => '#eeeeee',
        'c_lrc'          => '#333333',
        'c_lrc_hl'       => '#ff4081',
        'c_border'       => '#eeeeee',
        'c_text'         => '#666666',
        
        // 夜间模式颜色自定义
        'n_primary'      => '#ff4081',
        'n_bg'           => '#212121',
        'n_list_bg'      => '#333333',
        'n_list_cur_bg'  => '#444444',
        'n_lrc'          => '#999999',
        'n_lrc_hl'       => '#ffffff',
        'n_border'       => '#444444',
        'n_text'         => '#aaaaaa',
        
        // 会员与认证
        'vip_mode'       => 'off',
        'auth_type'      => 'cookie',      // cookie, account
        'cookie'         => '',
        'account_phone'  => '',
        'account_pw'     => '',
    ];

    public function __construct() {
        add_action('admin_menu', [$this, 'add_plugin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_footer', [$this, 'render_player']);
        
        // 注册 AJAX 代理接口
        add_action('wp_ajax_joker_player_proxy', [$this, 'ajax_proxy']);
        add_action('wp_ajax_nopriv_joker_player_proxy', [$this, 'ajax_proxy']);

        // 后台保存设置时，自动触发缓存清理
        add_action('update_option_' . $this->option_name, [$this, 'clear_api_cache'], 10, 2);
        
        // 后台设置页面样式
        add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_assets']);
    }
    
    /**
     * 后台设置页面样式加载
     */
    public function admin_enqueue_assets($hook) {
        if ($hook !== 'toplevel_page_joker-player-settings') return;
        wp_add_inline_style('wp-admin', '
            .joker-admin-v3 {
                max-width: 1200px;
                margin: 0 auto;
                padding: 20px 0;
            }
            .joker-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 30px;
                padding-bottom: 20px;
                border-bottom: 1px solid #eee;
            }
            .header-logo {
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .header-logo .dashicons {
                font-size: 32px;
                color: #ff4081;
            }
            .header-logo .v-tag {
                background: #ff4081;
                color: white;
                padding: 2px 8px;
                border-radius: 4px;
                font-size: 12px;
                margin-left: 10px;
            }
            .header-author {
                text-align: right;
                color: #666;
            }
            .joker-layout {
                display: flex;
                gap: 20px;
            }
            .joker-main {
                flex: 1;
            }
            .j-card {
                background: white;
                border-radius: 8px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                padding: 20px;
                margin-bottom: 20px;
            }
            .j-card-title {
                margin: 0 0 20px 0;
                padding: 0 0 10px 0;
                border-bottom: 1px solid #eee;
                font-size: 18px;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .j-card-title .dashicons {
                color: #ff4081;
            }
            .j-row {
                display: flex;
                gap: 20px;
                margin-bottom: 20px;
                flex-wrap: wrap;
            }
            .j-field {
                flex: 1;
                min-width: 280px;
                margin-bottom: 15px;
            }
            .j-field label {
                display: block;
                margin-bottom: 8px;
                font-weight: 600;
                color: #333;
            }
            .j-field input, .j-field select {
                width: 100%;
                padding: 8px 12px;
                border: 1px solid #ddd;
                border-radius: 4px;
                font-size: 14px;
            }
            .j-field input.large-text {
                width: 100%;
                max-width: 600px;
            }
            .j-tips {
                margin-top: 8px;
                padding: 10px;
                background: #f5f5f5;
                border-radius: 4px;
                font-size: 13px;
                color: #666;
                line-height: 1.5;
            }
            .color-customizer .j-field {
                margin-bottom: 20px;
            }
            .color-grid-container {
                display: flex;
                flex-wrap: wrap;
                gap: 20px;
            }
            .color-group {
                flex: 1;
                min-width: 300px;
            }
            .color-group h4 {
                margin: 0 0 15px 0;
                font-size: 16px;
                color: #333;
            }
            .c-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }
            .c-item {
                display: flex;
                flex-direction: column;
                gap: 5px;
            }
            .c-item span {
                font-size: 13px;
                color: #666;
            }
            .c-item input[type="color"] {
                width: 100%;
                height: 40px;
                padding: 0;
                border: none;
                cursor: pointer;
            }
            .j-actions {
                margin-top: 30px;
                display: flex;
                gap: 10px;
            }
            .j-actions .button-primary {
                background: #ff4081;
                border-color: #ff4081;
                padding: 8px 20px;
                font-size: 14px;
            }
            .j-actions .button-secondary {
                padding: 8px 20px;
                font-size: 14px;
            }
            .notice {
                margin: 20px 0;
            }
        ');
    }

    /**
     * 获取合并后的选项
     */
    private function get_options() {
        $saved = get_option($this->option_name);
        if (!is_array($saved)) {
            return $this->defaults;
        }
        return array_merge($this->defaults, $saved);
    }

    /**
     * 清理 API 数据缓存
     */
    public function clear_api_cache($old_value, $new_value) {
        global $wpdb;
        // 暴力清除所有以 joker_player_api_ 开头的瞬态缓存
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_joker_player_api_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_joker_player_api_%'");
    }

    /**
     * PHP 服务端代理请求 API（
     */
    public function ajax_proxy() {
        $opts = $this->get_options();
        
        if (empty($opts['playlist_id'])) {
            wp_send_json_error(['msg' => '歌单ID不能为空'], 400);
        }

        // 构建目标 API URL
        $api_base = rtrim(trim($opts['custom_api']), '?&/');
        $api_url = $api_base . '?' . http_build_query([
            'server' => $opts['server'],
            'type'   => $opts['type'],
            'id'     => $opts['playlist_id']
        ]);

        $headers = [
            'Accept'     => 'application/json',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36 WordPress/JokerPlayer'
        ];

        //cookie
        if ($opts['vip_mode'] === 'on' && !empty($opts['cookie'])) {
            $clean_cookie = '';
            $cookie_parts = explode(';', $opts['cookie']);
            foreach ($cookie_parts as $part) {
                if (strpos(trim($part), 'MUSIC_U=') === 0) {
                    $clean_cookie = trim($part);
                    break;
                }
            }
            $headers['Cookie'] = $clean_cookie ? $clean_cookie : trim($opts['cookie']);
        }

        //生成唯一缓存 Key
        $cache_key = 'joker_player_api_' . md5($api_url . (isset($headers['Cookie']) ? $headers['Cookie'] : ''));
        
        // 尝试命中缓存，避免频繁请求导致上游封禁IP
        $cached_data = get_transient($cache_key);
        if ($cached_data !== false) {
            wp_send_json_success($cached_data);
            exit;
        }

        // 使用 WordPress HTTP API 发起真实请求
        $response = wp_remote_get($api_url, [
            'timeout'   => 15,
            'sslverify' => false,
            'headers'   => $headers
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error(['msg' => 'API请求失败：' . $response->get_error_message()], 500);
        }

        $status = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($status !== 200 || json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(['msg' => 'API返回无效数据或服务不可用', 'status' => $status], $status);
        }

        // 将成功获取的最新数据缓存 1 小时
        set_transient($cache_key, $data, 3600);

        wp_send_json_success($data);
    }

    /**
     * 注册后台菜单
     */
    public function add_plugin_menu() {
        add_menu_page(
            'Joker Player Settings', 
            'Joker 音乐', 
            'manage_options', 
            'joker-player-settings', 
            [$this, 'settings_page'], 
            'dashicons-format-audio', 
            99
        );
    }

    /**
     * 注册设置
     */
    public function register_settings() {
        register_setting('joker_player_group', $this->option_name);
    }

    /**
     * 加载前端资源
     */
    public function enqueue_assets() {
        if (is_admin()) return;
        $opts = $this->get_options();
        
        wp_enqueue_style('aplayer-css', 'https://cdn.staticfile.org/aplayer/1.10.1/APlayer.min.css', [], '1.10.1');
        wp_enqueue_script('aplayer-js', 'https://cdn.staticfile.org/aplayer/1.10.1/APlayer.min.js', [], '1.10.1', true);
        wp_add_inline_style('aplayer-css', $this->generate_player_styles($opts));
    }

    /**
     * 动态核心引擎 CSS 生成
     * 包含：四维定位、DOM强行介入、移动端视口自适应等超大规模算法
     */
    private function generate_player_styles($opts) {
        $lrc_size = (int)$opts['lrc_size'];
        if ($lrc_size < 12) $lrc_size = 12;

        // 计算歌词容器总高度，防止任何情况的截断
        $lrc_line_height = $lrc_size * 1.5;
        $lrc_container_height = $lrc_line_height * 3 + 20;

        // 方位状态标识
        $pos = $opts['position'];
        $is_right = strpos($pos, 'right') !== false;
        $is_top = strpos($pos, 'top') !== false;

        // 根据播放器位置，计算歌词容器的安全避让距离
        $lrc_pos = $opts['lrc_pos'];
        $lrc_top_val = 'auto';
        $lrc_bottom_val = 'auto';

        if ($lrc_pos === 'top') {
            // 用户想把歌词放在网页顶端
            $lrc_top_val = $is_top ? '75px' : '15px'; // 如果播放器也在顶端，必须下移75px避开播放器
        } else {
            // 用户想把歌词放在网页底端
            $lrc_bottom_val = (!$is_top) ? '75px' : '15px'; // 如果播放器也在底端，必须上移75px避开播放器
        }

        // 色彩配置装载
        $day_vars = "
            --jk-pri: {$opts['c_primary']};
            --jk-bg: {$opts['c_bg']};
            --jk-l-bg: {$opts['c_list_bg']};
            --jk-l-cur: {$opts['c_list_cur_bg']};
            --jk-lrc: {$opts['c_lrc']};
            --jk-lrc-h: {$opts['c_lrc_hl']};
            --jk-bor: {$opts['c_border']};
            --jk-txt: {$opts['c_text']};
        ";
        $night_vars = "
            --jk-pri: {$opts['n_primary']};
            --jk-bg: {$opts['n_bg']};
            --jk-l-bg: {$opts['n_list_bg']};
            --jk-l-cur: {$opts['n_list_cur_bg']};
            --jk-lrc: {$opts['n_lrc']};
            --jk-lrc-h: {$opts['n_lrc_hl']};
            --jk-bor: {$opts['n_border']};
            --jk-txt: {$opts['n_text']};
        ";

        $css = ".joker-player-context { {$day_vars} }\n";

        // 夜间模式接管
        if ($opts['night_mode'] === 'always') {
            $css = ".joker-player-context { {$night_vars} }\n";
        } elseif ($opts['night_mode'] === 'auto') {
            $css .= "@media (prefers-color-scheme: dark) { .joker-player-context { {$night_vars} } }\n";
        } elseif ($opts['night_mode'] === 'time') {
            $css .= "body.joker-night-active .joker-player-context { {$night_vars} }\n";
        }

        $css .= "
            .joker-player-context .aplayer, .joker-player-context .aplayer * { box-sizing: content-box ; }
            .joker-player-context .aplayer svg { max-width: none ; max-height: none ; display: block ; width: 100% ; height: 100% ; }
            .joker-player-context .aplayer { background: var(--jk-bg) ; color: var(--jk-txt) ; border-color: var(--jk-bor) ; box-shadow: 0 4px 12px rgba(0,0,0,0.15) ; z-index: 99999 ; }
            .joker-player-context .aplayer .aplayer-body { background: var(--jk-bg) ; }
            .joker-player-context .aplayer .aplayer-info { border-bottom: 1px solid var(--jk-bor) ; }
            .joker-player-context .aplayer .aplayer-list { background: var(--jk-l-bg) ; }
            .joker-player-context .aplayer .aplayer-list ol li { border-top: 1px solid var(--jk-bor) ; color: var(--jk-txt) ; }
            .joker-player-context .aplayer .aplayer-list ol li:hover { background: var(--jk-l-cur) ; }
            .joker-player-context .aplayer .aplayer-list ol li.aplayer-list-light { background: var(--jk-l-cur) ; border-left-color: var(--jk-pri) ; }
            .joker-player-context .aplayer .aplayer-list ol li.aplayer-list-light .aplayer-list-title { color: var(--jk-pri) ; font-weight: bold; }
            .joker-player-context .aplayer .aplayer-info .aplayer-controller .aplayer-time { color: var(--jk-txt) ; }
            .joker-player-context .aplayer .aplayer-info .aplayer-controller .aplayer-time .aplayer-icon { display: inline-block ; width: 15px ; height: 15px ; opacity: 0.8 ; pointer-events: auto ; }
            .joker-player-context .aplayer .aplayer-info .aplayer-controller .aplayer-time .aplayer-icon:hover { opacity: 1 ; }
            .joker-player-context .aplayer .aplayer-info .aplayer-controller .aplayer-time .aplayer-icon path { fill: var(--jk-txt) ; }
            .joker-player-context .aplayer .aplayer-info .aplayer-controller .aplayer-time .aplayer-icon:hover path { fill: var(--jk-pri) ; }
            .joker-player-context .aplayer .aplayer-info .aplayer-controller .aplayer-bar-wrap .aplayer-bar .aplayer-loaded { background: var(--jk-bor) ; }
            .joker-player-context .aplayer .aplayer-info .aplayer-controller .aplayer-bar-wrap .aplayer-bar .aplayer-played { background: var(--jk-pri) ; }
            .joker-player-context .aplayer .aplayer-info .aplayer-controller .aplayer-bar-wrap .aplayer-bar .aplayer-thumb { background: var(--jk-pri) ; border-color: var(--jk-pri) ; }
            .joker-player-context .aplayer .aplayer-info .aplayer-controller .aplayer-volume-wrap .aplayer-volume-bar-wrap .aplayer-volume-bar .aplayer-volume-filled { background: var(--jk-pri) ; }
            
            /* 歌词区算法引擎 (避让逻辑) - 修复pointer-events，仅禁用歌词文本点击，保留按钮交互 */
            .joker-player-context .aplayer.aplayer-fixed .aplayer-lrc { 
                background: transparent ; pointer-events: auto ; text-align: center ; margin: 0 ; padding: 10px 0 ;
                height: {$lrc_container_height}px ; 
                top: {$lrc_top_val} ; 
                bottom: {$lrc_bottom_val} ;
                left: 0 ; right: 0 ; /* 强制水平居中 */
                z-index: 99997 ; /* 低于播放器主体，避免遮挡按钮 */
            }
            .joker-player-context .aplayer .aplayer-lrc p { 
                color: var(--jk-lrc) ; font-size: {$lrc_size}px ; line-height: {$lrc_line_height}px ; min-height: {$lrc_line_height}px ;
                background: transparent ; text-shadow: 1px 1px 3px rgba(0,0,0,0.5) ; filter: blur(0.5px); opacity: 0.8; transition: all 0.5s ease-out ;
                pointer-events: none ; /* 仅禁用歌词文本点击 */
            }
            .joker-player-context .aplayer .aplayer-lrc p.aplayer-lrc-current { color: var(--jk-lrc-h) ; opacity: 0.85; filter: none; transform: scale(1.15) ; font-weight: 800; }
        ";

        // APlayer 默认是 left 和 bottom 结构
        
        // 处理右侧 (Right)
        if ($is_right) {
            $css .= "
                /* 将父级容器移动到右侧 */
                .joker-player-context .aplayer.aplayer-fixed { right: 0 ; left: auto ; }
                /* 播放器主体锁定至右侧 */
                .joker-player-context .aplayer.aplayer-fixed .aplayer-body { right: 0 ; left: auto ; transition: right 0.3s ease ; }
                /* 窄体折叠模式 (原版往左缩，现修改为往右隐藏) */
                .joker-player-context .aplayer.aplayer-fixed.aplayer-narrow .aplayer-body { right: -66px ; left: auto ; }
                /* 将侧边唤醒按钮平移至播放器左侧，并做水平翻转 */
                .joker-player-context .aplayer.aplayer-fixed .aplayer-miniswitch { 
                    right: auto ; left: -18px ; 
                    border-radius: 4px 0 0 4px ; 
                    pointer-events: auto ; /* 确保按钮可点击 */
                }
                .joker-player-context .aplayer.aplayer-fixed .aplayer-miniswitch .aplayer-icon { transform: rotateY(180deg) ; }
                /* 列表展开锁定右侧对齐 */
                .joker-player-context .aplayer.aplayer-fixed .aplayer-list { right: 0 ; left: auto ; }
            ";
        }

        // 处理顶部 (Top)
        if ($is_top) {
            $css .= "
                /* 父级吸顶 - 提升层级避免遮挡 */
                .joker-player-context .aplayer.aplayer-fixed { 
                    top: 0 ; bottom: auto ; 
                    z-index: 99999 ; /* 强制最高层级 */
                }
                /* 播放器主体锁定至顶部 */
                .joker-player-context .aplayer.aplayer-fixed .aplayer-body { 
                    top: 0 ; bottom: auto ; 
                    z-index: 99999 ; /* 主体层级独立 */
                }
                /* 列表改为向下展开 - 修复遮挡+空白问题 (核心：top设为66px+10px偏移，避免被播放器遮挡) */
                .joker-player-context .aplayer.aplayer-fixed .aplayer-list { 
                    top: 76px ; bottom: auto ; 
                    border-top: 1px solid var(--jk-bor) ; border-bottom: 1px solid var(--jk-bor) ; 
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15) ;
                    z-index: 99998 ; /* 列表层级略低于主体但高于其他元素 */
                    max-height: 300px ; /* 限制列表最大高度，避免空白 */
                    overflow-y: auto ; /* 超出高度滚动，消除空白 */
                    margin: 76px 0 0 0; /* 移除默认margin导致的空白 */
                    /* 增加内边距，确保首行内容不被遮挡 */
                    padding: 0 ;
                }
                /* 修复顶部列表内部空白 */
                .joker-player-context .aplayer.aplayer-fixed .aplayer-list ol {
                    margin: 0 ;
                    padding: 0 ;
                }
                .joker-player-context .aplayer.aplayer-fixed .aplayer-list ol li {
                    padding: 8px 12px ; /* 精准控制内边距，避免空白 */
                    white-space: nowrap ;
                    overflow: hidden ;
                    text-overflow: ellipsis ;
                }
            ";
        } else {
            // 底部的默认避让
            $css .= "
                .joker-player-context .aplayer.aplayer-fixed .aplayer-list { 
                    bottom: 76px ; top: auto ; 
                    border-bottom: 1px solid var(--jk-bor) ; border-top: 1px solid var(--jk-bor) ;
                    max-height: 300px ; /* 统一限制高度，避免空白 */
                    overflow-y: auto ;
                    margin: 0 0 76px 0;
                    /* 增加内边距，确保末行内容不被遮挡 */
                    padding: 0 ;
                }
                .joker-player-context .aplayer.aplayer-fixed .aplayer-list ol {
                    margin: 0 ;
                    padding: 0 ;
                }
                .joker-player-context .aplayer.aplayer-fixed .aplayer-list ol li {
                    padding: 8px 12px ;
                    white-space: nowrap ;
                    overflow: hidden ;
                    text-overflow: ellipsis ;
                }
            ";
        }

        // 当视口宽度小于 768px 时介入
        $css .= "
            @media (max-width: 768px) {
                /* 解除固定宽度限制，确保父级可以横跨屏幕 */
                .joker-player-context .aplayer.aplayer-fixed { max-width: 100vw ; width: 100% ; }
                
                /* 强制歌单列表全屏宽度显示，防止在右上、右下模式时超出屏幕左侧边界 */
                .joker-player-context .aplayer.aplayer-fixed .aplayer-list { 
                    position: fixed ; 
                    left: 0 ; right: 0 ; 
                    width: 100vw ; max-width: 100vw ;
                    max-height: 300px; /* 移动端适配高度，避免空白 */
                    overflow-y: auto ;
                    margin: 0 ;
                    /* 移动端列表偏移修复 */
                    " . ($is_top ? "top: 76px ;" : "bottom: 76px ;") . "
                    " . ($is_top ? "margin-top: 76px;" : "margin-bottom: 76px ;") . "
                    padding:0 ;
                }
                
                /* 柔性计算：自动按 0.75 比例缩小歌词尺寸，防止大字号占用全屏。同时设置最小兜底尺寸 13px */
                .joker-player-context .aplayer.aplayer-fixed .aplayer-lrc p {
                    font-size: max(13px, calc({$lrc_size}px * 0.75)) ;
                    line-height: max(20px, calc({$lrc_line_height}px * 0.75)) ;
                    min-height: max(20px, calc({$lrc_line_height}px * 0.75)) ;
                }
                .joker-player-context .aplayer.aplayer-fixed .aplayer-lrc {
                    height: max(70px, calc({$lrc_container_height}px * 0.75)) ;
                    padding: 5px 0 ;
                }
                
                /* 移动端列表项优化，消除空白 */
                .joker-player-context .aplayer.aplayer-fixed .aplayer-list ol li {
                    padding: 6px 10px ;
                    font-size: 14px ;
                }
                
                /* 移动端按钮交互修复 */
                .joker-player-context .aplayer .aplayer-info .aplayer-controller .aplayer-time .aplayer-icon {
                    pointer-events: auto ;
                }
            }
        ";
        return $css;
    }

    /**
     * 前台播放器渲染与 JS 逻辑
     */
    public function render_player() {
        $opts = $this->get_options();
        if (empty($opts['playlist_id'])) return;

        // 真实 API 参数映射
        $play_order = $opts['play_order'];
        $ap_loop = 'all';
        $ap_order = 'list';
        
        if ($play_order === 'single') {
            $ap_loop = 'one';
            $ap_order = 'list';
        } elseif ($play_order === 'random') {
            $ap_loop = 'all';
            $ap_order = 'random';
        }

        echo "<!-- Joker Player UI Render Start -->\n";
        echo "<div class='joker-player-context'>\n";
        echo "  <div id='joker-player-instance'></div>\n";
        echo "</div>\n";
        
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Joker Player: 进程初始化中... (V1.4.0 四维自适应版)');
            
            // 守护进程加载 APlayer 类库 - 增加加载超时容错
            let aplayerLoadTimer = setInterval(() => {
                if (typeof window.APlayer !== 'undefined') {
                    clearInterval(aplayerLoadTimer);
                    
                    fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=joker_player_proxy', {
                        method: 'GET',
                        cache: 'no-cache',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(response => {
                        if (!response.ok) throw new Error('接口响应失败：' + response.status);
                        return response.json();
                    })
                    .then(result => {
                        if (result.success && result.data && result.data.length > 0) {
                            console.log('Joker Player: 源挂载完毕，共载入曲目：', result.data.length);
                            
                            const playerContainer = document.getElementById('joker-player-instance');
                            if (!playerContainer) return;
                            
                            const ap = new APlayer({
                                container: playerContainer,
                                fixed: true,
                                autoplay: false,
                                volume: <?php echo floatval($opts['default_volume'])/100; ?>,
                                theme: '<?php echo esc_js($opts['c_primary']); ?>',
                                loop: '<?php echo esc_js($ap_loop); ?>',
                                order: '<?php echo esc_js($ap_order); ?>',
                                listFolded: true,
                                listMaxHeight: 300, // 显式设置列表最大高度
                                lrcType: 3,
                                audio: result.data
                            });
                        } else {
                            throw new Error(result.data?.msg || '源解析空数据');
                        }
                    })
                    .catch(error => console.error('Joker Player 底层错误：', error));
                }
            }, 200);

            // 超时清理定时器，避免内存泄漏
            setTimeout(() => {
                if (typeof window.APlayer === 'undefined') {
                    clearInterval(aplayerLoadTimer);
                    console.error('Joker Player: APlayer 加载超时，请检查CDN链接');
                }
            }, 10000);
        });
        </script>
        <?php

        $this->inject_frontend_scripts($opts);
    }

    /**
     * 注入前端夜间模式监听逻辑
     */
    private function inject_frontend_scripts($opts) {
        ?>
        <script>
        (function() {
            document.addEventListener('DOMContentLoaded', function() {
                const opts = <?php echo json_encode([
                    'night_mode'  => $opts['night_mode'],
                    'night_start' => $opts['night_start'],
                    'night_end'   => $opts['night_end']
                ]); ?>;
                
                if (opts.night_mode === 'time') {
                    const checkNight = () => {
                        const now = new Date();
                        const hour = now.getHours();
                        const start = parseInt(opts.night_start.split(':')[0]);
                        const end = parseInt(opts.night_end.split(':')[0]);
                        
                        let isNight = false;
                        if (start > end) {
                            if (hour >= start || hour < end) isNight = true;
                        } else {
                            if (hour >= start && hour < end) isNight = true;
                        }
                        
                        if (isNight) {
                            document.body.classList.add('joker-night-active');
                        } else {
                            document.body.classList.remove('joker-night-active');
                        }
                    };
                    checkNight();
                    setInterval(checkNight, 60000);
                }
            });
        })();
        </script>
        <?php
    }

    /**
     * 后台设置页面 
     */
    public function settings_page() {
        $opts = $this->get_options();
        
        // 重置操作
        if (isset($_GET['reset']) && $_GET['reset'] == '1') {
            delete_option($this->option_name);
            $this->clear_api_cache('',''); // 重置时一并清空数据缓存
            echo '<div class="notice notice-success is-dismissible"><p>✅ Joker Player 所有核心设置已成功归还为默认安全值，并已清空本地 API 缓存。</p></div>';
            $opts = $this->defaults;
        }
        
        // 成功保存
        if (isset($_GET['settings-updated']) && $_GET['settings-updated'] == 'true') {
            echo '<div class="notice notice-success is-dismissible"><p>🎉 配置已保存！</p></div>';
        }

        ?>
        <div class="wrap joker-admin-v3">
            <div class="joker-header">
                <div class="header-logo">
                    <span class="dashicons dashicons-format-audio"></span>
                    <h1>Joker Player Settings</h1>
                    <span class="v-tag">v1.4.0 Professional</span>
                </div>
                <div class="header-author">
                    Designed & Developed by <strong>Just.Joker</strong><br>
                    <small style="color:#aaa;font-weight:normal;">Kernel Refactored for Stability & Responsiveness</small>
                </div>
            </div>

            <form method="post" action="options.php">
                <?php settings_fields('joker_player_group'); ?>
                <?php do_settings_sections('joker_player_group'); ?>
                
                <div class="joker-layout">
                    <!-- 左侧设置版块 -->
                    <div class="joker-main">
                        <div class="j-card">
                            <h3 class="j-card-title"><span class="dashicons dashicons-admin-links"></span> 解析与源配置</h3>
                            <div class="j-row">
                                <div class="j-field">
                                    <label>音乐平台</label>
                                    <select name="joker_player_options[server]">
                                        <option value="netease" <?php selected($opts['server'], 'netease'); ?>>网易云音乐</option>
                                        <option value="tencent" <?php selected($opts['server'], 'tencent'); ?>>QQ 音乐</option>
                                        <option value="kugou" <?php selected($opts['server'], 'kugou'); ?>>酷狗音乐</option>
                                        <option value="baidu" <?php selected($opts['server'], 'baidu'); ?>>百度音乐</option>
                                        <option value="xiami" <?php selected($opts['server'], 'xiami'); ?>>虾米音乐</option>
                                    </select>
                                </div>
                                <div class="j-field">
                                    <label>数据源 ID (歌单 / 单曲)</label>
                                    <input type="text" name="joker_player_options[playlist_id]" value="<?php echo esc_attr($opts['playlist_id']); ?>" placeholder="例如: 2238058922">
                                </div>
                            </div>
                            <div class="j-field">
                                <label>Meting API 接口底包地址</label>
                                <input type="url" name="joker_player_options[custom_api]" value="<?php echo esc_url($opts['custom_api']); ?>" class="large-text">
                                <div class="j-tips">
                                    <strong>极速瞬态缓存说明（V1.4 新特性）：</strong><br>
                                    本插件采用了高级 Transient 引擎，每次成功获取外部 API 数据后会将其在服务器中安全保存 1 小时，极大提升播放器加载速度、消灭跨域报错，并彻底免疫上游接口因请求频繁而封禁IP的问题。点击下方“保存”按钮会自动为您清空旧缓存以加载最新歌单。
                                </div>
                            </div>
                        </div>

                        <div class="j-card">
                            <h3 class="j-card-title"><span class="dashicons dashicons-controls-play"></span> 播放器核心配置</h3>
                            <div class="j-row">
                                <div class="j-field">
                                    <label>播放器位置</label>
                                    <select name="joker_player_options[position]">
                                        <option value="left-bottom" <?php selected($opts['position'], 'left-bottom'); ?>>左下角</option>
                                        <option value="left-top" <?php selected($opts['position'], 'left-top'); ?>>左上角</option>
                                        <option value="right-bottom" <?php selected($opts['position'], 'right-bottom'); ?>>右下角</option>
                                        <option value="right-top" <?php selected($opts['position'], 'right-top'); ?>>右上角</option>
                                    </select>
                                </div>
                                <div class="j-field">
                                    <label>播放顺序</label>
                                    <select name="joker_player_options[play_order]">
                                        <option value="list" <?php selected($opts['play_order'], 'list'); ?>>列表循环</option>
                                        <option value="single" <?php selected($opts['play_order'], 'single'); ?>>单曲循环</option>
                                        <option value="random" <?php selected($opts['play_order'], 'random'); ?>>随机播放</option>
                                    </select>
                                </div>
                            </div>
                            <div class="j-row">
                                <div class="j-field">
                                    <label>默认音量 (0-100)</label>
                                    <input type="number" min="0" max="100" name="joker_player_options[default_volume]" value="<?php echo esc_attr($opts['default_volume']); ?>">
                                </div>
                                <div class="j-field">
                                    <label>歌词字体大小 (px)</label>
                                    <input type="number" min="12" max="30" name="joker_player_options[lrc_size]" value="<?php echo esc_attr($opts['lrc_size']); ?>">
                                </div>
                            </div>
                            <div class="j-field">
                                <label>歌词显示位置</label>
                                <select name="joker_player_options[lrc_pos]">
                                    <option value="bottom" <?php selected($opts['lrc_pos'], 'bottom'); ?>>页面底部</option>
                                    <option value="top" <?php selected($opts['lrc_pos'], 'top'); ?>>页面顶部</option>
                                </select>
                            </div>
                        </div>

                        <div class="j-card color-customizer">
                            <h3 class="j-card-title"><span class="dashicons dashicons-art"></span> 深度颜色与沉浸模式自定义</h3>
                            
                            <div class="j-field">
                                <label>夜间模式介入逻辑</label>
                                <select name="joker_player_options[night_mode]" id="night-mode-sel">
                                    <option value="auto" <?php selected($opts['night_mode'], 'auto'); ?>>智能跟随设备客户端系统 (prefers-color-scheme)</option>
                                    <option value="time" <?php selected($opts['night_mode'], 'time'); ?>>指定时间段内强制接管切换</option>
                                    <option value="always" <?php selected($opts['night_mode'], 'always'); ?>>始终死锁在夜间模式</option>
                                    <option value="never" <?php selected($opts['night_mode'], 'never'); ?>>始终拒绝夜间模式</option>
                                </select>
                            </div>

                            <div id="time-picker-row" style="display: <?php echo $opts['night_mode'] === 'time' ? 'flex' : 'none'; ?>; gap: 20px; margin-bottom: 20px;">
                                <div class="j-field">
                                    <label>晚间潜入时间</label>
                                    <input type="time" name="joker_player_options[night_start]" value="<?php echo esc_attr($opts['night_start']); ?>">
                                </div>
                                <div class="j-field">
                                    <label>清晨唤醒时间</label>
                                    <input type="time" name="joker_player_options[night_end]" value="<?php echo esc_attr($opts['night_end']); ?>">
                                </div>
                            </div>

                            <div class="color-grid-container">
                                <div class="color-group">
                                    <h4>白天模式配色 (Day Palette)</h4>
                                    <div class="c-grid">
                                        <div class="c-item"><span>点睛主题色</span><input type="color" name="joker_player_options[c_primary]" value="<?php echo esc_attr($opts['c_primary']); ?>"></div>
                                        <div class="c-item"><span>主背景色</span><input type="color" name="joker_player_options[c_bg]" value="<?php echo esc_attr($opts['c_bg']); ?>"></div>
                                        <div class="c-item"><span>列表背景板</span><input type="color" name="joker_player_options[c_list_bg]" value="<?php echo esc_attr($opts['c_list_bg']); ?>"></div>
                                        <div class="c-item"><span>正在播放条目</span><input type="color" name="joker_player_options[c_list_cur_bg]" value="<?php echo esc_attr($opts['c_list_cur_bg']); ?>"></div>
                                        <div class="c-item"><span>闲置歌词</span><input type="color" name="joker_player_options[c_lrc]" value="<?php echo esc_attr($opts['c_lrc']); ?>"></div>
                                        <div class="c-item"><span>点亮歌词</span><input type="color" name="joker_player_options[c_lrc_hl]" value="<?php echo esc_attr($opts['c_lrc_hl']); ?>"></div>
                                        <div class="c-item"><span>切分边框</span><input type="color" name="joker_player_options[c_border]" value="<?php echo esc_attr($opts['c_border']); ?>"></div>
                                        <div class="c-item"><span>常规文字集</span><input type="color" name="joker_player_options[c_text]" value="<?php echo esc_attr($opts['c_text']); ?>"></div>
                                    </div>
                                </div>
                                <div class="color-group">
                                    <h4>夜间模式配色 (Night Palette)</h4>
                                    <div class="c-grid">
                                        <div class="c-item"><span>点睛主题色</span><input type="color" name="joker_player_options[n_primary]" value="<?php echo esc_attr($opts['n_primary']); ?>"></div>
                                        <div class="c-item"><span>主背景色</span><input type="color" name="joker_player_options[n_bg]" value="<?php echo esc_attr($opts['n_bg']); ?>"></div>
                                        <div class="c-item"><span>列表背景板</span><input type="color" name="joker_player_options[n_list_bg]" value="<?php echo esc_attr($opts['n_list_bg']); ?>"></div>
                                        <div class="c-item"><span>正在播放条目</span><input type="color" name="joker_player_options[n_list_cur_bg]" value="<?php echo esc_attr($opts['n_list_cur_bg']); ?>"></div>
                                        <div class="c-item"><span>闲置歌词</span><input type="color" name="joker_player_options[n_lrc]" value="<?php echo esc_attr($opts['n_lrc']); ?>"></div>
                                        <div class="c-item"><span>点亮歌词</span><input type="color" name="joker_player_options[n_lrc_hl]" value="<?php echo esc_attr($opts['n_lrc_hl']); ?>"></div>
                                        <div class="c-item"><span>切分边框</span><input type="color" name="joker_player_options[n_border]" value="<?php echo esc_attr($opts['n_border']); ?>"></div>
                                        <div class="c-item"><span>常规文字集</span><input type="color" name="joker_player_options[n_text]" value="<?php echo esc_attr($opts['n_text']); ?>"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="j-card">
                            <h3 class="j-card-title"><span class="dashicons dashicons-shield-alt"></span> 会员与认证配置</h3>
                            <div class="j-field">
                                <label>VIP 模式</label>
                                <select name="joker_player_options[vip_mode]">
                                    <option value="off" <?php selected($opts['vip_mode'], 'off'); ?>>关闭</option>
                                    <option value="on" <?php selected($opts['vip_mode'], 'on'); ?>>开启</option>
                                </select>
                            </div>
                            <div class="j-field">
                                <label>认证方式</label>
                                <select name="joker_player_options[auth_type]">
                                    <option value="cookie" <?php selected($opts['auth_type'], 'cookie'); ?>>Cookie (推荐)</option>
                                    <option value="account" <?php selected($opts['auth_type'], 'account'); ?>>账号密码 (暂未启用)</option>
                                </select>
                            </div>
                            <div class="j-field">
                                <label>网易云 MUSIC_U Cookie</label>
                                <textarea name="joker_player_options[cookie]" rows="3" class="large-text" placeholder="请输入包含 MUSIC_U 的 Cookie 字符串"><?php echo esc_textarea($opts['cookie']); ?></textarea>
                            </div>
                            <div class="j-field" style="display: <?php echo $opts['auth_type'] === 'account' ? 'block' : 'none'; ?>;">
                                <label>手机号</label>
                                <input type="text" name="joker_player_options[account_phone]" value="<?php echo esc_attr($opts['account_phone']); ?>" placeholder="仅作预留，暂未启用">
                            </div>
                            <div class="j-field" style="display: <?php echo $opts['auth_type'] === 'account' ? 'block' : 'none'; ?>;">
                                <label>密码</label>
                                <input type="password" name="joker_player_options[account_pw]" value="<?php echo esc_attr($opts['account_pw']); ?>" placeholder="仅作预留，暂未启用">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 操作按钮 -->
                <div class="j-actions">
                    <?php submit_button('保存所有配置', 'primary', 'submit', false); ?>
                    <a href="?page=joker-player-settings&reset=1" class="button button-secondary" onclick="return confirm('确定要重置所有设置为默认值吗？此操作不可恢复！')">重置默认配置</a>
                </div>
            </form>
        </div>

        <script>
        // 夜间模式时间选择器显隐逻辑
        document.addEventListener('DOMContentLoaded', function() {
            const nightModeSel = document.getElementById('night-mode-sel');
            const timePickerRow = document.getElementById('time-picker-row');
            
            if (nightModeSel && timePickerRow) {
                nightModeSel.addEventListener('change', function() {
                    timePickerRow.style.display = this.value === 'time' ? 'flex' : 'none';
                });
            }
            
            // 认证方式显隐逻辑
            const authTypeSel = document.querySelector('select[name="joker_player_options[auth_type]"]');
            const accountPhone = document.querySelector('input[name="joker_player_options[account_phone]"]').closest('.j-field');
            const accountPw = document.querySelector('input[name="joker_player_options[account_pw]"]').closest('.j-field');
            
            if (authTypeSel && accountPhone && accountPw) {
                authTypeSel.addEventListener('change', function() {
                    const isAccount = this.value === 'account';
                    accountPhone.style.display = isAccount ? 'block' : 'none';
                    accountPw.style.display = isAccount ? 'block' : 'none';
                });
            }
        });
        </script>
        <?php
    }
}

// 初始化插件
new JokerMetingPlayer();
?>
