<?php

namespace DgoraWcas\Engines\TNTSearchMySQL\Support;

// Exit if accessed directly
use DgoraWcas\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SynonymsHandler {

	private $lookup;
	private $synonyms;

	public function __construct() {
	}

	/**
	 * Sets synonyms.
	 *
	 * @param string|null $rawSynonyms Optional raw synonyms data. If null, the synonyms are fetched from the plugin settings.
	 *
	 * @return void
	 */
	public function setSynonyms( ?string $rawSynonyms = null ): void {

		if ( $this->synonyms !== null ) {
			return;
		}

		$groups   = [];
		$synonyms = [];

		/**
		 * When we're in the SHORTINIT context, the list of synonyms is provided via the method parameter.
		 * In other contexts, it is retrieved directly from the plugin settings (search_synonyms).
		 */
		if ( is_null( $rawSynonyms ) ) {
			$rawSynonyms = DGWT_WCAS()->settings->getOption( 'search_synonyms' );
		}

		if ( ! empty( $rawSynonyms ) ) {
			$groups = explode( PHP_EOL, mb_strtolower( $rawSynonyms ) );
			$groups = array_map( 'trim', $groups );
		}

		if ( ! empty( $groups ) ) {
			foreach ( $groups as $group ) {
				$synonyms[] = array_map( 'trim', explode( ',', $group ?? '' ) );
			}
		}

		$this->synonyms = $synonyms;
	}

	/**
	 * Grouped list of synonyms.
	 *
	 * @return array
	 */
	public function getSynonyms(): array {
		return $this->synonyms ?? [];
	}

	/**
	 * Check if synonyms were added.
	 *
	 * @return bool
	 */
	public function hasSynonyms(): bool {
		return ! empty( $this->synonyms );
	}

	/**
	 * Determines if text canonization can be performed.
	 *
	 * @return bool
	 */
	public function canCanonize(): bool {

		$canCanonize = true;

		if ( ! defined( 'DGWT_WCAS_SYN_MAX_COMPLEX_PATTERNS' ) ) {
			define( 'DGWT_WCAS_SYN_MAX_COMPLEX_PATTERNS', 1000 );
		}

		if ( ! defined( 'DGWT_WCAS_SYN_MAX_SINGLE_WORD' ) ) {
			define( 'DGWT_WCAS_SYN_MAX_SINGLE_WORD', 40000 );
		}

		/**
		 * Safety check #1 – perform canonization only if synonyms are present.
		 */
		if ( ! $this->hasSynonyms() || is_null( $this->lookup ) ) {
			$canCanonize = false;
		}

		/**
		 * Safety check #2 – perform a maximum of 1,000 preg_replace() operations.
		 * This number of patterns should add an overhead of approximately 15ms.
		 */
		if ( ! empty( $this->lookup['complex_patterns'] ) && count( $this->lookup['complex_patterns'] ) > DGWT_WCAS_SYN_MAX_COMPLEX_PATTERNS ) {
			$canCanonize = false;
		}

		/**
		 * Safety check #3 – perform a maximum of 40000 operations inside preg_replace_callback.
		 * The operation with this limit should add an overhead of approximately 2ms.
		 */
		if ( ! empty( $this->lookup['single_word'] ) && count( $this->lookup['single_word'] ) > DGWT_WCAS_SYN_MAX_SINGLE_WORD ) {
			$canCanonize = false;
		}

		return $canCanonize;
	}

	/**
	 * Apply synonymous to the text
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	public function applySynonyms( string $text ): string {

		$this->setSynonyms();
		$synonyms = $this->getSynonyms();

		if ( empty( $synonyms ) ) {
			return $text;
		}

		$subject = mb_strtolower( $text );
		$suffix  = '';

		foreach ( $synonyms as $i => $synonymGroup ) {

			foreach ( $synonymGroup as $phrase ) {

				$phrase = Helpers::escPhraseForRegex( $phrase );

				if ( ! empty( $phrase ) && preg_match( "/([^a-zA-Z0-9\p{Cyrillic}]|^)$phrase([^a-zA-Z0-9\p{Cyrillic}}]|$)/i", $subject ) ) {

					$suffix .= ' ' . implode( ' ', $synonymGroup );
					break;
				}
			}
		}

		return $text . $suffix;
	}

	/**
	 * It creates a special structure for storing synonyms,
	 * making it easy to map an entire group of synonyms to the first word in the group.
	 *
	 * @return void
	 */
	public function buildLookup(): void {
		$lookup = [];

		$this->lookup = [
			'single_word'          => [],
			'complex_patterns'     => [],
			'complex_replacements' => [],
		];

		foreach ( $this->getSynonyms() as $group ) {
			foreach ( $group as $phrase ) {
				// The representative, meaning the main synonym, is the first synonym in the group.
				$lookup[ mb_strtolower( $phrase ) ] = mb_strtolower( $group[0] );
			}
		}

		foreach ( $lookup as $from => $to ) {

			/**
			 * We need to collect single-word synonyms into a separate array because we can apply a batch preg_replace_callback to them.
			 * This function is much more efficient than preg_replace, which will be used for multi-word synonyms.
			 */
			if ( preg_match( '/^[\p{L}\p{N}]+$/u', $from ) ) {
				$this->lookup['single_word'][ $from ] = $to;
				continue;
			}

			/**
			 * For multi-word synonyms, we need to create an array of patterns and their corresponding replacement words.
			 * In this case, the less efficient preg_replace function will be used.
			 */
			$this->lookup['complex_patterns'][]     = '/(?<![\p{L}\p{N}])' . preg_quote( $from, '/' ) . '(?![\p{L}\p{N}])/u';
			$this->lookup['complex_replacements'][] = $to;
		}
	}

	/**
	 * Replaces all occurrences of synonyms in the text with a single selected synonym that serves as the representative.
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	public function canonize( string $text ): string {

		if ( ! $this->canCanonize() ) {
			return $text;
		}

		$text = mb_strtolower( $text );

		if ( ! empty( $this->lookup['complex_patterns'] ) && ! empty( $this->lookup['complex_replacements'] ) ) {
			$text = preg_replace(
				$this->lookup['complex_patterns'],
				$this->lookup['complex_replacements'],
				$text
			);
		}

		if ( ! empty( $this->lookup['single_word'] ) ) {
			$text = preg_replace_callback(
				'/[\p{L}\p{N}]+/u',
				fn( $m ) => $this->lookup['single_word'][ $m[0] ] ?? $m[0],
				$text
			);
		}

		return $text;
	}
}
