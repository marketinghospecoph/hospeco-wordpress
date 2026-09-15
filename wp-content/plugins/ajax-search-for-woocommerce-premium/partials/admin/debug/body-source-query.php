<?php

use DgoraWcas\Engines\TNTSearchMySQL\Indexer\SourceQuery;

// Exit if accessed directly
if ( ! defined( 'DGWT_WCAS_FILE' ) ) {
	exit;
}
?>
	<h3>Source query (IDs)</h3>
	<form action="<?php echo admin_url( 'admin.php' ); ?>" method="get">
		<input type="hidden" name="page" value="dgwt_wcas_debug">
		<?php wp_nonce_field( 'dgwt_wcas_debug_source_query', '_wpnonce', false ); ?>
		<input type="hidden" name="source_query" value="1">
		<button class="button" type="submit">Get source query</button>
	</form>

	<h3>Source query (Content)</h3>
	<p>Displays the raw SQL used to fetch content for selected products that will serve as the source for indexing in
		the searchable index. Also displays a table with the results.</p>
	<form action="<?php echo admin_url( 'admin.php' ); ?>" method="get">
		<input type="hidden" name="page" value="dgwt_wcas_debug">
		<?php wp_nonce_field( 'dgwt_wcas_debug_source_query', '_wpnonce', false ); ?>
		<input type="hidden" name="source_query_content" value="1">
		<input
				type="text"
				name="source_query_content_ids"
				placeholder="Product IDs, separated by commas"
				value="
				<?php
				//phpcs:ignore WordPress.Security.NonceVerification.Recommended
				echo isset( $_GET['source_query_content_ids'] ) ? htmlspecialchars( $_GET['source_query_content_ids'] ) : '';
				?>
				"
		>
		<button class="button" type="submit">Show raw SQL and output</button>
	</form>
<?php

if (
	! empty( $_GET['source_query'] ) &&
	! empty( $_REQUEST['_wpnonce'] ) &&
	wp_verify_nonce( $_REQUEST['_wpnonce'], 'dgwt_wcas_debug_source_query' )
) :

	$source = new SourceQuery( [ 'ids' => true ] );

	$products = $source->getData();
	$query    = $source->getQuery();
	?>
<table class="wc_status_table widefat">
	<tr>
		<td><b>Total Products</b></td>
		<td><?php echo count( $source->getData() ); ?></td>
	</tr>
</table>
<h3>SQL (IDs)</h3>
<pre>
	<?php echo $query; ?>
</pre>
	<?php

endif;

if (
	! empty( $_GET['source_query_content'] ) &&
	! empty( $_GET['source_query_content_ids'] ) &&
	! empty( $_REQUEST['_wpnonce'] ) &&
	wp_verify_nonce( $_REQUEST['_wpnonce'], 'dgwt_wcas_debug_source_query' )
) :
	$rawIds = explode( ',', $_GET['source_query_content_ids'] );
	$intIds = array_map( 'intval', $rawIds );

	$source = new SourceQuery(
		[
			'package' => $intIds,
		]
	);

	$products = $source->getData();
	$query    = $source->getQuery();

	?>
	<h3>SQL (Content)</h3>
	<pre>
	<?php echo $query; ?>
</pre>
	<?php
	foreach ( $products as $index => $product ) {
		echo '<h2>' . htmlspecialchars( $product['ID'] ) . '</h2>';
		echo "<table class='wc_status_table widefat'>";
		echo '<tbody>';
		foreach ( $product as $key => $value ) {
			echo "<tr><td><strong>{$key}</strong></td><td>" . nl2br( htmlspecialchars( $value ) ) . '</td></tr>';
		}
		echo '</tbody></table>';
	}
	?>
	<?php

endif;
