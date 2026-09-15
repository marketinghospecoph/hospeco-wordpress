<?php

namespace ThemeAtelier\BetterChatSupport\Admin;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;

if (! defined('ABSPATH')) {
    die;
}

class Analytics
{
    private $table;

    function __construct()
    {
        global $wpdb;
        $this->table = $wpdb->prefix . 'mcs_analytics';

        $this->create_table();
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_action('wp_footer', [$this, 'inject_tracking_script']);
    }

    public function create_table()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table           = $this->table;

        $sql = "CREATE TABLE $table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            widget_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            event_type VARCHAR(20) NOT NULL,
            device_type VARCHAR(10) NOT NULL DEFAULT 'desktop',
            country VARCHAR(3) NOT NULL DEFAULT '',
            browser VARCHAR(30) NOT NULL DEFAULT '',
            page_url VARCHAR(500) NOT NULL DEFAULT '',
            session_id VARCHAR(64) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_widget_event_date (widget_id, event_type, created_at),
            KEY idx_device (device_type),
            KEY idx_country (country),
            KEY idx_browser (browser)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public function register_rest_routes()
    {
        register_rest_route('better-chat-support/v1', '/analytics/track', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'track_event'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('better-chat-support/v1', '/analytics', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_summary'],
            'permission_callback' => [$this, 'admin_permission'],
        ]);

        register_rest_route('better-chat-support/v1', '/analytics/chart', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_chart'],
            'permission_callback' => [$this, 'admin_permission'],
        ]);

