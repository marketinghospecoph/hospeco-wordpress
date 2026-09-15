<?php

namespace DgoraWcas\Engines\TNTSearchMySQL\Indexer\Taxonomy;

use DgoraWcas\Engines\TNTSearchMySQL\Indexer\AbstractDocument;
use DgoraWcas\Multilingual;

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

		$this->setDocumentType();
		$this->setDocumentLang();
	}

	private function setDocumentType() {
		$data = $this->getData();
		$this->setType( 'tax_' . $data['taxonomy'] );
		$this->unsetDataKey( 'taxonomy' );
	}

	private function setDocumentLang() {
		$data = $this->getData();
		if ( ! empty( $data['lang'] ) ) {
			$this->setLang( $data['lang'] );
			$this->unsetDataKey( 'lang' );
		} else {
			$taxonomy = ltrim( $this->getType(), 'tax_' );
			$this->setLang( Multilingual::isMultilingual() ? Multilingual::getTermLang( $this->getID(), $taxonomy ) : '' );
		}
	}
}
