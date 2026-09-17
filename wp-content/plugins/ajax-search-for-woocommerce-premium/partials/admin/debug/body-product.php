<?php

use DgoraWcas\Helpers;

// Exit if accessed directly
if ( ! defined( 'DGWT_WCAS_FILE' ) ) {
	exit;
}

$nonceValid = ! empty( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'dgwt_wcas_debug_product' );
$productID  = $nonceValid && ! empty( $_GET['product_id'] ) ? $_GET['product_id'] : '';
$lang       = ! empty( $_GET['lang'] ) ? $_GET['lang'] : '';

if ( ! empty( $productID ) ) {
	$p = new \DgoraWcas\Engines\TNTSearchMySQL\Debug\Product( $productID, $lang );

	$readableIndexData = $p->getReadableIndexData();
	$wordlist          = $p->getSearchableIndexData();
	$wordlistSQL       = $p->getDataForIndexingBySource();
}

?>

<h3>Product debug</h3>
<form action="<?php echo admin_url( 'admin.php' ); ?>" method="get">
	<input
		type="hidden" name="page" value="dgwt_wcas_debug">
	<?php wp_nonce_field( 'dgwt_wcas_debug_product', '_wpnonce', false ); ?>
	<input
		type="text" class="regular-text" id="dgwt-wcas-debug-product" name="product_id"
		value="<?php echo esc_html( $productID ); ?>" placeholder="Product ID">
	<input
		type="text" class="small-text" id="dgwt-wcas-debug-search-lang" name="lang"
		value="<?php echo esc_html( $lang ); ?>" placeholder="lang">
	<button class="button" type="submit">Debug</button>
</form>

<?php if ( ! empty( $productID ) && ! $p->product->isValid() ) : ?>
	<p>Wrong product ID</p>
<?php endif; ?>

<?php
if ( ! empty( $productID ) && $p->product->isValid() ) :

	$catVisByCrud      = $p->product->getVisibilityByCRUD__premium_only();
	$catVisByTerms     = $p->product->getVisibilityByTerms__premium_only( 'catalog_visibility' );
	$inStock           = $p->product->getWooObject()->is_in_stock();
	$stockVisByTerms   = $p->product->getVisibilityByTerms__premium_only( 'stock_status' );
	$indexability      = $p->product->checkIndexability__premium_only();
	$canIndex          = $indexability['canIndex'];
	$notIndexingReason = $indexability['notIndexingReason'];
	?>

	<table class="wc_status_table widefat" cellspacing="0">
		<thead>
		<tr>
			<th colspan="2" data-export-label="Searchable Index"><h3>General</h3></th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td><b>Can index: </b></td>
			<td><?php echo $canIndex ? 'yes' : 'no'; ?></td>
		</tr>
		<?php if ( ! $canIndex && ! empty( $notIndexingReason ) ) : ?>
			<tr>
				<td><b>Reason for not indexing:</b></td>
				<td><?php echo esc_html( $notIndexingReason ); ?></td>
			</tr>
		<?php endif; ?>
		<tr>
			<td><b>Catalog visibility</b></td>
			<td>
				<p>
					<b>By WooCommerce CRUD (<code>WC_Product::get_catalog_visibility()</code>)</b>
					<br/>
					<?php echo esc_html( $catVisByCrud['label'] ); ?>
				</p>
				<hr/>
				<p>
					<b>By term_relationships</b>
				</p>
				<ul>
					<?php
					foreach ( $catVisByTerms as $key => $value ) {
						echo '<li>' . $key . ' (assigned to term ID: ' . $value . ')</li>';
					}
					if ( empty( $catVisByTerms ) ) {
						echo 'nothing stored in the DB';
					}
					?>
				</ul>
				</p>
				</td>
		</tr>
		<tr>
			<td><b>Visibility validation </b></td>
			<td>
			<?php

			if ( array_diff( Helpers::catalogVisibilityMap( $catVisByCrud['key'] ), array_keys( $catVisByTerms ) ) == [] ) {
				echo '<span style="color: #4caf50"><b>ok</b></span>';
			} else {
				echo '<span style="color: #d63638"><b>Wrong!!!</b></span> WooCommerce <code>WC_Product::get_catalog_visibility()</code> return other information than is stored in term_relationships';
			}

			?>
				</td>
		</tr>
		<tr>
			<td><b>Stock status</b></td>
			<td>
				<p>
					<b>By WooCommerce CRUD (<code>WC_Product::is_in_stock()</code>)</b>
					<br/>
					<?php echo 'in stock: ' . esc_html( $inStock ? 'true' : 'false' ); ?>
				</p>
				<hr/>
				<p>
					<b>By term_relationships</b>
				</p>
				<ul>
					<?php
					foreach ( $stockVisByTerms as $key => $value ) {
						echo '<li>' . $key . ' (assigned to term ID: ' . $value . ')</li>';
					}
					if ( empty( $stockVisByTerms ) ) {
						echo 'nothing stored in the DB';
					}
					?>
				</ul>
				</p>
				</td>
		</tr>
		<tr>
			<td><b>Stock status validation</b></td>
			<td>
			<?php

			if ( ! $inStock && in_array( 'outofstock', array_keys( $stockVisByTerms ) ) ) {
				echo '<span style="color: #4caf50"><b>ok</b></span>';
			} elseif ( $inStock && empty( $stockVisByTerms ) ) {
				echo '<span style="color: #4caf50"><b>ok</b></span>';
			} else {
				echo '<span style="color: #d63638"><b>Wrong!!!</b></span> WooCommerce <code>WC_Product::is_in_stock()</code> return other information than is stored in term_relationships';
			}

			?>
				</td>
		</tr>
		</tbody>
	</table>

	<table class="wc_status_table widefat" cellspacing="0">
		<thead>
		<tr>
			<th colspan="2" data-export-label="Searchable Index"><h3>Readable Index (stored in the database)</h3></th>
		</tr>
		</thead>
		<tbody>

		<?php

		foreach ( $readableIndexData as $key => $data ) :
			?>
			<tr>
				<td><b><?php echo $key; ?>: </b></td>
				<td><?php echo esc_html( $data ); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>

	<table class="wc_status_table widefat" cellspacing="0">
		<thead>
		<tr>
			<th colspan="2" data-export-label="Searchable Index"><h3>Searchable Index (stored in the database)</h3></th>
		</tr>
		</thead>
		<tbody>

		<tr>
			<td><b>Total terms:</b></td>
			<td><?php echo count( $wordlist ); ?></td>
		</tr>

		<tr>
			<td><b>Wordlist: </b></td>
			<td class="dgwt-wcas-table-wordlist">
				<p>
					<?php foreach ( $wordlist as $term ) : ?>
						<?php echo $term . '<br />'; ?>
					<?php endforeach; ?>
				</p>
			</td>
		</tr>
		</tbody>
	</table>

<?php endif; ?>
