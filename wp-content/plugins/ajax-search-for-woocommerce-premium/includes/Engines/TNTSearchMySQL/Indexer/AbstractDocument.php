<?php

namespace DgoraWcas\Engines\TNTSearchMySQL\Indexer;

use DgoraWcas\Engines\TNTSearchMySQL\Support\Stemmer\StemmerInterface;
use DgoraWcas\Engines\TNTSearchMySQL\Support\Tokenizer\TokenizerInterface;
use DgoraWcas\Engines\TNTSearchMySQL\Support\SynonymsHandler;
use DgoraWcas\Helpers;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class AbstractDocument {
	private $lang = '';

	private $type = '';

	private $ID = 0;

	/**@var int|array */
	private $data;

	private $config;

	/** @var array */
	protected $dataToIndex;

	/** @var StemmerInterface */
	private $stemmer;

	/** @var TokenizerInterface */
	private $tokenizer;

	/** @var SynonymsHandler */
	private $synonymsHandler;

	/** @var string */
	private $indexRole;

	public function __construct( $data, $config = [] ) {
		if ( is_numeric( $data ) ) {
			$data = [ 'ID' => intval( $data ) ];
		}

		$this->data   = $data;
		$this->config = $config;
		$this->setID();

		$this->setDataToIndex( $this->getData() );
	}

	private function setID() {
		if ( ! empty( $this->data['ID'] ) ) {
			$this->ID = (int) $this->data['ID'];
			unset( $this->data['ID'] );
		}
	}

	protected function unsetDataKey( $key ) {
		if ( isset( $this->data[ $key ] ) ) {
			unset( $this->data[ $key ] );
			$this->setDataToIndex( $this->getData() );
		}
	}

	/**
	 * @return int
	 */
	public function getID() {
		return $this->ID;
	}

	/**
	 * @return string
	 */
	public function getLang() {
		return $this->lang;
	}

	public function setLang( $lang ) {
		//phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found
		return $this->lang = $lang;
	}

	/**
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}

	public function setType( $type ) {
		$this->type = $type;
	}

	/**
	 * @return array
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * @return array
	 */
	public function getDataToIndex() {
		return $this->dataToIndex;
	}

	protected function setDataToIndex( $data ) {
		$this->dataToIndex = $data;
	}

	/**
	 * @return array
	 */
	public function getConfig() {
		return $this->config;
	}

	/**
	 * Get table name
	 *
	 * @param string $type | searchable_wordlist
	 *                     | searchable_doclist
	 *                     | searchable_info
	 *                     | searchable_cache
	 *                     | vendors
	 *                     | variations
	 *                     | taxonomy
	 *                     | readable
	 *
	 * @return string
	 */
	public function getTableName( $type ) {
		return Utils::getTableName( $type, $this->getLang() );
	}

	/**
	 * @param SynonymsHandler $synonymsHandler
	 */
	public function setSynonymsHandler( SynonymsHandler $synonymsHandler ) {
		$this->synonymsHandler = $synonymsHandler;
	}

	/**
	 * @return SynonymsHandler
	 */
	public function getSynonymsHandler() {
		return $this->synonymsHandler;
	}

	/**
	 * @param TokenizerInterface $tokenizer
	 */
	public function setTokenizer( TokenizerInterface $tokenizer ) {
		$this->tokenizer = $tokenizer;
	}

	/**
	 * @return TokenizerInterface
	 */
	public function getTokenizer() {
		return $this->tokenizer;
	}

	/**
	 * @param StemmerInterface $stemmer
	 */
	public function setStemmer( StemmerInterface $stemmer ) {
		$this->stemmer = $stemmer;
	}

	/**
	 * @return StemmerInterface
	 */
	public function getStemmer() {
		return $this->stemmer;
	}

	/**
	 * @param string $indexRole
	 */
	public function setIndexRole( string $indexRole ) {
		$this->indexRole = $indexRole;
	}

	/**
	 * Save the document data to the index
	 *
	 * @return void
	 * @throws WPDBException
	 */
	public function save() {
		$this->prepareDataToIndex();
		$this->saveDataToIndex();
	}

	/**
	 * @throws WPDBException
	 */
	public function update() {
		$this->delete();
		$this->save();
	}

	/**
	 * @throws WPDBException
	 */
	public function delete() {
		$doclistTable  = $this->getTableName( 'searchable_doclist' );
		$wordlistTable = $this->getTableName( 'searchable_wordlist' );

		WPDB::get_instance()->query(
			WPDB::get_instance()->prepare(
				"
				DELETE FROM $doclistTable
				WHERE $doclistTable.doc_id = %d
                AND $doclistTable.term_id IN (
                    SELECT id FROM $wordlistTable WHERE $wordlistTable.type = %s
                )
				",
				$this->getID(),
				$this->getType()
			)
		);

		WPDB::get_instance()->query(
			"
			DELETE FROM $wordlistTable
			WHERE $wordlistTable.id NOT IN (
			    SELECT term_id FROM $doclistTable
		    )"
		);
	}

	public function getWordList() {
		global $wpdb;

		$doclistTable  = $this->getTableName( 'searchable_doclist' ) . ( $this->indexRole === 'tmp' ? '_tmp' : '' );
		$wordlistTable = $this->getTableName( 'searchable_wordlist' ) . ( $this->indexRole === 'tmp' ? '_tmp' : '' );

		$sql = "SELECT wordlist.term
             	FROM $doclistTable doclist
             	INNER JOIN $wordlistTable wordlist ON doclist.term_id = wordlist.id
            	WHERE doclist.doc_id = %d
            	AND wordlist.type = %s
                ORDER BY wordlist.term ASC";

		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$query = $wpdb->prepare( $sql, $this->getID(), $this->getType() );

		return (array) $wpdb->get_results( $query, ARRAY_A );
		//phpcs:enable
	}

	/**
	 * Prepare document data for indexing
	 *
	 * @return void
	 */
	public function prepareDataToIndex() {
		$this->setDataToIndex( $this->getData() );
		$this->processData();
	}

	/**
	 * Proper operation of saving a document to the index
	 *
	 * @return void
	 * @throws WPDBException
	 */
	protected function saveDataToIndex() {
		$termIds = $this->saveWordlist();
		$this->saveDoclist( $termIds );
	}

	/**
	 * Process data to index
	 *
	 * @return void
	 */
	protected function processData() {
		if ( empty( $this->dataToIndex ) ) {
			return;
		}

		if ( empty( $this->tokenizer ) || empty( $this->stemmer ) || empty( $this->synonymsHandler ) ) {
			return;
		}

		foreach ( $this->dataToIndex as $key => $value ) {
			$this->dataToIndex[ $key ] = $this->processText( $key, $value );
		}
	}

	/**
	 * Process single line of text
	 *
	 * @param string $key
	 * @param string $text
	 *
	 * @return array
	 */
	private function processText( $key, $text ) {
		$text      = Utils::clearContent( $text );
		$text      = Utils::appendJoinedText( $key, $text );
		$text      = Utils::replaceSpecialCharacters( $text );
		$text      = $this->synonymsHandler->applySynonyms( $text );
		$stopwords = apply_filters( 'dgwt/wcas/indexer/searchable/stopwords', [], $this->getType(), $this->getID(), $this->getLang() );
		$words     = $this->tokenizer->tokenize( $text, $stopwords );
		$stems     = [];
		foreach ( $words as $word ) {
			if ( $word !== '' ) {
				$stems[] = $this->stemmer->stem( $word );
			}
		}
		if ( ! empty( $stems ) && apply_filters( 'dgwt/wcas/indexer/searchable/convert_to_greeklish', true ) ) {
			foreach ( $stems as $stem ) {
				if ( Helpers::isGreekText__premium_only( $stem ) ) {
					$greeklish = Helpers::convertToGreeklish__premium_only( Helpers::removeGreekAccents( $stem ) );
					if ( is_string( $greeklish ) && $greeklish !== $stem ) {
						$stems[] = $greeklish;
					}
				}
			}
		}

		return $stems;
	}

	/**
	 * Save words
	 *
	 * @return array
	 * @throws WPDBException
	 */
	private function saveWordlist() {
		$termIds  = [];
		$allWords = [];

		$table = $this->getTableName( 'searchable_wordlist' ) . ( $this->indexRole === 'tmp' ? '_tmp' : '' );

		// Counting hits for every word
		array_map(
			function ( $column ) use ( &$allWords ) {
				foreach ( $column as $word ) {
					if ( array_key_exists( $word, $allWords ) ) {
						$allWords[ $word ]['hits'] ++;
					} else {
						$allWords[ $word ] = [
							'hits' => 1,
						];
					}
				}
			},
			$this->dataToIndex
		);

		foreach ( $allWords as $key => $word ) {
			$term = WPDBSecond::get_instance()->get_row(
				WPDBSecond::get_instance()->prepare(
					"
                    SELECT id, num_hits
                    FROM $table
                    WHERE term = %s
                    AND type = %s
                    ",
					$key,
					$this->getType()
				),
				ARRAY_A
			);

			if ( empty( $term ) ) {
				WPDBSecond::get_instance()->insert(
					$table,
					[
						'term'     => $key,
						'type'     => $this->getType(),
						'num_hits' => $word['hits'],
					],
					[
						'%s',
						'%s',
						'%d',
					]
				);

				if ( ! empty( WPDBSecond::get_instance()->db->insert_id ) ) {
					$termIds[] = WPDBSecond::get_instance()->db->insert_id;
				}
			} else {
				$termIds[] = (int) $term['id'];

				WPDBSecond::get_instance()->update(
					$table,
					[
						'num_hits' => $word['hits'] + (int) $term['num_hits'],
					],
					[
						'id' => $term['id'],
					],
					[
						'%d',
					],
					[
						'%d',
					]
				);
			}
		}

		return $termIds;
	}

	/**
	 * Save docs
	 *
	 * @param int[] $termIds
	 *
	 * @return void
	 * @throws WPDBException
	 */
	private function saveDoclist( $termdIds ) {
		$table = $this->getTableName( 'searchable_doclist' ) . ( $this->indexRole === 'tmp' ? '_tmp' : '' );

		foreach ( $termdIds as $termId ) {
			$data = [
				'term_id' => $termId,
				'doc_id'  => $this->getID(),
			];

			$format = [
				'%d',
				'%d',
			];

			WPDBSecond::get_instance()->insert( $table, $data, $format );
		}
	}
}
