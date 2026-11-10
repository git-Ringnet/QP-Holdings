<?php
defined( 'ABSPATH' ) || exit;

final class QP_LLM_Sync {
	public static function is_translation_copy( int $post_id ): bool {
		if ( get_post_meta( $post_id, '_qp_llm_is_translation', true ) ) {
			return true;
		}
		if ( get_post_meta( $post_id, '_qp_translation_lang', true ) ) {
			return true;
		}
		return false;
	}

	/**
	 * ACF/meta keys the careers listing and job detail page actually render.
	 *
	 * @return string[]
	 */
	public static function job_meta_keys(): array {
		return array(
			'job_display_title',
			'job_summary',
			'department',
			'location',
			'workplace',
			'level',
			'experience',
			'salary',
			'reports_to',
			'job_general_info',
			'job_goal',
			'job_main_responsibilities',
			'job_requirements_detail',
			'job_benefits_detail',
		);
	}
	/** @return string[] */
	public static function news_meta_keys(): array {
		return array( 'author_name', 'custom_datetime', 'category_info', 'source' );
	}

	/** @return string[] */
	public static function project_meta_keys(): array {
		return array(
			'subtitle',
			'display_title',
			'category_info',
			'address',
			'scale',
			'area_total',
			'investment_total',
			'investor_name',
			'developer',
			'completion_year',
			'handover',
			'highlights',
			'hero_heading',
		);
	}


	/**
	 * Fields sent to the LLM. Include the text metadata rendered by each post type.
	 *
	 * @return array<string, string>
	 */
	public static function collect_fields( WP_Post $post ): array {
		$fields = array(
			'title'   => (string) $post->post_title,
			'excerpt' => (string) $post->post_excerpt,
			'content' => (string) $post->post_content,
		);
		if ( 'qp_job' === $post->post_type ) {
			$meta_keys = self::job_meta_keys();
		} elseif ( 'qp_news' === $post->post_type ) {
			$meta_keys = self::news_meta_keys();
		} elseif ( 'qp_project' === $post->post_type ) {
			$meta_keys = self::project_meta_keys();
		} elseif ( 'qp_investor_doc' === $post->post_type ) {
			$count = (int) get_post_meta( (int) $post->ID, 'doc_files', true );
			for ( $i = 0; $i < $count; $i++ ) {
				$val = (string) get_post_meta( (int) $post->ID, 'doc_files_' . $i . '_title', true );
				if ( '' !== trim( $val ) ) {
					$fields[ 'doc_files_' . $i . '_title' ] = $val;
				}
			}
			return $fields;
		} else {
			return $fields;
		}

		foreach ( $meta_keys as $meta_key ) {
			$value = get_post_meta( (int) $post->ID, $meta_key, true );
			if ( is_string( $value ) && '' !== trim( $value ) ) {
				$fields[ $meta_key ] = $value;
			}
		}
		if ( 'qp_job' === $post->post_type ) {
			if ( ! empty( $fields['job_display_title'] ) ) {
				$fields['title'] = $fields['job_display_title'];
			}
			if ( '' === trim( $fields['excerpt'] ) && ! empty( $fields['job_summary'] ) ) {
				$fields['excerpt'] = $fields['job_summary'];
			}
		}
		return $fields;
	}

	/**
	 * Write translated text metadata after the VI meta clone.
	 *
	 * @param array<string, string> $translated
	 */
	public static function apply_translated_meta( int $target_id, array $translated ): void {
		$post_type = get_post_type( $target_id );
		if ( 'qp_job' === $post_type ) {
			$meta_keys = self::job_meta_keys();
		} elseif ( 'qp_news' === $post_type ) {
			$meta_keys = self::news_meta_keys();
		} elseif ( 'qp_project' === $post_type ) {
			$meta_keys = self::project_meta_keys();
		} elseif ( 'qp_investor_doc' === $post_type ) {
			$count = (int) get_post_meta( $target_id, 'doc_files', true );
			for ( $i = 0; $i < $count; $i++ ) {
				$key = 'doc_files_' . $i . '_title';
				if ( isset( $translated[ $key ] ) && is_string( $translated[ $key ] ) && '' !== trim( $translated[ $key ] ) ) {
					update_post_meta( $target_id, $key, $translated[ $key ] );
				}
			}
			return;
		} else {
			return;
		}
		foreach ( $meta_keys as $meta_key ) {
			if ( ! isset( $translated[ $meta_key ] ) || ! is_string( $translated[ $meta_key ] ) ) {
				continue;
			}
			update_post_meta( $target_id, $meta_key, $translated[ $meta_key ] );
		}
	}


