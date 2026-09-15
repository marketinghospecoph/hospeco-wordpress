<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

$current = isset($_GET['list_type']) ? sanitize_text_field($_GET['list_type']) : 'email';

$types = [
    'email' => 'Email Blacklist',
    'ip'    => 'IP Blacklist'
];

// Pagination
$items_per_page = 10;
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($current_page - 1) * $items_per_page;

// Get rows with paging
$rows = Pi_dpmw_Blocklist_DB::get_rows($current, $items_per_page, $offset);

// Get total rows count for current type
$total_items = Pi_dpmw_Blocklist_DB::count_rows($current); // You’ll add this method
$total_pages = ceil($total_items / $items_per_page);
?>

<div id="row_title" class="pisol-form-element-row row py-4 border-bottom align-items-center  bg-dark2 text-light field-type-setting_category">
    <div class="pisol-form-label-col col-md-12">
        <h2 class="pisol-field-title mt-0 mb-0 text-light font-weight-light h4">Blacklister Email/IP cant place order</h2>
    </div>
</div>

<div class="row">
    <div class="col-7 my-3">
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="d-flex align-items-center">
            <?php wp_nonce_field('piws_blacklist_add_entry', 'piws_blacklist_nonce'); ?>
            <input type="hidden" name="action" value="piws_blacklist_add">
            <div class="flex-grow-1 mr-1">
                <input type="text" name="blacklist_value" id="blacklist_value" class="form-control" required placeholder="Add <?php echo esc_attr($current); ?> address">
            </div>

            <div class="flex-grow-1 mr-1">
                <input type="text" name="blacklist_note" id="blacklist_note" class="form-control" placeholder="<?php esc_html_e('Note (optional)', 'disable-payment-method-for-woocommerce'); ?>">
            </div>

            <input type="hidden" name="blacklist_type" value="<?php echo esc_attr($current); ?>">

            <div>
                <button type="submit" class="btn btn-primary">
                    <span class="dashicons dashicons-plus" style="margin-top: 4px;"></span> <?php esc_html_e('Add', 'disable-payment-method-for-woocommerce'); ?>
                </button>
            </div>
        </form>

    </div>
    <div class="col-5 my-3 text-right">
        <div class="btn-group" role="group" aria-label="Blacklist type selector">
            <?php foreach ($types as $key => $label): ?>
                <a href="<?php echo esc_url(add_query_arg([
                                'tab'       => 'blacklist',
                                'list_type' => $key
                            ])); ?>"
                    class="btn btn-outline-primary <?php echo $current === $key ? 'active' : ''; ?>">
                    <?php echo esc_html($label); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php
if (isset($_GET['message'])) {
    if (isset($_GET['status']) && $_GET['status'] === 'warning') {
        echo '<div class="alert alert-danger my-3"><p class="mb-0">' . esc_html($_GET['message']) . '</p></div>';
    } else {
        echo '<div class="alert alert-success my-3"><p class="mb-0">' . esc_html($_GET['message']) . '</p></div>';
    }
}
?>
<div class="row">
    <div class="col-12 mb-3">
        <div id="pisol-dpmw-disable-rules-list-view">
            <table class="table table-striped">
                <thead>
                    <tr class="afrsm-head">
                        <th><?php esc_html_e('Type', 'disable-payment-method-for-woocommerce'); ?></th>
                        <th><?php esc_html_e('Value', 'disable-payment-method-for-woocommerce'); ?></th>
                        <th><?php esc_html_e('Note', 'disable-payment-method-for-woocommerce'); ?></th>
                        <th><?php esc_html_e('Time', 'disable-payment-method-for-woocommerce'); ?></th>
                        <th><?php esc_html_e('Actions', 'disable-payment-method-for-woocommerce'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($rows)): ?>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td style="text-transform: capitalize;"><?php echo esc_html($row['type']); ?></td>
                                <td><?php echo esc_html($row['value']); ?></td>
                                <td><?php echo esc_html($row['note']); ?></td>
                                <td><?php echo esc_html($row['created_at']); ?></td>
                                <td>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline;">
                                        <?php wp_nonce_field('piws_blacklist_delete_entry_' . $row['id'], 'piws_blacklist_delete_nonce'); ?>
                                        <input type="hidden" name="action" value="piws_blacklist_delete">
                                        <input type="hidden" name="id" value="<?php echo esc_attr($row['id']); ?>">
                                        <input type="hidden" name="type" value="<?php echo esc_attr($row['type']); ?>">
                                        <button type="submit" class="btn btn-primary btn-sm"
                                            onclick="return confirm('Are you sure you want to delete this entry?');">
                                            <span class="dashicons dashicons-trash "></span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center"><?php esc_html_e('No entries found.', 'disable-payment-method-for-woocommerce'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
            <nav>
                <ul class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $current_page == $i ? 'active' : ''; ?>">
                            <a class="page-link" href="<?php
                                                        echo esc_url(add_query_arg([
                                                            'tab'       => 'blacklist',
                                                            'list_type' => $current,
                                                            'paged'     => $i
                                                        ]));
                                                        ?>">
                                <?php echo esc_html($i); ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>