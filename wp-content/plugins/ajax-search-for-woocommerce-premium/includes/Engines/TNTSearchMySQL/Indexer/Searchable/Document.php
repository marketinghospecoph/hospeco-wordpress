<?php

namespace DgoraWcas\Engines\TNTSearchMySQL\Indexer\Searchable;

use DgoraWcas\Engines\TNTSearchMySQL\Indexer\AbstractDocument;
use DgoraWcas\Multilingual;
use DgoraWcas\Product;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Document extends AbstractDocument {
	/**
	 * @param int|array $data Post ID or array with it's data.
	 * @param array $config
	 */
	public function __construct( $data, $config = [] ) {
		parent::__construct( $data, $config );

		$this->setDocumentPostType();
		$this->setDocumentLang();
	}

	private function setDocumentPostType() {
		$data = $this->getData();
		if ( ! empty( $data['post_type'] ) ) {
			$this->setType( $data['post_type'] );
			$this->unsetDataKey( 'post_type' );
		} else {
			$this->setType( get_post_type( $this->getID() ) );
		}
	}

	private function setDocumentLang() {
		$data = $this->getData();
		if ( ! empty( $data['lang'] ) ) {
			$this->setLang( $data['lang'] );
			$this->unsetDataKey( 'lang' );
		} else {
			$this->setLang( Multilingual::isMultilingual() ? Multilingual::getPostLang( $this->getID(), $this->getType() ) : '' );
		}
	}

	public function prepareDataToIndex() {
		$this->setDataToIndex( $this->getData() );
		$this->applyCustomAttributes();
		$this->processData();
	}

	/**
	 * Add custom attributes values to index data
	 *
	 * @return void
	 */
	private function applyCustomAttributes() {
		$config = $this->getConfig();

		if ( isset( $config['scope']['attributes'] ) && $config['scope']['attributes'] ) {
			$customAttributesValues = Product::getCustomAttributes( $this->getID() );
			if ( ! empty( $customAttributesValues ) ) {
				$sep = ' | ';
				if ( ! isset( $this->dataToIndex['custom_attributes'] ) ) {
					$this->dataToIndex['custom_attributes'] = '';
					$sep                                    = '';
				}

				$this->dataToIndex['custom_attributes'] .= $sep . implode( ' | ', $customAttributesValues );
			}
		}
	}
}
