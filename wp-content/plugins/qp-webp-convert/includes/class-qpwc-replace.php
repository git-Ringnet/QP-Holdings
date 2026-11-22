<?php
/**
 * Thay ảnh gốc JPG/PNG bằng WebP để giảm dung lượng hosting.
 *
 * Mỗi ảnh (attachment) làm theo thứ tự an toàn — dừng giữa chừng ở bước nào website vẫn hiện đủ ảnh:
 *   1. Tạo WebP cho ảnh gốc + mọi kích thước (anh.jpg → anh.jpg.webp, nằm cạnh).
 *   2. Đổi đường dẫn trong database: nội dung bài, postmeta, options, termmeta (kể cả dữ liệu serialize / JSON).
 *   3. Cập nhật bản ghi ảnh: _wp_attached_file, metadata, post_mime_type = image/webp.
 *   4. Chuyển file gốc vào uploads/qpwc-originals/ (cùng đường dẫn con) — chưa xóa.
 * Quản trị kiểm tra website rồi bấm "Xóa vĩnh viễn ảnh gốc" để giải phóng dung lượng (purge).
 * Trước khi purge có thể hoàn tác từng ảnh hoặc toàn bộ (undo).
 *
 * Chỉ đụng JPG/PNG. PDF, video, GIF, SVG, WebP có sẵn: không đụng.
 */

defined( 'ABSPATH' ) || exit;

final class QPWC_Replace {

	const STATE       = 'qpwc_replace';
	const META        = '_qpwc_replaced';
	const BACKUP_DIR  = 'qpwc-originals';
	const BATCH_SIZE  = 5;
	const TIME_LIMIT  = 8;

	/** Meta key của chính attachment — cập nhật riêng, không cho bước thay chuỗi đụng vào. */
	const SKIP_META = array( '_wp_attached_file', '_wp_attachment_metadata', '_wp_attachment_backup_sizes', self::META );