        register_rest_route('better-chat-support/v1', '/analytics/devices', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_devices'],
            'permission_callback' => [$this, 'admin_permission'],
        ]);

        register_rest_route('better-chat-support/v1', '/analytics/tables', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_tables'],
            'permission_callback' => [$this, 'admin_permission'],
        ]);
    }

    public function admin_permission()
    {
        return current_user_can('manage_options');
    }

    public function track_event(WP_REST_Request $request)
    {
        global $wpdb;

        $event_type = sanitize_text_field($request->get_param('event_type') ?? '');
        if (! in_array($event_type, ['visitor', 'view', 'conversion'], true)) {
            return new WP_REST_Response(['success' => false], 400);
        }

        $widget_id  = absint($request->get_param('widget_id') ?? 0);
        $device     = sanitize_text_field($request->get_param('device_type') ?? 'desktop');
        $session_id = sanitize_text_field($request->get_param('session_id') ?? '');
        $page_url   = esc_url_raw(substr($request->get_param('page_url') ?? '', 0, 500));

        $country = $this->detect_country();
        $browser = $this->detect_browser();

        $wpdb->insert(
            $this->table,
            [
                'widget_id'   => $widget_id,
                'event_type'  => $event_type,
                'device_type' => in_array($device, ['desktop', 'mobile', 'other'], true) ? $device : 'desktop',
                'country'     => substr($country, 0, 3),
                'browser'     => substr($browser, 0, 30),
                'page_url'    => $page_url,
                'session_id'  => substr($session_id, 0, 64),
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        return new WP_REST_Response(['success' => true], 200);
    }

    public function get_summary(WP_REST_Request $request)
    {
        global $wpdb;

        [$from, $to, $widget_where, $date_where] = $this->build_where($request->get_params());

        $table = $this->table;
        $row   = $wpdb->get_row(
            "SELECT
                SUM(event_type = 'visitor') AS visitors,
                SUM(event_type = 'view') AS views,
                SUM(event_type = 'conversion') AS conversions
            FROM $table WHERE 1=1 $date_where $widget_where"
        );

        $visitors    = (int) ($row->visitors ?? 0);
        $views       = (int) ($row->views ?? 0);
        $conversions = (int) ($row->conversions ?? 0);
        $rate        = $visitors > 0 ? round(($conversions / $visitors) * 100, 1) : 0;

        return new WP_REST_Response([
            'visitors'        => $visitors,
            'views'           => $views,
            'conversions'     => $conversions,
            'conversion_rate' => $rate,
        ]);
    }

    public function get_chart(WP_REST_Request $request)
    {
        global $wpdb;

        $params                                  = $request->get_params();
        [$from, $to, $widget_where, $date_where] = $this->build_where($params);

        $period   = $params['period'] ?? 'this_week';
        $group_by = in_array($period, ['this_year', 'last_year'], true) ? 'month' : 'day';
        $table    = $this->table;

        if ($group_by === 'month') {
            $select = "DATE_FORMAT(created_at, '%Y-%m') AS period_key";
            $group  = "DATE_FORMAT(created_at, '%Y-%m')";
        } else {
            $select = 'DATE(created_at) AS period_key';
            $group  = 'DATE(created_at)';
        }

        $rows = $wpdb->get_results(
            "SELECT $select, event_type, COUNT(*) AS cnt
            FROM $table WHERE 1=1 $date_where $widget_where
            GROUP BY $group, event_type"
        );

        $labels   = $this->build_label_range($from, $to, $group_by);
        $datasets = [
            'visitors'    => array_fill(0, count($labels), 0),
            'views'       => array_fill(0, count($labels), 0),
            'conversions' => array_fill(0, count($labels), 0),
        ];

        $index_map = array_flip($labels);
        foreach ($rows as $row) {
            $key = $group_by === 'month'
                ? substr($row->period_key, 0, 7)
                : $row->period_key;
            if (! isset($index_map[$key])) {
                continue;
            }
            $i        = $index_map[$key];
            $type_map = ['visitor' => 'visitors', 'view' => 'views', 'conversion' => 'conversions'];
            if (isset($type_map[$row->event_type])) {
                $datasets[$type_map[$row->event_type]][$i] = (int) $row->cnt;
            }
        }

        return new WP_REST_Response(['labels' => $labels, 'datasets' => $datasets]);
    }

    public function get_devices(WP_REST_Request $request)
    {
        global $wpdb;

        [$from, $to, $widget_where, $date_where] = $this->build_where($request->get_params());
        $table = $this->table;

        $rows = $wpdb->get_results(
            "SELECT device_type, event_type, COUNT(*) AS cnt
            FROM $table WHERE 1=1 $date_where $widget_where
            GROUP BY device_type, event_type"
        );

        $result = [
            'desktop' => ['visitors' => 0, 'views' => 0, 'conversions' => 0],
            'mobile'  => ['visitors' => 0, 'views' => 0, 'conversions' => 0],
            'other'   => ['visitors' => 0, 'views' => 0, 'conversions' => 0],
        ];

        $type_map = ['visitor' => 'visitors', 'view' => 'views', 'conversion' => 'conversions'];
        foreach ($rows as $row) {
            $dev = $row->device_type;
            if (! isset($result[$dev])) {
                $dev = 'other';
            }
            if (isset($type_map[$row->event_type])) {
                $result[$dev][$type_map[$row->event_type]] = (int) $row->cnt;
            }
        }

        return new WP_REST_Response($result);
    }

    public function get_tables(WP_REST_Request $request)
    {
        global $wpdb;

        [$from, $to, $widget_where, $date_where] = $this->build_where($request->get_params());
        $table = $this->table;

        $countries = $wpdb->get_results(
            "SELECT country AS label,
                SUM(event_type = 'view') AS views,
                SUM(event_type = 'conversion') AS conversions
            FROM $table WHERE 1=1 $date_where $widget_where AND country != ''
            GROUP BY country ORDER BY views DESC LIMIT 10"
        );

        $pages = $wpdb->get_results(
            "SELECT page_url AS label,
                SUM(event_type = 'view') AS views,
                SUM(event_type = 'conversion') AS conversions
            FROM $table WHERE 1=1 $date_where $widget_where AND page_url != ''
            GROUP BY page_url ORDER BY views DESC LIMIT 10"
        );

        $browsers = $wpdb->get_results(
            "SELECT browser AS label,
                SUM(event_type = 'view') AS views,
                SUM(event_type = 'conversion') AS conversions
            FROM $table WHERE 1=1 $date_where $widget_where AND browser != ''
            GROUP BY browser ORDER BY views DESC LIMIT 10"
        );

        $format = function ($rows) {
            return array_map(function ($row) {
                return [
                    'label'       => $row->label,
                    'views'       => (int) $row->views,
                    'conversions' => (int) $row->conversions,
                ];
            }, $rows ?: []);
        };

        return new WP_REST_Response([
            'countries' => $format($countries),
            'pages'     => $format($pages),
            'browsers'  => $format($browsers),
        ]);
    }

    private function build_where(array $params)
    {
        global $wpdb;

        $period    = $params['period'] ?? 'this_week';
        $widget_id = $params['widget_id'] ?? 'all';
        $date_from = $params['date_from'] ?? '';
        $date_to   = $params['date_to'] ?? '';

        [$from, $to] = $this->resolve_period($period, $date_from, $date_to);

        $widget_where = '';
        if ($widget_id !== 'all' && is_numeric($widget_id)) {
            $widget_where = $wpdb->prepare(' AND widget_id = %d', (int) $widget_id);
        }

        $date_where = $wpdb->prepare(
            ' AND created_at >= %s AND created_at <= %s',
            $from . ' 00:00:00',
            $to . ' 23:59:59'
        );

        return [$from, $to, $widget_where, $date_where];
    }

    private function resolve_period($period, $date_from = '', $date_to = '')
    {
        $now = current_time('timestamp');

        switch ($period) {
            case 'last_week':
                $dow         = (int) date('N', $now);
                $this_monday = $now - ($dow - 1) * DAY_IN_SECONDS;
                $last_monday = $this_monday - 7 * DAY_IN_SECONDS;
                $from        = date('Y-m-d', $last_monday);
                $to          = date('Y-m-d', $last_monday + 6 * DAY_IN_SECONDS);
                break;
            case 'this_month':
                $from = date('Y-m-01', $now);
                $to   = date('Y-m-t', $now);
                break;
            case 'last_month':
                $first_of_this = mktime(0, 0, 0, (int) date('m', $now), 1, (int) date('Y', $now));
                $last_ts       = $first_of_this - DAY_IN_SECONDS;
                $from          = date('Y-m-01', $last_ts);
                $to            = date('Y-m-t', $last_ts);
                break;
            case 'this_year':
                $from = date('Y-01-01', $now);
                $to   = date('Y-12-31', $now);
                break;
            case 'last_year':
                $year = (int) date('Y', $now) - 1;
                $from = "{$year}-01-01";
                $to   = "{$year}-12-31";
                break;
            case 'custom':
                $from = ! empty($date_from) ? sanitize_text_field($date_from) : date('Y-m-d', $now - 6 * DAY_IN_SECONDS);
                $to   = ! empty($date_to)   ? sanitize_text_field($date_to)   : date('Y-m-d', $now);
                break;
            default: // this_week
                $dow  = (int) date('N', $now);
                $from = date('Y-m-d', $now - ($dow - 1) * DAY_IN_SECONDS);
                $to   = date('Y-m-d', $now + (7 - $dow) * DAY_IN_SECONDS);
        }

        return [$from, $to];
    }

    private function build_label_range($from, $to, $group_by)
    {
        $labels = [];
        $start  = strtotime($from);
        $end    = strtotime($to);

        if ($group_by === 'month') {
            while ($start <= $end) {
                $labels[] = date('Y-m', $start);
                $start    = mktime(0, 0, 0, (int) date('m', $start) + 1, 1, (int) date('Y', $start));
            }
        } else {
            while ($start <= $end) {
                $labels[] = date('Y-m-d', $start);
                $start   += DAY_IN_SECONDS;
            }
        }

        return $labels;
    }

    private function detect_country()
    {
        $headers = ['CF-IPCountry', 'HTTP_X_COUNTRY_CODE', 'HTTP_GEOIP_COUNTRY_CODE'];
        foreach ($headers as $h) {
            $val = $_SERVER[$h] ?? '';
            if ($val && strlen($val) <= 3) {
                return strtoupper(sanitize_text_field($val));
            }
        }
        return '';
    }

    private function detect_browser()
    {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (stripos($ua, 'Edg') !== false)           return 'Edge';
        if (stripos($ua, 'OPR') !== false)           return 'Opera';
        if (stripos($ua, 'YaBrowser') !== false)     return 'Yandex';
        if (stripos($ua, 'SamsungBrowser') !== false) return 'Samsung';
        if (stripos($ua, 'Chrome') !== false)        return 'Chrome';
        if (stripos($ua, 'Firefox') !== false)       return 'Firefox';
        if (stripos($ua, 'Safari') !== false)        return 'Safari';
        if (stripos($ua, 'MSIE') !== false || stripos($ua, 'Trident') !== false) return 'IE';
        return 'Other';
    }

    public function inject_tracking_script()
    {
        $track_url = esc_url_raw(rest_url('better-chat-support/v1/analytics/track'));
        $url_json  = wp_json_encode($track_url);
        ?>
<script>(function(){
var trackUrl=<?php echo $url_json; ?>;
function getDevice(){var ua=navigator.userAgent;if(/tablet|ipad|playbook|silk/i.test(ua))return"other";if(/mobile|android|iphone|ipod|blackberry|iemobile|opera mini/i.test(ua))return"mobile";return"desktop";}
var device=getDevice();
function track(eventType,widgetId,sessionId){fetch(trackUrl,{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({event_type:eventType,widget_id:widgetId||0,device_type:device,session_id:sessionId||"",page_url:window.location.pathname})}).catch(function(){});}
function getOrCreateSession(){var key="mcs_sid";var sid=sessionStorage.getItem(key);if(!sid){sid=Math.random().toString(36).slice(2)+Date.now().toString(36);sessionStorage.setItem(key,sid);}return sid;}
var sid=getOrCreateSession();
if(!sessionStorage.getItem("mcs_visited")){sessionStorage.setItem("mcs_visited","1");track("visitor",0,sid);}
function setupTracking(){
var bubbles=document.querySelectorAll(".mSupport_bubble .mSupport_button");
if(!bubbles.length)return;
if("IntersectionObserver"in window){var observer=new IntersectionObserver(function(entries){entries.forEach(function(entry){if(entry.isIntersecting){var wid=entry.target.getAttribute("data-widget-id")||0;track("view",wid,sid);observer.unobserve(entry.target);}});},{threshold:0.5});bubbles.forEach(function(el){observer.observe(el);});}
document.addEventListener("click",function(e){
var link=e.target.closest(".better_chat_support_link,.mSupport__send-message,.mSupport_button,.chat-link,.better_chat_support_multi_user");if(link){track("conversion",0,sid);return;}
var anchor=e.target.closest('a[href*="m.me/"],a[href*="messenger.com/"]');if(anchor){track("conversion",0,sid);return;}
},{passive:true});
}
if(document.readyState==="loading"){document.addEventListener("DOMContentLoaded",setupTracking);}else{setupTracking();}
})();</script>
<?php
    }
}
