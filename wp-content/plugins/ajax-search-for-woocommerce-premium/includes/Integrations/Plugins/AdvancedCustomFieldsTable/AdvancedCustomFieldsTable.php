<?php
/**
 * @dgwt_wcas_premium_only
 */

namespace DgoraWcas\Integrations\Plugins\AdvancedCustomFieldsTable;

use DgoraWcas\Engines\TNTSearchMySQL\Config;
use DgoraWcas\Integrations\Plugins\AbstractPluginIntegration;
use DgoraWcas\Integrations\Plugins\AdvancedCustomFields\AdvancedCustomFields;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Integration with Advanced Custom Fields: Table Field
 *
 * Plugin URL: https://wordpress.org/plugins/advanced-custom-fields-table-field/
 * Author: Johann Heyne
 */
class AdvancedCustomFieldsTable extends AbstractPluginIntegration {
	protected const VERSION_CONST = 'ACF_VERSION';
	protected const MIN_VERSION   = '5.8.0';
	protected const LABEL         = 'ACF Table Field';

	public static function isActive(): bool {
		if ( parent::isActive() === false ) {
			return false;
		}

		if ( ! Config::isPluginActive( 'advanced-custom-fields-table-field/acf-table.php' ) ) {
			return false;
		}

		return true;
	}

	public function init(): void {
		add_filter( 'dgwt/wcas/integrations/acf/allowed_field_types', [ $this, 'addFieldType' ] );
		add_filter( 'dgwt/wcas/integrations/acf/field_processed', [ $this, 'processField' ], 10, 5 );

		add_filter( 'dgwt/wcas/indexer/items_row', [ $this, 'processTableData' ] );
		add_filter(
			'dgwt/wcas/tnt/indexer/searchable/product_data',
			[
				$this,
				'processTableDataInDocument',
			],
			10,
			3
		);
	}

	/**
	 * Add 'table' to the list of supported ACF field types
	 *
	 * @param string[] $types
	 *
	 * @return string[]
	 */
	public function addFieldType( $types ) {
		$types[] = 'table';

		return $types;
	}

	/**
	 * Process field and add it to "Search in custom fields" list
	 *
	 * @param boolean $result
	 * @param array $field
	 * @param AdvancedCustomFields $acf
	 * @param string $label
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function processField( $result, $field, $acf, $label, $key ) {
		if ( $result || $field['type'] !== 'table' ) {
			return $result;
		}

		$newLabel      = $label . ' → ' . $field['label'];
		$newKey        = $key . $field['name'];
		$acf->fields[] = [
			'label' => $newLabel,
			'key'   => $newKey,
		];

		return true;
	}

	/**
	 * Process item custom fields and extract table contents
	 *
	 * @param $row
	 *
	 * @return mixed
	 */
	public function processTableData( $row ) {
		foreach ( $row as $key => $value ) {
			if ( strpos( $key, 'cf_' ) === false ) {
				continue;
			}

			// Test if we have ACF Table field
			$data = maybe_unserialize( $value );
			if ( ! isset( $data['acftf'] ) || ! isset( $data['b'] ) ) {
				continue;
			}

			// Replace serialized data with the contents of the table cells
			$row[ $key ] = trim( $this->getTableContent( $data['b'] ), ' |' );
		}

		return $row;
	}

	/**
	 * Process custom fields of single product when it's updated
	 *
	 * @param $document
	 * @param $productID
	 * @param $product
	 *
	 * @return mixed
	 */
	public function processTableDataInDocument( $document, $productID, $product ) {
		return $this->processTableData( $document );
	}

	/**
	 * Get table content from body of 'table' field
	 *
	 * We have to go through every column of every row
	 *
	 * @param $body
	 *
	 * @return string
	 */
	private function getTableContent( $body ) {
		$content = '';
		if ( empty( $body ) || ! is_array( $body ) ) {
			return $content;
		}

		if ( isset( $body['c'] ) ) {
			$content = ' | ' . $body['c'];
		} else {
			foreach ( $body as $item ) {
				$content .= $this->getTableContent( $item );
			}
		}

		return $content;
	}
}