	public static function existing_translation( WP_Post $source, string $lang ): int {
		$existing = function_exists( 'pll_get_post' ) ? (int) pll_get_post( (int) $source->ID, $lang ) : 0;
		return $existing ?: self::find_by_source_meta( $source, $lang );
	}

	public static function upsert_translation( WP_Post $source, string $lang, array $translated, bool $manual ): int {
		$source_id = (int) $source->ID;
		$title     = isset( $translated['title'] ) ? trim( (string) $translated['title'] ) : '';
		if ( '' === $title ) {
			$title = (string) $source->post_title;
		}

		$post_lang = '';
		if ( function_exists( 'pll_get_post_language' ) ) {
			$post_lang = (string) pll_get_post_language( $source_id );
		}
		if ( $post_lang && $post_lang === $lang ) {
			if ( ! $manual ) {
				throw new RuntimeException( 'In-place translation requires an explicit manual request.' );
			}
			return self::update_in_place( $source, $title, $translated );
		}

		$existing = self::existing_translation( $source, $lang );
		if ( $existing && ! $manual ) {
			throw new RuntimeException( 'Existing translation requires an explicit manual request.' );
		}

		$post_args = array(
			'post_type'    => $source->post_type,
			'post_status'  => $source->post_status,
			'post_title'   => $title,
			'post_excerpt' => $translated['excerpt'] ?? $source->post_excerpt,
			'post_content' => $translated['content'] ?? $source->post_content,
			'post_date'    => $source->post_date,
		);
		if ( $existing ) {
			$post_args['ID'] = $existing;
		} else {
			$post_args['post_name'] = $source->post_name . '-' . $lang;
		}

		$GLOBALS['qp_llm_suppress_queue'] = true;
		add_filter( 'wp_insert_post_data', array( __CLASS__, 'skip_kses_filter' ), 10, 2 );
		$translated_id = wp_insert_post( wp_slash( $post_args ), true );
		remove_filter( 'wp_insert_post_data', array( __CLASS__, 'skip_kses_filter' ), 10 );
		$GLOBALS['qp_llm_suppress_queue'] = false;

		if ( is_wp_error( $translated_id ) || ! $translated_id ) {
			throw new RuntimeException( is_wp_error( $translated_id ) ? $translated_id->get_error_message() : 'insert failed' );
		}
		$translated_id = (int) $translated_id;

		update_post_meta( $translated_id, '_qp_llm_is_translation', 1 );
		update_post_meta( $translated_id, '_qp_translation_lang', $lang );
		update_post_meta( $translated_id, '_qp_source_slug', $source->post_name );
		update_post_meta( $translated_id, '_qp_llm_source_id', $source_id );

		$thumb = get_post_thumbnail_id( $source_id );
		if ( $thumb ) {
			set_post_thumbnail( $translated_id, $thumb );
		}

		self::copy_terms( $source_id, $translated_id, $source->post_type, $lang );
		self::copy_public_meta( $source_id, $translated_id );
		self::apply_translated_meta( $translated_id, $translated );

		if ( function_exists( 'pll_set_post_language' ) && function_exists( 'pll_save_post_translations' ) ) {
			if ( function_exists( 'pll_get_post_language' ) && ! pll_get_post_language( $source_id ) ) {
				pll_set_post_language( $source_id, QP_LLM_Settings::instance()->get()['source_lang'] );
			}
			pll_set_post_language( $translated_id, $lang );
			$group = array();
			if ( function_exists( 'pll_get_post_translations' ) ) {
				$group = (array) pll_get_post_translations( $source_id );
			}
			$group[ QP_LLM_Settings::instance()->get()['source_lang'] ] = $source_id;
			$group[ $lang ] = $translated_id;
			pll_save_post_translations( $group );
		}

		return $translated_id;
	}

