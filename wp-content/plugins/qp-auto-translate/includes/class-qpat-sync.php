<?php
/**
 * Creates / updates the EN and ZH copies of a Vietnamese post.
 *
 * Uses the same post meta as QP LLM Translate, because the theme reads them to
 * switch languages and existing translations must be recognised (no duplicates):
 *   _qp_translation_lang    'en' | 'zh'
 *   _qp_llm_source_id       ID of the Vietnamese post
 *   _qp_llm_is_translation  1
 *   _qp_source_slug         slug of the Vietnamese post
 */

defined( 'ABSPATH' ) || exit;

final class QPAT_Sync {
	const META_LANG      = '_qp_translation_lang';
	const META_SOURCE_ID = '_qp_llm_source_id';
	const META_IS_COPY   = '_qp_llm_is_translation';
	const META_SLUG      = '_qp_source_slug';
	const META_ENGINE    = '_qpat_engine';
	const META_TIME      = '_qpat_translated_at';
	/** Fingerprint of the Vietnamese text the copy was translated from. */
	const META_HASH      = '_qpat_source_hash';

	public static function is_translation_copy( int $post_id ): bool {
		return (bool) get_post_meta( $post_id, self::META_IS_COPY, true )
			|| (bool) get_post_meta( $post_id, self::META_LANG, true );
	}

	/**
	 * Is this a Vietnamese original we may translate?
	 */
	public static function is_source_post( WP_Post $post ): bool {
		if ( self::is_translation_copy( (int) $post->ID ) ) {
			return false;
		}
		if ( function_exists( 'pll_get_post_language' ) ) {
			$lang = (string) pll_get_post_language( (int) $post->ID );
			if ( '' !== $lang && 'vi' !== $lang ) {
				return false;
			}
		}
		return true;
	}

	/** @return string[] */
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

	/** @return string[] */
	private static function meta_keys_for( string $post_type ): array {
		switch ( $post_type ) {
			case 'qp_job':
				return self::job_meta_keys();
			case 'qp_news':
				return self::news_meta_keys();
			case 'qp_project':
				return self::project_meta_keys();
		}
		return array();
	}

