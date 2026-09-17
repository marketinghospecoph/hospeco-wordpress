<?php
/**
 * @dgwt_wcas_premium_only
 */

namespace DgoraWcas\Integrations\Plugins\WooCommerceSingleVariations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VariationTitleBuilder {

	public function isEnabled(): bool {
		return ! empty( static::getPluginOption( 'variationTitleEnabled' ) );
	}

	public function build( \WC_Product_Variation $variation ): string {
		$customTitle = get_post_meta( $variation->get_id(), 'variation_title', true );
		if ( is_string( $customTitle ) && $customTitle !== '' ) {
			return $customTitle;
		}

		$parentProduct = wc_get_product( $variation->get_parent_id() );
		$baseTitle     = is_a( $parentProduct, 'WC_Product' ) ? $parentProduct->get_name() : $variation->get_name();

		$titleTemplate           = (string) static::getPluginOption( 'variationTitleTemplate', '{title} in {attributes}' );
		$attributesTemplate      = (string) static::getPluginOption( 'variationTitleAttributesTemplate', '{attributes_name} {attribute_values}' );
		$attributeNamesAppendix  = (string) static::getPluginOption( 'variationTitleAttributeNamesAppendix', ' and ' );
		$excludedTitleAttributes = static::getPluginOption( 'excludedTitleAttributes', [] );
		$excludedTitleAttributes = is_array( $excludedTitleAttributes ) ? $excludedTitleAttributes : [];
		$variationAttributes     = $variation->get_attributes();
		$formattedTitles         = [];

		foreach ( $variationAttributes as $attributeKey => $attributeValue ) {
			$attributeKey   = urldecode( (string) $attributeKey );
			$attributeValue = trim( urldecode( (string) $attributeValue ) );

			if ( $attributeValue === '' || in_array( $attributeKey, $excludedTitleAttributes, true ) ) {
				continue;
			}

			$attributeName = $attributeKey;

			if ( taxonomy_exists( $attributeKey ) ) {
				$attributeName = wc_attribute_label( $attributeKey );

				$term = get_term_by( 'slug', $attributeValue, $attributeKey );
				if ( ! is_wp_error( $term ) && ! empty( $term->name ) ) {
					$attributeValue = urldecode( (string) $term->name );
				}
			} elseif ( is_a( $parentProduct, 'WC_Product' ) ) {
				$customAttributes = $parentProduct->get_attributes();
				if ( isset( $customAttributes[ $attributeKey ] ) && is_object( $customAttributes[ $attributeKey ] ) ) {
					$attributeName = (string) $customAttributes[ $attributeKey ]->get_name();
				}
			}

			$formattedTitles[] = str_replace(
				[ '{attributes_name}', '{attribute_values}' ],
				[ $attributeName, $attributeValue ],
				$attributesTemplate
			);
		}

		if ( empty( $formattedTitles ) ) {
			return $baseTitle;
		}

		$title = str_replace(
			[ '{title}', '{attributes}' ],
			[ $baseTitle, implode( $attributeNamesAppendix, $formattedTitles ) ],
			$titleTemplate
		);

		$title = str_replace( [ '&#8211;', '  ' ], [ '', ' ' ], $title );

		return trim( $title );
	}

	private static function getPluginOption( string $key, $default = null ) {
		$options = get_option( 'woocommerce_single_variations_options', [] );
		if ( ! is_array( $options ) ) {
			return $default;
		}

		return array_key_exists( $key, $options ) ? $options[ $key ] : $default;
	}
}
