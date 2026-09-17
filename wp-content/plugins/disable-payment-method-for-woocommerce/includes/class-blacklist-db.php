<?php 

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Pi_dpmw_Blocklist_DB {

    const DB_VERSION = '1.0.0';
    const DB_VERSION_OPTION = 'pi_dpmw_blocklist_db_version';

    public static function install() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'pi_dpmw_order_blocklist';
        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            type VARCHAR(20) NOT NULL,
            value TEXT NOT NULL,
            note TEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY type_key (type)
        ) $charset_collate;";

        dbDelta($sql);

        update_option(self::DB_VERSION_OPTION, self::DB_VERSION);
    }

    // Optional future upgrade logic
    public static function maybe_upgrade() {
        $installed_version = get_option(self::DB_VERSION_OPTION);
        if ($installed_version !== self::DB_VERSION) {
            self::install();
        }
    }

    public static function add_row($type, $value, $note = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'pi_dpmw_order_blocklist';

        return $wpdb->insert(
            $table,
            [
                'type' => $type,
                'value' => $value,
                'note' => $note,
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s', '%s']
        );
    }

    public static function bulk_add_rows(array $items) {
        global $wpdb;
        $table = $wpdb->prefix . 'pi_dpmw_order_blocklist';
        $rows_added = 0;

        foreach ($items as $item) {
            if (!isset($item['type']) || !isset($item['value'])) {
                continue;
            }

            $wpdb->insert(
                $table,
                [
                    'type' => sanitize_text_field($item['type']),
                    'value' => sanitize_text_field($item['value']),
                    'note' => isset($item['note']) ? sanitize_text_field($item['note']) : '',
                    'created_at' => current_time('mysql')
                ],
                ['%s', '%s', '%s', '%s']
            );
            $rows_added++;
        }

        return $rows_added;
    }

    public static function delete_row($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'pi_dpmw_order_blocklist';

        return $wpdb->delete(
            $table,
            ['id' => intval($id)],
            ['%d']
        );
    }

    public static function is_blocked($type, $value) {
        global $wpdb;
        $table = $wpdb->prefix . 'pi_dpmw_order_blocklist';

        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE type = %s AND value = %s",
                $type, $value
            )
        ) > 0;
    }

    public static function get_rows($type, $limit = 20, $offset = 0) {
        global $wpdb;
        $table = $wpdb->prefix . 'pi_dpmw_order_blocklist';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE type = %s ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $type, $limit, $offset
            ),
            ARRAY_A
        );
    }

    public static function count_rows($type) {
        global $wpdb;
        $table = $wpdb->prefix . 'pi_dpmw_order_blocklist';

        return (int) $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM $table WHERE type = %s", $type)
        );
    }


}