	/**
	 * Text sent to the translation engine: title, excerpt, content and the
	 * text meta each post type renders.
	 *
	 * @return array<string, string>
	 */
	public static function collect_fields( WP_Post $post ): array {
		$fields = array(
			'title'   => (string) $post->post_title,
			'excerpt' => (string) $post->post_excerpt,
			'content' => (string) $post->post_content,
		);

		if ( 'qp_investor_doc' === $post->post_type ) {
			$count = (int) get_post_meta( (int) $post->ID, 'doc_files', true );
			for ( $i = 0; $i < $count; $i++ ) {
				$val = (string) get_post_meta( (int) $post->ID, 'doc_files_' . $i . '_title', true );
				if ( '' !== trim( $val ) ) {
					$fields[ 'doc_files_' . $i . '_title' ] = $val;
				}
			}
			return $fields;
		}

		foreach ( self::meta_keys_for( $post->post_type ) as $meta_key ) {
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

	/** Content fingerprint: detects edits made while a job waits in the queue. */
	public static function source_hash( WP_Post $post ): string {
		$parts = array( $post->post_type, $post->post_status, $post->post_title, $post->post_excerpt, $post->post_content );
		foreach ( self::collect_fields( $post ) as $k => $v ) {
			$parts[] = $k . '=' . $v;
		}
		return hash( 'sha256', implode( "\0", $parts ) );
	}

	/** Fingerprint of the translatable text only (status/date ignored). */
	public static function text_hash( WP_Post $post ): string {
		$parts = array();
		foreach ( self::collect_fields( $post ) as $k => $v ) {
			$parts[] = $k . '=' . $v;
		}
		return hash( 'sha256', implode( "\0", $parts ) );
	}

	/**
	 * Was the copy translated from an older version of the Vietnamese text?
	 * Copies made before this was recorded count as outdated.
	 */
	public static function copy_outdated( WP_Post $source, int $copy_id ): bool {
		$stored = (string) get_post_meta( $copy_id, self::META_HASH, true );
		return '' === $stored || ! hash_equals( $stored, self::text_hash( $source ) );
	}

	/**
	 * Give the EN/ZH copies the same status (and publish date) as the
	 * Vietnamese post, e.g. translated while still a draft, then published.
	 *
	 * @return int Number of copies changed.
	 */
	public static function sync_status( WP_Post $source ): int {
		$status = (string) $source->post_status;
		if ( in_array( $status, array( 'trash', 'auto-draft', 'inherit' ), true ) ) {
			return 0;
		}
		$changed = 0;
		foreach ( self::family( (int) $source->ID ) as $lang => $id ) {
			if ( 'vi' === $lang || ! $id || (int) $id === (int) $source->ID ) {
				continue;
			}
			$copy = get_post( (int) $id );
			if ( ! $copy || 'trash' === $copy->post_status || $copy->post_status === $status ) {
				continue;
			}
			self::write_post(
				array(
					'ID'            => (int) $id,
					'post_status'   => $status,
					'post_date'     => $source->post_date,
					'post_date_gmt' => $source->post_date_gmt,
					'edit_date'     => true,
				)
			);
			++$changed;
		}
		return $changed;
	}

	/**
	 * ID of the existing EN/ZH copy of $source, or 0.
	 */
	public static function existing_translation( WP_Post $source, string $lang ): int {
		if ( function_exists( 'pll_get_post' ) ) {
			$id = (int) pll_get_post( (int) $source->ID, $lang );
			if ( $id && $id !== (int) $source->ID ) {
				return $id;
			}
		}
		$found = get_posts(
			array(
				'post_type'        => $source->post_type,
				'post_status'      => 'any',
				'posts_per_page'   => 1,
				'fields'           => 'ids',
				'orderby'          => 'ID',
				'order'            => 'ASC',
				'suppress_filters' => true,
				'lang'             => '',
				'meta_query'       => array(
					array(
						'key'   => self::META_SOURCE_ID,
						'value' => (string) $source->ID,
					),
					array(
						'key'   => self::META_LANG,
						'value' => $lang,
					),
				),
			)
		);
		return $found ? (int) $found[0] : 0;
	}

	/**
	 * All language versions of a post, from either side (VI original or copy).
	 *
	 * @return array<string, int> lang => post ID (only versions that exist)
	 */
	public static function family( int $post_id ): array {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return array();
		}
		$source_id = $post_id;
		if ( self::is_translation_copy( $post_id ) ) {
			$source_id = (int) get_post_meta( $post_id, self::META_SOURCE_ID, true );
		}
		$source = $source_id ? get_post( $source_id ) : null;
		if ( ! $source ) {
			$lang = (string) get_post_meta( $post_id, self::META_LANG, true );
			return $lang ? array( $lang => $post_id ) : array();
		}
		$out = array( 'vi' => (int) $source->ID );
		foreach ( array( 'en', 'zh' ) as $lang ) {
			$id = self::existing_translation( $source, $lang );
			if ( $id ) {
				$out[ $lang ] = $id;
			}
		}
		return $out;
	}

	/**
	 * Create or update the $lang copy of $source with translated fields.
	 *
	 * @param array<string, string> $translated
	 */
	public static function upsert_translation( WP_Post $source, string $lang, array $translated, string $engine = '' ): int {
		$source_id = (int) $source->ID;
		$title     = trim( (string) ( $translated['title'] ?? '' ) );
		if ( '' === $title ) {
			$title = (string) $source->post_title;
		}

		$existing = self::existing_translation( $source, $lang );
		$slug     = self::source_slug( $source );

		$post_args = array(
			'post_type'    => $source->post_type,
			'post_status'  => $source->post_status,
			'post_title'   => $title,
			'post_excerpt' => $translated['excerpt'] ?? $source->post_excerpt,
			'post_content' => $translated['content'] ?? $source->post_content,
			'post_date'    => $source->post_date,
			'post_author'  => $source->post_author,
			'menu_order'   => $source->menu_order,
			'post_parent'  => self::parent_for( $source, $lang ),
		);
		if ( $existing ) {
			$post_args['ID'] = $existing;
		} else {
			$post_args['post_name'] = $slug . '-' . $lang;
		}

		$translated_id = self::write_post( $post_args );

		self::copy_public_meta( $source_id, $translated_id );
		update_post_meta( $translated_id, self::META_IS_COPY, 1 );
		update_post_meta( $translated_id, self::META_LANG, $lang );
		update_post_meta( $translated_id, self::META_SLUG, $slug );
		update_post_meta( $translated_id, self::META_SOURCE_ID, $source_id );
		update_post_meta( $translated_id, self::META_ENGINE, $engine );
		update_post_meta( $translated_id, self::META_TIME, current_time( 'mysql' ) );
		update_post_meta( $translated_id, self::META_HASH, self::text_hash( $source ) );

		$thumb = get_post_thumbnail_id( $source_id );
		if ( $thumb ) {
			set_post_thumbnail( $translated_id, $thumb );
		}

		self::copy_terms( $source_id, $translated_id, $source->post_type, $lang );
		self::apply_translated_meta( $translated_id, $source->post_type, $translated );
		self::link_polylang( $source_id, $translated_id, $lang );

		return $translated_id;
	}

	/**
	 * Slug of the Vietnamese post; a post published without one (seen on
	 * production: copies ended up as "en-28") falls back to its title.
	 */
	public static function source_slug( WP_Post $source ): string {
		$slug = (string) $source->post_name;
		if ( '' === $slug ) {
			$slug = sanitize_title( (string) $source->post_title );
		}
		if ( '' === $slug ) {
			$slug = $source->post_type . '-' . $source->ID;
		}
		return $slug;
	}

	/** Replace the content of a copy (review "accept") without queueing anything. */
	public static function write_content( int $copy_id, string $content ): void {
		self::write_post( array( 'ID' => $copy_id, 'post_content' => $content ) );
	}

	/** @param array<string, mixed> $fields post fields (post_title, post_excerpt...) */
	public static function write_fields( int $copy_id, array $fields ): void {
		self::write_post( array( 'ID' => $copy_id ) + $fields );
	}

	/**
	 * Data that is the same in every language, synced from the Vietnamese post
	 * without calling any translation API: featured image, categories / tags,
	 * page template, order, parent page and non-text custom fields.
	 *
	 * @return int Number of copies updated.
	 */
	public static function sync_shared( WP_Post $source ): int {
		$n = 0;
		foreach ( self::family( (int) $source->ID ) as $lang => $copy_id ) {
			if ( 'vi' === $lang || (int) $copy_id === (int) $source->ID ) {
				continue;
			}
			$copy = get_post( (int) $copy_id );
			if ( ! $copy || 'trash' === $copy->post_status ) {
				continue;
			}
			self::copy_public_meta( (int) $source->ID, (int) $copy_id, self::translatable_meta_keys( $source ) );

			$thumb = (int) get_post_thumbnail_id( $source->ID );
			if ( $thumb ) {
				set_post_thumbnail( (int) $copy_id, $thumb );
			} else {
				delete_post_thumbnail( (int) $copy_id );
			}
			self::copy_terms( (int) $source->ID, (int) $copy_id, $source->post_type, $lang );

			$parent = self::parent_for( $source, $lang );
			if ( (int) $copy->menu_order !== (int) $source->menu_order || (int) $copy->post_parent !== $parent ) {
				self::write_post(
					array(
						'ID'          => (int) $copy_id,
						'menu_order'  => (int) $source->menu_order,
						'post_parent' => $parent,
					)
				);
			}
			++$n;
		}
		return $n;
	}

	/** Meta keys holding translated text: never overwritten by the shared-data sync. */
	private static function translatable_meta_keys( WP_Post $source ): array {
		$keys = self::meta_keys_for( $source->post_type );
		if ( 'qp_investor_doc' === $source->post_type ) {
			$count = (int) get_post_meta( (int) $source->ID, 'doc_files', true );
			for ( $i = 0; $i < $count; $i++ ) {
				$keys[] = 'doc_files_' . $i . '_title';
			}
		}
		return $keys;
	}

	/** Parent page of a copy: the same-language copy of the Vietnamese parent, if any. */
	private static function parent_for( WP_Post $source, string $lang ): int {
		if ( ! $source->post_parent ) {
			return 0;
		}
		$parent = get_post( (int) $source->post_parent );
		if ( ! $parent ) {
			return 0;
		}
		$copy = self::existing_translation( $parent, $lang );
		return $copy ? $copy : (int) $source->post_parent;
	}

	private static function write_post( array $args ): int {
		// Our own insert must not queue another translation (old plugin included).
		$GLOBALS['qpat_suppress_queue']    = true;
		$GLOBALS['qp_llm_suppress_queue'] = true;
		// Translated HTML comes from the original post (written by an editor):
		// background jobs run without a user, so kses would strip embeds/iframes.
		kses_remove_filters();
		try {
			$id = isset( $args['ID'] )
				? wp_update_post( wp_slash( $args ), true )
				: wp_insert_post( wp_slash( $args ), true );
		} finally {
			kses_init();
			$GLOBALS['qpat_suppress_queue']    = false;
			$GLOBALS['qp_llm_suppress_queue'] = false;
		}
		if ( is_wp_error( $id ) || ! $id ) {
			throw new RuntimeException( is_wp_error( $id ) ? $id->get_error_message() : 'Không tạo được bài dịch.' );
		}
		return (int) $id;
	}

	/**
	 * @param array<string, string> $translated
	 */
	private static function apply_translated_meta( int $target_id, string $post_type, array $translated ): void {
		if ( 'qp_investor_doc' === $post_type ) {
			foreach ( $translated as $key => $value ) {
				if ( preg_match( '/^doc_files_\d+_title$/', (string) $key ) && '' !== trim( (string) $value ) ) {
					update_post_meta( $target_id, $key, $value );
				}
			}
			return;
		}
		foreach ( self::meta_keys_for( $post_type ) as $meta_key ) {
			if ( isset( $translated[ $meta_key ] ) && is_string( $translated[ $meta_key ] ) ) {
				update_post_meta( $target_id, $meta_key, $translated[ $meta_key ] );
			}
		}
	}

	private static function copy_terms( int $source_id, int $target_id, string $post_type, string $lang ): void {
		foreach ( get_object_taxonomies( $post_type ) as $taxonomy ) {
			if ( in_array( $taxonomy, array( 'language', 'post_translations' ), true ) ) {
				continue;
			}
			$terms = wp_get_object_terms( $source_id, $taxonomy, array( 'fields' => 'ids' ) );
			if ( is_wp_error( $terms ) || empty( $terms ) ) {
				continue;
			}
			$mapped = array();
			foreach ( $terms as $term_id ) {
				$mapped_id = (int) $term_id;
				if ( function_exists( 'pll_get_term' ) ) {
					$translated_term = (int) pll_get_term( (int) $term_id, $lang );
					if ( $translated_term ) {
						$mapped_id = $translated_term;
					}
				}
				$mapped[] = $mapped_id;
			}
			wp_set_object_terms( $target_id, $mapped, $taxonomy );
		}
	}

	private static function copy_public_meta( int $source_id, int $target_id, array $also_skip = array() ): void {
		$skip = array_merge(
			array(
				self::META_IS_COPY,
				self::META_LANG,
				self::META_SOURCE_ID,
				self::META_SLUG,
				'_edit_lock',
				'_edit_last',
				'_wp_old_slug',
				'_thumbnail_id',
			),
			$also_skip
		);
		foreach ( get_post_meta( $source_id ) as $key => $values ) {
			$key = (string) $key;
			if ( in_array( $key, $skip, true ) || 0 === strpos( $key, '_pll' ) || 0 === strpos( $key, '_qpat' ) ) {
				continue;
			}
			delete_post_meta( $target_id, $key );
			foreach ( (array) $values as $value ) {
				add_post_meta( $target_id, $key, maybe_unserialize( $value ) );
			}
		}
	}

	private static function link_polylang( int $source_id, int $translated_id, string $lang ): void {
		if ( ! function_exists( 'pll_set_post_language' ) || ! function_exists( 'pll_save_post_translations' ) ) {
			return;
		}
		if ( function_exists( 'pll_get_post_language' ) && ! pll_get_post_language( $source_id ) ) {
			pll_set_post_language( $source_id, 'vi' );
		}
		pll_set_post_language( $translated_id, $lang );
		$group         = function_exists( 'pll_get_post_translations' ) ? (array) pll_get_post_translations( $source_id ) : array();
		$group['vi']   = $source_id;
		$group[ $lang ] = $translated_id;
		pll_save_post_translations( $group );
	}
}