	public static function init(): void {
		add_action( 'wp_ajax_qpwc_replace', array( __CLASS__, 'ajax' ) );
		add_action( 'template_redirect', array( __CLASS__, 'redirect_old_url' ), 0 );
		add_action( 'delete_attachment', array( __CLASS__, 'on_delete' ), 5 );
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'qpwc replace', array( __CLASS__, 'cli' ) );
		}
	}

	/* ------------------------------------------------------------------ đường dẫn */

	public static function basedir(): string {
		$up = wp_get_upload_dir();
		return untrailingslashit( (string) $up['basedir'] );
	}

	public static function backup_root(): string {
		return self::basedir() . '/' . self::BACKUP_DIR;
	}

	/**
	 * Các file của attachment, đường dẫn tương đối so với uploads: [ 'main' => rel, 'sizes' => [rel...], 'original' => rel|null ].
	 */
	public static function files_of( int $id, ?array $meta = null ): array {
		$main = (string) get_post_meta( $id, '_wp_attached_file', true );
		if ( null === $meta ) {
			$meta = wp_get_attachment_metadata( $id );
			$meta = is_array( $meta ) ? $meta : array();
		}
		$dir   = '' === dirname( $main ) || '.' === dirname( $main ) ? '' : dirname( $main ) . '/';
		$sizes = array();
		if ( ! empty( $meta['sizes'] ) && is_array( $meta['sizes'] ) ) {
			foreach ( $meta['sizes'] as $s ) {
				if ( ! empty( $s['file'] ) && $dir . $s['file'] !== $main ) {
					$sizes[ $dir . $s['file'] ] = true;
				}
			}
		}
		$original = ! empty( $meta['original_image'] ) ? $dir . $meta['original_image'] : null;
		if ( $original === $main || isset( $sizes[ (string) $original ] ) ) {
			$original = null;
		}
		return array(
			'main'     => $main,
			'sizes'    => array_keys( $sizes ),
			'original' => $original,
		);
	}

	/* ------------------------------------------------------------------ thay một ảnh */

	/**
	 * @return array{status:string, reason:string, freed:int, rows:int}
	 *         status: replaced | skipped | failed
	 */
	public static function replace_attachment( int $id ): array {
		$out = array( 'status' => 'failed', 'reason' => '', 'freed' => 0, 'rows' => 0 );
		try {
			$mime = (string) get_post_mime_type( $id );
			if ( get_post_meta( $id, self::META, true ) ) {
				return array_merge( $out, array( 'status' => 'skipped', 'reason' => 'already' ) );
			}
			if ( ! in_array( $mime, array( 'image/jpeg', 'image/png' ), true ) ) {
				return array_merge( $out, array( 'status' => 'skipped', 'reason' => 'unsupported_type' ) );
			}
			$meta = wp_get_attachment_metadata( $id );
			$meta = is_array( $meta ) ? $meta : array();
			$f    = self::files_of( $id, $meta );
			if ( '' === $f['main'] || ! preg_match( '/\.(jpe?g|png)$/i', $f['main'] ) ) {
				return array_merge( $out, array( 'status' => 'skipped', 'reason' => 'unsupported_type' ) );
			}

			$base    = self::basedir();
			$quality = QPWC_Settings::quality();

			// 1. Ảnh chính bắt buộc phải có WebP nhỏ hơn, nếu không giữ nguyên cả ảnh.
			$r = QPWC_Converter::convert( $base . '/' . $f['main'], $quality );
			if ( QPWC_Converter::CONVERTED !== $r['status'] ) {
				$st = QPWC_Converter::SKIPPED === $r['status'] ? 'skipped' : 'failed';
				return array_merge( $out, array( 'status' => $st, 'reason' => 'main: ' . $r['reason'] ) );
			}
			$map = array( $f['main'] => $f['main'] . '.webp' );

			// Các kích thước + ảnh gốc trước khi WP thu nhỏ (original_image): cỡ nào WebP không nhỏ hơn thì giữ JPG/PNG của cỡ đó.
			$others = $f['sizes'];
			if ( $f['original'] ) {
				$others[] = $f['original'];
			}
			foreach ( $others as $rel ) {
				$abs = $base . '/' . $rel;
				if ( ! is_file( $abs ) ) {
					continue; // cỡ đã mất từ trước: để nguyên metadata
				}
				$rr = QPWC_Converter::convert( $abs, $quality );
				if ( QPWC_Converter::CONVERTED === $rr['status'] ) {
					$map[ $rel ] = $rel . '.webp';
				}
			}

			// 2. Đổi đường dẫn trong database.
			$out['rows'] = self::rewrite_refs( $map, $f['main'] );

			// 3. Cập nhật bản ghi ảnh (và các bản ghi media khác dùng chung file — Polylang).
			$ids = self::attachments_sharing( $f['main'] );
			foreach ( $ids as $aid ) {
				self::swap_attachment_record( (int) $aid, $map );
			}

			// 4. Chuyển file gốc vào thư mục tạm.
			foreach ( array_keys( $map ) as $rel ) {
				$out['freed'] += self::move_file( $base . '/' . $rel, self::backup_root() . '/' . $rel );
			}

			if ( empty( $GLOBALS['qpwc_in_batch'] ) ) {
				self::purge_page_cache();
			}
			$out['status'] = 'replaced';
			$out['reason'] = 'ok';
			return $out;
		} catch ( \Throwable $e ) {
			QPWC_Settings::log( 'Thay ảnh gốc thất bại: ' . $e->getMessage(), $id );
			$out['reason'] = 'exception: ' . $e->getMessage();
			return $out;
		}
	}

	/** Các attachment có cùng file ảnh chính (Polylang tạo bản ghi media riêng cho từng ngôn ngữ). */
	private static function attachments_sharing( string $rel ): array {
		global $wpdb;
		$ids = $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value = %s", $rel ) );
		return array_map( 'intval', (array) $ids );
	}

	/** @param array<string,string> $map rel cũ → rel mới */
	private static function swap_attachment_record( int $aid, array $map ): void {
		global $wpdb;
		$meta     = wp_get_attachment_metadata( $aid );
		$meta     = is_array( $meta ) ? $meta : array();
		$old_file = (string) get_post_meta( $aid, '_wp_attached_file', true );
		$post     = get_post( $aid );

		update_post_meta(
			$aid,
			self::META,
			array(
				'attached_file' => $old_file,
				'metadata'      => $meta,
				'mime'          => $post ? $post->post_mime_type : '',
				'guid'          => $post ? $post->guid : '',
				'map'           => $map,
				'time'          => time(),
				'purged'        => 0,
			)
		);

		$base = self::basedir();
		$dir  = '' === dirname( $old_file ) || '.' === dirname( $old_file ) ? '' : dirname( $old_file ) . '/';
		if ( isset( $map[ $old_file ] ) ) {
			$meta['file'] = $map[ $old_file ];
			$abs          = $base . '/' . $map[ $old_file ];
			if ( is_file( $abs ) ) {
				$meta['filesize'] = (int) filesize( $abs );
			}
		}
		if ( ! empty( $meta['sizes'] ) && is_array( $meta['sizes'] ) ) {
			foreach ( $meta['sizes'] as $k => $s ) {
				$rel = $dir . ( $s['file'] ?? '' );
				if ( isset( $map[ $rel ] ) ) {
					$meta['sizes'][ $k ]['file']      = basename( $map[ $rel ] );
					$meta['sizes'][ $k ]['mime-type'] = 'image/webp';
					$abs                              = $base . '/' . $map[ $rel ];
					if ( is_file( $abs ) ) {
						$meta['sizes'][ $k ]['filesize'] = (int) filesize( $abs );
					}
				}
			}
		}
		if ( ! empty( $meta['original_image'] ) && isset( $map[ $dir . $meta['original_image'] ] ) ) {
			$meta['original_image'] = basename( $map[ $dir . $meta['original_image'] ] );
		}

		// Ghi thẳng meta (không qua wp_update_attachment_metadata để không kích hoạt lại bộ lọc chuyển đổi).
		update_post_meta( $aid, '_wp_attachment_metadata', $meta );
		if ( isset( $map[ $old_file ] ) ) {
			update_post_meta( $aid, '_wp_attached_file', $map[ $old_file ] );
			$wpdb->update(
				$wpdb->posts,
				array(
					'post_mime_type' => 'image/webp',
					'guid'           => $post ? self::replace_in_string( (string) $post->guid, $map ) : '',
				),
				array( 'ID' => $aid )
			);
		}
		clean_post_cache( $aid );
	}

	/** Chuyển file (cùng ổ đĩa → rename, nhanh). Trả về số byte của file đã chuyển. */
	private static function move_file( string $from, string $to ): int {
		if ( ! is_file( $from ) ) {
			return 0;
		}
		$bytes = (int) filesize( $from );
		wp_mkdir_p( dirname( $to ) );
		if ( @rename( $from, $to ) ) {
			return $bytes;
		}
		if ( @copy( $from, $to ) && @unlink( $from ) ) {
			return $bytes;
		}
		return 0;
	}

	/* ------------------------------------------------------------------ đổi đường dẫn trong database */

	/**
	 * Thay mọi tham chiếu tới file cũ bằng file mới. Không thay khi phía sau đã là ".webp" (chạy lại không bị lặp).
	 *
	 * @param array<string,string> $map  rel cũ → rel mới (vd 2026/09/anh-768x512.jpg → 2026/09/anh-768x512.jpg.webp)
	 */
	public static function replace_in_string( string $s, array $map ): string {
		foreach ( $map as $old => $new ) {
			foreach ( array( array( $old, $new ), array( str_replace( '/', '\\/', $old ), str_replace( '/', '\\/', $new ) ) ) as $pair ) {
				if ( false === strpos( $s, $pair[0] ) ) {
					continue;
				}
				// Không đổi khi phía sau là chữ/số hoặc một đuôi khác (anh.jpg.webp, anh.jpg.bak, anh.jpgx) — chỉ đổi đúng tên file.
				$s = (string) preg_replace( '~(?<![A-Za-z0-9_.\-])' . preg_quote( $pair[0], '~' ) . '(?!\.[A-Za-z0-9]|[A-Za-z0-9_\-])~', $pair[1], $s );
			}
		}
		return $s;
	}

	/** Thay trong giá trị có thể là chuỗi serialize. Trả về null nếu không đổi hoặc không an toàn (có object). */
	private static function replace_in_value( string $raw, array $map ): ?string {
		if ( is_serialized( $raw ) ) {
			$data = @unserialize( $raw, array( 'allowed_classes' => false ) ); // phpcs:ignore
			if ( false === $data && 'b:0;' !== $raw ) {
				return null;
			}
			$changed = false;
			$safe    = true;
			$walk    = static function ( $v ) use ( &$walk, $map, &$changed, &$safe ) {
				if ( is_string( $v ) ) {
					if ( is_serialized( $v ) ) {
						$inner = self::replace_in_value( $v, $map );
						if ( null !== $inner ) {
							$changed = true;
							return $inner;
						}
						return $v;
					}
					$n = self::replace_in_string( $v, $map );
					if ( $n !== $v ) {
						$changed = true;
					}
					return $n;
				}
				if ( is_array( $v ) ) {
					foreach ( $v as $k => $x ) {
						$v[ $k ] = $walk( $x );
					}
					return $v;
				}
				if ( is_object( $v ) ) {
					$safe = false; // __PHP_Incomplete_Class... không ghi lại để tránh hỏng dữ liệu
				}
				return $v;
			};
			$data = $walk( $data );
			if ( ! $safe || ! $changed ) {
				return null;
			}
			return serialize( $data ); // phpcs:ignore
		}
		$n = self::replace_in_string( $raw, $map );
		return $n === $raw ? null : $n;
	}

	/**
	 * @param string $main_rel  dùng tên gốc (không đuôi) để lọc nhanh các dòng có khả năng chứa ảnh này
	 * @return int số dòng đã sửa
	 */
	public static function rewrite_refs( array $map, string $main_rel ): int {
		global $wpdb;
		$stem   = preg_replace( '/\.(jpe?g|png|webp)(\.webp)?$/i', '', $main_rel );
		$like1  = '%' . $wpdb->esc_like( $stem ) . '%';
		$like2  = '%' . $wpdb->esc_like( str_replace( '/', '\\/', $stem ) ) . '%';
		$rows   = 0;

		// Bài viết, trang, bài dịch, bản lưu (revision)... — mọi loại trừ attachment.
		$posts = $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_content, post_excerpt FROM {$wpdb->posts} WHERE post_type <> 'attachment' AND ( post_content LIKE %s OR post_content LIKE %s OR post_excerpt LIKE %s )", $like1, $like2, $like1 ) );
		foreach ( (array) $posts as $p ) {
			$c = self::replace_in_value( (string) $p->post_content, $map );
			$e = self::replace_in_value( (string) $p->post_excerpt, $map );
			$set = array();
			if ( null !== $c ) {
				$set['post_content'] = $c;
			}
			if ( null !== $e ) {
				$set['post_excerpt'] = $e;
			}
			if ( $set ) {
				$wpdb->update( $wpdb->posts, $set, array( 'ID' => (int) $p->ID ) );
				clean_post_cache( (int) $p->ID );
				++$rows;
			}
		}

		$skip = "'" . implode( "','", array_map( 'esc_sql', self::SKIP_META ) ) . "'";
		foreach ( array( array( $wpdb->postmeta, 'meta_id', 'post_id', 'post' ), array( $wpdb->termmeta, 'meta_id', 'term_id', 'term' ) ) as $t ) {
			list( $table, $pk, $obj, $type ) = $t;
			$list = $wpdb->get_results( $wpdb->prepare( "SELECT {$pk} AS id, {$obj} AS obj, meta_value FROM {$table} WHERE meta_key NOT IN ({$skip}) AND ( meta_value LIKE %s OR meta_value LIKE %s )", $like1, $like2 ) ); // phpcs:ignore
			foreach ( (array) $list as $m ) {
				$n = self::replace_in_value( (string) $m->meta_value, $map );
				if ( null !== $n ) {
					$wpdb->update( $table, array( 'meta_value' => $n ), array( $pk => (int) $m->id ) );
					wp_cache_delete( (int) $m->obj, $type . '_meta' );
					++$rows;
				}
			}
		}

		$opts = $wpdb->get_results( $wpdb->prepare( "SELECT option_id, option_name, option_value FROM {$wpdb->options} WHERE option_name NOT LIKE %s AND option_name NOT LIKE %s AND option_name NOT IN ('qpwc_replace','qpwc_bulk','qpwc_log') AND ( option_value LIKE %s OR option_value LIKE %s )", '\_transient%', '\_site\_transient%', $like1, $like2 ) );
		foreach ( (array) $opts as $o ) {
			$n = self::replace_in_value( (string) $o->option_value, $map );
			if ( null !== $n ) {
				$wpdb->update( $wpdb->options, array( 'option_value' => $n ), array( 'option_id' => (int) $o->option_id ) );
				wp_cache_delete( $o->option_name, 'options' );
				++$rows;
			}
		}
		if ( $opts ) {
			wp_cache_delete( 'alloptions', 'options' );
		}
		return $rows;
	}

	/* ------------------------------------------------------------------ hoàn tác / xóa vĩnh viễn */

	/** Hoàn tác một ảnh (chỉ được khi ảnh gốc chưa bị xóa vĩnh viễn). */
	public static function undo_attachment( int $id ): array {
		$out  = array( 'status' => 'failed', 'reason' => '' );
		$info = get_post_meta( $id, self::META, true );
		if ( ! is_array( $info ) ) {
			return array( 'status' => 'skipped', 'reason' => 'not_replaced' );
		}
		if ( ! empty( $info['purged'] ) ) {
			return array( 'status' => 'skipped', 'reason' => 'purged' );
		}
		$map  = (array) $info['map'];
		$base = self::basedir();
		// File gốc về chỗ cũ trước, rồi mới đổi lại database (dừng giữa chừng thì cả 2 bản đều còn).
		foreach ( array_keys( $map ) as $rel ) {
			$bak = self::backup_root() . '/' . $rel;
			if ( is_file( $bak ) && ! is_file( $base . '/' . $rel ) ) {
				wp_mkdir_p( dirname( $base . '/' . $rel ) );
				@rename( $bak, $base . '/' . $rel );
			}
			if ( ! is_file( $base . '/' . $rel ) ) {
				$out['reason'] = 'missing_original: ' . $rel;
				return $out;
			}
		}
		self::rewrite_refs( array_flip( $map ), (string) $info['attached_file'] . '.webp' );

		global $wpdb;
		foreach ( self::attachments_sharing( (string) ( $map[ $info['attached_file'] ] ?? '' ) ) as $aid ) {
			$i = get_post_meta( $aid, self::META, true );
			if ( ! is_array( $i ) ) {
				continue;
			}
			update_post_meta( $aid, '_wp_attached_file', $i['attached_file'] );
			update_post_meta( $aid, '_wp_attachment_metadata', $i['metadata'] );
			$wpdb->update( $wpdb->posts, array( 'post_mime_type' => $i['mime'], 'guid' => $i['guid'] ), array( 'ID' => $aid ) );
			delete_post_meta( $aid, self::META );
			clean_post_cache( $aid );
		}
		if ( empty( $GLOBALS['qpwc_in_batch'] ) ) {
			self::purge_page_cache();
		}
		return array( 'status' => 'restored', 'reason' => 'ok' );
	}

	/** Xóa vĩnh viễn thư mục ảnh gốc đã thay. Trả về [files, bytes]. */
	public static function purge_originals(): array {
		global $wpdb;
		$root  = self::backup_root();
		$files = 0;
		$bytes = 0;
		if ( is_dir( $root ) ) {
			$it = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $root, \FilesystemIterator::SKIP_DOTS ), \RecursiveIteratorIterator::CHILD_FIRST );
			foreach ( $it as $f ) {
				if ( $f->isDir() ) {
					@rmdir( $f->getPathname() );
				} else {
					$b = (int) $f->getSize();
					if ( @unlink( $f->getPathname() ) ) {
						++$files;
						$bytes += $b;
					}
				}
			}
			@rmdir( $root );
		}
		// Đánh dấu không hoàn tác được nữa.
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT meta_id, post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s", self::META ) );
		foreach ( (array) $rows as $r ) {
			$v = maybe_unserialize( $r->meta_value );
			if ( is_array( $v ) && empty( $v['purged'] ) ) {
				$v['purged'] = time();
				update_post_meta( (int) $r->post_id, self::META, $v );
			}
		}
		$s           = self::state();
		$s['purged'] = (int) $s['purged'] + $bytes;
		self::save( $s );
		return array( 'files' => $files, 'bytes' => $bytes );
	}

	/** Dung lượng đang nằm trong thư mục ảnh gốc chờ xóa. */
	public static function backup_bytes(): int {
		$root = self::backup_root();
		$sum  = 0;
		if ( is_dir( $root ) ) {
			$it = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $root, \FilesystemIterator::SKIP_DOTS ) );
			foreach ( $it as $f ) {
				if ( $f->isFile() ) {
					$sum += (int) $f->getSize();
				}
			}
		}
		return $sum;
	}

	public static function replaced_count(): int {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s", self::META ) );
	}

	/* ------------------------------------------------------------------ an toàn */

	/** Link cũ anh.jpg (bài chia sẻ, chỗ sót) → chuyển hướng 301 sang anh.jpg.webp nếu ảnh gốc đã được thay. */
	public static function redirect_old_url(): void {
		if ( ! is_404() ) {
			return;
		}
		$path = (string) wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '', PHP_URL_PATH ); // phpcs:ignore
		if ( ! preg_match( '/\.(jpe?g|png)$/i', $path ) ) {
			return;
		}
		$up      = wp_get_upload_dir();
		$baseurl = (string) wp_parse_url( (string) $up['baseurl'], PHP_URL_PATH );
		if ( '' === $baseurl || 0 !== strpos( $path, $baseurl . '/' ) ) {
			return;
		}
		$rel = rawurldecode( substr( $path, strlen( $baseurl ) + 1 ) );
		if ( false !== strpos( $rel, '..' ) ) {
			return;
		}
		if ( ! is_file( self::basedir() . '/' . $rel ) && is_file( self::basedir() . '/' . $rel . '.webp' ) ) {
			wp_safe_redirect( $up['baseurl'] . '/' . str_replace( '%2F', '/', rawurlencode( $rel ) ) . '.webp', 301 );
			exit;
		}
	}

	/** Xóa ảnh khỏi thư viện → xóa luôn bản gốc đang chờ trong thư mục tạm. */
	public static function on_delete( $id ): void {
		$info = get_post_meta( (int) $id, self::META, true );
		if ( is_array( $info ) && empty( $info['purged'] ) ) {
			foreach ( array_keys( (array) $info['map'] ) as $rel ) {
				$bak = self::backup_root() . '/' . $rel;
				if ( is_file( $bak ) && count( self::attachments_sharing( (string) ( $info['map'][ $info['attached_file'] ] ?? '' ) ) ) <= 1 ) {
					@unlink( $bak );
				}
			}
		}
	}

	private static function purge_page_cache(): void {
		if ( has_action( 'litespeed_purge_all' ) ) {
			do_action( 'litespeed_purge_all' );
		}
	}

	/* ------------------------------------------------------------------ ảnh mới tải lên */

	/**
	 * Gọi từ QPWC_Media::on_metadata khi bật "tự thay ảnh mới": ảnh vừa tải lên chưa có bài nào dùng,
	 * nên đổi luôn sang WebP và xóa ảnh gốc ngay. Trả về metadata đã đổi tên file.
	 */
	public static function replace_new_upload( int $id, array $data ): array {
		global $wpdb;
		$main = (string) get_post_meta( $id, '_wp_attached_file', true );
		$mime = (string) get_post_mime_type( $id );
		if ( '' === $main || ! in_array( $mime, array( 'image/jpeg', 'image/png' ), true ) || get_post_meta( $id, self::META, true ) ) {
			return $data;
		}
		$base = self::basedir();
		if ( ! is_file( $base . '/' . $main . '.webp' ) || ! is_file( $base . '/' . $main ) ) {
			return $data; // ảnh chính không có WebP nhỏ hơn → giữ nguyên
		}
		$f   = self::files_of( $id, $data );
		$map = array( $main => $main . '.webp' );
		foreach ( array_merge( $f['sizes'], $f['original'] ? array( $f['original'] ) : array() ) as $rel ) {
			if ( is_file( $base . '/' . $rel . '.webp' ) ) {
				$map[ $rel ] = $rel . '.webp';
			}
		}
		$dir          = '' === dirname( $main ) || '.' === dirname( $main ) ? '' : dirname( $main ) . '/';
		$data['file'] = $map[ $main ];
		$data['filesize'] = (int) filesize( $base . '/' . $map[ $main ] );
		if ( ! empty( $data['sizes'] ) && is_array( $data['sizes'] ) ) {
			foreach ( $data['sizes'] as $k => $s ) {
				$rel = $dir . ( $s['file'] ?? '' );
				if ( isset( $map[ $rel ] ) ) {
					$data['sizes'][ $k ]['file']      = basename( $map[ $rel ] );
					$data['sizes'][ $k ]['mime-type'] = 'image/webp';
					$data['sizes'][ $k ]['filesize']  = (int) filesize( $base . '/' . $map[ $rel ] );
				}
			}
		}
		if ( ! empty( $data['original_image'] ) && isset( $map[ $dir . $data['original_image'] ] ) ) {
			$data['original_image'] = basename( $map[ $dir . $data['original_image'] ] );
		}
		update_post_meta( $id, '_wp_attached_file', $map[ $main ] );
		$post = get_post( $id );
		$wpdb->update(
			$wpdb->posts,
			array(
				'post_mime_type' => 'image/webp',
				'guid'           => $post ? self::replace_in_string( (string) $post->guid, $map ) : '',
			),
			array( 'ID' => $id )
		);
		clean_post_cache( $id );
		foreach ( array_keys( $map ) as $rel ) {
			@unlink( $base . '/' . $rel );
		}
		update_post_meta( $id, QPWC_Media::META_STATUS, 'done' );
		return $data;
	}

	/* ------------------------------------------------------------------ chạy hàng loạt */

	public static function default_state(): array {
		return array(
			'status'    => 'idle', // idle | running | paused | done | undoing
			'mode'      => 'replace', // replace | undo
			'last_id'   => 0,
			'processed' => 0,
			'replaced'  => 0,
			'skipped'   => 0,
			'failed'    => 0,
			'moved'     => 0, // byte ảnh gốc đã chuyển vào thư mục tạm
			'rows'      => 0, // số dòng database đã đổi đường dẫn
			'restored'  => 0,
			'purged'    => 0, // byte đã xóa vĩnh viễn
			'started'   => 0,
		);
	}

	public static function state(): array {
		$s = get_option( self::STATE, array() );
		return wp_parse_args( is_array( $s ) ? $s : array(), self::default_state() );
	}

	private static function save( array $s ): void {
		update_option( self::STATE, $s, false );
	}

	public static function start( string $mode = 'replace' ): array {
		if ( 'replace' === $mode && ! QPWC_Settings::get( 'backup_at' ) ) {
			return array( 'error' => 'Chưa xác nhận đã sao lưu thư mục ảnh và database. Hãy sao lưu rồi tick xác nhận trước khi thay ảnh gốc.' );
		}
		if ( 'replace' === $mode && 'none' === QPWC_Converter::engine() ) {
			return array( 'error' => 'Máy chủ chưa hỗ trợ tạo WebP.' );
		}
		if ( 'replace' === $mode ) {
			QPWC_Settings::update( array( 'replace_new' => 1 ) ); // ảnh tải lên sau này cũng dùng WebP thay ảnh gốc
		}
		$old              = self::state();
		$s                = self::default_state();
		$s['purged']      = (int) $old['purged'];
		$s['status']      = 'running';
		$s['mode']        = 'undo' === $mode ? 'undo' : 'replace';
		$s['started']     = time();
		self::save( $s );
		return $s;
	}

	public static function pause(): array {
		$s = self::state();
		if ( 'running' === $s['status'] ) {
			$s['status'] = 'paused';
			self::save( $s );
		}
		return $s;
	}

	public static function resume(): array {
		$s = self::state();
		if ( 'paused' === $s['status'] ) {
			$s['status'] = 'running';
			self::save( $s );
		}
		return $s;
	}

	public static function step( int $batch = self::BATCH_SIZE, int $time_limit = self::TIME_LIMIT ): array {
		global $wpdb;
		$s = self::state();
		if ( 'running' !== $s['status'] ) {
			return $s;
		}
		if ( 'undo' === $s['mode'] ) {
			$ids = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND post_id > %d ORDER BY post_id ASC LIMIT %d", self::META, (int) $s['last_id'], $batch ) );
		} else {
			$ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_mime_type IN ('image/jpeg','image/png') AND ID > %d ORDER BY ID ASC LIMIT %d", (int) $s['last_id'], $batch ) );
		}
		if ( empty( $ids ) ) {
			$s['status'] = 'done';
			self::save( $s );
			return $s;
		}
		$t0                      = microtime( true );
		$GLOBALS['qpwc_in_batch'] = true; // xóa cache trang 1 lần sau cả lô, không phải sau từng ảnh
		foreach ( $ids as $id ) {
			$id = (int) $id;
			if ( 'undo' === $s['mode'] ) {
				$r = self::undo_attachment( $id );
				if ( 'restored' === $r['status'] ) {
					++$s['restored'];
				} elseif ( 'failed' === $r['status'] ) {
					++$s['failed'];
					QPWC_Settings::log( 'Hoàn tác thất bại: ' . $r['reason'], $id );
				} else {
					++$s['skipped'];
				}
			} else {
				$r = self::replace_attachment( $id );
				if ( 'replaced' === $r['status'] ) {
					++$s['replaced'];
					$s['moved'] += (int) $r['freed'];
					$s['rows']  += (int) $r['rows'];
				} elseif ( 'failed' === $r['status'] ) {
					++$s['failed'];
					if ( 0 !== strpos( $r['reason'], 'main: missing_file' ) ) {
						QPWC_Settings::log( 'Không thay được ảnh gốc: ' . $r['reason'], $id );
					}
				} else {
					++$s['skipped'];
				}
			}
			++$s['processed'];
			$s['last_id'] = $id;
			if ( ( microtime( true ) - $t0 ) > $time_limit ) {
				break;
			}
		}
		$GLOBALS['qpwc_in_batch'] = false;
		self::purge_page_cache();
		self::save( $s );
		return $s;
	}

	public static function ajax(): void {
		check_ajax_referer( 'qpwc_bulk', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'error' => 'Không đủ quyền.' ), 403 );
		}
		$do = isset( $_POST['do'] ) ? sanitize_key( wp_unslash( $_POST['do'] ) ) : 'status'; // phpcs:ignore WordPress.Security.NonceVerification
		if ( 'start' === $do ) {
			$out = self::start( 'replace' );
		} elseif ( 'undo' === $do ) {
			$out = self::start( 'undo' );
		} elseif ( 'pause' === $do ) {
			$out = self::pause();
		} elseif ( 'resume' === $do ) {
			$out = self::resume();
		} elseif ( 'step' === $do ) {
			$out = self::step();
		} elseif ( 'purge' === $do ) {
			$st = self::state();
			if ( 'running' === $st['status'] ) {
				wp_send_json_error( array( 'error' => 'Đang chạy, hãy tạm dừng trước.' ) );
			}
			$res = self::purge_originals();
			$out = self::state();
			$out['purge_result'] = $res;
		} else {
			$out = self::state();
		}
		if ( isset( $out['error'] ) ) {
			wp_send_json_error( $out );
		}
		wp_send_json_success(
			array(
				'state'    => $out,
				'replaced' => self::replaced_count(),
				'backup'   => self::backup_bytes(),
			)
		);
	}

	/** wp qpwc replace --backup-done | --undo | --purge */
	public static function cli( array $args, array $assoc ): void {
		if ( ! empty( $assoc['purge'] ) ) {
			$r = self::purge_originals();
			\WP_CLI::success( sprintf( 'Đã xóa vĩnh viễn %d file, %s.', $r['files'], size_format( $r['bytes'] ) ) );
			return;
		}
		if ( ! empty( $assoc['backup-done'] ) ) {
			QPWC_Settings::update( array( 'backup_at' => time() ) );
		}
		$st = self::start( ! empty( $assoc['undo'] ) ? 'undo' : 'replace' );
		if ( isset( $st['error'] ) ) {
			\WP_CLI::error( $st['error'] . ' (thêm --backup-done nếu đã sao lưu)' );
		}
		do {
			$s = self::step( 10, 30 );
			\WP_CLI::log( sprintf( 'Đã xử lý %d (thay %d, hoàn tác %d, bỏ qua %d, lỗi %d)', $s['processed'], $s['replaced'], $s['restored'], $s['skipped'], $s['failed'] ) );
		} while ( 'running' === $s['status'] );
		\WP_CLI::success( 'Hoàn tất.' );
	}
}