	/**
	 * Translate title/body on the same post (Polylang language already set).
	 *
	 * @param array<string, string> $translated
	 */
	private static function update_in_place( WP_Post $source, string $title, array $translated ): int {
		$source_id = (int) $source->ID;
		$GLOBALS['qp_llm_suppress_queue'] = true;
		add_filter( 'wp_insert_post_data', array( __CLASS__, 'skip_kses_filter' ), 10, 2 );
		$updated = wp_update_post(
			wp_slash(
				array(
					'ID'           => $source_id,
					'post_title'   => $title,
					'post_excerpt' => $translated['excerpt'] ?? $source->post_excerpt,
					'post_content' => $translated['content'] ?? $source->post_content,
				)
			),
			true
		);
		remove_filter( 'wp_insert_post_data', array( __CLASS__, 'skip_kses_filter' ), 10 );
		$GLOBALS['qp_llm_suppress_queue'] = false;

		if ( is_wp_error( $updated ) || ! $updated ) {
			throw new RuntimeException( is_wp_error( $updated ) ? $updated->get_error_message() : 'update failed' );
		}

		self::apply_translated_meta( $source_id, $translated );
		return $source_id;
	}

	public static function skip_kses_filter( array $data, array $postarr ): array {
		return $data;
	}

	private static function find_by_source_meta( WP_Post $source, string $lang ): int {
		$found = get_posts(
			array(
				'post_type'        => $source->post_type,
				'post_status'      => 'any',
				'posts_per_page'   => 1,
				'fields'           => 'ids',
				'suppress_filters' => true,
				'lang'             => '',
				'meta_query'       => array(
					array(
						'key'   => '_qp_llm_source_id',
						'value' => (string) $source->ID,
					),
					array(
						'key'   => '_qp_translation_lang',
						'value' => $lang,
					),
				),
			)
		);
		return $found ? (int) $found[0] : 0;
	}

	private static function copy_terms( int $source_id, int $target_id, string $post_type, string $lang ): void {
		$taxonomies = get_object_taxonomies( $post_type );
		foreach ( $taxonomies as $taxonomy ) {
			$terms = wp_get_object_terms( $source_id, $taxonomy, array( 'fields' => 'ids' ) );
			if ( is_wp_error( $terms ) || empty( $terms ) ) {
				continue;
			}
			$mapped = array();
			foreach ( $terms as $term_id ) {
				$mapped_id = (int) $term_id;
				if ( function_exists( 'pll_get_term' ) ) {
					$translated_term = pll_get_term( (int) $term_id, $lang );
					if ( $translated_term ) {
						$mapped_id = (int) $translated_term;
					}
				}
				$mapped[] = $mapped_id;
			}
			wp_set_object_terms( $target_id, $mapped, $taxonomy );
		}
	}

	private static function copy_public_meta( int $source_id, int $target_id ): void {
		$skip = array(
			'_qp_llm_is_translation',
			'_qp_translation_lang',
			'_qp_llm_source_id',
			'_edit_lock',
			'_edit_last',
		);
		$all  = get_post_meta( $source_id );
		foreach ( $all as $key => $values ) {
			if ( in_array( $key, $skip, true ) ) {
				continue;
			}
			if ( 0 === strpos( (string) $key, '_pll' ) ) {
				continue;
			}
			delete_post_meta( $target_id, $key );
			foreach ( (array) $values as $value ) {
				add_post_meta( $target_id, $key, maybe_unserialize( $value ) );
			}
		}
	}
}
