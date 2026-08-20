<?php
/**
 * Plugin Name: MyMomo - Connector
 * Plugin URI: https://virtualofficeai.com.au
 * Description: Connect your WordPress site to MyMomo for AI-powered content management, SEO optimization, and site automation.
 * Version: 1.16.2
 * Author: Cornerstone & Compass
 * Author URI: https://cornerstoneandcompass.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.8
 * Requires PHP: 7.4
 *
 * @package MyMomo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Read version from the plugin header so the constant CANNOT drift from the
// "Version:" line above. Previously this was hardcoded and silently fell
// behind, so the auto-updater always thought a newer version was available.
if ( ! function_exists( 'get_file_data' ) ) {
	require_once ABSPATH . 'wp-includes/functions.php';
}
$voa_plugin_data = get_file_data( __FILE__, array( 'Version' => 'Version' ), 'plugin' );
define( 'VOA_CONNECTOR_VERSION', ! empty( $voa_plugin_data['Version'] ) ? $voa_plugin_data['Version'] : '0.0.0' );
unset( $voa_plugin_data );
define( 'VOA_CONNECTOR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'VOA_CONNECTOR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'VOA_API_KEY_OPTION', 'voa_api_key' );
define( 'VOA_LAST_PING_TRANSIENT', 'voa_last_ping' );
define( 'VOA_RATE_LIMIT_TRANSIENT_PREFIX', 'voa_rate_limit_' );
define( 'VOA_RATE_LIMIT_REQUESTS', 60 );
define( 'VOA_RATE_LIMIT_PERIOD', 60 ); // seconds
define( 'VOA_ALLOWED_ORIGINS', array( 'virtualofficeai.com.au', 'cc-api.benjamin-a2d.workers.dev' ) );
define( 'VOA_UPDATE_URL', 'https://api.virtualofficeai.com.au/api/plugin-update' );
define( 'VOA_PLUGIN_SLUG', 'virtual-office-ai-connector' );
// Worker base for outbound (site -> worker) calls, e.g. minting a voice token.
define( 'VOA_WORKER_BASE', 'https://api.virtualofficeai.com.au' );
// Cached voice-agent config pushed from MyMomo on save (so the widget
// renders instantly without a round-trip). Public-safe subset only.
define( 'VOA_VOICE_CONFIG_OPTION', 'voa_voice_config' );

// Hook into plugin activation
register_activation_hook( __FILE__, 'voa_activate_plugin' );
register_deactivation_hook( __FILE__, 'voa_deactivate_plugin' );

// Admin hooks
add_action( 'admin_menu', 'voa_add_admin_menu' );
add_action( 'admin_init', 'voa_register_settings' );

// REST API hooks
add_action( 'rest_api_init', 'voa_register_rest_routes' );

// Security hooks
add_filter( 'rest_authentication_errors', 'voa_check_authentication', 1 );

// Output custom CSS injected by AI commands (stored in voa_custom_css option)
add_action( 'wp_head', 'voa_output_custom_css', 999 );
function voa_output_custom_css() {
	$css = get_option( 'voa_custom_css', '' );
	if ( ! empty( $css ) ) {
		echo '<style id="voa-custom-css">' . wp_strip_all_tags( $css ) . '</style>';
	}
}

// ── SEO managed from MyMomo ("CC fields") ──────────────────────────────────
// Per-page SEO is stored in post meta (_voa_seo_*) set via POST /voa/v1/seo,
// and rendered here so the site needs NO third-party SEO plugin. If Yoast or
// Rank Math is active we stand down to avoid duplicate tags.
function voa_seo_plugin_active() {
	return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || class_exists( 'RankMath' );
}
add_filter( 'pre_get_document_title', 'voa_seo_filter_title', 99 );
function voa_seo_filter_title( $title ) {
	if ( voa_seo_plugin_active() || ! is_singular() ) {
		return $title;
	}
	$t = get_post_meta( get_queried_object_id(), '_voa_seo_title', true );
	return $t ? $t : $title;
}
add_action( 'wp_head', 'voa_seo_output_head', 1 );
function voa_seo_output_head() {
	if ( voa_seo_plugin_active() || ! is_singular() ) {
		return;
	}
	$id = get_queried_object_id();
	if ( ! $id ) {
		return;
	}
	$desc   = get_post_meta( $id, '_voa_seo_desc', true );
	$canon  = get_post_meta( $id, '_voa_seo_canonical', true );
	$ogt    = get_post_meta( $id, '_voa_seo_og_title', true );
	$ogd    = get_post_meta( $id, '_voa_seo_og_desc', true );
	$ogi    = get_post_meta( $id, '_voa_seo_og_image', true );
	$jsonld = get_post_meta( $id, '_voa_seo_jsonld', true );
	if ( ! $desc && ! $canon && ! $ogt && ! $jsonld ) {
		return;
	}
	echo "\n<!-- SEO managed by MyMomo -->\n";
	if ( $desc ) {
		echo '<meta name="description" content="' . esc_attr( $desc ) . '" />' . "\n";
	}
	if ( $canon ) {
		echo '<link rel="canonical" href="' . esc_url( $canon ) . '" />' . "\n";
	}
	$ogt = $ogt ? $ogt : get_post_meta( $id, '_voa_seo_title', true );
	$ogd = $ogd ? $ogd : $desc;
	if ( $ogt ) {
		echo '<meta property="og:title" content="' . esc_attr( $ogt ) . '" />' . "\n";
	}
	if ( $ogd ) {
		echo '<meta property="og:description" content="' . esc_attr( $ogd ) . '" />' . "\n";
	}
	if ( $ogi ) {
		echo '<meta property="og:image" content="' . esc_url( $ogi ) . '" />' . "\n";
	}
	echo '<meta property="og:type" content="website" />' . "\n";
	if ( $jsonld ) {
		// Re-encode to neutralise any embedded </script> before output.
		$decoded = json_decode( $jsonld, true );
		if ( null !== $decoded ) {
			echo '<script type="application/ld+json">' . wp_json_encode( $decoded ) . '</script>' . "\n";
		}
	}
}

// POST /voa/v1/seo - set per-page SEO meta from CC. Body: { items: [ { id|url,
// title, metaDescription, canonical, ogTitle, ogDescription, ogImage, jsonLd } ] }.
// Resolve an image URL (including resized -WxH variants) to its attachment ID,
// so SEO pushes can set the image's alt text. Falls back to the full-size URL
// when the given URL points at a generated thumbnail.
function voa_attachment_id_from_url( $url ) {
	$url = preg_replace( '/[#?].*$/', '', trim( (string) $url ) );
	if ( '' === $url ) {
		return 0;
	}
	$id = attachment_url_to_postid( $url );
	if ( ! $id ) {
		$full = preg_replace( '/-\d+x\d+(\.[a-z0-9]+)$/i', '$1', $url );
		if ( $full !== $url ) {
			$id = attachment_url_to_postid( $full );
		}
	}
	return (int) $id;
}

function voa_endpoint_set_seo( $request ) {
	$body  = $request->get_json_params();
	$items = ( isset( $body['items'] ) && is_array( $body['items'] ) ) ? $body['items'] : array();
	if ( empty( $items ) ) {
		return new WP_REST_Response( array( 'error' => 'items required' ), 400 );
	}
	$map = array(
		'title'           => '_voa_seo_title',
		'metaDescription' => '_voa_seo_desc',
		'description'     => '_voa_seo_desc',
		'canonical'       => '_voa_seo_canonical',
		'ogTitle'         => '_voa_seo_og_title',
		'ogDescription'   => '_voa_seo_og_desc',
		'ogImage'         => '_voa_seo_og_image',
	);
	$results = array();
	$purge_pids = array();
	foreach ( $items as $it ) {
		$pid = 0;
		if ( ! empty( $it['id'] ) ) {
			$pid = intval( $it['id'] );
		} elseif ( ! empty( $it['url'] ) ) {
			$pid = url_to_postid( esc_url_raw( $it['url'] ) );
		}
		if ( ! $pid ) {
			$results[] = array( 'url' => isset( $it['url'] ) ? $it['url'] : null, 'ok' => false, 'error' => 'page not found' );
			continue;
		}
		foreach ( $map as $k => $meta ) {
			if ( array_key_exists( $k, $it ) ) {
				update_post_meta( $pid, $meta, sanitize_text_field( wp_unslash( (string) $it[ $k ] ) ) );
			}
		}
		if ( array_key_exists( 'jsonLd', $it ) ) {
			$ld     = $it['jsonLd'];
			$ld_str = is_string( $ld ) ? $ld : wp_json_encode( $ld );
			if ( null !== json_decode( $ld_str ) ) {
				update_post_meta( $pid, '_voa_seo_jsonld', wp_slash( $ld_str ) );
			}
		}
		$alt_done = 0;
		if ( ! empty( $it['imageAlts'] ) && is_array( $it['imageAlts'] ) ) {
			foreach ( $it['imageAlts'] as $ia ) {
				if ( empty( $ia['src'] ) || ! isset( $ia['alt'] ) ) {
					continue;
				}
				$aid = voa_attachment_id_from_url( (string) $ia['src'] );
				if ( $aid ) {
					update_post_meta( $aid, '_wp_attachment_image_alt', sanitize_text_field( wp_unslash( (string) $ia['alt'] ) ) );
					$alt_done++;
				}
			}
		}
		voa_purge_post_cache( $pid );
		$purge_pids[] = $pid;
		$results[] = array( 'id' => $pid, 'url' => get_permalink( $pid ), 'ok' => true, 'altsApplied' => $alt_done );
	}
	voa_wpstaq_clear_page_cache( $purge_pids );
	return new WP_REST_Response( array( 'ok' => true, 'results' => $results ), 200 );
}

// ── Favicon / Site Icon ────────────────────────────────────────────────────
// POST /voa/v1/site-icon  { imageUrl } | { attachmentId }
// Sets the native WordPress Site Icon. WP then emits the full favicon set Google
// and browsers read - <link rel="icon"> at 32/192, <link rel="apple-touch-icon">
// at 180, the msapplication tile at 270 - and serves /favicon.ico. The SERVER
// generates the square sizes (below), so no client-side image tooling is needed.
function voa_add_site_icon_sizes( $sizes ) {
	foreach ( array( 32, 180, 192, 270, 512 ) as $s ) {
		$sizes[ 'site_icon-' . $s ] = array( 'width' => $s, 'height' => $s, 'crop' => true );
	}
	return $sizes;
}

function voa_endpoint_set_site_icon( $request ) {
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	$body = $request->get_json_params();
	$att  = ! empty( $body['attachmentId'] ) ? intval( $body['attachmentId'] ) : 0;
	$url  = ! empty( $body['imageUrl'] ) ? esc_url_raw( $body['imageUrl'] ) : '';
	if ( ! $att && $url ) {
		$tmp = download_url( $url );
		if ( is_wp_error( $tmp ) ) {
			return new WP_REST_Response( array( 'error' => 'download failed: ' . $tmp->get_error_message() ), 400 );
		}
		$name = basename( (string) wp_parse_url( $url, PHP_URL_PATH ) );
		if ( ! $name ) { $name = 'site-icon.png'; }
		$att = media_handle_sideload( array( 'name' => $name, 'tmp_name' => $tmp ), 0 );
		if ( is_wp_error( $att ) ) {
			if ( file_exists( $tmp ) ) { wp_delete_file( $tmp ); }
			return new WP_REST_Response( array( 'error' => 'sideload failed: ' . $att->get_error_message() ), 400 );
		}
	}
	if ( ! $att ) {
		return new WP_REST_Response( array( 'error' => 'attachmentId or imageUrl required' ), 400 );
	}
	if ( ! wp_attachment_is_image( $att ) ) {
		return new WP_REST_Response( array( 'error' => 'attachment is not an image' ), 400 );
	}
	// Generate the square sizes WP serves in its favicon tags.
	add_filter( 'intermediate_image_sizes_advanced', 'voa_add_site_icon_sizes' );
	$file = get_attached_file( $att );
	if ( $file && file_exists( $file ) ) {
		$meta = wp_generate_attachment_metadata( $att, $file );
		if ( ! is_wp_error( $meta ) ) { wp_update_attachment_metadata( $att, $meta ); }
	}
	remove_filter( 'intermediate_image_sizes_advanced', 'voa_add_site_icon_sizes' );
	update_option( 'site_icon', $att );
	// Purge the front page + blog home so the favicon appears immediately where it
	// matters most (Google reads the homepage); other pages refresh as they cycle.
	$front = (int) get_option( 'page_on_front' );
	if ( $front ) { voa_purge_post_cache( $front ); }
	$blog = (int) get_option( 'page_for_posts' );
	if ( $blog ) { voa_purge_post_cache( $blog ); }
	return new WP_REST_Response( array(
		'ok'           => true,
		'attachmentId' => $att,
		'icons'        => array(
			'32'  => get_site_icon_url( 32 ),
			'180' => get_site_icon_url( 180 ),
			'192' => get_site_icon_url( 192 ),
			'270' => get_site_icon_url( 270 ),
		),
	), 200 );
}

// POST /voa/v1/root-file - write a verification / well-known file to the web root
// (ABSPATH). Needed because search engines & favicon.ico are fetched as STATIC
// files from the document root, which the host's nginx serves directly (a path
// like /googleXXX.html or /favicon.ico never reaches WordPress). Tightly scoped:
// basename only (no traversal), safe extensions only (never .php), 256KB cap.
// Body: { filename, content? | contentBase64? | fromUrl? }.
function voa_endpoint_write_root_file( $request ) {
	$body     = $request->get_json_params();
	$filename = isset( $body['filename'] ) ? basename( (string) $body['filename'] ) : '';
	if ( ! $filename || ! preg_match( '/^[A-Za-z0-9][A-Za-z0-9._-]{0,127}\.(html|htm|txt|xml|ico|png)$/', $filename ) ) {
		return new WP_REST_Response( array( 'error' => 'invalid filename - basename only, extension must be html/htm/txt/xml/ico/png' ), 400 );
	}
	if ( strpos( $filename, '..' ) !== false || in_array( strtolower( $filename ), array( 'index.html', 'index.htm', 'wp-config.php' ), true ) ) {
		return new WP_REST_Response( array( 'error' => 'filename not permitted' ), 400 );
	}
	$bytes = null;
	if ( ! empty( $body['fromUrl'] ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		$tmp = download_url( esc_url_raw( $body['fromUrl'] ) );
		if ( is_wp_error( $tmp ) ) {
			return new WP_REST_Response( array( 'error' => 'download failed: ' . $tmp->get_error_message() ), 400 );
		}
		$bytes = file_get_contents( $tmp ); // phpcs:ignore
		wp_delete_file( $tmp );
	} elseif ( isset( $body['contentBase64'] ) ) {
		$bytes = base64_decode( (string) $body['contentBase64'], true );
		if ( false === $bytes ) {
			return new WP_REST_Response( array( 'error' => 'invalid base64' ), 400 );
		}
	} elseif ( isset( $body['content'] ) ) {
		$bytes = (string) $body['content'];
	} else {
		return new WP_REST_Response( array( 'error' => 'content, contentBase64, or fromUrl required' ), 400 );
	}
	if ( strlen( $bytes ) > 262144 ) {
		return new WP_REST_Response( array( 'error' => 'file too large (max 256KB)' ), 400 );
	}
	if ( ! is_writable( ABSPATH ) ) {
		return new WP_REST_Response( array( 'error' => 'web root is not writable by PHP', 'root' => ABSPATH, 'writable' => false ), 500 );
	}
	$path    = ABSPATH . $filename;
	$written = file_put_contents( $path, $bytes ); // phpcs:ignore
	if ( false === $written ) {
		return new WP_REST_Response( array( 'error' => 'write failed', 'path' => $path ), 500 );
	}
	return new WP_REST_Response( array( 'ok' => true, 'url' => home_url( '/' . $filename ), 'bytes' => $written ), 200 );
}

// ── Visitor analytics beacon ────────────────────────────────────────────────
// Loads the first-party, cookieless tracker on the front end. ON by default for
// every connected site; disable per-site with option voa_analytics_disabled=1.
// The tracking logic lives in the worker (a.js) so it can evolve with no plugin
// update. We skip admin/editor/audit requests and logged-in staff to avoid skew.
add_action( 'wp_footer', 'voa_analytics_beacon', 20 );
function voa_analytics_beacon() {
	if ( get_option( 'voa_analytics_disabled' ) ) {
		return;
	}
	if ( is_admin() || is_customize_preview() ) {
		return;
	}
	if ( isset( $_GET['voa_edit'] ) || isset( $_GET['voa_t'] ) || isset( $_GET['voacheck'] ) || isset( $_GET['_voseo'] ) ) {
		return; // Live Editor / SEO-audit fetches - don't count as visits
	}
	if ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) {
		return; // don't track the owner/staff
	}
	echo "\n<script async src=\"https://api.virtualofficeai.com.au/api/analytics/a.js\"></script>\n";
}

// ── Behavior insights beacon ────────────────────────────────────────────────
// Loads the first-party behaviour tracker (heatmaps, scroll maps, session
// replay) on the front end. Mirrors voa_analytics_beacon: ON by default,
// disable per-site with option voa_behavior_disabled=1. The key is minted in
// MyMomo and stored as voa_behavior_key; t.js reads it from ?key= so the
// worker can resolve the site without a per-request lookup. Same admin/editor/
// staff skips as the analytics beacon to avoid skewing the data.
add_action( 'wp_footer', 'voa_behavior_beacon', 21 );
function voa_behavior_beacon() {
	if ( get_option( 'voa_behavior_disabled' ) ) {
		return;
	}
	if ( is_admin() || is_customize_preview() ) {
		return;
	}
	if ( isset( $_GET['voa_edit'] ) || isset( $_GET['voa_t'] ) || isset( $_GET['voacheck'] ) || isset( $_GET['_voseo'] ) ) {
		return; // Live Editor / SEO-audit fetches - don't count as visits
	}
	if ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) {
		return; // don't track the owner/staff
	}
	echo "\n<script async src=\"https://api.virtualofficeai.com.au/api/behavior/t.js?key=" . esc_attr( get_option( 'voa_behavior_key' ) ) . "\"></script>\n";
}

// ── Voice agent: receive cached config from MyMomo ───────────────────────────
// Worker -> site (X-VOA-Key). Stores a public-safe subset the front-end widget
// renders from, so the widget paints instantly with no round-trip.
function voa_endpoint_voice_config_set( $request ) {
	$auth = voa_validate_request();
	if ( is_wp_error( $auth ) ) {
		return $auth;
	}
	$body = $request->get_json_params();
	if ( ! is_array( $body ) ) {
		$body = array();
	}
	$branding = isset( $body['branding'] ) && is_array( $body['branding'] ) ? $body['branding'] : array();
	$accent   = isset( $branding['accent'] ) ? sanitize_hex_color( $branding['accent'] ) : '';
	if ( empty( $accent ) ) {
		$accent = '#4f46e5';
	}
	$config = array(
		'enabled'      => ! empty( $body['enabled'] ),
		'greeting'     => isset( $body['greeting'] ) ? sanitize_text_field( $body['greeting'] ) : '',
		'voiceId'      => isset( $body['voiceId'] ) ? sanitize_text_field( $body['voiceId'] ) : '',
		'businessName' => isset( $body['businessName'] ) ? sanitize_text_field( $body['businessName'] ) : '',
		'branding'     => array(
			'accent'         => $accent,
			'label'          => isset( $branding['label'] ) ? sanitize_text_field( $branding['label'] ) : 'Talk to us',
			'position'       => ( isset( $branding['position'] ) && 'bottom-left' === $branding['position'] ) ? 'bottom-left' : 'bottom-right',
			'greetingBubble' => ! isset( $branding['greetingBubble'] ) || ! empty( $branding['greetingBubble'] ),
		),
		'updatedAt'    => current_time( 'mysql' ),
	);
	update_option( VOA_VOICE_CONFIG_OPTION, $config, false );
	return rest_ensure_response( array( 'success' => true ) );
}

// ── Voice agent: mint a browser session ─────────────────────────────────────
// PUBLIC (website visitor calls this). We proxy to the worker server-side with
// the site's X-VOA-Key so the secret never reaches the browser. The worker
// enforces the plan gate + that the widget is enabled for this host, and
// returns a short-lived Cartesia token.
function voa_endpoint_voice_session( $request ) {
	$rl = voa_check_rate_limit();
	if ( is_wp_error( $rl ) ) {
		return $rl;
	}
	$config = get_option( VOA_VOICE_CONFIG_OPTION );
	if ( empty( $config ) || empty( $config['enabled'] ) ) {
		return new WP_Error( 'voa_voice_disabled', 'Voice agent is not enabled for this site.', array( 'status' => 403 ) );
	}
	$key = voa_get_api_key();
	if ( empty( $key ) ) {
		return new WP_Error( 'voa_no_key', 'Site is not connected.', array( 'status' => 503 ) );
	}
	$resp = wp_remote_post(
		VOA_WORKER_BASE . '/api/voice/site-token',
		array(
			'timeout' => 15,
			'headers' => array(
				'Content-Type' => 'application/json',
				'X-VOA-Key'    => $key,
			),
			'body'    => wp_json_encode( array( 'siteUrl' => home_url() ) ),
		)
	);
	if ( is_wp_error( $resp ) ) {
		return new WP_Error( 'voa_voice_upstream', 'Voice service unavailable.', array( 'status' => 502 ) );
	}
	$code = (int) wp_remote_retrieve_response_code( $resp );
	$data = json_decode( wp_remote_retrieve_body( $resp ), true );
	if ( 200 !== $code || ! is_array( $data ) ) {
		$msg = is_array( $data ) && isset( $data['error'] ) ? $data['error'] : 'Voice service error.';
		return new WP_Error( 'voa_voice_upstream', $msg, array( 'status' => 502 ) );
	}
	return rest_ensure_response( $data );
}

// ── Voice agent: front-end widget ───────────────────────────────────────────
// Floating, branded, real-time voice button. Renders only when the cached
// config says the widget is enabled. Skips admin / staff / editor contexts.
add_action( 'wp_footer', 'voa_voice_widget', 25 );
function voa_voice_widget() {
	if ( is_admin() || is_customize_preview() ) {
		return;
	}
	if ( isset( $_GET['voa_edit'] ) || isset( $_GET['voa_t'] ) || isset( $_GET['voacheck'] ) || isset( $_GET['_voseo'] ) ) {
		return;
	}
	$config = get_option( VOA_VOICE_CONFIG_OPTION );
	if ( empty( $config ) || empty( $config['enabled'] ) ) {
		return;
	}
	$branding = isset( $config['branding'] ) && is_array( $config['branding'] ) ? $config['branding'] : array();
	$cfg = array(
		'sessionUrl' => esc_url_raw( rest_url( 'voa/v1/voice/session' ) ),
		'agentBase'  => 'wss://api.cartesia.ai/agents/stream/',
		'accent'     => isset( $branding['accent'] ) ? $branding['accent'] : '#4f46e5',
		'label'      => isset( $branding['label'] ) ? $branding['label'] : 'Talk to us',
		'position'   => isset( $branding['position'] ) ? $branding['position'] : 'bottom-right',
		'greeting'   => isset( $config['greeting'] ) ? $config['greeting'] : '',
		'bubble'     => ! isset( $branding['greetingBubble'] ) || ! empty( $branding['greetingBubble'] ),
	);
	echo "\n<div id=\"voa-voice-root\"></div>\n";
	echo '<script>(function(){var VOA_CFG=' . wp_json_encode( $cfg ) . ";\n" . voa_voice_widget_js() . "\n})();</script>\n";
}

// The widget client. Vanilla JS, no external deps. Connects to Cartesia's
// agent stream directly (the access token goes in the query string) and streams
// PCM16@16kHz both ways - the same protocol the in-app agent uses.
function voa_voice_widget_js() {
	return <<<'JS'
var st='idle',ws=null,playCtx=null,micCtx=null,micStream=null,proc=null,micSrc=null,streamId=null,nextPlay=0;
var pos=VOA_CFG.position==='bottom-left'?'left:20px;':'right:20px;';
var wrap=document.createElement('div');
wrap.style.cssText='position:fixed;bottom:20px;'+pos+'z-index:2147483000;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;';
var btn=document.createElement('button');
btn.style.cssText='display:flex;align-items:center;gap:9px;padding:13px 18px;border:none;border-radius:999px;cursor:pointer;color:#fff;font-size:14px;font-weight:700;box-shadow:0 8px 28px rgba(0,0,0,0.28);background:'+VOA_CFG.accent+';transition:box-shadow .2s;';
var dot=document.createElement('span');
dot.style.cssText='width:10px;height:10px;border-radius:50%;background:#fff;flex:none;';
var label=document.createElement('span');
btn.appendChild(dot);btn.appendChild(label);wrap.appendChild(btn);
var bubble=null;
if(VOA_CFG.bubble&&VOA_CFG.greeting){
  bubble=document.createElement('div');
  bubble.textContent=VOA_CFG.greeting;
  bubble.style.cssText='max-width:240px;margin-bottom:10px;padding:11px 14px;background:#fff;color:#111;border-radius:14px;font-size:13px;line-height:1.45;box-shadow:0 8px 28px rgba(0,0,0,0.2);'+(VOA_CFG.position==='bottom-left'?'':'margin-left:auto;');
  wrap.insertBefore(bubble,btn);
  setTimeout(function(){if(bubble)bubble.style.display='none';},9000);
}
document.body.appendChild(wrap);

function setState(s){
  st=s;
  dot.style.background=s==='live'?'#22c55e':(s==='connecting'?'#fde047':'#fff');
  label.textContent=s==='live'?'Listening, tap to end':(s==='connecting'?'Connecting...':VOA_CFG.label);
  btn.style.boxShadow=s==='live'?'0 0 0 4px rgba(255,255,255,0.35),0 8px 28px rgba(0,0,0,0.28)':'0 8px 28px rgba(0,0,0,0.28)';
}
function stop(){
  try{if(proc){proc.onaudioprocess=null;proc.disconnect();}}catch(e){}
  try{if(micSrc)micSrc.disconnect();}catch(e){}
  try{if(micStream)micStream.getTracks().forEach(function(t){t.stop();});}catch(e){}
  try{if(micCtx)micCtx.close();}catch(e){}
  try{if(playCtx)playCtx.close();}catch(e){}
  try{if(ws)ws.close();}catch(e){}
  ws=null;playCtx=null;micCtx=null;micStream=null;proc=null;micSrc=null;streamId=null;nextPlay=0;
  setState('idle');
}
function playChunk(b64){
  try{
    var bin=atob(b64),bytes=new Uint8Array(bin.length),i;
    for(i=0;i<bin.length;i++)bytes[i]=bin.charCodeAt(i);
    var i16=new Int16Array(bytes.buffer);if(!i16.length)return;
    var f32=new Float32Array(i16.length);
    for(i=0;i<i16.length;i++)f32[i]=i16[i]/(i16[i]<0?0x8000:0x7FFF);
    var buf=playCtx.createBuffer(1,f32.length,16000);buf.getChannelData(0).set(f32);
    var src=playCtx.createBufferSource();src.buffer=buf;src.connect(playCtx.destination);
    var now=playCtx.currentTime;if(nextPlay<now+0.02)nextPlay=now+0.02;
    src.start(nextPlay);nextPlay+=buf.duration;
  }catch(e){}
}
function startMic(){
  return navigator.mediaDevices.getUserMedia({audio:{echoCancellation:true,noiseSuppression:true,autoGainControl:true}}).then(function(stream){
    micStream=stream;
    micCtx=new (window.AudioContext||window.webkitAudioContext)();
    if(micCtx.state==='suspended'){try{micCtx.resume();}catch(e){}}
    micSrc=micCtx.createMediaStreamSource(stream);
    proc=micCtx.createScriptProcessor(2048,1,1);
    proc.onaudioprocess=function(ev){
      if(!ws||ws.readyState!==1||!streamId)return;
      var raw=ev.inputBuffer.getChannelData(0),ratio=micCtx.sampleRate/16000,len=Math.round(raw.length/ratio),out=new Int16Array(len),i;
      for(i=0;i<len;i++){var idx=i*ratio,lo=Math.floor(idx),hi=Math.min(lo+1,raw.length-1),s=Math.max(-1,Math.min(1,raw[lo]*(1-(idx-lo))+raw[hi]*(idx-lo)));out[i]=s<0?s*0x8000:s*0x7FFF;}
      var b2=new Uint8Array(out.buffer),bs='';
      for(i=0;i<b2.length;i++)bs+=String.fromCharCode(b2[i]);
      ws.send(JSON.stringify({event:'media_input',stream_id:streamId,media:{payload:btoa(bs)}}));
    };
    micSrc.connect(proc);proc.connect(micCtx.destination);
  });
}
function start(){
  setState('connecting');
  if(bubble)bubble.style.display='none';
  fetch(VOA_CFG.sessionUrl,{method:'POST',headers:{'Content-Type':'application/json'},body:'{}'}).then(function(r){
    return r.json().then(function(d){return {ok:r.ok,d:d};});
  }).then(function(res){
    var d=res.d;
    if(!res.ok||!d||!d.access_token||!d.agent_id){throw new Error((d&&d.error)||'unavailable');}
    var url=VOA_CFG.agentBase+d.agent_id+'?access_token='+encodeURIComponent(d.access_token)+'&cartesia_version='+encodeURIComponent(d.cartesia_version||'2025-04-16');
    ws=new WebSocket(url);
    playCtx=new (window.AudioContext||window.webkitAudioContext)({sampleRate:16000});
    if(playCtx.state==='suspended'){try{playCtx.resume();}catch(e){}}
    ws.onopen=function(){
      ws.send(JSON.stringify({event:'start',config:{input_format:'pcm_16000',output_format:'pcm_16000'},metadata:{session_token:d.session_token}}));
      setState('live');
      startMic().catch(function(){stop();});
    };
    ws.onmessage=function(e){
      var m;try{m=JSON.parse(e.data);}catch(x){return;}
      if(!streamId)streamId=m.stream_id||m.id||'default';
      var ty=m.event||m.type;
      if(ty==='media_output'&&m.media&&m.media.payload)playChunk(m.media.payload);
    };
    ws.onerror=function(){};
    ws.onclose=function(){stop();};
  }).catch(function(){stop();});
}
btn.addEventListener('click',function(){if(st==='idle')start();else stop();});
setState('idle');
JS;
}

// ── Keep thin author/date archives out of the index ─────────────────────────
// Drop the users provider from the core sitemap and noindex author/date archives
// so low-value pages don't dilute crawl/indexing. Stands down if a real SEO
// plugin is managing this already.
add_filter( 'wp_sitemaps_add_provider', 'voa_sitemap_drop_users', 10, 2 );
function voa_sitemap_drop_users( $provider, $name ) {
	return ( 'users' === $name ) ? false : $provider;
}
add_action( 'wp_head', 'voa_noindex_thin_archives', 1 );
function voa_noindex_thin_archives() {
	if ( voa_seo_plugin_active() ) {
		return;
	}
	// Thin/duplicate archives: author, date, category, tag and other taxonomy
	// archives. noindex,follow keeps link equity flowing while keeping these
	// low-value listing pages out of the index.
	if ( is_author() || is_date() || is_category() || is_tag() || is_tax() ) {
		echo '<meta name="robots" content="noindex,follow" />' . "\n";
	}
}

// Purge a single post's full-page cache so SEO changes appear immediately.
// update_post_meta alone does NOT trigger a purge, and managed-host nginx caches
// (e.g. WPStaq) don't listen to clean_post_cache / plugin-specific hooks. The one
// thing they DO purge on is a genuine post update (save_post / transition_post_status)
// - the same path the Live Editor uses successfully - so we fire wp_update_post.
function voa_purge_post_cache( $pid ) {
	static $busy = array();
	if ( ! $pid || isset( $busy[ $pid ] ) ) {
		return; // re-entrancy guard: wp_update_post fires save_post, which can loop back here
	}
	$busy[ $pid ] = true;
	$post = get_post( $pid );
	if ( $post && 'publish' === $post->post_status ) {
		// A no-content update: re-saves the post unchanged (same as clicking "Update"),
		// firing the hooks the host's page cache purges on. Does not alter page copy.
		wp_update_post( array( 'ID' => $pid ) );
	} else {
		clean_post_cache( $pid );
	}
	// Best-effort extra signals for sites that DO run these (harmless otherwise).
	if ( function_exists( 'wp_cache_post_change' ) ) {
		wp_cache_post_change( $pid ); // WP Super Cache
	}
	do_action( 'litespeed_purge_post', $pid );
	do_action( 'rt_nginx_helper_purge_all' );
	unset( $busy[ $pid ] );
}

// ── WPStaq page-cache auto-purge ─────────────────────────────────────────────
// WPStaq's nginx full-page cache has NO REST purge API and is NOT flushed by
// wp_update_post / clean_post_cache on a REST write (confirmed: a pushed SEO
// change stays HIT until the cache is cleared). Its only programmatic purge is
// the WP-CLI command `wp wpstaq clear-cache --post_id=N`. After a REST SEO write
// we fire that without blocking the request. Guarded to WPStaq - a no-op on
// every other host. Status is logged to options voa_wpstaq_purge_log /
// voa_wpstaq_purge_run_log for debugging (wp option get ...).
function voa_is_wpstaq() {
	return class_exists( '\WPStaq\Hosting\Helpers\StaqHelper' ) || is_dir( '/var/www/preflight' );
}
function voa_shell_exec_ok() {
	if ( ! function_exists( 'shell_exec' ) ) {
		return false;
	}
	$disabled = array_map( 'trim', explode( ',', (string) ini_get( 'disable_functions' ) ) );
	return ! in_array( 'shell_exec', $disabled, true );
}
function voa_wp_bin() {
	foreach ( array( '/usr/local/bin/wp', '/usr/bin/wp', '/bin/wp' ) as $p ) {
		if ( @is_executable( $p ) ) {
			return $p;
		}
	}
	return '';
}
// Dispatch a page-cache purge for the given post IDs after a REST SEO write.
// Instant via a detached shell call when the web process can shell out; otherwise
// async on the next WP-Cron tick (managed-host cron runs under WP-CLI, where the
// wpstaq command works).
function voa_wpstaq_clear_page_cache( $pids ) {
	if ( ! voa_is_wpstaq() ) {
		return;
	}
	$pids = array_values( array_unique( array_filter( array_map( 'intval', (array) $pids ) ) ) );
	if ( empty( $pids ) ) {
		return;
	}
	$bin = voa_wp_bin();
	if ( $bin && voa_shell_exec_ok() ) {
		foreach ( $pids as $pid ) {
			@shell_exec( 'nohup ' . escapeshellarg( $bin ) . ' wpstaq clear-cache --post_id=' . $pid . ' --path=' . escapeshellarg( ABSPATH ) . ' > /dev/null 2>&1 &' );
		}
		update_option( 'voa_wpstaq_purge_log', gmdate( 'c' ) . ' inline ' . implode( ',', $pids ), false );
		return;
	}
	wp_schedule_single_event( time() + 1, 'voa_wpstaq_purge_event', array( $pids ) );
	update_option( 'voa_wpstaq_purge_log', gmdate( 'c' ) . ' scheduled ' . implode( ',', $pids ), false );
}
add_action( 'voa_wpstaq_purge_event', 'voa_wpstaq_run_purge' );
function voa_wpstaq_run_purge( $pids ) {
	$log = array();
	foreach ( (array) $pids as $pid ) {
		$pid = (int) $pid;
		if ( ! $pid ) {
			continue;
		}
		if ( class_exists( 'WP_CLI' ) ) {
			try {
				\WP_CLI::runcommand( "wpstaq clear-cache --post_id={$pid}", array( 'launch' => false, 'exit_error' => false ) );
				$log[] = $pid . ':cli';
				continue;
			} catch ( \Throwable $e ) {
				$log[] = $pid . ':clierr';
			}
		}
		$bin = voa_wp_bin();
		if ( $bin && voa_shell_exec_ok() ) {
			@shell_exec( escapeshellarg( $bin ) . ' wpstaq clear-cache --post_id=' . $pid . ' --path=' . escapeshellarg( ABSPATH ) . ' 2>&1' );
			$log[] = $pid . ':shell';
		} else {
			$log[] = $pid . ':noexec';
		}
	}
	update_option( 'voa_wpstaq_purge_run_log', gmdate( 'c' ) . ' ' . implode( ',', $log ), false );
}

// ── Yoast-style SEO meta box ───────────────────────────────────────────────
// Makes the CC-managed SEO fields VISIBLE and editable in the WP editor on every
// page/post - reading & writing the same _voa_seo_* meta the REST endpoint sets
// and the front-end (voa_seo_output_head / voa_seo_filter_title) renders. Stands
// down if Yoast / Rank Math is active so we never double up.
add_action( 'add_meta_boxes', 'voa_seo_add_meta_box' );
function voa_seo_add_meta_box() {
	if ( voa_seo_plugin_active() ) {
		return;
	}
	$types = get_post_types( array( 'public' => true ), 'names' );
	unset( $types['attachment'] );
	foreach ( $types as $pt ) {
		add_meta_box( 'voa_seo_box', 'MyMomo - SEO', 'voa_seo_render_meta_box', $pt, 'normal', 'high' );
	}
}

// Build a clean fallback description from a post's content for the editor preview
// & placeholder. Some pages have CSS (e.g. ":root{ --primary:#000; }") or builder
// markup sitting in post_content as plain text - strip it so the hint is readable.
function voa_seo_fallback_excerpt( $post ) {
	$raw = has_excerpt( $post ) ? get_the_excerpt( $post ) : $post->post_content;
	$raw = preg_replace( '#<(style|script)\b[^>]*>.*?</\1>#is', ' ', $raw ); // drop style/script blocks
	$raw = strip_shortcodes( $raw );
	$raw = wp_strip_all_tags( $raw );
	$raw = preg_replace( '/[@.#:][^{}\n]{0,160}\{[^{}]*\}/s', ' ', $raw );   // ":root{…}", ".cls{…}", "@media…{…}"
	$raw = preg_replace( '/--[a-z0-9-]+\s*:\s*[^;{}]+;?/i', ' ', $raw );      // leftover CSS custom props
	$raw = html_entity_decode( (string) $raw, ENT_QUOTES, 'UTF-8' );
	$raw = preg_replace( '/\s+/', ' ', $raw );
	return wp_trim_words( trim( (string) $raw ), 30, '' );
}

function voa_seo_render_meta_box( $post ) {
	wp_nonce_field( 'voa_seo_save', 'voa_seo_nonce' );
	$title  = get_post_meta( $post->ID, '_voa_seo_title', true );
	$desc   = get_post_meta( $post->ID, '_voa_seo_desc', true );
	$canon  = get_post_meta( $post->ID, '_voa_seo_canonical', true );
	$ogt    = get_post_meta( $post->ID, '_voa_seo_og_title', true );
	$ogd    = get_post_meta( $post->ID, '_voa_seo_og_desc', true );
	$ogi    = get_post_meta( $post->ID, '_voa_seo_og_image', true );
	$jsonld = get_post_meta( $post->ID, '_voa_seo_jsonld', true );
	$perma  = get_permalink( $post->ID );
	$ph_t   = get_the_title( $post->ID ) . ' | ' . get_bloginfo( 'name' );
	$ph_d   = voa_seo_fallback_excerpt( $post );
	?>
	<div class="voa-seo-box">
		<p class="voa-seo-managed"><span class="voa-dot"></span> Managed by MyMomo. These fields control how this page appears in Google &amp; social. Leave a field blank to fall back to the site default.</p>

		<div class="voa-seo-preview">
			<div class="voa-prev-url"><?php echo esc_html( $perma ); ?></div>
			<div class="voa-prev-title" id="voa_prev_title"><?php echo esc_html( $title ? $title : $ph_t ); ?></div>
			<div class="voa-prev-desc" id="voa_prev_desc"><?php echo esc_html( $desc ? $desc : $ph_d ); ?></div>
		</div>

		<label class="voa-seo-label">SEO Title
			<span class="voa-count" id="voa_count_title"></span>
		</label>
		<input type="text" class="voa-seo-input" id="voa_seo_title" name="voa_seo_title" value="<?php echo esc_attr( $title ); ?>" placeholder="<?php echo esc_attr( $ph_t ); ?>" />

		<label class="voa-seo-label">Meta Description
			<span class="voa-count" id="voa_count_desc"></span>
		</label>
		<textarea class="voa-seo-input" id="voa_seo_desc" name="voa_seo_desc" rows="3" placeholder="<?php echo esc_attr( $ph_d ); ?>"><?php echo esc_textarea( $desc ); ?></textarea>

		<label class="voa-seo-label">Canonical URL <span class="voa-hint">(optional)</span></label>
		<input type="url" class="voa-seo-input" name="voa_seo_canonical" value="<?php echo esc_attr( $canon ); ?>" placeholder="<?php echo esc_attr( $perma ); ?>" />

		<details class="voa-seo-adv">
			<summary>Social sharing (Open Graph)</summary>
			<label class="voa-seo-label">OG Title</label>
			<input type="text" class="voa-seo-input" name="voa_seo_og_title" value="<?php echo esc_attr( $ogt ); ?>" placeholder="Defaults to the SEO title" />
			<label class="voa-seo-label">OG Description</label>
			<textarea class="voa-seo-input" name="voa_seo_og_desc" rows="2" placeholder="Defaults to the meta description"><?php echo esc_textarea( $ogd ); ?></textarea>
			<label class="voa-seo-label">OG Image URL</label>
			<input type="url" class="voa-seo-input" name="voa_seo_og_image" value="<?php echo esc_attr( $ogi ); ?>" placeholder="https://…/image.jpg" />
		</details>

		<details class="voa-seo-adv">
			<summary>Structured data (JSON-LD schema)</summary>
			<p class="voa-hint">Advanced - must be valid JSON. Invalid JSON is ignored on save.</p>
			<textarea class="voa-seo-input voa-mono" name="voa_seo_jsonld" rows="6" placeholder='{"@context":"https://schema.org", ...}'><?php echo esc_textarea( $jsonld ); ?></textarea>
		</details>
	</div>

	<style>
		.voa-seo-box{font-size:13px;color:#1d2327}
		.voa-seo-managed{display:flex;align-items:center;gap:8px;background:#f6f7f7;border:1px solid #e0e0e0;border-radius:6px;padding:8px 10px;margin:0 0 14px;color:#50575e}
		.voa-seo-box .voa-dot{width:8px;height:8px;border-radius:50%;background:#9BE610;flex:0 0 auto;box-shadow:0 0 0 3px rgba(155,230,16,.25)}
		.voa-seo-preview{border:1px solid #e0e0e0;border-radius:8px;padding:12px 14px;margin:0 0 16px;background:#fff;max-width:600px}
		.voa-prev-url{color:#006621;font-size:13px;line-height:1.3;word-break:break-all}
		.voa-prev-title{color:#1a0dab;font-size:18px;line-height:1.3;margin:2px 0;font-family:arial,sans-serif}
		.voa-prev-desc{color:#4d5156;font-size:13px;line-height:1.4}
		.voa-seo-label{display:block;font-weight:600;margin:14px 0 4px}
		.voa-seo-input{width:100%;max-width:600px;padding:7px 9px;border:1px solid #8c8f94;border-radius:4px;font-size:13px;box-sizing:border-box}
		.voa-mono{font-family:Menlo,Consolas,monospace;font-size:12px}
		.voa-count{font-weight:400;color:#646970;margin-left:6px;font-size:12px}
		.voa-count.ok{color:#0a7c2f}.voa-count.warn{color:#b26a00}.voa-count.bad{color:#b32d2e}
		.voa-hint{color:#646970;font-weight:400;font-size:12px}
		.voa-seo-adv{margin-top:16px;border-top:1px solid #f0f0f1;padding-top:10px}
		.voa-seo-adv summary{cursor:pointer;font-weight:600;color:#2271b1}
	</style>
	<script>
	(function(){
		var t=document.getElementById('voa_seo_title'),
		    d=document.getElementById('voa_seo_desc'),
		    ct=document.getElementById('voa_count_title'),
		    cd=document.getElementById('voa_count_desc'),
		    pt=document.getElementById('voa_prev_title'),
		    pd=document.getElementById('voa_prev_desc');
		if(!t)return;
		function band(el,n,lo,hi){el.textContent=n+' chars';el.className='voa-count '+(n===0?'':(n>hi?'bad':(n<lo?'warn':'ok')));}
		function upd(){
			band(ct,t.value.length,30,60);
			band(cd,d.value.length,70,155);
			pt.textContent=t.value||t.getAttribute('placeholder');
			pd.textContent=d.value||d.getAttribute('placeholder');
		}
		t.addEventListener('input',upd);d.addEventListener('input',upd);upd();
	})();
	</script>
	<?php
}

add_action( 'save_post', 'voa_seo_save_meta_box', 10, 2 );
function voa_seo_save_meta_box( $post_id, $post ) {
	if ( ! isset( $_POST['voa_seo_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['voa_seo_nonce'] ) ), 'voa_seo_save' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$text_fields = array(
		'voa_seo_title'    => '_voa_seo_title',
		'voa_seo_og_title' => '_voa_seo_og_title',
	);
	$area_fields = array(
		'voa_seo_desc'    => '_voa_seo_desc',
		'voa_seo_og_desc' => '_voa_seo_og_desc',
	);
	$url_fields = array(
		'voa_seo_canonical' => '_voa_seo_canonical',
		'voa_seo_og_image'  => '_voa_seo_og_image',
	);
	foreach ( $text_fields as $f => $m ) {
		if ( ! isset( $_POST[ $f ] ) ) {
			continue;
		}
		$v = sanitize_text_field( wp_unslash( $_POST[ $f ] ) );
		'' === $v ? delete_post_meta( $post_id, $m ) : update_post_meta( $post_id, $m, $v );
	}
	foreach ( $area_fields as $f => $m ) {
		if ( ! isset( $_POST[ $f ] ) ) {
			continue;
		}
		$v = sanitize_textarea_field( wp_unslash( $_POST[ $f ] ) );
		'' === $v ? delete_post_meta( $post_id, $m ) : update_post_meta( $post_id, $m, $v );
	}
	foreach ( $url_fields as $f => $m ) {
		if ( ! isset( $_POST[ $f ] ) ) {
			continue;
		}
		$v = esc_url_raw( wp_unslash( $_POST[ $f ] ) );
		'' === $v ? delete_post_meta( $post_id, $m ) : update_post_meta( $post_id, $m, $v );
	}
	if ( isset( $_POST['voa_seo_jsonld'] ) ) {
		$ld = trim( wp_unslash( $_POST['voa_seo_jsonld'] ) );
		if ( '' === $ld ) {
			delete_post_meta( $post_id, '_voa_seo_jsonld' );
		} elseif ( null !== json_decode( $ld ) ) {
			update_post_meta( $post_id, '_voa_seo_jsonld', wp_slash( $ld ) );
		}
	}
	voa_purge_post_cache( $post_id );
}

// Allow the MyMomo Live Editor to embed this site in an iframe, but ONLY
// for editor requests (identified by the voa_edit / voa_t query param the app
// always appends). The public site keeps its X-Frame-Options protection.
//
// Many managed hosts send "X-Frame-Options: SAMEORIGIN" at the web-server level,
// which PHP can't reliably remove. So instead we send a Content-Security-Policy
// "frame-ancestors" directive - browsers honour frame-ancestors in preference to
// X-Frame-Options, so this re-enables embedding even when the host sets XFO.
add_action( 'send_headers', 'voa_relax_framing_for_editor', 999 );
function voa_relax_framing_for_editor() {
	if ( ! isset( $_GET['voa_edit'] ) && ! isset( $_GET['voa_t'] ) ) {
		return;
	}
	if ( function_exists( 'header_remove' ) ) {
		header_remove( 'X-Frame-Options' ); // best-effort if PHP set it
	}

	// Every origin the MyMomo app is served from. The desktop shell loads
	// https://mymomo.com.au, so leaving it out of this list is what makes the
	// Live Editor render as a blank white panel on external sites — the browser
	// refuses the frame and the app has no way to see that it happened.
	// Keep the virtualofficeai.com.au entries: sites on an older connector and
	// the API host both still answer there.
	$origins = array(
		'https://mymomo.com.au',
		'https://*.mymomo.com.au',
		'https://virtualofficeai.com.au',
		'https://*.virtualofficeai.com.au',
		'https://*.pages.dev',
	);

	// Filterable so a new app domain never again needs a plugin release to fix.
	$origins = apply_filters( 'voa_frame_ancestors', $origins );

	// Only scheme://host (optionally *.host, optionally :port) is allowed through.
	// A filtered value containing a space or semicolon would otherwise let a caller
	// append arbitrary CSP directives to this header.
	$origins = array_values( array_filter( (array) $origins, function ( $origin ) {
		return is_string( $origin )
			&& preg_match( '#^https?://(\*\.)?[A-Za-z0-9.-]+(:[0-9]{1,5})?$#', $origin );
	} ) );
	if ( empty( $origins ) ) {
		return;
	}

	$ancestors = "frame-ancestors 'self' " . implode( ' ', $origins );
	header( "Content-Security-Policy: $ancestors", false );
}

// Edit mode script - injected when ?voa_edit=1 is in the URL (for Live Editor iframe)
add_action( 'wp_footer', 'voa_edit_mode_script', 9999 );
function voa_edit_mode_script() {
	if ( ! isset( $_GET['voa_edit'] ) || $_GET['voa_edit'] !== '1' ) {
		return;
	}
	?>
	<style id="voa-edit-mode-css">
		.voa-highlight { outline: 2px solid #9BE610 !important; outline-offset: 2px; cursor: pointer !important; transition: outline-color 0.15s; }
		.voa-selected { outline: 3px solid #06b6d4 !important; outline-offset: 2px; }
		.voa-edit-badge { position: fixed; top: 8px; right: 8px; background: #9BE610; color: #06060b; padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: 700; z-index: 999999; font-family: -apple-system, sans-serif; pointer-events: none; }
	</style>
	<div class="voa-edit-badge">Edit Mode</div>
	<script>
	(function() {
		var selected = null;
		var ignoreTags = {HTML:1, BODY:1, HEAD:1, SCRIPT:1, STYLE:1, LINK:1, META:1, NOSCRIPT:1, BR:1, HR:1};

		function getXPath(el) {
			if (!el || el.nodeType !== 1) return '';
			var parts = [];
			while (el && el.nodeType === 1) {
				var idx = 1;
				var sib = el.previousSibling;
				while (sib) { if (sib.nodeType === 1 && sib.tagName === el.tagName) idx++; sib = sib.previousSibling; }
				parts.unshift(el.tagName.toLowerCase() + '[' + idx + ']');
				el = el.parentNode;
			}
			return '/' + parts.join('/');
		}

		document.addEventListener('mouseover', function(e) {
			var t = e.target;
			if (ignoreTags[t.tagName] || t.closest('.voa-edit-badge')) return;
			t.classList.add('voa-highlight');
		});

		document.addEventListener('mouseout', function(e) {
			var t = e.target;
			t.classList.remove('voa-highlight');
		});

		// Build a sibling snapshot so the AI knows about repeated patterns
		// (e.g. user clicks one of three feature cards - we send all three).
		function siblingSnapshot(el) {
			if (!el || !el.parentElement) return [];
			var sibs = [];
			var children = el.parentElement.children || [];
			for (var i = 0; i < children.length && sibs.length < 6; i++) {
				var c = children[i];
				if (!c || c === el) continue;
				if (ignoreTags[c.tagName]) continue;
				var t = (c.innerText || '').replace(/\s+/g, ' ').trim().substring(0, 140);
				if (!t) continue;
				sibs.push({ tag: c.tagName.toLowerCase(), text: t });
			}
			return sibs;
		}

		// Look at child structure: useful when user clicks a wrapper and
		// the AI needs to know what's inside (icons, headings, paragraphs).
		function childSnapshot(el) {
			if (!el || !el.children) return [];
			var out = [];
			var children = el.querySelectorAll('h1, h2, h3, h4, h5, h6, p, button, a, img, svg');
			for (var i = 0; i < children.length && out.length < 12; i++) {
				var c = children[i];
				var t = (c.innerText || c.alt || '').replace(/\s+/g, ' ').trim().substring(0, 100);
				if (c.tagName === 'IMG') t = c.alt || c.src || '';
				if (c.tagName === 'SVG' && !t) t = '(svg icon)';
				out.push({ tag: c.tagName.toLowerCase(), text: t });
			}
			return out;
		}

		// Walk up the DOM looking for the nearest Elementor widget wrapper.
		// Elementor renders every widget inside <div class="elementor-element"
		// data-id="abc123" data-widget_type="heading.default">. Capturing
		// these lets the AI go straight to inspect_widget / update_widgets
		// with an exact id instead of guessing by text content.
		function findElementorContext(el) {
			var widget = null, section = null, container = null;
			var n = el;
			while (n && n !== document.body) {
				if (n.classList && n.classList.contains('elementor-element')) {
					var t = n.getAttribute('data-element_type') || '';
					if (!widget && (t.indexOf('widget') === 0 || n.getAttribute('data-widget_type'))) {
						widget = {
							id: n.getAttribute('data-id') || '',
							widgetType: (n.getAttribute('data-widget_type') || '').replace(/\.default$/, '') || null,
							elementType: t || null
						};
					}
					if (!section && t === 'section') {
						section = { id: n.getAttribute('data-id') || '' };
					}
					if (!container && t === 'container') {
						container = { id: n.getAttribute('data-id') || '' };
					}
				}
				n = n.parentNode;
			}
			return { widget: widget, section: section, container: container };
		}

		// If the click landed on an SVG/img/icon inside an icon widget, the
		// "widget" is actually the icon-box / icon wrapper. Surface the
		// click-target's intrinsic kind too so the AI can disambiguate.
		function targetKind(el) {
			if (!el) return null;
			if (el.tagName === 'IMG') return 'img';
			if (el.tagName === 'svg' || el.closest('svg')) return 'svg';
			if (el.tagName === 'I' && el.className && /\bfa[srbldt]?\b|fontawesome/i.test(el.className)) return 'fa-icon';
			if (el.tagName === 'I') return 'icon-i';
			if (el.tagName === 'A') return 'link';
			if (el.tagName === 'BUTTON') return 'button';
			if (/^H[1-6]$/.test(el.tagName)) return el.tagName.toLowerCase();
			if (el.tagName === 'P') return 'paragraph';
			return null;
		}

		document.addEventListener('click', function(e) {
			var t = e.target;
			if (ignoreTags[t.tagName] || t.closest('.voa-edit-badge')) return;
			e.preventDefault();
			e.stopPropagation();

			if (selected) selected.classList.remove('voa-selected');
			selected = t;
			t.classList.add('voa-selected');

			var text = (t.innerText || '').substring(0, 200);
			var cls = t.className.replace('voa-highlight', '').replace('voa-selected', '').trim();
			var styles = window.getComputedStyle(t);
			var elem = findElementorContext(t);

			window.parent.postMessage({
				type: 'voa-element-selected',
				element: {
					tag: t.tagName.toLowerCase(),
					text: text,
					classes: cls,
					id: t.id || '',
					xpath: getXPath(t),
					href: t.href || '',
					src: t.src || '',
					targetKind: targetKind(t),
					elementor: elem,
					currentStyles: {
						color: styles.color,
						backgroundColor: styles.backgroundColor,
						fontSize: styles.fontSize,
						fontWeight: styles.fontWeight,
						padding: styles.padding,
						margin: styles.margin
					},
					siblings: siblingSnapshot(t),
					children: childSnapshot(t),
					parentTag: t.parentElement ? t.parentElement.tagName.toLowerCase() : null,
					siblingCount: t.parentElement ? (t.parentElement.children.length - 1) : 0
				}
			}, '*');
		}, true);

		// Prevent navigation in edit mode
		document.addEventListener('click', function(e) {
			if (e.target.tagName === 'A' || e.target.closest('a')) {
				e.preventDefault();
			}
		}, true);
	})();
	</script>
	<?php
}

// Auto-update hooks - check our server for plugin updates
add_filter( 'pre_set_site_transient_update_plugins', 'voa_check_for_updates' );
add_filter( 'plugins_api', 'voa_plugin_info', 20, 3 );
add_filter( 'upgrader_post_install', 'voa_after_update', 10, 3 );

/**
 * Check for plugin updates from the VOA server.
 *
 * @param object $transient The update_plugins transient.
 * @return object Modified transient with our update info.
 */
function voa_check_for_updates( $transient ) {
	if ( empty( $transient->checked ) ) {
		return $transient;
	}

	$plugin_file = VOA_PLUGIN_SLUG . '/virtual-office-ai-connector.php';
	$response = wp_remote_get( add_query_arg( array(
		'version' => VOA_CONNECTOR_VERSION,
		'slug'    => VOA_PLUGIN_SLUG,
		'site'    => rawurlencode( (string) wp_parse_url( home_url(), PHP_URL_HOST ) ),
	), VOA_UPDATE_URL . '/check' ), array( 'timeout' => 10 ) );

	if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
		return $transient;
	}

	$update_data = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( ! empty( $update_data['new_version'] ) && version_compare( VOA_CONNECTOR_VERSION, $update_data['new_version'], '<' ) ) {
		$transient->response[ $plugin_file ] = (object) array(
			'slug'        => VOA_PLUGIN_SLUG,
			'plugin'      => $plugin_file,
			'new_version' => $update_data['new_version'],
			'package'     => $update_data['download_url'],
			'url'         => $update_data['homepage'] ?? 'https://virtualofficeai.com.au',
			'tested'      => $update_data['tested'] ?? '6.7',
			'requires'    => $update_data['requires'] ?? '5.8',
		);
	}

	return $transient;
}

/**
 * Provide plugin information for the WordPress plugin details modal.
 *
 * @param false|object|array $result The result object or false.
 * @param string             $action The API action.
 * @param object             $args   The plugin API arguments.
 * @return false|object
 */
function voa_plugin_info( $result, $action, $args ) {
	if ( 'plugin_information' !== $action || VOA_PLUGIN_SLUG !== ( $args->slug ?? '' ) ) {
		return $result;
	}

	$response = wp_remote_get( VOA_UPDATE_URL . '/info', array(
		'timeout' => 10,
		'body'    => array( 'slug' => VOA_PLUGIN_SLUG ),
	) );

	if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
		return $result;
	}

	$info = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( empty( $info ) ) {
		return $result;
	}

	return (object) array(
		'name'          => $info['name'] ?? 'MyMomo - Connector',
		'slug'          => VOA_PLUGIN_SLUG,
		'version'       => $info['version'] ?? VOA_CONNECTOR_VERSION,
		'author'        => $info['author'] ?? '<a href="https://cornerstoneandcompass.com">Cornerstone & Compass</a>',
		'homepage'      => $info['homepage'] ?? 'https://virtualofficeai.com.au',
		'download_link' => $info['download_url'] ?? '',
		'requires'      => $info['requires'] ?? '5.8',
		'tested'        => $info['tested'] ?? '6.7',
		'sections'      => array(
			'description' => $info['description'] ?? 'Connect your WordPress site to MyMomo.',
			'changelog'   => $info['changelog'] ?? '',
		),
	);
}

/**
 * After update, make sure the plugin folder name stays correct.
 *
 * @param bool  $response   Install response.
 * @param array $hook_extra Extra arguments passed to the upgrader.
 * @param array $result     Installation result data.
 * @return array
 */
function voa_after_update( $response, $hook_extra, $result ) {
	global $wp_filesystem;

	$plugin_file = VOA_PLUGIN_SLUG . '/virtual-office-ai-connector.php';

	if ( ! isset( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $plugin_file ) {
		return $result;
	}

	// Ensure the destination folder name matches our expected slug
	$proper_destination = WP_PLUGIN_DIR . '/' . VOA_PLUGIN_SLUG;
	if ( isset( $result['destination'] ) && $result['destination'] !== $proper_destination ) {
		$wp_filesystem->move( $result['destination'], $proper_destination );
		$result['destination'] = $proper_destination;
	}

	// Re-activate the plugin after update
	activate_plugin( $plugin_file );

	// Force WP to re-check our update endpoint on the next page load instead
	// of trusting the now-stale cached "update available" transient. Without
	// this, WP keeps showing "Update to 1.7.x" for hours after we already
	// installed 1.7.x and the version_compare returns false.
	delete_site_transient( 'update_plugins' );

	return $result;
}

/**
 * Sanitize HTML content from the VOA API while preserving modern CSS.
 *
 * wp_kses_post strips position, display:grid/flex, data URIs, and other CSS
 * properties required for modern page layouts. This function allows a broader
 * set of safe HTML/CSS while still removing scripts and dangerous attributes.
 *
 * @param string $content Raw HTML content.
 * @return string Sanitized HTML.
 */
function voa_sanitize_content( $content ) {
	// Remove script tags and event handlers but keep everything else
	$content = preg_replace( '/<script\b[^>]*>(.*?)<\/script>/is', '', $content );
	$content = preg_replace( '/\bon\w+\s*=\s*(["\']).*?\1/i', '', $content );
	$content = preg_replace( '/\bon\w+\s*=\s*\S+/i', '', $content );
	// Remove javascript: protocol in href/src
	$content = preg_replace( '/(?:href|src)\s*=\s*(["\'])javascript:.*?\1/i', 'href=$1#$1', $content );
	// Remove iframe, object, embed tags
	$content = preg_replace( '/<(iframe|object|embed)\b[^>]*>(.*?)<\/\1>/is', '', $content );
	$content = preg_replace( '/<(iframe|object|embed)\b[^>]*\/?>/is', '', $content );
	return $content;
}

/**
 * Convert HTML content into Elementor JSON data structure.
 *
 * Splits HTML into top-level sections (root-level <div> elements) and wraps
 * each in an Elementor section > column > HTML widget. This allows Elementor
 * to render the content natively while keeping each section independently
 * editable in the visual builder.
 *
 * @param string $html The HTML content to convert.
 * @return string JSON-encoded Elementor data array.
 */
function voa_html_to_elementor_data( $html ) {
	$elements = array();

	// Check if DOMDocument is available (some hosts disable it)
	if ( ! class_exists( 'DOMDocument' ) ) {
		return voa_wrap_single_elementor_widget( $html );
	}

	// Split into top-level elements using DOMDocument
	$doc = new DOMDocument();
	// Suppress warnings from HTML5 tags, wrap in UTF-8 envelope
	@$doc->loadHTML( '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>' . $html . '</body></html>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );

	$body = $doc->getElementsByTagName( 'body' )->item( 0 );
	if ( ! $body ) {
		// Fallback: wrap entire HTML in one widget
		return voa_wrap_single_elementor_widget( $html );
	}

	$child_count = 0;
	foreach ( $body->childNodes as $node ) {
		if ( XML_ELEMENT_NODE === $node->nodeType ) {
			$child_count++;
		}
	}

	// If no child elements found or only whitespace, wrap everything in one widget
	if ( 0 === $child_count ) {
		return voa_wrap_single_elementor_widget( $html );
	}

	foreach ( $body->childNodes as $node ) {
		// Skip text nodes and whitespace
		if ( XML_ELEMENT_NODE !== $node->nodeType ) {
			continue;
		}

		$section_html = $doc->saveHTML( $node );
		// Fix encoding artifacts from DOMDocument
		$section_html = html_entity_decode( $section_html, ENT_QUOTES, 'UTF-8' );

		$section_id = substr( md5( wp_rand() . microtime() ), 0, 7 );
		$column_id  = substr( md5( wp_rand() . microtime() . 'c' ), 0, 7 );
		$widget_id  = substr( md5( wp_rand() . microtime() . 'w' ), 0, 7 );

		$elements[] = array(
			'id'       => $section_id,
			'elType'   => 'section',
			'settings' => array(
				'stretch_section' => 'section-stretched',
				'layout'          => 'full_width',
				'gap'             => 'no',
				'content_width'   => array(
					'unit' => '%',
					'size' => 100,
				),
				'padding'         => array(
					'unit'     => 'px',
					'top'      => '0',
					'right'    => '0',
					'bottom'   => '0',
					'left'     => '0',
					'isLinked' => true,
				),
				'margin'          => array(
					'unit'     => 'px',
					'top'      => '0',
					'right'    => '0',
					'bottom'   => '0',
					'left'     => '0',
					'isLinked' => true,
				),
			),
			'elements' => array(
				array(
					'id'       => $column_id,
					'elType'   => 'column',
					'settings' => array(
						'_column_size' => 100,
						'padding'      => array(
							'unit'     => 'px',
							'top'      => '0',
							'right'    => '0',
							'bottom'   => '0',
							'left'     => '0',
							'isLinked' => true,
						),
					),
					'elements' => array(
						array(
							'id'         => $widget_id,
							'elType'     => 'widget',
							'widgetType' => 'html',
							'settings'   => array(
								'html' => $section_html,
							),
							'elements'   => array(),
						),
					),
				),
			),
		);
	}

	return wp_json_encode( $elements );
}

/**
 * Wrap entire HTML in a single Elementor section/column/widget.
 * Fallback when DOMDocument can't split sections.
 *
 * @param string $html Raw HTML content.
 * @return string JSON-encoded Elementor data.
 */
function voa_wrap_single_elementor_widget( $html ) {
	$section_id = substr( md5( wp_rand() . microtime() ), 0, 7 );
	$column_id  = substr( md5( wp_rand() . microtime() . 'c' ), 0, 7 );
	$widget_id  = substr( md5( wp_rand() . microtime() . 'w' ), 0, 7 );

	$data = array(
		array(
			'id'       => $section_id,
			'elType'   => 'section',
			'settings' => array(
				'stretch_section' => 'section-stretched',
				'layout'          => 'full_width',
				'gap'             => 'no',
				'padding'         => array(
					'unit'     => 'px',
					'top'      => '0',
					'right'    => '0',
					'bottom'   => '0',
					'left'     => '0',
					'isLinked' => true,
				),
			),
			'elements' => array(
				array(
					'id'       => $column_id,
					'elType'   => 'column',
					'settings' => array(
						'_column_size' => 100,
						'padding'      => array(
							'unit'     => 'px',
							'top'      => '0',
							'right'    => '0',
							'bottom'   => '0',
							'left'     => '0',
							'isLinked' => true,
						),
					),
					'elements' => array(
						array(
							'id'         => $widget_id,
							'elType'     => 'widget',
							'widgetType' => 'html',
							'settings'   => array(
								'html' => $html,
							),
							'elements'   => array(),
						),
					),
				),
			),
		),
	);

	return wp_json_encode( $data );
}

/**
 * Activate the plugin - generate API key
 */
function voa_activate_plugin() {
	if ( ! get_option( VOA_API_KEY_OPTION ) ) {
		$api_key = bin2hex( random_bytes( 32 ) );
		add_option( VOA_API_KEY_OPTION, $api_key );
	}
}

/**
 * Deactivate the plugin
 */
function voa_deactivate_plugin() {
	// Plugin state preserved on deactivation
}

/**
 * Generate a new API key
 */
function voa_generate_new_api_key() {
	$api_key = bin2hex( random_bytes( 32 ) );
	update_option( VOA_API_KEY_OPTION, $api_key );
	return $api_key;
}

/**
 * Get the stored API key
 */
function voa_get_api_key() {
	return get_option( VOA_API_KEY_OPTION );
}

/**
 * Check authentication for all VOA endpoints
 *
 * @param mixed $result The authentication result.
 * @return mixed
 */
function voa_check_authentication( $result ) {
	// Only apply to VOA routes
	if ( ! isset( $_SERVER['REQUEST_URI'] ) || strpos( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), '/wp-json/voa/' ) === false ) {
		return $result;
	}

	// Allow unauthenticated requests to pass through; we'll check auth inside each endpoint
	if ( is_wp_error( $result ) ) {
		return $result;
	}

	return $result;
}

/**
 * Validate API request authentication
 *
 * @return bool|WP_Error
 */
function voa_validate_request() {
	// Get the API key from request header
	$request_key = isset( $_SERVER['HTTP_X_VOA_KEY'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_VOA_KEY'] ) ) : '';
	$stored_key  = voa_get_api_key();

	// Validate API key
	if ( empty( $request_key ) || ! hash_equals( $stored_key, $request_key ) ) {
		return new WP_Error(
			'voa_invalid_api_key',
			'Invalid or missing API key.',
			array( 'status' => 401 )
		);
	}

	// For server-to-server requests with valid API key, allow all origins
	// Check Origin/Referer but only for informational purposes with valid key
	$origin = isset( $_SERVER['HTTP_ORIGIN'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_ORIGIN'] ) ) : '';
	$referer = isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';

	// Valid API key means we trust the request regardless of origin
	return true;
}

/**
 * Check rate limiting
 *
 * @return bool|WP_Error
 */
function voa_check_rate_limit() {
	$client_ip = voa_get_client_ip();
	$transient_key = VOA_RATE_LIMIT_TRANSIENT_PREFIX . $client_ip;
	$request_count = get_transient( $transient_key );

	if ( false === $request_count ) {
		$request_count = 0;
	}

	$request_count++;

	if ( $request_count > VOA_RATE_LIMIT_REQUESTS ) {
		return new WP_Error(
			'voa_rate_limit_exceeded',
			'Rate limit exceeded. Maximum ' . VOA_RATE_LIMIT_REQUESTS . ' requests per ' . VOA_RATE_LIMIT_PERIOD . ' seconds.',
			array( 'status' => 429 )
		);
	}

	set_transient( $transient_key, $request_count, VOA_RATE_LIMIT_PERIOD );

	return true;
}

/**
 * Get client IP address
 *
 * @return string
 */
function voa_get_client_ip() {
	if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
		// Cloudflare
		return sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) );
	} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		$ips = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
		return trim( $ips[0] );
	} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
		return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
	}
	return '0.0.0.0';
}

/**
 * Register REST routes
 */
function voa_register_rest_routes() {
	// Status endpoint
	register_rest_route(
		'voa/v1',
		'/status',
		array(
			'methods'             => 'GET',
			'callback'            => 'voa_endpoint_status',
			'permission_callback' => '__return_true',
		)
	);

	// Posts endpoints
	register_rest_route(
		'voa/v1',
		'/posts',
		array(
			array(
				'methods'             => 'GET',
				'callback'            => 'voa_endpoint_get_posts',
				'permission_callback' => '__return_true',
			),
			array(
				'methods'             => 'POST',
				'callback'            => 'voa_endpoint_create_post',
				'permission_callback' => '__return_true',
			),
		)
	);

	register_rest_route(
		'voa/v1',
		'/posts/(?P<id>\d+)',
		array(
			array(
				'methods'             => 'PUT',
				'callback'            => 'voa_endpoint_update_post',
				'permission_callback' => '__return_true',
			),
			array(
				'methods'             => 'DELETE',
				'callback'            => 'voa_endpoint_delete_post',
				'permission_callback' => '__return_true',
			),
		)
	);

	// Pages endpoints
	register_rest_route(
		'voa/v1',
		'/pages',
		array(
			array(
				'methods'             => 'GET',
				'callback'            => 'voa_endpoint_get_pages',
				'permission_callback' => '__return_true',
			),
			array(
				'methods'             => 'POST',
				'callback'            => 'voa_endpoint_create_page',
				'permission_callback' => '__return_true',
			),
		)
	);

	register_rest_route(
		'voa/v1',
		'/seo',
		array(
			'methods'             => 'POST',
			'callback'            => 'voa_endpoint_set_seo',
			'permission_callback' => '__return_true',
		)
	);
	register_rest_route(
		'voa/v1',
		'/site-icon',
		array(
			'methods'             => 'POST',
			'callback'            => 'voa_endpoint_set_site_icon',
			'permission_callback' => '__return_true',
		)
	);
	register_rest_route(
		'voa/v1',
		'/root-file',
		array(
			'methods'             => 'POST',
			'callback'            => 'voa_endpoint_write_root_file',
			'permission_callback' => '__return_true',
		)
	);
	register_rest_route(
		'voa/v1',
		'/pages/(?P<id>\d+)',
		array(
			array(
				'methods'             => 'PUT',
				'callback'            => 'voa_endpoint_update_page',
				'permission_callback' => '__return_true',
			),
			array(
				'methods'             => 'DELETE',
				'callback'            => 'voa_endpoint_delete_page',
				'permission_callback' => '__return_true',
			),
		)
	);

	// Media endpoints
	register_rest_route(
		'voa/v1',
		'/media',
		array(
			array(
				'methods'             => 'GET',
				'callback'            => 'voa_endpoint_get_media',
				'permission_callback' => '__return_true',
			),
			array(
				'methods'             => 'POST',
				'callback'            => 'voa_endpoint_upload_media',
				'permission_callback' => '__return_true',
			),
		)
	);

	// Plugins endpoints
	register_rest_route(
		'voa/v1',
		'/plugins',
		array(
			'methods'             => 'GET',
			'callback'            => 'voa_endpoint_get_plugins',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		'voa/v1',
		'/plugins/(?P<slug>[a-z0-9-]+)/activate',
		array(
			'methods'             => 'POST',
			'callback'            => 'voa_endpoint_activate_plugin',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		'voa/v1',
		'/plugins/(?P<slug>[a-z0-9-]+)/deactivate',
		array(
			'methods'             => 'POST',
			'callback'            => 'voa_endpoint_deactivate_plugin',
			'permission_callback' => '__return_true',
		)
	);

	// Theme endpoints
	register_rest_route(
		'voa/v1',
		'/theme',
		array(
			'methods'             => 'GET',
			'callback'            => 'voa_endpoint_get_theme',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		'voa/v1',
		'/theme/customizer',
		array(
			'methods'             => 'PUT',
			'callback'            => 'voa_endpoint_update_customizer',
			'permission_callback' => '__return_true',
		)
	);

	// Menus endpoints
	register_rest_route(
		'voa/v1',
		'/menus',
		array(
			'methods'             => 'GET',
			'callback'            => 'voa_endpoint_get_menus',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		'voa/v1',
		'/menus/(?P<id>\d+)',
		array(
			'methods'             => 'PUT',
			'callback'            => 'voa_endpoint_update_menu',
			'permission_callback' => '__return_true',
		)
	);

	// WooCommerce endpoints
	register_rest_route(
		'voa/v1',
		'/woocommerce/products',
		array(
			array(
				'methods'             => 'GET',
				'callback'            => 'voa_endpoint_wc_get_products',
				'permission_callback' => '__return_true',
			),
			array(
				'methods'             => 'POST',
				'callback'            => 'voa_endpoint_wc_create_product',
				'permission_callback' => '__return_true',
			),
		)
	);

	register_rest_route(
		'voa/v1',
		'/woocommerce/products/(?P<id>\d+)',
		array(
			'methods'             => 'PUT',
			'callback'            => 'voa_endpoint_wc_update_product',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		'voa/v1',
		'/woocommerce/orders',
		array(
			'methods'             => 'GET',
			'callback'            => 'voa_endpoint_wc_get_orders',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		'voa/v1',
		'/woocommerce/stats',
		array(
			'methods'             => 'GET',
			'callback'            => 'voa_endpoint_wc_get_stats',
			'permission_callback' => '__return_true',
		)
	);

	// Settings endpoint
	register_rest_route(
		'voa/v1',
		'/settings',
		array(
			array(
				'methods'             => 'GET',
				'callback'            => 'voa_endpoint_get_settings',
				'permission_callback' => '__return_true',
			),
			array(
				'methods'             => 'POST',
				'callback'            => 'voa_endpoint_update_settings',
				'permission_callback' => '__return_true',
			),
		)
	);

	// Strip embedded headers/footers from page content
	register_rest_route(
		'voa/v1',
		'/pages/strip-headers',
		array(
			'methods'             => 'POST',
			'callback'            => 'voa_endpoint_strip_page_headers',
			'permission_callback' => '__return_true',
		)
	);

	// Elementor-aware widget update. Walks _elementor_data and patches
	// matching widgets so edits to Elementor-built pages actually reflect on
	// the live page (post_content edits are ignored by Elementor).
	register_rest_route(
		'voa/v1',
		'/elementor-update',
		array(
			'methods'             => 'POST',
			'callback'            => 'voa_endpoint_elementor_update',
			'permission_callback' => '__return_true',
		)
	);

	// Elementor-aware READ: returns a flat list of widgets on a page so the
	// AI can see what's actually there (widget types, IDs, key settings) and
	// plan precise edits without guessing.
	register_rest_route(
		'voa/v1',
		'/elementor-read',
		array(
			'methods'             => 'GET',
			'callback'            => 'voa_endpoint_elementor_read',
			'permission_callback' => '__return_true',
		)
	);

	// Inspect a single Elementor widget - returns its full settings so the
	// AI can see exactly what's there (icon library, color values, custom CSS
	// classes, image URLs, etc.) before planning a style change.
	register_rest_route(
		'voa/v1',
		'/elementor-widget',
		array(
			'methods'             => 'GET',
			'callback'            => 'voa_endpoint_elementor_widget',
			'permission_callback' => '__return_true',
		)
	);

	// Return Elementor kit data (global colors, fonts, default settings).
	// Lets the AI know the brand palette so colour suggestions match.
	register_rest_route( 'voa/v1', '/kit', array(
		'methods' => 'GET', 'callback' => 'voa_endpoint_kit',
		'permission_callback' => '__return_true',
	) );

	// POST /voa/v1/kit-update - patch the Elementor kit's global system /
	// custom colors and typography in one shot. Lets the AI rebrand a site
	// in a single call instead of touching every widget that hardcodes a
	// color. Added in 1.10.0.
	register_rest_route( 'voa/v1', '/kit-update', array(
		'methods' => 'POST', 'callback' => 'voa_endpoint_kit_update',
		'permission_callback' => '__return_true',
	) );

	// Force-regenerate Elementor's CSS cache so widget edits become visible.
	// Elementor writes per-post CSS files that survive widget edits unless
	// explicitly invalidated.
	register_rest_route( 'voa/v1', '/elementor-clear-cache', array(
		'methods' => 'POST', 'callback' => 'voa_endpoint_elementor_clear_cache',
		'permission_callback' => '__return_true',
	) );

	// Upload a base64-encoded image to the media library and return its URL+ID.
	// Used when the AI needs to swap an image / icon asset.
	register_rest_route( 'voa/v1', '/upload-image', array(
		'methods' => 'POST', 'callback' => 'voa_endpoint_upload_image',
		'permission_callback' => '__return_true',
	) );

	// List media library items (id, url, alt, mime) so the AI can pick an
	// existing asset instead of uploading a new one.
	register_rest_route( 'voa/v1', '/media-list', array(
		'methods' => 'GET', 'callback' => 'voa_endpoint_media_list',
		'permission_callback' => '__return_true',
	) );

	// Read currently-active theme + page builder + key plugin status so the
	// AI knows what's available (FA Pro, Elementor Pro, etc.).
	register_rest_route( 'voa/v1', '/site-profile', array(
		'methods' => 'GET', 'callback' => 'voa_endpoint_site_profile',
		'permission_callback' => '__return_true',
	) );

	// ──────────────────────────────────────────────────────────────────────
	// Restore-point support endpoints (added in 1.9.0)
	//
	// These let the worker capture a snapshot of "everything an edit could
	// touch" BEFORE running a write, and replay that snapshot byte-for-byte
	// later via /elementor-set-raw or /option. Without these, an undo would
	// have to round-trip via /execute (PHP injection risk + heavier).
	// ──────────────────────────────────────────────────────────────────────

	// GET /voa/v1/elementor-raw?pageId=X - returns raw _elementor_data, plus
	// post_content + page_template so a future restore can rebuild the page.
	register_rest_route( 'voa/v1', '/elementor-raw', array(
		'methods' => 'GET', 'callback' => 'voa_endpoint_elementor_raw',
		'permission_callback' => '__return_true',
	) );

	// POST /voa/v1/elementor-set-raw - replaces _elementor_data wholesale
	// (and optionally post_content + page_template). Mirrors the cache
	// invalidation that voa_endpoint_elementor_update does.
	register_rest_route( 'voa/v1', '/elementor-set-raw', array(
		'methods' => 'POST', 'callback' => 'voa_endpoint_elementor_set_raw',
		'permission_callback' => '__return_true',
	) );

	// GET /voa/v1/option?key=K - read a WP option value (string scalar only;
	// no objects). POST /voa/v1/option {key, value, delete?} - write or
	// delete. Replaces the "execute-php to read/write voa_custom_css" pattern
	// the worker used to use.
	register_rest_route( 'voa/v1', '/option', array(
		array( 'methods' => 'GET',  'callback' => 'voa_endpoint_option_get', 'permission_callback' => '__return_true' ),
		array( 'methods' => 'POST', 'callback' => 'voa_endpoint_option_set', 'permission_callback' => '__return_true' ),
	) );

	// GET /voa/v1/menu-raw?menuId=X - returns the full structure of a single
	// menu (id, name, items[]) so the worker can snapshot it before an edit
	// and reapply on undo.
	register_rest_route( 'voa/v1', '/menu-raw', array(
		'methods' => 'GET', 'callback' => 'voa_endpoint_menu_raw',
		'permission_callback' => '__return_true',
	) );

	// Execute action endpoint
	register_rest_route(
		'voa/v1',
		'/execute',
		array(
			'methods'             => 'POST',
			'callback'            => 'voa_endpoint_execute_action',
			'permission_callback' => '__return_true',
		)
	);

	// Theme install endpoint (v1 "Re-create in WordPress")
	register_rest_route(
		'voa/v1',
		'/theme-install',
		array(
			'methods'             => 'POST',
			'callback'            => 'voa_endpoint_theme_install',
			'permission_callback' => '__return_true',
		)
	);

	// Plugin install endpoint (per-site CC Fields variant)
	register_rest_route(
		'voa/v1',
		'/plugin-install',
		array(
			'methods'             => 'POST',
			'callback'            => 'voa_endpoint_plugin_install',
			'permission_callback' => '__return_true',
		)
	);

	// Recreate - batch-create pages then set homepage + build primary menu
	register_rest_route(
		'voa/v1',
		'/recreate',
		array(
			'methods'             => 'POST',
			'callback'            => 'voa_endpoint_recreate',
			'permission_callback' => '__return_true',
		)
	);

	// Media sideload - fetches a remote URL into the WP Media Library and
	// returns the new attachment's URL + id so callers can rewrite image
	// references from external hosts to the site's own media library.
	register_rest_route(
		'voa/v1',
		'/media-sideload',
		array(
			'methods'             => 'POST',
			'callback'            => 'voa_endpoint_media_sideload',
			'permission_callback' => '__return_true',
		)
	);

	// Plugin-options setter - scoped to options whose key ends in
	// "f_global_vars" so the recreate pipeline can patch per-site plugin
	// globals (company name, phone, logo_url, etc.) without exposing
	// arbitrary WP options.
	register_rest_route(
		'voa/v1',
		'/plugin-options',
		array(
			'methods'             => 'POST',
			'callback'            => 'voa_endpoint_plugin_options',
			'permission_callback' => '__return_true',
		)
	);

	// Voice agent: receive + cache the compiled config (worker -> site, X-VOA-Key).
	register_rest_route(
		'voa/v1',
		'/voice/config',
		array(
			'methods'             => 'POST',
			'callback'            => 'voa_endpoint_voice_config_set',
			'permission_callback' => '__return_true',
		)
	);

	// Voice agent: mint a short-lived session for the browser widget. PUBLIC
	// (the website visitor calls this); we proxy to the worker server-side with
	// the site's X-VOA-Key so the secret never reaches the browser.
	register_rest_route(
		'voa/v1',
		'/voice/session',
		array(
			'methods'             => 'POST',
			'callback'            => 'voa_endpoint_voice_session',
			'permission_callback' => '__return_true',
		)
	);

	// Insights (behavior analytics): receive the ingest key so voa_behavior_beacon()
	// can auto-inject t.js with zero manual steps (worker -> site, X-VOA-Key).
	register_rest_route(
		'voa/v1',
		'/behavior/config',
		array(
			'methods'             => 'POST',
			'callback'            => 'voa_endpoint_behavior_config_set',
			'permission_callback' => '__return_true',
		)
	);

	// AI visibility: llms.txt + AI-crawler robots rules pushed from MyMomo.
	register_rest_route(
		'voa/v1',
		'/ai-visibility',
		array(
			array(
				'methods'             => 'GET',
				'callback'            => 'voa_endpoint_ai_visibility_get',
				'permission_callback' => '__return_true',
			),
			array(
				'methods'             => 'POST',
				'callback'            => 'voa_endpoint_ai_visibility_set',
				'permission_callback' => '__return_true',
			),
		)
	);
}

/**
 * Endpoint: POST /behavior/config
 *
 * Worker -> site. Stores the Insights ingest key (and an optional disabled
 * flag) so voa_behavior_beacon() can auto-inject the tracking script. Authed
 * with the per-site X-VOA-Key, identical to the voice config push.
 *
 * @param WP_REST_Request $request The request object.
 * @return WP_REST_Response|WP_Error
 */
function voa_endpoint_behavior_config_set( $request ) {
	$auth = voa_validate_request();
	if ( is_wp_error( $auth ) ) {
		return $auth;
	}
	$body = $request->get_json_params();
	if ( ! is_array( $body ) ) {
		$body = array();
	}
	$key = isset( $body['key'] ) ? sanitize_text_field( $body['key'] ) : '';
	if ( '' !== $key ) {
		update_option( 'voa_behavior_key', $key );
	}
	update_option( 'voa_behavior_disabled', ! empty( $body['disabled'] ) ? 1 : 0 );
	return rest_ensure_response( array( 'success' => true ) );
}

// ── AI visibility (llms.txt + AI crawler robots rules) ─────────────────────
// Managed from MyMomo: the worker pushes llms.txt content and per-bot
// allow/block rules; the plugin serves /llms.txt and appends the rules to the
// virtual robots.txt. Options:
//   voa_llms_txt - full llms.txt body (empty = not served)
//   voa_ai_bots  - array of user-agent => "allow"|"block"

add_action( 'template_redirect', 'voa_serve_llms_txt', 0 );
function voa_serve_llms_txt() {
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
	if ( '/llms.txt' !== $uri ) {
		return;
	}
	$body = (string) get_option( 'voa_llms_txt', '' );
	if ( '' === trim( $body ) ) {
		return; // no content configured - fall through to the normal 404
	}
	header( 'Content-Type: text/plain; charset=utf-8' );
	echo $body; // phpcs:ignore WordPress.Security.EscapeOutput -- plain-text file, set only via the authed VO push
	exit;
}

add_filter( 'robots_txt', 'voa_ai_bots_robots', 99, 2 );
function voa_ai_bots_robots( $output, $public ) {
	$bots = get_option( 'voa_ai_bots', array() );
	if ( ! is_array( $bots ) || empty( $bots ) ) {
		return $output;
	}
	$lines = array( '', '# AI crawler rules managed by MyMomo' );
	foreach ( $bots as $agent => $mode ) {
		$agent = preg_replace( '/[^A-Za-z0-9_\-\. ]/', '', (string) $agent );
		if ( '' === $agent ) {
			continue;
		}
		$lines[] = 'User-agent: ' . $agent;
		$lines[] = ( 'block' === $mode ) ? 'Disallow: /' : 'Allow: /';
	}
	if ( '' !== trim( (string) get_option( 'voa_llms_txt', '' ) ) ) {
		$lines[] = '';
		$lines[] = '# llms.txt: ' . home_url( '/llms.txt' );
	}
	return $output . "\n" . implode( "\n", $lines ) . "\n";
}

/**
 * Endpoint: GET /ai-visibility - current llms.txt + AI bot rules.
 */
function voa_endpoint_ai_visibility_get( $request ) {
	$auth = voa_validate_request();
	if ( is_wp_error( $auth ) ) {
		return $auth;
	}
	return rest_ensure_response( array(
		'llmsTxt' => (string) get_option( 'voa_llms_txt', '' ),
		'aiBots'  => (array) get_option( 'voa_ai_bots', array() ),
		'llmsUrl' => home_url( '/llms.txt' ),
	) );
}

/**
 * Endpoint: POST /ai-visibility - set llms.txt content and/or AI bot rules.
 * Body: { llmsTxt?: string, aiBots?: { agent: "allow"|"block" } }
 */
function voa_endpoint_ai_visibility_set( $request ) {
	$auth = voa_validate_request();
	if ( is_wp_error( $auth ) ) {
		return $auth;
	}
	$body = $request->get_json_params();
	if ( ! is_array( $body ) ) {
		$body = array();
	}
	if ( array_key_exists( 'llmsTxt', $body ) ) {
		update_option( 'voa_llms_txt', (string) $body['llmsTxt'] );
	}
	if ( isset( $body['aiBots'] ) && is_array( $body['aiBots'] ) ) {
		$clean = array();
		foreach ( $body['aiBots'] as $agent => $mode ) {
			$agent = preg_replace( '/[^A-Za-z0-9_\-\. ]/', '', (string) $agent );
			if ( '' === $agent ) {
				continue;
			}
			$clean[ $agent ] = ( 'block' === $mode ) ? 'block' : 'allow';
		}
		update_option( 'voa_ai_bots', $clean );
	}
	return rest_ensure_response( array(
		'success' => true,
		'llmsTxt' => (string) get_option( 'voa_llms_txt', '' ),
		'aiBots'  => (array) get_option( 'voa_ai_bots', array() ),
	) );
}

// ── AI crawler activity log (server-side; the JS analytics beacon never sees these) ──
// AI crawlers fetch raw HTML and don't execute JavaScript, so they never fire
// the analytics beacon. Count them here on the PHP request instead, keyed by
// crawler and UTC day, capped to 30 days - so MyMomo can show "which AI
// assistants are reading this site" WITHOUT inflating the human visitor numbers.
add_action( 'init', 'voa_log_ai_crawler', 1 );
function voa_log_ai_crawler() {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}
	if ( empty( $_SERVER['REQUEST_METHOD'] ) || 'GET' !== $_SERVER['REQUEST_METHOD'] ) {
		return;
	}
	$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) : '';
	$agent = voa_match_ai_crawler( $ua );
	if ( null === $agent ) {
		return;
	}
	$today  = gmdate( 'Y-m-d' );
	$cutoff = gmdate( 'Y-m-d', time() - 30 * DAY_IN_SECONDS );
	$hits   = get_option( 'voa_ai_crawler_hits', array() );
	if ( ! is_array( $hits ) ) {
		$hits = array();
	}
	if ( ! isset( $hits[ $agent ] ) || ! is_array( $hits[ $agent ] ) ) {
		$hits[ $agent ] = array();
	}
	$hits[ $agent ][ $today ] = ( isset( $hits[ $agent ][ $today ] ) ? (int) $hits[ $agent ][ $today ] : 0 ) + 1;
	// Prune anything older than 30 days so the option stays tiny.
	foreach ( $hits as $a => $days ) {
		foreach ( $days as $d => $c ) {
			if ( $d < $cutoff ) {
				unset( $hits[ $a ][ $d ] );
			}
		}
		if ( empty( $hits[ $a ] ) ) {
			unset( $hits[ $a ] );
		}
	}
	update_option( 'voa_ai_crawler_hits', $hits, false );
}

// Match a User-Agent to a known AI crawler; returns the canonical agent name or null.
// Tokens without a distinct UA (Google-Extended, Applebot-Extended are robots.txt
// tokens only) simply never match, which is correct.
function voa_match_ai_crawler( $ua ) {
	if ( '' === $ua ) {
		return null;
	}
	$map = array(
		'gptbot'             => 'GPTBot',
		'oai-searchbot'      => 'OAI-SearchBot',
		'chatgpt-user'       => 'ChatGPT-User',
		'claudebot'          => 'ClaudeBot',
		'claude-searchbot'   => 'Claude-SearchBot',
		'claude-user'        => 'Claude-User',
		'perplexitybot'      => 'PerplexityBot',
		'perplexity-user'    => 'Perplexity-User',
		'google-extended'    => 'Google-Extended',
		'applebot-extended'  => 'Applebot-Extended',
		'meta-externalagent' => 'meta-externalagent',
		'amazonbot'          => 'Amazonbot',
		'ccbot'              => 'CCBot',
		'bytespider'         => 'Bytespider',
	);
	$lc = strtolower( $ua );
	foreach ( $map as $token => $name ) {
		if ( false !== strpos( $lc, $token ) ) {
			return $name;
		}
	}
	return null;
}

/**
 * Endpoint: GET /ai-crawler-activity - the server-side AI crawler hit log.
 * Returns { hits: { agent: { "YYYY-MM-DD": count } }, updatedAt }.
 */
function voa_endpoint_ai_crawler_activity( $request ) {
	$auth = voa_validate_request();
	if ( is_wp_error( $auth ) ) {
		return $auth;
	}
	$hits = get_option( 'voa_ai_crawler_hits', array() );
	return rest_ensure_response( array(
		'hits'      => is_array( $hits ) ? $hits : array(),
		'updatedAt' => time() * 1000,
	) );
}

/**
 * Endpoint: GET /status
 *
 * @param WP_REST_Request $request The request object.
 * @return WP_REST_Response|WP_Error
 */
function voa_endpoint_status( WP_REST_Request $request ) {
	$auth = voa_validate_request();
	if ( is_wp_error( $auth ) ) {
		return $auth;
	}

	$rate_limit = voa_check_rate_limit();
	if ( is_wp_error( $rate_limit ) ) {
		return $rate_limit;
	}

	// Update last ping
	set_transient( VOA_LAST_PING_TRANSIENT, current_time( 'mysql' ), 7 * DAY_IN_SECONDS );

	global $wp_version;

	$response = array(
		'wp_version'        => $wp_version,
		'site_name'         => get_bloginfo( 'name' ),
		'site_url'          => get_site_url(),
		'active_theme'      => wp_get_theme()->get( 'Name' ),
		'theme_version'     => wp_get_theme()->get( 'Version' ),
		'php_version'       => phpversion(),
		'is_multisite'      => is_multisite(),
		'permalink_structure' => get_option( 'permalink_structure' ),
		'active_plugins'    => array_map(
			function( $plugin_file ) {
				$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file );
				return array(
					'name'    => $plugin_data['Name'],
					'slug'    => dirname( $plugin_file ),
					'version' => $plugin_data['Version'],
					'active'  => true,
				);
			},
			get_option( 'active_plugins', array() )
		),
		'last_ping'         => get_transient( VOA_LAST_PING_TRANSIENT ),
	);

	return rest_ensure_response( $response );
}

/**
 * Endpoint: GET /posts
 *
 * @param WP_REST_Request $request The request object.
 * @return WP_REST_Response|WP_Error
 */
function voa_endpoint_get_posts( WP_REST_Request $request ) {
	$auth = voa_validate_request();
	if ( is_wp_error( $auth ) ) {
		return $auth;
	}

	$rate_limit = voa_check_rate_limit();
	if ( is_wp_error( $rate_limit ) ) {
		return $rate_limit;
	}

	$page     = absint( $request->get_param( 'page' ) ) ?: 1;
	$per_page = absint( $request->get_param( 'per_page' ) ) ?: 10;
	$status   = sanitize_key( $request->get_param( 'status' ) ) ?: 'publish';
	$search   = sanitize_text_field( $request->get_param( 'search' ) ) ?: '';
	$category = absint( $request->get_param( 'category' ) ) ?: 0;
	$tag      = absint( $request->get_param( 'tag' ) ) ?: 0;

	$args = array(
		'post_type'      => 'post',
		'post_status'    => array( 'publish', 'draft', 'pending' ),
		'posts_per_page' => $per_page,
		'paged'          => $page,
		'orderby'        => 'date',
		'order'          => 'DESC',
	);

	if ( ! empty( $status ) ) {
		$args['post_status'] = $status;
	}

	if ( ! empty( $search ) ) {
		$args['s'] = $search;
	}

	if ( $category > 0 ) {
		$args['cat'] = $category;
	}

	if ( $tag > 0 ) {
		$args['tag__in'] = array( $tag );
	}

	$query = new WP_Query( $args );
	$posts = array();

	foreach ( $query->posts as $post ) {
		$posts[] = voa_format_post_response( $post );
	}

	$response = array(
		'posts'       => $posts,
		'total'       => $query->found_posts,
		'total_pages' => $query->max_num_pages,
		'page'        => $page,
	);

	return rest_ensure_response( $response );
}

/**
 * Format a post for API response
 *
 * @param WP_Post $post The post object.
 * @return array
 */
function voa_format_post_response( $post ) {
	$featured_image_id = get_post_thumbnail_id( $post->ID );
	$featured_image_url = $featured_image_id ? wp_get_attachment_url( $featured_image_id ) : '';

	$categories = get_the_category( $post->ID );
	$category_list = array_map(
		function( $cat ) {
			return array(
				'id'   => $cat->term_id,
				'name' => $cat->name,
				'slug' => $cat->slug,
			);
		},
		$categories
	);

	$tags = get_the_tags( $post->ID );
	$tag_list = is_array( $tags ) ? array_map(
		function( $tag ) {
			return array(
				'id'   => $tag->term_id,
				'name' => $tag->name,
				'slug' => $tag->slug,
			);
		},
		$tags
	) : array();

	$author = get_userdata( $post->post_author );

	return array(
		'id'                   => $post->ID,
		'title'                => $post->post_title,
		'slug'                 => $post->post_name,
		'status'               => $post->post_status,
		'date'                 => $post->post_date,
		'excerpt'              => $post->post_excerpt,
		'categories'           => $category_list,
		'tags'                 => $tag_list,
		'author'               => array(
			'id'   => $post->post_author,
			'name' => $author ? $author->display_name : '',
		),
		'featured_image_url'   => $featured_image_url,
	);
}

/**
 * Endpoint: POST /posts
 *
 * @param WP_REST_Request $request The request object.
 * @return WP_REST_Response|WP_Error
 */
function voa_endpoint_create_post( WP_REST_Request $request ) {
	$auth = voa_validate_request();
	if ( is_wp_error( $auth ) ) {
		return $auth;
	}

	$rate_limit = voa_check_rate_limit();
	if ( is_wp_error( $rate_limit ) ) {
		return $rate_limit;
	}

	$title = sanitize_text_field( $request->get_param( 'title' ) );
	$content = voa_sanitize_content( $request->get_param( 'content' ) );
	$status = sanitize_key( $request->get_param( 'status' ) ) ?: 'draft';
	$excerpt = sanitize_text_field( $request->get_param( 'excerpt' ) ) ?: '';
	$featured_image_id = absint( $request->get_param( 'featured_image_id' ) ) ?: 0;
	$categories = $request->get_param( 'categories' ) ?: array();
	$tags = $request->get_param( 'tags' ) ?: array();
	$meta = $request->get_param( 'meta' ) ?: array();

	if ( empty( $title ) ) {
		return new WP_Error( 'voa_missing_title', 'Title is required.', array( 'status' => 400 ) );
	}

	// Sanitize categories and tags
	$categories = array_map( 'absint', $categories );
	$tags = array_map( 'sanitize_text_field', $tags );

	$post_id = wp_insert_post(
		array(
			'post_title'   => $title,
			'post_content' => $content,
			'post_status'  => $status,
			'post_excerpt' => $excerpt,
			'post_type'    => 'post',
			'post_category' => $categories,
			'tags_input'   => $tags,
		)
	);

	if ( is_wp_error( $post_id ) ) {
		return $post_id;
	}

	// Set featured image
	if ( $featured_image_id > 0 ) {
		set_post_thumbnail( $post_id, $featured_image_id );
	}

	// Set meta fields
	if ( is_array( $meta ) ) {
		foreach ( $meta as $key => $value ) {
			$key = sanitize_key( $key );
			if ( ! empty( $key ) ) {
				update_post_meta( $post_id, $key, wp_kses_post( $value ) );
			}
		}
	}

	$post = get_post( $post_id );
	$response = voa_format_post_response( $post );

	return rest_ensure_response( $response );
}

/**
 * Endpoint: PUT /posts/{id}
 *
 * @param WP_REST_Request $request The request object.
 * @return WP_REST_Response|WP_Error
 */
function voa_endpoint_update_post( WP_REST_Request $request ) {
	$auth = voa_validate_request();
	if ( is_wp_error( $auth ) ) {
		return $auth;
	}

	$rate_limit = voa_check_rate_limit();
	if ( is_wp_error( $rate_limit ) ) {
		return $rate_limit;
	}

	$post_id = absint( $request->get_param( 'id' ) );
	$post = get_post( $post_id );

	if ( ! $post || 'post' !== $post->post_type ) {
		return new WP_Error( 'voa_not_found', 'Post not found.', array( 'status' => 404 ) );
	}

	$update_data = array( 'ID' => $post_id );

	if ( $request->has_param( 'title' ) ) {
		$update_data['post_title'] = sanitize_text_field( $request->get_param( 'title' ) );
	}

	if ( $request->has_param( 'content' ) ) {
		$update_data['post_content'] = voa_sanitize_content( $request->get_param( 'content' ) );
	}

	if ( $request->has_param( 'status' ) ) {
		$update_data['post_status'] = sanitize_key( $request->get_param( 'status' ) );
	}

	if ( $request->has_param( 'excerpt' ) ) {
		$update_data['post_excerpt'] = sanitize_text_field( $request->get_param( 'excerpt' ) );
	}

	// Prevent wp_update_post from validating Elementor virtual templates
	$update_data['page_template'] = 'default';

	$result = wp_update_post( $update_data, true );

	if ( is_wp_error( $result ) ) {
		return new WP_Error( 'voa_update_failed', $result->get_error_message(), array( 'status' => 500 ) );
	}

	// Restore the real template
	$current_template = get_post_meta( $post_id, '_wp_page_template', true );
	if ( $current_template && 'default' !== $current_template ) {
		update_post_meta( $post_id, '_wp_page_template', $current_template );
	}

	// If content was updated, handle page builder integration
	if ( $request->has_param( 'content' ) ) {
		$raw_content = voa_sanitize_content( $request->get_param( 'content' ) );

		// Elementor - write native Elementor JSON data
		if ( defined( 'ELEMENTOR_VERSION' ) && get_post_meta( $post_id, '_elementor_data', true ) ) {
			$elementor_json = voa_html_to_elementor_data( $raw_content );
			update_post_meta( $post_id, '_elementor_data', wp_slash( $elementor_json ) );
			update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );
		}

		// Divi
		if ( get_post_meta( $post_id, '_et_pb_use_builder', true ) === 'on' ) {
			update_post_meta( $post_id, '_et_pb_use_builder', 'off' );
		}
		// Beaver Builder
		if ( get_post_meta( $post_id, '_fl_builder_data', true ) ) {
			delete_post_meta( $post_id, '_fl_builder_data' );
			delete_post_meta( $post_id, '_fl_builder_data_settings' );
			delete_post_meta( $post_id, '_fl_builder_draft' );
			delete_post_meta( $post_id, '_fl_builder_draft_settings' );
			update_post_meta( $post_id, '_fl_builder_enabled', false );
		}
		// WPBakery
		if ( get_post_meta( $post_id, '_wpb_vc_js_status', true ) === 'true' ) {
			update_post_meta( $post_id, '_wpb_vc_js_status', 'false' );
		}
	}

	if ( $request->has_param( 'featured_image_id' ) ) {
		$featured_image_id = absint( $request->get_param( 'featured_image_id' ) );
		if ( $featured_image_id > 0 ) {
			set_post_thumbnail( $post_id, $featured_image_id );
		}
	}

	if ( $request->has_param( 'categories' ) ) {
		$categories = array_map( 'absint', $request->get_param( 'categories' ) );
		wp_set_post_categories( $post_id, $categories );
	}

	if ( $request->has_param( 'tags' ) ) {
		$tags = array_map( 'sanitize_text_field', $request->get_param( 'tags' ) );
		wp_set_post_tags( $post_id, $tags );
	}

	if ( $request->has_param( 'meta' ) ) {
		$meta = $request->get_param( 'meta' );
		if ( is_array( $meta ) ) {
			foreach ( $meta as $key => $value ) {
				$key = sanitize_key( $key );
				if ( ! empty( $key ) ) {
					update_post_meta( $post_id, $key, wp_kses_post( $value ) );
				}
			}
		}
	}

	$post = get_post( $post_id );
	$response = voa_format_post_response( $post );

	return rest_ensure_response( $response );
}

/**
 * Endpoint: GET /pages
 *
 * @param WP_REST_Request $request The request object.
 * @return WP_REST_Response|WP_Error
 */
function voa_endpoint_get_pages( WP_REST_Request $request ) {
	$auth = voa_validate_request();
	if ( is_wp_error( $auth ) ) {
		return $auth;
	}

	$rate_limit = voa_check_rate_limit();
	if ( is_wp_error( $rate_limit ) ) {
		return $rate_limit;
	}

	$page     = absint( $request->get_param( 'page' ) ) ?: 1;
	$per_page = absint( $request->get_param( 'per_page' ) ) ?: 10;
	$status   = sanitize_key( $request->get_param( 'status' ) ) ?: 'publish';
	$search   = sanitize_text_field( $request->get_param( 'search' ) ) ?: '';

	$args = array(
		'post_type'      => 'page',
		'post_status'    => array( 'publish', 'draft', 'pending' ),
		'posts_per_page' => $per_page,
		'paged'          => $page,
		'orderby'        => 'date',
		'order'          => 'DESC',
	);

	if ( ! empty( $status ) ) {
		$args['post_status'] = $status;
	}

	if ( ! empty( $search ) ) {
		$args['s'] = $search;
	}

	$query = new WP_Query( $args );
	$pages = array();

	foreach ( $query->posts as $page_post ) {
		$pages[] = voa_format_page_response( $page_post );
	}

	$response = array(
		'pages'       => $pages,
		'total'       => $query->found_posts,
		'total_pages' => $query->max_num_pages,
		'page'        => $page,
	);

	return rest_ensure_response( $response );
}

/**
 * Format a page for API response
 *
 * @param WP_Post $page The page object.
 * @return array
 */
function voa_format_page_response( $page ) {
	$featured_image_id = get_post_thumbnail_id( $page->ID );
	$featured_image_url = $featured_image_id ? wp_get_attachment_url( $featured_image_id ) : '';

	$template = get_page_template_slug( $page->ID );

	return array(
		'id'                 => $page->ID,
		'title'              => $page->post_title,
		'slug'               => $page->post_name,
		'status'             => $page->post_status,
		'date'               => $page->post_date,
		'excerpt'            => $page->post_excerpt,
		'parent'             => $page->post_parent,
		'menu_order'         => $page->menu_order,
		'template'           => $template ?: 'default',
		'featured_image_url' => $featured_image_url,
	);
}

/**
 * Endpoint: POST /pages
 *
 * @param WP_REST_Request $request The request object.
 * @return WP_REST_Response|WP_Error
 */
function voa_endpoint_create_page( WP_REST_Request $request ) {
	$auth = voa_validate_request();
	if ( is_wp_error( $auth ) ) {
		return $auth;
	}

	$rate_limit = voa_check_rate_limit();
	if ( is_wp_error( $rate_limit ) ) {
		return $rate_limit;
	}

	$title = sanitize_text_field( $request->get_param( 'title' ) );
	$content = voa_sanitize_content( $request->get_param( 'content' ) );
	$status = sanitize_key( $request->get_param( 'status' ) ) ?: 'draft';
	$parent = absint( $request->get_param( 'parent' ) ) ?: 0;
	$template = sanitize_key( $request->get_param( 'template' ) ) ?: '';
	$menu_order = absint( $request->get_param( 'menu_order' ) ) ?: 0;

	if ( empty( $title ) ) {
		return new WP_Error( 'voa_missing_title', 'Title is required.', array( 'status' => 400 ) );
	}

	$post_id = wp_insert_post(
		array(
			'post_title'   => $title,
			'post_content' => $content,
			'post_status'  => $status,
			'post_type'    => 'page',
			'post_parent'  => $parent,
			'menu_order'   => $menu_order,
		)
	);

	if ( is_wp_error( $post_id ) ) {
		return $post_id;
	}

	if ( ! empty( $template ) ) {
		update_post_meta( $post_id, '_wp_page_template', $template );
	}

	// For Elementor sites: convert HTML to native Elementor JSON data
	if ( defined( 'ELEMENTOR_VERSION' ) && ! empty( $content ) ) {
		$elementor_json = voa_html_to_elementor_data( $content );
		update_post_meta( $post_id, '_elementor_data', wp_slash( $elementor_json ) );
		update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );
		if ( empty( $template ) ) {
			update_post_meta( $post_id, '_wp_page_template', 'elementor_full_width' );
		}
	}

	$page = get_post( $post_id );
	$response = voa_format_page_response( $page );

	return rest_ensure_response( $response );
}

/**
 * Endpoint: PUT /pages/{id}
 *
 * @param WP_REST_Request $request The request object.
 * @return WP_REST_Response|WP_Error
 */
function voa_endpoint_update_page( WP_REST_Request $request ) {
	$auth = voa_validate_request();
	if ( is_wp_error( $auth ) ) {
		return $auth;
	}

	$rate_limit = voa_check_rate_limit();
	if ( is_wp_error( $rate_limit ) ) {
		return $rate_limit;
	}

	$post_id = absint( $request->get_param( 'id' ) );
	$page = get_post( $post_id );

	if ( ! $page || 'page' !== $page->post_type ) {
		return new WP_Error( 'voa_not_found', 'Page not found.', array( 'status' => 404 ) );
	}

	$update_data = array( 'ID' => $post_id );

	if ( $request->has_param( 'title' ) ) {
		$update_data['post_title'] = sanitize_text_field( $request->get_param( 'title' ) );
	}

	if ( $request->has_param( 'content' ) ) {
		$update_data['post_content'] = voa_sanitize_content( $request->get_param( 'content' ) );
	}

	if ( $request->has_param( 'status' ) ) {
		$update_data['post_status'] = sanitize_key( $request->get_param( 'status' ) );
	}

	if ( $request->has_param( 'parent' ) ) {
		$update_data['post_parent'] = absint( $request->get_param( 'parent' ) );
	}

	if ( $request->has_param( 'menu_order' ) ) {
		$update_data['menu_order'] = absint( $request->get_param( 'menu_order' ) );
	}

	// CRITICAL: Force page_template to 'default' before wp_update_post to prevent
	// WordPress from loading the existing Elementor virtual template (e.g. elementor_full_width)
	// and failing validation. Elementor templates are NOT file-based, so wp_insert_post's
	// is_valid_page_template() check rejects them. We set the real template via update_post_meta after.
	$update_data['page_template'] = 'default';

	$result = wp_update_post( $update_data, true );

	if ( is_wp_error( $result ) ) {
		return new WP_Error( 'voa_update_failed', $result->get_error_message(), array( 'status' => 500 ) );
	}

	// Immediately restore the correct template via post meta (bypasses WP validation)
	$current_template = get_post_meta( $post_id, '_wp_page_template', true );
	if ( $current_template && 'default' !== $current_template ) {
		update_post_meta( $post_id, '_wp_page_template', $current_template );
	}

	// If content was updated, write builder-native data so the page renders properly.
	// For Elementor: convert HTML into Elementor JSON (section > column > HTML widget).
	// For other builders: clear their data so WordPress renders post_content directly.
	if ( $request->has_param( 'content' ) ) {
		$raw_content = voa_sanitize_content( $request->get_param( 'content' ) );

		// Elementor - write native Elementor JSON data so Elementor renders our content
		if ( defined( 'ELEMENTOR_VERSION' ) ) {
			$elementor_json = voa_html_to_elementor_data( $raw_content );
			// wp_slash needed because update_post_meta runs wp_unslash internally
			update_post_meta( $post_id, '_elementor_data', wp_slash( $elementor_json ) );
			update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );
			update_post_meta( $post_id, '_wp_page_template', 'elementor_full_width' );

			// Clear Elementor CSS cache so it regenerates from new data
			$upload_dir = wp_upload_dir();
			$elementor_css = $upload_dir['basedir'] . '/elementor/css/post-' . $post_id . '.css';
			if ( file_exists( $elementor_css ) ) {
				wp_delete_file( $elementor_css );
			}
			if ( class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance && isset( \Elementor\Plugin::$instance->files_manager ) ) {
				\Elementor\Plugin::$instance->files_manager->clear_cache();
			}
		}

		// Divi Builder - clear its builder flag so it renders post_content
		if ( get_post_meta( $post_id, '_et_pb_use_builder', true ) === 'on' ) {
			update_post_meta( $post_id, '_et_pb_use_builder', 'off' );
		}

		// Beaver Builder - clear its layout data
		if ( get_post_meta( $post_id, '_fl_builder_data', true ) ) {
			delete_post_meta( $post_id, '_fl_builder_data' );
			delete_post_meta( $post_id, '_fl_builder_data_settings' );
			delete_post_meta( $post_id, '_fl_builder_draft' );
			delete_post_meta( $post_id, '_fl_builder_draft_settings' );
			update_post_meta( $post_id, '_fl_builder_enabled', false );
		}

		// WPBakery - clear its shortcode flag
		if ( get_post_meta( $post_id, '_wpb_vc_js_status', true ) === 'true' ) {
			update_post_meta( $post_id, '_wpb_vc_js_status', 'false' );
		}
	}

	if ( $request->has_param( 'template' ) ) {
		$template = sanitize_key( $request->get_param( 'template' ) );
		update_post_meta( $post_id, '_wp_page_template', $template );
	}

	$page = get_post( $post_id );
	$response = voa_format_page_response( $page );
	$response['content_updated'] = $request->has_param( 'content' );

	return rest_ensure_response( $response );
}

/**
 * Endpoint: DELETE /pages/{id}
 *
 * @param WP_REST_Request $request The request object.
 * @return WP_REST_Response|WP_Error
 */
function voa_endpoint_strip_page_headers( WP_REST_Request $request ) {
	$auth = voa_validate_request();
	if ( is_wp_error( $auth ) ) {
		return $auth;
	}

	$rate_limit = voa_check_rate_limit();
	if ( is_wp_error( $rate_limit ) ) {
		return $rate_limit;
	}

	$page_ids = $request->get_param( 'pageIds' );
	if ( ! is_array( $page_ids ) || empty( $page_ids ) ) {
		return new WP_Error( 'voa_missing_param', 'pageIds array is required.', array( 'status' => 400 ) );
	}

	$results = array();

	foreach ( $page_ids as $pid ) {
		$pid = absint( $pid );
		$post = get_post( $pid );
		if ( ! $post ) {
			$results[] = array( 'id' => $pid, 'status' => 'not_found' );
			continue;
		}

		$content = $post->post_content;
		if ( empty( $content ) || strlen( $content ) < 50 ) {
			$results[] = array( 'id' => $pid, 'title' => $post->post_title, 'status' => 'skip' );
			continue;
		}

		$original_len = strlen( $content );
		$removed = 0;

		if ( class_exists( 'DOMDocument' ) ) {
			$doc = new DOMDocument();
			// Use meta charset tag instead of mb_convert_encoding HTML-ENTITIES (removed in PHP 8.2)
			@$doc->loadHTML(
				'<html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"></head><body><div id="voa-wrap">' . $content . '</div></body></html>',
				LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
			);

			$wrapper = $doc->getElementById( 'voa-wrap' );
			if ( $wrapper ) {
				// Collect top-level element children
				$children = array();
				foreach ( $wrapper->childNodes as $child ) {
					if ( $child->nodeType === XML_ELEMENT_NODE ) {
						$children[] = $child;
					}
				}

				foreach ( $children as $child ) {
					$html = $doc->saveHTML( $child );
					$is_header = false;
					$is_footer = false;

					// Header detection: contains navigation links (Home + other page links)
					if ( preg_match( '/<a[^>]*>\s*Home\s*<\/a>/i', $html )
						&& preg_match( '/<a[^>]*>\s*(Services|About|Contact|Consultations|Blog|Portfolio)\s*<\/a>/i', $html )
						&& strlen( $html ) < 4000 ) {
						$is_header = true;
					}

					// Header detection: contains <nav> element
					if ( stripos( $html, '<nav' ) !== false && strlen( $html ) < 4000 ) {
						$is_header = true;
					}

					// Header detection: looks like a site branding bar
					if ( preg_match( '/Virtual\s*Office/i', $html )
						&& preg_match( '/<a[^>]*>.*?Home.*?<\/a>/is', $html )
						&& strlen( $html ) < 4000 ) {
						$is_header = true;
					}

					// Footer detection: copyright symbols or "all rights reserved"
					if ( preg_match( '/(&copy;|\xC2\xA9|copyright|all\s+rights\s+reserved)/i', $html )
						&& strlen( $html ) < 4000 ) {
						$is_footer = true;
					}

					if ( $is_header || $is_footer ) {
						$wrapper->removeChild( $child );
						$removed++;
					}
				}
			}

			if ( $removed > 0 ) {
				// Extract cleaned content
				$cleaned = '';
				if ( $wrapper ) {
					foreach ( $wrapper->childNodes as $child ) {
						$cleaned .= $doc->saveHTML( $child );
					}
				}
				$cleaned = trim( $cleaned );

				// Save cleaned content - use page_template='default' to bypass WP validation
				wp_update_post( array(
					'ID'            => $pid,
					'post_content'  => $cleaned,
					'page_template' => 'default',
				) );

				// Restore the real Elementor template
				$tpl = get_post_meta( $pid, '_wp_page_template', true );
				if ( $tpl && 'default' !== $tpl ) {
					update_post_meta( $pid, '_wp_page_template', $tpl );
				}

				// Update Elementor data if applicable
				if ( defined( 'ELEMENTOR_VERSION' ) && function_exists( 'voa_html_to_elementor_data' ) ) {
					$elementor_json = voa_html_to_elementor_data( $cleaned );
					update_post_meta( $pid, '_elementor_data', wp_slash( $elementor_json ) );
					update_post_meta( $pid, '_elementor_edit_mode', 'builder' );

					// Clear Elementor CSS cache
					if ( class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance && isset( \Elementor\Plugin::$instance->files_manager ) ) {
						\Elementor\Plugin::$instance->files_manager->clear_cache();
					}
				}

				$results[] = array(
					'id'               => $pid,
					'title'            => $post->post_title,
					'status'           => 'stripped',
					'sections_removed' => $removed,
					'size_before'      => $original_len,
					'size_after'       => strlen( $cleaned ),
				);
			} else {
				$results[] = array( 'id' => $pid, 'title' => $post->post_title, 'status' => 'clean' );
			}
		} else {
			$results[] = array( 'id' => $pid, 'status' => 'error', 'reason' => 'DOMDocument not available' );
		}
	}

	return rest_ensure_response( array( 'success' => true, 'results' => $results ) );
}

function voa_endpoint_delete_page( WP_REST_Request $request ) {
	$auth = voa_validate_request();
	if ( is_wp_error( $auth ) ) {
		return $auth;
	}

	$rate_limit = voa_check_rate_limit();
	if ( is_wp_error( $rate_limit ) ) {
		return $rate_limit;
	}

	$post_id = absint( $request->get_param( 'id' ) );
	$page = get_post( $post_id );

	if ( ! $page || 'page' !== $page->post_type ) {
		return new WP_Error( 'voa_not_found', 'Page not found.', array( 'status' => 404 ) );
	}

	$title = $page->post_title;
	$result = wp_trash_post( $post_id );

	if ( ! $result ) {
		return new WP_Error( 'voa_delete_failed', 'Failed to delete page.', array( 'status' => 500 ) );
	}

	return rest_ensure_response( array(
		'success' => true,
		'message' => "Page '$title' moved to trash.",
		'id'      => $post_id,
	) );
}

/**
 * Endpoint: DELETE /posts/{id}
 *
 * @param WP_REST_Request $request The request object.
 * @return WP_REST_Response|WP_Error
 */
function voa_endpoint_delete_post( WP_REST_Request $request ) {
	$auth = voa_validate_request();
	if ( is_wp_error( $auth ) ) {
		return $auth;
	}

	$rate_limit = voa_check_rate_limit();
	if ( is_wp_error( $rate_limit ) ) {
		return $rate_limit;
	}

	$post_id = absint( $request->get_param( 'id' ) );
	$post = get_post( $post_id );

	if ( ! $post || 'post' !== $post->post_type ) {
		return new WP_Error( 'voa_not_found', 'Post not found.', array( 'status' => 404 ) );
	}

	$title = $post->post_title;
	$result = wp_trash_post( $post_id );

	if ( ! $result ) {
		return new WP_Error( 'voa_delete_failed', 'Failed to delete post.', array( 'status' => 500 ) );
	}

	return rest_ensure_response( array(
		'success' => true,
		'message' => "Post '$title' moved to trash.",
		'id'      => $post_id,
	) );
}

/**
 * Endpoint: GET /media
 *
 * @param WP_REST_Request $request The request object.
 * @return WP_REST_Response|WP_Error
 */
function voa_endpoint_get_media( WP_REST_Request $request ) {
	$auth = voa_validate_request();
	if ( is_wp_error( $auth ) ) {
		return $auth;
	}

	$rate_limit = voa_check_rate_limit();
	if ( is_wp_error( $rate_limit ) ) {
		return $rate_limit;
	}

	$page     = absint( $request->get_param( 'page' ) ) ?: 1;
	$per_page = absint( $request->get_param( 'per_page' ) ) ?: 10;

	$args = array(
		'post_type'      => 'attachment',
		'posts_per_page' => $per_page,
		'paged'          => $page,
		'orderby'        => 'date',
		'order'          => 'DESC',
	);

	$query = new WP_Query( $args );
	$media = array();

	foreach ( $query->posts as $attachment ) {
		$media[] = voa_format_media_response( $attachment );
	}

	$response = array(
		'media'       => $media,
		'total'       => $query->found_posts,
		'total_pages' => $query->max_num_pages,
		'page'        => $page,
	);

	return rest_ensure_response( $response );
}

/**
 * Format media for API response
 *
 * @param WP_Post $attachment The attachment object.
 * @return array
 */
function voa_format_media_response( $attachment ) {
	$metadata = wp_get_attachment_metadata( $attachment->ID );
	$alt_text = get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true );

	$sizes = array();
	if ( is_array( $metadata ) && isset( $metadata['sizes'] ) ) {
		foreach ( $metadata['sizes'] as $size_name => $size_data ) {
			$sizes[ $size_name ] = array(
				'url'    => wp_get_attachment_image_url( $attachment->ID, $size_name ),
				'width'  => $size_data['width'],
				'height' => $size_data['height'],
			);
		}
	}

	return array(
		'id'        => $attachment->ID,
		'title'     => $attachment->post_title,
		'url'       => wp_get_attachment_url( $attachment->ID ),
		'mime_type' => get_post_mime_type( $attachment->ID ),
		'alt_text'  => $alt_text,
		'date'      => $attachment->post_date,
		'sizes'     => $sizes,
	);
}

/**
 * Endpoint: POST /media
 *
 * @param WP_REST_Request $request The request object.
 * @return WP_REST_Response|WP_Error
 */
function voa_endpoint_upload_media( WP_REST_Request $request ) {
	$auth = voa_validate_request();
	if ( is_wp_error( $auth ) ) {
		return $auth;
	}

	$rate_limit = voa_check_rate_limit();
	if ( is_wp_error( $rate_limit ) ) {
		return $rate_limit;
	}

	$file_data = $request->get_param( 'file' );
	$filename = sanitize_file_name( $request->get_param( 'filename' ) );
	$alt_text = sanitize_text_field( $request->get_param( 'alt_text' ) ) ?: '';

	if ( empty( $file_data ) || empty( $filename ) ) {
		return new WP_Error( 'voa_missing_file', 'File data and filename are required.', array( 'status' => 400 ) );
	}

	// Decode base64 file data
	$decoded_file = base64_decode( $file_data, true );
	if ( false === $decoded_file ) {
		return new WP_Error( 'voa_invalid_base64', 'Invalid base64 file data.', array( 'status' => 400 ) );
	}

	// Create temporary file
	$upload_dir = wp_upload_dir();
	$temp_file = $upload_dir['basedir'] . '/' . $filename;

	// Ensure the directory exists
	if ( ! file_exists( $upload_dir['basedir'] ) ) {
		wp_mkdir_p( $upload_dir['basedir'] );
	}

	// Write file
	$bytes_written = file_put_contents( $temp_file, $decoded_file );
	if ( false === $bytes_written ) {
		return new WP_Error( 'voa_file_write_failed', 'Failed to write file.', array( 'status' => 500 ) );
	}

	// Insert as attachment
	$attachment_id = wp_insert_attachment(
		array(
			'post_title'     => sanitize_file_name( pathinfo( $filename, PATHINFO_FILENAME ) ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		),
		$temp_file
	);

	if ( is_wp_error( $attachment_id ) ) {
		unlink( $temp_file );
		return $attachment_id;
	}

	// Generate metadata
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$attach_data = wp_generate_attachment_metadata( $attachment_id, $temp_file );
	wp_update_attachment_metadata( $attachment_id, $attach_data );

	// Set alt text
	if ( ! empty( $alt_text ) ) {
		update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt_text );
	}

	$attachment = get_post( $attachment_id );
	$response = voa_format_media_response( $attachment );

	return rest_ensure_response( $response );
}

/**
 * Endpoint: GET /plugins
 *
 * @param WP_REST_Request $request The request object.
 * @return WP_REST_Response|WP_Error
 */
function voa_endpoint_get_plugins( WP_REST_Request $request ) {
	$auth = voa_validate_request();
	if ( is_wp_error( $auth ) ) {
		return $auth;
	}

	$rate_limit = voa_check_rate_limit();
	if ( is_wp_error( $rate_limit ) ) {
		return $rate_limit;
	}

	if ( ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$all_plugins = get_plugins();
	$active_plugins = get_option( 'active_plugins', array() );
	$plugins = array();

	foreach ( $all_plugins as $plugin_file => $plugin_data ) {
		$slug = dirname( $plugin_file );
		$is_active = in_array( $plugin_file, $active_plugins, true );

		$plugins[] = array(
			'name'                => $plugin_data['Name'],
			'slug'                => $slug,
			'version'             => $plugin_data['Version'],
			'active'              => $is_active,
			'update_available'    => false, // Would need to check against WordPress.org API
		);

		// AI crawler activity: server-side hit log (bots never fire the JS beacon).
		register_rest_route(
			'voa/v1',
			'/ai-crawler-activity',
			array(
				'methods'             => 'GET',
				'callback'            => 'voa_endpoint_ai_crawler_activity',
				'permission_callback' => '__return_true',
			)
		);
	}

	return rest_ensure_response( array( 'plugins' => $plugins ) );
}

/**
 * Endpoint: POST /plugins/{slug}/activate
 *
 * @param WP_REST_Request $request The request object.
 * @return WP_REST_Response|WP_Error
 */
function voa_endpoint_activate_plugin( WP_REST_Request $request ) {
	$auth = voa_validate_request();
	if ( is_wp_error( $auth ) ) {
		return $auth;
	}

	$rate_limit = voa_check_rate_limit();
	if ( is_wp_error( $rate_limit ) ) {
		return $rate_limit;
	}

	$slug = sanitize_key( $request->get_param( 'slug' ) );

	if ( ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$all_plugins = get_plugins();
	$plugin_file = null;

	foreach ( $all_plugins as $file => $data ) {
		if ( dirname( $file ) === $slug ) {
			$plugin_file = $file;
			break;
		}
	}

	if ( ! $plugin_file ) {
		return new WP_Error( 'voa_plugin_not_found', 'Plugin not found.', array( 'status' => 404 ) );
	}

	$result = activate_plugin( $plugin_file );

	if ( is_wp_error( $result ) ) {
		return $result;
	}

	return rest_ensure_response( array( 'success' => true, 'message' => 'Plugin activated.' ) );
}

/**
 * Endpoint: POST /plugins/{slug}/deactivate
 *
 * @param WP_REST_Request $request The request object.
 * @return WP_REST_Response|WP_Error
 */
function voa_endpoint_deactivate_plugin( WP_REST_Request $request ) {
	$auth = voa_validate_request();
	if ( is_wp_error( $auth ) ) {
		return $auth;
	}

	$rate_limit = voa_check_rate_limit();
	if ( is_wp_error( $rate_limit ) ) {
		return $rate_limit;
	}

	$slug = sanitize_key( $request->get_param( 'slug' ) );

	if ( ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$all_plugins = get_plugins();
	$plugin_file = null;

	foreach ( $all_plugins as $file => $data ) {
		if ( dirname( $file ) === $slug ) {
			$plugin_file = $file;
			break;
		}
	}

	if ( ! $plugin_file ) {
		return new WP_Error( 'voa_plugin_not_found', 'Plugin not found.', array( 'status' => 404 ) );
	}

	deactivate_plugins( $plugin_file );

	return rest_ensure_response( array( 'success' => true, 'message' => 'Plugin deactivated.' ) );
}

/**
 * Endpoint: GET /theme
 *
 * @param WP_REST_Request $request The request object.
 * @return WP_REST_Response|WP_Error
 */
function voa_endpoint_get_theme( WP_REST_Request $request ) {
	$auth = voa_validate_request();
	if ( is_wp_error( $auth ) ) {
		return $auth;
	}

	$rate_limit = voa_check_rate_limit();
	if ( is_wp_error( $rate_limit ) ) {
		return $rate_limit;
	}

	$theme = wp_get_theme();
	$screenshot = $theme->get_screenshot();

	$response = array(
		'name'                => $theme->get( 'Name' ),
		'version'             => $theme->get( 'Version' ),
		'template'            => $theme->get_template(),
		'screenshot_url'      => $screenshot ?: '',
		'customizer_settings' => get_theme_mods(),
	);

	return rest_ensure_response( $response );
}

/**
 * Endpoint: PUT /theme/customizer
 *
 * @param WP_REST_Request $request The request object.
 * @return WP_REST_Response|WP_Error
 */
function voa_endpoint_update_customizer( WP_REST_Request $request ) {
	$auth = voa_validate_request();
	if ( is_wp_error( $auth ) ) {
		return $auth;
	}

	$rate_limit = voa_check_rate_limit();
	if ( is_wp_error( $rate_limit ) ) {
		return $rate_limit;
	}

	$settings = $request->get_param( 'settings' );

	if ( ! is_array( $settings ) ) {
		return new WP_Error( 'voa_invalid_settings', 'Settings must be an object.', array( 'status' => 400 ) );
	}

	foreach ( $settings as $key => $value ) {
		$key = sanitize_key( $key );
		if ( ! empty( $key ) ) {
			set_theme_mod( $key, wp_kses_post( $value ) );
		}
	}

	return rest_ensure_response( array( 'success' => true, 'message' => 'Theme customizer updated.' ) );
}

/**
 * Endpoint: GET /menus
 *
 * @param WP_REST_Request $request The request object.
 * @return WP_REST_Response|WP_Error
 */
function voa_endpoint_get_menus( WP_REST_Request $request ) {
	$auth = voa_validate_request();
	if ( is_wp_error( $auth ) ) {
		return $auth;
	}

	$rate_limit = voa_check_rate_limit();
	if ( is_wp_error( $rate_limit ) ) {
		return $rate_limit;
	}

	$menus = get_terms( array(
		'taxonomy' => 'nav_menu',
		'hide_empty' => false,
	) );

	$menu_data = array();

	foreach ( $menus as $menu ) {
		$items = wp_get_nav_menu_items( $menu->term_id );

		$menu_data[] = array(
			'id'    => $menu->term_id,
			'name'  => $menu->name,
			'slug'  => $menu->slug,
			'items' => is_array( $items ) ? array_map( function( $item ) {
				return array(
					'id'       => $item->ID,
					'title'    => $item->title,
					'url'      => $item->url,
					'parent'   => $item->menu_item_parent,
					'object'   => $item->object,
					'object_id' => $item->object_id,
				);
			}, $items ) : array(),
		);
	}

	return rest_ensure_response( array( 'menus' => $menu_data ) );
}

/**
 * Endpoint: PUT /menus/{id}
 *
 * @param WP_REST_Request $request The request object.
 * @return WP_REST_Response|WP_Error
 */
function voa_endpoint_update_menu( WP_REST_Request $request ) {
	$auth = voa_validate_request();
	if ( is_wp_error( $auth ) ) {
		return $auth;
	}

	$rate_limit = voa_check_rate_limit();
	if ( is_wp_error( $rate_limit ) ) {
		return $rate_limit;
	}

	$menu_id = absint( $request->get_param( 'id' ) );
	$menu = get_term( $menu_id, 'nav_menu' );

	if ( ! $menu || is_wp_error( $menu ) ) {
		return new WP_Error( 'voa_menu_not_found', 'Menu not found.', array( 'status' => 404 ) );
	}

	$items        = $request->get_param( 'items' );
	$replace_all  = (bool) $request->get_param( 'replaceAll' );

	if ( ! is_array( $items ) ) {
		return new WP_Error( 'voa_invalid_items', 'Items must be an array.', array( 'status' => 400 ) );
	}

	$results = array();

	// replaceAll: wipe every existing item first so a restore-point apply
	// can recreate the exact captured state without leftover items hanging
	// around. Skipped by default for per-item create/update/delete callers.
	if ( $replace_all ) {
		$existing = wp_get_nav_menu_items( $menu_id );
		if ( is_array( $existing ) ) {
			foreach ( $existing as $ex ) {
				wp_delete_nav_menu_item( $ex->ID );
				$results[] = array( 'id' => $ex->ID, 'action' => 'deleted_for_replace' );
			}
		}
	}

	foreach ( $items as $item_data ) {
		$item_id    = absint( $item_data['id'] ?? 0 );
		$menu_order = absint( $item_data['menu_order'] ?? 0 );
		$parent     = absint( $item_data['parent'] ?? 0 );
		$title      = sanitize_text_field( $item_data['title'] ?? '' );
		$url        = esc_url_raw( $item_data['url'] ?? '' );
		$classes    = $item_data['classes'] ?? array();
		$type       = sanitize_key( $item_data['type'] ?? 'custom' );
		$object     = sanitize_key( $item_data['object'] ?? '' );
		$object_id  = absint( $item_data['object_id'] ?? 0 );
		$action     = sanitize_key( $item_data['action'] ?? 'update' ); // update, create, delete

		if ( 'delete' === $action && $item_id > 0 ) {
			wp_delete_nav_menu_item( $item_id );
			$results[] = array( 'id' => $item_id, 'action' => 'deleted' );
			continue;
		}

		$menu_item_args = array(
			'menu-item-parent-id' => $parent,
			'menu-item-position'  => $menu_order,
			'menu-item-status'    => 'publish',
		);

		if ( ! empty( $title ) )   $menu_item_args['menu-item-title']     = $title;
		if ( ! empty( $url ) )     $menu_item_args['menu-item-url']       = $url;
		if ( ! empty( $type ) )    $menu_item_args['menu-item-type']      = $type;
		if ( ! empty( $object ) )  $menu_item_args['menu-item-object']    = $object;
		if ( $object_id > 0 )      $menu_item_args['menu-item-object-id'] = $object_id;
		if ( is_array( $classes ) && ! empty( $classes ) ) {
			$menu_item_args['menu-item-classes'] = implode( ' ', array_map( 'sanitize_html_class', $classes ) );
		}

		// item_id = 0 means create new, > 0 means update existing
		$result_id = wp_update_nav_menu_item( $menu_id, $item_id, $menu_item_args );

		if ( is_wp_error( $result_id ) ) {
			$results[] = array( 'id' => $item_id, 'error' => $result_id->get_error_message() );
		} else {
			$results[] = array( 'id' => $result_id, 'action' => $item_id > 0 ? 'updated' : 'created' );
		}
	}

	return rest_ensure_response( array( 'success' => true, 'message' => 'Menu updated.', 'items' => $results ) );
}

/**
 * Endpoint: GET /woocommerce/products
 *
 * @param WP_REST_Request $request The request object.
 * @return WP_REST_Response|WP_Error
 */
function voa_endpoint_wc_get_products( WP_REST_Request $request ) {
	$auth = voa_validate_request();
	if ( is_wp_error( $auth ) ) {
		return $auth;
	}

	$rate_limit = voa_check_rate_limit();
	if ( is_wp_error( $rate_limit ) ) {
		return $rate_limit;
	}

	if ( ! class_exists( 'WooCommerce' ) ) {
		return new WP_Error( 'voa_woocommerce_not_active', 'WooCommerce is not active.', array( 'status' => 400 ) );
	}

	$page = absint( $request->get_param( 'page' ) ) ?: 1;
	$per_page = absint( $request->get_param( 'per_page' ) ) ?: 10;

	$args = array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => $per_page,
		'paged'          => $page,
		'orderby'        => 'date',
		'order'          => 'DESC',
	);

	$query = new WP_Query( $args );
	$products = array();

	foreach ( $query->posts as $post ) {
		$product = wc_get_product( $post->ID );

		if ( $product ) {
			$image_id = $product->get_image_id();
			$images = $product->get_gallery_image_ids();
			array_unshift( $images, $image_id );

			$products[] = array(
				'id'         => $product->get_id(),
				'name'       => $product->get_name(),
				'sku'        => $product->get_sku(),
				'price'      => $product->get_price(),
				'stock'      => $product->get_stock_quantity(),
				'status'     => $product->get_status(),
				'categories' => array_map( function( $cat ) {
					return array( 'id' => $cat->term_id, 'name' => $cat->name );
				}, $product->get_category_ids() ),
				'images'     => array_filter( array_map( function( $img_id ) {
					return wp_get_attachment_url( $img_id );
				}, $images ) ),
			);
		}
	}

	return rest_ensure_response( array(
		'products'    => $products,
		'total'       => $query->found_posts,
		'total_pages' => $query->max_num_pages,
		'page'        => $page,
	) );
}

/**
 * Endpoint: POST /woocommerce/products
 *
 * @param WP_REST_Request $request The request object.
 * @return WP_REST_Response|WP_Error
 */
function voa_endpoint_wc_create_product( WP_REST_Request $request ) {
	$auth = voa_validate_request();
	if ( is_wp_error( $auth ) ) {
		return $auth;
	}

	$rate_limit = voa_check_rate_limit();
	if ( is_wp_error( $rate_limit ) ) {
		return $rate_limit;
	}

	if ( ! class_exists( 'WooCommerce' ) ) {
		return new WP_Error( 'voa_woocommerce_not_active', 'WooCommerce is not active.', array( 'status' => 400 ) );
	}

	$product = new WC_Product_Simple();
	$product->set_name( sanitize_text_field( $request->get_param( 'name' ) ) );
	$product->set_description( wp_kses_post( $request->get_param( 'description' ) ) );
	$product->set_price( floatval( $request->get_param( 'price' ) ) );
	$product->set_stock_quantity( absint( $request->get_param( 'stock' ) ) );
	$product->set_status( 'publish' );

	$sku = sanitize_text_field( $request->get_param( 'sku' ) );
	if ( ! empty( $sku ) ) {
		$product->set_sku( $sku );
	}

	$product_id = $product->save();

	if ( is_wp_error( $product_id ) ) {
		return $product_id;
	}

	$categories = $request->get_param( 'categories' ) ?: array();
	if ( ! empty( $categories ) ) {
		wp_set_post_terms( $product_id, array_map( 'absint', $categories ), 'product_cat' );
	}

	$product = wc_get_product( $product_id );

	return rest_ensure_response( array(
		'id'      => $product->get_id(),
		'name'    => $product->get_name(),
		'sku'     => $product->get_sku(),
		'price'   => $product->get_price(),
	) );
}

/**
 * Endpoint: PUT /woocommerce/products/{id}
 *
 * @param WP_REST_Request $request The request object.
 * @return WP_REST_Response|WP_Error
 */
function voa_endpoint_wc_update_product( WP_REST_Request $request ) {
	$auth = voa_validate_request();
	if ( is_wp_error( $auth ) ) {
		return $auth;
	}

	$rate_limit = voa_check_rate_limit();
	if ( is_wp_error( $rate_limit ) ) {
		return $rate_limit;
	}

	if ( ! class_exists( 'WooCommerce' ) ) {
		return new WP_Error( 'voa_woocommerce_not_active', 'WooCommerce is not active.', array( 'status' => 400 ) );
	}

	$product_id = absint( $request->get_param( 'id' ) );
	$product = wc_get_product( $product_id );

	if ( ! $product ) {
		return new WP_Error( 'voa_product_not_found', 'Product not found.', array( 'status' => 404 ) );
	}

	if ( $request->has_param( 'name' ) ) {
		$product->set_name( sanitize_text_field( $request->get_param( 'name' ) ) );
	}

	if ( $request->has_param( 'description' ) ) {
		$product->set_description( wp_kses_post( $request->get_param( 'description' ) ) );
	}

	if ( $request->has_param( 'price' ) ) {
		$product->set_price( floatval( $request->get_param( 'price' ) ) );
	}

	if ( $request->has_param( 'stock' ) ) {
		$product->set_stock_quantity( absint( $request->get_param( 'stock' ) ) );
	}

	if ( $request->has_param( 'sku' ) ) {
		$product->set_sku( sanitize_text_field( $request->get_param( 'sku' ) ) );
	}

	$product->save();

	$product = wc_get_product( $product_id );

	return rest_ensure_response( array(
		'id'      => $product->get_id(),
		'name'    => $product->get_name(),
		'sku'     => $product->get_sku(),
		'price'   => $product->get_price(),
	) );
}

/**
 * Endpoint: GET /woocommerce/orders
 *
 * @param WP_REST_Request $request The request object.
 * @return WP_REST_Response|WP_Error
 */
function voa_endpoint_wc_get_orders( WP_REST_Request $request ) {
	$auth = voa_validate_request();
	if ( is_wp_error( $auth ) ) {
		return $auth;
	}

	$rate_limit = voa_check_rate_limit();
	if ( is_wp_error( $rate_limit ) ) {
		return $rate_limit;
	}

	if ( ! class_exists( 'WooCommerce' ) ) {
		return new WP_Error( 'voa_woocommerce_not_active', 'WooCommerce is not active.', array( 'status' => 400 ) );
	}

	$page = absint( $request->get_param( 'page' ) ) ?: 1;
	$per_page = absint( $request->get_param( 'per_page' ) ) ?: 10;

	$orders = wc_get_orders( array(
		'limit'  => $per_page,
		'offset' => ( $page - 1 ) * $per_page,
		'orderby' => 'date',
		'order'   => 'DESC',
	) );

	$total_orders = wc_get_orders( array(
		'limit'  => -1,
		'return' => 'ids',
	) );

	$order_data = array();

	foreach ( $orders as $order ) {
		$items = array_map( function( $item ) {
			return array(
				'id'       => $item->get_id(),
				'name'     => $item->get_name(),
				'quantity' => $item->get_quantity(),
				'price'    => $item->get_total(),
			);
		}, $order->get_items() );

		$order_data[] = array(
			'id'       => $order->get_id(),
			'status'   => $order->get_status(),
			'total'    => $order->get_total(),
			'customer' => array(
				'name'  => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
				'email' => $order->get_billing_email(),
			),
			'items'    => $items,
			'date'     => $order->get_date_created()->format( 'Y-m-d H:i:s' ),
		);
	}

	return rest_ensure_response( array(
		'orders'      => $order_data,
		'total'       => count( $total_orders ),
		'total_pages' => ceil( count( $total_orders ) / $per_page ),
		'page'        => $page,
	) );
}

/**
 * Endpoint: GET /woocommerce/stats
 *
 * @param WP_REST_Request $request The request object.
 * @return WP_REST_Response|WP_Error
 */
function voa_endpoint_wc_get_stats( WP_REST_Request $request ) {
	$auth = voa_validate_request();
	if ( is_wp_error( $auth ) ) {
		return $auth;
	}

	$rate_limit = voa_check_rate_limit();
	if ( is_wp_error( $rate_limit ) ) {
		return $rate_limit;
	}

	if ( ! class_exists( 'WooCommerce' ) ) {
		return new WP_Error( 'voa_woocommerce_not_active', 'WooCommerce is not active.', array( 'status' => 400 ) );
	}

	$period = sanitize_key( $request->get_param( 'period' ) ) ?: 'month';

	$date_from = '';
	switch ( $period ) {
		case 'day':
			$date_from = date( 'Y-m-d 00:00:00', strtotime( '-1 day' ) );
			break;
		case 'week':
			$date_from = date( 'Y-m-d 00:00:00', strtotime( '-7 days' ) );
			break;
		case 'year':
			$date_from = date( 'Y-m-d 00:00:00', strtotime( '-1 year' ) );
			break;
		case 'month':
		default:
			$date_from = date( 'Y-m-d 00:00:00', strtotime( '-30 days' ) );
			break;
	}

	$orders = wc_get_orders( array(
		'limit'     => -1,
		'return'    => 'objects',
		'date_after' => $date_from,
	) );

	$total_sales = 0;
	$orders_count = 0;
	$top_products = array();

	foreach ( $orders as $order ) {
		if ( 'completed' === $order->get_status() ) {
			$total_sales += floatval( $order->get_total() );
			$orders_count++;

			foreach ( $order->get_items() as $item ) {
				$product_id = $item->get_product_id();
				if ( ! isset( $top_products[ $product_id ] ) ) {
					$top_products[ $product_id ] = array(
						'name'     => $item->get_name(),
						'quantity' => 0,
						'total'    => 0,
					);
				}
				$top_products[ $product_id ]['quantity'] += $item->get_quantity();
				$top_products[ $product_id ]['total'] += floatval( $item->get_total() );
			}
		}
	}

	// Sort top products by total
	usort( $top_products, function( $a, $b ) {
		return $b['total'] <=> $a['total'];
	} );

	$average_order = $orders_count > 0 ? $total_sales / $orders_count : 0;

	return rest_ensure_response( array(
		'total_sales'    => round( $total_sales, 2 ),
		'orders_count'   => $orders_count,
		'average_order'  => round( $average_order, 2 ),
		'top_products'   => array_slice( $top_products, 0, 10 ),
	) );
}

/**
 * Endpoint: GET /settings
 *
 * Returns key WordPress settings including reading settings, homepage, blog page, etc.
 *
 * @param WP_REST_Request $request The request object.
 * @return WP_REST_Response|WP_Error
 */
function voa_endpoint_get_settings( WP_REST_Request $request ) {
	$auth = voa_validate_request();
	if ( is_wp_error( $auth ) ) {
		return $auth;
	}

	return rest_ensure_response( array(
		'blogname'        => get_option( 'blogname' ),
		'blogdescription' => get_option( 'blogdescription' ),
		'siteurl'         => get_option( 'siteurl' ),
		'home'            => get_option( 'home' ),
		'show_on_front'   => get_option( 'show_on_front' ),
		'page_on_front'   => (int) get_option( 'page_on_front' ),
		'page_for_posts'  => (int) get_option( 'page_for_posts' ),
		'posts_per_page'  => (int) get_option( 'posts_per_page' ),
		'permalink_structure' => get_option( 'permalink_structure' ),
		'date_format'     => get_option( 'date_format' ),
		'time_format'     => get_option( 'time_format' ),
		'timezone_string' => get_option( 'timezone_string' ),
	) );
}

/**
 * Endpoint: POST /settings
 *
 * Updates WordPress settings. Supports: blogname, blogdescription, show_on_front,
 * page_on_front, page_for_posts, posts_per_page, permalink_structure.
 *
 * @param WP_REST_Request $request The request object.
 * @return WP_REST_Response|WP_Error
 */
function voa_endpoint_update_settings( WP_REST_Request $request ) {
	$auth = voa_validate_request();
	if ( is_wp_error( $auth ) ) {
		return $auth;
	}

	$rate_limit = voa_check_rate_limit();
	if ( is_wp_error( $rate_limit ) ) {
		return $rate_limit;
	}

	$allowed_settings = array(
		'blogname', 'blogdescription', 'show_on_front', 'page_on_front',
		'page_for_posts', 'posts_per_page', 'permalink_structure',
		'date_format', 'time_format', 'timezone_string',
	);

	$updated = array();
	$params = $request->get_json_params();

	foreach ( $params as $key => $value ) {
		$key = sanitize_key( $key );
		if ( in_array( $key, $allowed_settings, true ) ) {
			update_option( $key, sanitize_text_field( $value ) );
			$updated[ $key ] = $value;
		}
	}

	if ( empty( $updated ) ) {
		return new WP_Error( 'voa_no_settings', 'No valid settings provided.', array( 'status' => 400 ) );
	}

	return rest_ensure_response( array(
		'success' => true,
		'message' => 'Settings updated.',
		'updated' => $updated,
	) );
}

/**
 * Endpoint: POST /execute
 *
 * @param WP_REST_Request $request The request object.
 * @return WP_REST_Response|WP_Error
 */
function voa_endpoint_execute_action( WP_REST_Request $request ) {
	$auth = voa_validate_request();
	if ( is_wp_error( $auth ) ) {
		return $auth;
	}

	$rate_limit = voa_check_rate_limit();
	if ( is_wp_error( $rate_limit ) ) {
		return $rate_limit;
	}

	// Support direct PHP code execution (the AI sends arbitrary WP function calls)
	$code = $request->get_param( 'code' );
	if ( ! empty( $code ) ) {
		// Block dangerous operations - file system writes, shell commands, eval chains
		$blocked_patterns = array(
			'file_put_contents', 'file_get_contents', 'fopen', 'fwrite', 'unlink',
			'exec(', 'shell_exec', 'system(', 'passthru', 'popen', 'proc_open',
			'eval(', 'assert(', 'preg_replace.*e', 'create_function',
			'$_GET', '$_POST', '$_REQUEST', '$_SERVER', '$_FILES',
			'base64_decode', 'gzinflate', 'str_rot13',
			'curl_exec', 'wp_remote_', 'wp_safe_remote_',
		);
		foreach ( $blocked_patterns as $pattern ) {
			if ( stripos( $code, $pattern ) !== false ) {
				return new WP_Error( 'voa_blocked_code', "Blocked operation detected: $pattern", array( 'status' => 403 ) );
			}
		}

		ob_start();
		$result = null;
		$error = null;
		try {
			$result = eval( $code );
		} catch ( \Throwable $e ) {
			$error = $e->getMessage();
		}
		$output = ob_get_clean();

		if ( $error ) {
			return new WP_Error( 'voa_php_error', $error, array( 'status' => 500 ) );
		}

		return rest_ensure_response( array(
			'success' => true,
			'result'  => $result,
			'output'  => $output,
		) );
	}

	// Legacy: support named actions for backwards compatibility
	$action = sanitize_key( $request->get_param( 'action' ) );
	$params = $request->get_param( 'params' ) ?: array();

	$result = null;

	switch ( $action ) {
		case 'update_option':
			$option_name = sanitize_key( $params['option_name'] ?? '' );
			$option_value = $params['option_value'] ?? '';

			if ( empty( $option_name ) ) {
				return new WP_Error( 'voa_missing_param', 'option_name is required.', array( 'status' => 400 ) );
			}

			update_option( $option_name, $option_value );
			$result = get_option( $option_name );
			break;

		case 'flush_rewrite_rules':
			flush_rewrite_rules();
			$result = 'Rewrite rules flushed.';
			break;

		case 'create_menu_item':
			$menu_id = absint( $params['menu_id'] ?? 0 );
			$title = sanitize_text_field( $params['title'] ?? '' );
			$url = esc_url_raw( $params['url'] ?? '' );
			$parent = absint( $params['parent'] ?? 0 );

			if ( empty( $menu_id ) || empty( $title ) || empty( $url ) ) {
				return new WP_Error( 'voa_missing_param', 'menu_id, title, and url are required.', array( 'status' => 400 ) );
			}

			$item_id = wp_update_nav_menu_item( $menu_id, 0, array(
				'menu-item-title'   => $title,
				'menu-item-url'     => $url,
				'menu-item-parent-id' => $parent,
				'menu-item-status'  => 'publish',
			) );

			$result = array( 'item_id' => $item_id );
			break;

		case 'update_nav_menu':
			$menu_id = absint( $params['menu_id'] ?? 0 );
			$menu_name = sanitize_text_field( $params['menu_name'] ?? '' );

			if ( empty( $menu_id ) || empty( $menu_name ) ) {
				return new WP_Error( 'voa_missing_param', 'menu_id and menu_name are required.', array( 'status' => 400 ) );
			}

			wp_update_nav_menu_object( $menu_id, array( 'menu-name' => $menu_name ) );
			$result = get_term( $menu_id, 'nav_menu' );
			break;

		default:
			return new WP_Error( 'voa_unknown_action', 'Unknown action. Use "code" param for PHP execution.', array( 'status' => 400 ) );
	}

	return rest_ensure_response( array(
		'action' => $action,
		'result' => $result,
	) );
}

/**
 * Add admin menu
 */
function voa_add_admin_menu() {
	add_options_page(
		'MyMomo - Connector',
		'MyMomo',
		'manage_options',
		'voa-settings',
		'voa_render_admin_page'
	);
}

/**
 * Register settings
 */
function voa_register_settings() {
	register_setting( 'voa-settings', VOA_API_KEY_OPTION );
}

/**
 * Render admin page
 */
function voa_render_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Unauthorized access', 'virtual-office-ai' ) );
	}

	$api_key = voa_get_api_key();
	$last_ping = get_transient( VOA_LAST_PING_TRANSIENT );
	$site_url = get_site_url();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'MyMomo - Connector', 'virtual-office-ai' ); ?></h1>

		<div class="card">
			<h2><?php esc_html_e( 'Connection Details', 'virtual-office-ai' ); ?></h2>
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="voa-site-url"><?php esc_html_e( 'Site URL', 'virtual-office-ai' ); ?></label>
					</th>
					<td>
						<input type="text" id="voa-site-url" value="<?php echo esc_attr( $site_url ); ?>" readonly style="width: 100%; padding: 8px; background: #f5f5f5;">
						<button type="button" class="button button-secondary" onclick="voa_copy_to_clipboard('voa-site-url')" style="margin-top: 5px;">
							<?php esc_html_e( 'Copy', 'virtual-office-ai' ); ?>
						</button>
						<p class="description"><?php esc_html_e( 'Enter this URL in MyMomo settings.', 'virtual-office-ai' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="voa-api-key"><?php esc_html_e( 'API Key', 'virtual-office-ai' ); ?></label>
					</th>
					<td>
						<input type="text" id="voa-api-key" value="<?php echo esc_attr( $api_key ); ?>" readonly style="width: 100%; padding: 8px; background: #f5f5f5; font-family: monospace; word-break: break-all;">
						<button type="button" class="button button-secondary" onclick="voa_copy_to_clipboard('voa-api-key')" style="margin-top: 5px;">
							<?php esc_html_e( 'Copy API Key', 'virtual-office-ai' ); ?>
						</button>
						<p class="description"><?php esc_html_e( 'This key is used for secure API authentication. Keep it private.', 'virtual-office-ai' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Last Connection', 'virtual-office-ai' ); ?></label>
					</th>
					<td>
						<p>
							<?php
							if ( $last_ping ) {
								echo esc_html( 'Last ping: ' . $last_ping );
							} else {
								esc_html_e( 'No connection established yet.', 'virtual-office-ai' );
							}
							?>
						</p>
					</td>
				</tr>
			</table>
		</div>

		<div class="card">
			<h2><?php esc_html_e( 'API Key Management', 'virtual-office-ai' ); ?></h2>
			<p><?php esc_html_e( 'If you need to regenerate your API key, click the button below. This will invalidate the current key.', 'virtual-office-ai' ); ?></p>
			<form method="post" style="display: inline;">
				<?php wp_nonce_field( 'voa_regenerate_key', 'voa_nonce' ); ?>
				<button type="submit" name="voa_regenerate" class="button button-primary" onclick="return confirm('<?php echo esc_attr( __( 'Are you sure you want to regenerate the API key? This will invalidate the current key.', 'virtual-office-ai' ) ); ?>');">
					<?php esc_html_e( 'Regenerate API Key', 'virtual-office-ai' ); ?>
				</button>
			</form>
		</div>
	</div>

	<script type="text/javascript">
		function voa_copy_to_clipboard(element_id) {
			const element = document.getElementById(element_id);
			const text = element.value;

			navigator.clipboard.writeText(text).then(function() {
				alert('<?php echo esc_attr( __( 'Copied to clipboard!', 'virtual-office-ai' ) ); ?>');
			}, function(err) {
				console.error('Could not copy text: ', err);
			});
		}
	</script>

	<?php
	// Handle regenerate key form
	if ( isset( $_POST['voa_regenerate'] ) ) {
		if ( ! isset( $_POST['voa_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['voa_nonce'] ), 'voa_regenerate_key' ) ) {
			wp_die( esc_html__( 'Nonce verification failed', 'virtual-office-ai' ) );
		}

		voa_generate_new_api_key();
		?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'API key has been regenerated successfully.', 'virtual-office-ai' ); ?></p>
		</div>
		<?php
	}
}

/**
 * Endpoint: POST /theme-install
 *
 * Writes a set of theme files into wp-content/themes/{slug}/ and switches to
 * the theme. Accepts files as a map of relative-path -> base64 content so we
 * do not need zip support in the Cloudflare Worker.
 *
 * Body:
 *   {
 *     "slug": "voa-imported",
 *     "name": "VOA Imported Site",
 *     "files": { "style.css": "<base64>", "header.php": "<base64>", ... },
 *     "activate": true
 *   }
 *
 * @param WP_REST_Request $request The request object.
 * @return WP_REST_Response|WP_Error
 */
function voa_endpoint_theme_install( WP_REST_Request $request ) {
	$auth = voa_validate_request();
	if ( is_wp_error( $auth ) ) {
		return $auth;
	}

	$rate_limit = voa_check_rate_limit();
	if ( is_wp_error( $rate_limit ) ) {
		return $rate_limit;
	}

	$slug     = sanitize_title( (string) $request->get_param( 'slug' ) );
	$name     = sanitize_text_field( (string) $request->get_param( 'name' ) );
	$files    = $request->get_param( 'files' );
	$activate = (bool) $request->get_param( 'activate' );

	if ( empty( $slug ) ) {
		return new WP_Error( 'voa_missing_slug', 'slug is required.', array( 'status' => 400 ) );
	}
	if ( ! is_array( $files ) || empty( $files ) ) {
		return new WP_Error( 'voa_missing_files', 'files must be a non-empty object.', array( 'status' => 400 ) );
	}

	// Reject anything that would escape the themes directory.
	$allowed_ext = array( 'php', 'css', 'js', 'json', 'txt', 'md', 'html', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'ico', 'woff', 'woff2', 'ttf', 'otf', 'eot' );
	foreach ( $files as $relpath => $_ ) {
		if ( ! is_string( $relpath ) || $relpath === '' ) {
			return new WP_Error( 'voa_bad_path', 'file path must be a non-empty string.', array( 'status' => 400 ) );
		}
		if ( strpos( $relpath, '..' ) !== false || strpos( $relpath, "\0" ) !== false ) {
			return new WP_Error( 'voa_bad_path', 'file path contains invalid segment: ' . $relpath, array( 'status' => 400 ) );
		}
		if ( strpos( $relpath, '/' ) === 0 || preg_match( '#^[a-zA-Z]:[\\\\/]#', $relpath ) ) {
			return new WP_Error( 'voa_bad_path', 'file path must be relative: ' . $relpath, array( 'status' => 400 ) );
		}
		$ext = strtolower( pathinfo( $relpath, PATHINFO_EXTENSION ) );
		if ( ! in_array( $ext, $allowed_ext, true ) ) {
			return new WP_Error( 'voa_bad_ext', 'file extension not allowed: ' . $relpath, array( 'status' => 400 ) );
		}
	}

	$themes_dir = get_theme_root();
	if ( empty( $themes_dir ) || ! is_dir( $themes_dir ) ) {
		return new WP_Error( 'voa_no_themes_dir', 'Theme root not found.', array( 'status' => 500 ) );
	}

	$theme_dir = trailingslashit( $themes_dir ) . $slug;
	if ( ! file_exists( $theme_dir ) ) {
		if ( ! wp_mkdir_p( $theme_dir ) ) {
			return new WP_Error( 'voa_mkdir_failed', 'Failed to create theme directory: ' . $theme_dir, array( 'status' => 500 ) );
		}
	}

	$written = array();
	$errors  = array();
	foreach ( $files as $relpath => $b64 ) {
		$target = trailingslashit( $theme_dir ) . ltrim( $relpath, '/' );
		$parent = dirname( $target );
		if ( ! file_exists( $parent ) ) {
			if ( ! wp_mkdir_p( $parent ) ) {
				$errors[] = array( 'path' => $relpath, 'error' => 'mkdir failed' );
				continue;
			}
		}
		$data = base64_decode( (string) $b64, true );
		if ( false === $data ) {
			$errors[] = array( 'path' => $relpath, 'error' => 'invalid base64' );
			continue;
		}
		$bytes = file_put_contents( $target, $data );
		if ( false === $bytes ) {
			$errors[] = array( 'path' => $relpath, 'error' => 'write failed' );
			continue;
		}
		$written[] = array( 'path' => $relpath, 'bytes' => $bytes );
	}

	if ( ! empty( $errors ) && empty( $written ) ) {
		return new WP_Error( 'voa_write_failed', 'All writes failed.', array( 'status' => 500, 'errors' => $errors ) );
	}

	// Make sure wp_get_theme sees the new dir.
	wp_clean_themes_cache();

	$theme = wp_get_theme( $slug );
	$activated = false;
	if ( $activate && $theme && ! $theme->errors() ) {
		switch_theme( $slug );
		$activated = true;
	}

	$theme_errors = array();
	if ( $theme && $theme->errors() ) {
		foreach ( $theme->errors()->get_error_messages() as $msg ) {
			$theme_errors[] = $msg;
		}
	}

	return rest_ensure_response( array(
		'success'      => true,
		'slug'         => $slug,
		'name'         => $theme ? $theme->get( 'Name' ) : $name,
		'version'      => $theme ? $theme->get( 'Version' ) : '',
		'activated'    => $activated,
		'theme_dir'    => $theme_dir,
		'written'      => $written,
		'errors'       => $errors,
		'theme_errors' => $theme_errors,
	) );
}

/**
 * Endpoint: POST /plugin-install
 *
 * Writes a set of plugin files into wp-content/plugins/{slug}/ and optionally
 * activates the plugin. Accepts files as a map of relative-path -> base64
 * content so we do not need zip support in the Cloudflare Worker.
 *
 * Body:
 *   {
 *     "slug": "acme-fields",
 *     "main_file": "acme-fields.php",
 *     "files": { "acme-fields.php": "<base64>", "includes/class-acme-sections.php": "<base64>", ... },
 *     "activate": true,
 *     "replace": true
 *   }
 *
 * If "replace" is true and a plugin directory with that slug already exists,
 * its contents are wiped before new files are written (scoped to the plugin
 * directory; nothing outside it is touched).
 *
 * @param WP_REST_Request $request The request object.
 * @return WP_REST_Response|WP_Error
 */
function voa_endpoint_plugin_install( WP_REST_Request $request ) {
	$auth = voa_validate_request();
	if ( is_wp_error( $auth ) ) {
		return $auth;
	}

	$rate_limit = voa_check_rate_limit();
	if ( is_wp_error( $rate_limit ) ) {
		return $rate_limit;
	}

	$slug      = sanitize_title( (string) $request->get_param( 'slug' ) );
	$main_file = sanitize_text_field( (string) $request->get_param( 'main_file' ) );
	$files     = $request->get_param( 'files' );
	$activate  = (bool) $request->get_param( 'activate' );
	$replace   = (bool) $request->get_param( 'replace' );

	if ( empty( $slug ) ) {
		return new WP_Error( 'voa_missing_slug', 'slug is required.', array( 'status' => 400 ) );
	}
	if ( ! is_array( $files ) || empty( $files ) ) {
		return new WP_Error( 'voa_missing_files', 'files must be a non-empty object.', array( 'status' => 400 ) );
	}

	// Same path-safety rules as theme-install. Plugins can ship the same
	// file types (PHP, CSS, JS, assets, fonts).
	$allowed_ext = array( 'php', 'css', 'js', 'json', 'txt', 'md', 'html', 'po', 'pot', 'mo', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'ico', 'woff', 'woff2', 'ttf', 'otf', 'eot' );
	foreach ( $files as $relpath => $_ ) {
		if ( ! is_string( $relpath ) || $relpath === '' ) {
			return new WP_Error( 'voa_bad_path', 'file path must be a non-empty string.', array( 'status' => 400 ) );
		}
		if ( strpos( $relpath, '..' ) !== false || strpos( $relpath, "\0" ) !== false ) {
			return new WP_Error( 'voa_bad_path', 'file path contains invalid segment: ' . $relpath, array( 'status' => 400 ) );
		}
		if ( strpos( $relpath, '/' ) === 0 || preg_match( '#^[a-zA-Z]:[\\\\/]#', $relpath ) ) {
			return new WP_Error( 'voa_bad_path', 'file path must be relative: ' . $relpath, array( 'status' => 400 ) );
		}
		$ext = strtolower( pathinfo( $relpath, PATHINFO_EXTENSION ) );
		if ( ! in_array( $ext, $allowed_ext, true ) ) {
			return new WP_Error( 'voa_bad_ext', 'file extension not allowed: ' . $relpath, array( 'status' => 400 ) );
		}
	}

	// Resolve the plugins directory. WP_PLUGIN_DIR is defined in wp-load.
	$plugins_dir = defined( 'WP_PLUGIN_DIR' ) ? WP_PLUGIN_DIR : trailingslashit( WP_CONTENT_DIR ) . 'plugins';
	if ( empty( $plugins_dir ) || ! is_dir( $plugins_dir ) ) {
		return new WP_Error( 'voa_no_plugins_dir', 'Plugins directory not found.', array( 'status' => 500 ) );
	}

	$plugin_dir = trailingslashit( $plugins_dir ) . $slug;

	// Defensive: make absolutely sure we're still under the plugins dir.
	$plugins_dir_real = realpath( $plugins_dir );
	if ( false === $plugins_dir_real ) {
		return new WP_Error( 'voa_no_plugins_dir', 'Plugins directory unresolvable.', array( 'status' => 500 ) );
	}

	// If plugin is already installed and active, deactivate it first so we
	// can safely overwrite its files without hitting a half-loaded state.
	$main_rel    = $main_file !== '' ? $main_file : ( $slug . '.php' );
	$plugin_file = $slug . '/' . ltrim( $main_rel, '/' );
	if ( function_exists( 'is_plugin_active' ) && is_plugin_active( $plugin_file ) ) {
		deactivate_plugins( $plugin_file, true );
	}

	// Optional clean replace: remove old files inside the plugin directory.
	if ( $replace && is_dir( $plugin_dir ) ) {
		$plugin_dir_real = realpath( $plugin_dir );
		// Must still be a child of plugins dir after realpath.
		if ( $plugin_dir_real && 0 === strpos( $plugin_dir_real, $plugins_dir_real ) && $plugin_dir_real !== $plugins_dir_real ) {
			voa_rrm_dir_contents( $plugin_dir_real );
		}
	}

	if ( ! file_exists( $plugin_dir ) ) {
		if ( ! wp_mkdir_p( $plugin_dir ) ) {
			return new WP_Error( 'voa_mkdir_failed', 'Failed to create plugin directory: ' . $plugin_dir, array( 'status' => 500 ) );
		}
	}

	$written = array();
	$errors  = array();
	foreach ( $files as $relpath => $b64 ) {
		$target = trailingslashit( $plugin_dir ) . ltrim( $relpath, '/' );
		$parent = dirname( $target );
		if ( ! file_exists( $parent ) ) {
			if ( ! wp_mkdir_p( $parent ) ) {
				$errors[] = array( 'path' => $relpath, 'error' => 'mkdir failed' );
				continue;
			}
		}
		$data = base64_decode( (string) $b64, true );
		if ( false === $data ) {
			$errors[] = array( 'path' => $relpath, 'error' => 'invalid base64' );
			continue;
		}
		$bytes = file_put_contents( $target, $data );
		if ( false === $bytes ) {
			$errors[] = array( 'path' => $relpath, 'error' => 'write failed' );
			continue;
		}
		$written[] = array( 'path' => $relpath, 'bytes' => $bytes );
	}

	if ( ! empty( $errors ) && empty( $written ) ) {
		return new WP_Error( 'voa_write_failed', 'All writes failed.', array( 'status' => 500, 'errors' => $errors ) );
	}

	// Confirm the main plugin file is now on disk, otherwise activation will fail.
	$main_target = trailingslashit( $plugin_dir ) . ltrim( $main_rel, '/' );
	if ( ! file_exists( $main_target ) ) {
		return new WP_Error( 'voa_main_missing', 'Main plugin file not present after write: ' . $main_rel, array( 'status' => 500, 'written' => $written, 'errors' => $errors ) );
	}

	// Flush plugin-list cache so get_plugins() / activate_plugin() see new files.
	if ( function_exists( 'wp_clean_plugins_cache' ) ) {
		wp_clean_plugins_cache( true );
	}

	$activated     = false;
	$activate_error = '';
	if ( $activate ) {
		if ( ! function_exists( 'activate_plugin' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$result = activate_plugin( $plugin_file, '', false, true );
		if ( is_wp_error( $result ) ) {
			$activate_error = $result->get_error_message();
		} elseif ( null === $result ) {
			$activated = true;
		}
	}

	return rest_ensure_response( array(
		'success'        => true,
		'slug'           => $slug,
		'plugin_file'    => $plugin_file,
		'plugin_dir'     => $plugin_dir,
		'activated'      => $activated,
		'activate_error' => $activate_error,
		'written'        => $written,
		'errors'         => $errors,
	) );
}

/**
 * Recursively sanitize a sections payload before writing to post_meta.
 * Keeps scalar values, arrays, and nested structures; converts anything
 * else to an empty string. Caps recursion depth so a malicious deeply-
 * nested input can't blow the stack.
 *
 * @param mixed $v     Value to sanitize.
 * @param int   $depth Current recursion depth (internal).
 * @return mixed
 */
function voa_sanitize_sections( $v, $depth = 0 ) {
	if ( $depth > 8 ) {
		return '';
	}
	if ( is_array( $v ) ) {
		$out = array();
		foreach ( $v as $k => $child ) {
			$ck = is_string( $k ) ? sanitize_key( $k ) : $k;
			if ( is_int( $k ) ) {
				$ck = (int) $k;
			}
			$out[ $ck ] = voa_sanitize_sections( $child, $depth + 1 );
		}
		return $out;
	}
	if ( is_bool( $v ) || is_int( $v ) || is_float( $v ) ) {
		return $v;
	}
	if ( is_string( $v ) ) {
		// wp_kses_post preserves safe HTML (img, a, p, etc.) for rich-text fields.
		return wp_kses_post( $v );
	}
	return '';
}

/**
 * Recursively remove the CONTENTS of a directory, but keep the directory
 * itself. Used only for the per-plugin replace flow, and always called
 * with a path already verified to be inside WP_PLUGIN_DIR.
 *
 * @param string $dir Absolute path to a directory.
 * @return void
 */
function voa_rrm_dir_contents( $dir ) {
	if ( ! is_dir( $dir ) ) {
		return;
	}
	$items = @scandir( $dir );
	if ( ! is_array( $items ) ) {
		return;
	}
	foreach ( $items as $item ) {
		if ( '.' === $item || '..' === $item ) {
			continue;
		}
		$path = $dir . DIRECTORY_SEPARATOR . $item;
		if ( is_link( $path ) ) {
			@unlink( $path );
			continue;
		}
		if ( is_dir( $path ) ) {
			voa_rrm_dir_contents( $path );
			@rmdir( $path );
		} else {
			@unlink( $path );
		}
	}
}

/**
 * Endpoint: POST /recreate
 *
 * Create or update a batch of pages, then optionally set the static homepage
 * and build the primary navigation menu from the same page list.
 *
 * Body:
 *   {
 *     "pages": [
 *       {
 *         "slug": "about",
 *         "title": "About",
 *         "content": "<...>",
 *         "sections": [ { "type": "hero", ... }, ... ]
 *       },
 *       ...
 *     ],
 *     "home_slug": "home",
 *     "menu_name": "Primary",
 *     "menu_location": "primary",
 *     "include_home_in_menu": true,
 *     "sections_meta_key": "_acmef_sections"
 *   }
 *
 * Existing pages are matched by slug and updated; new slugs create new pages.
 *
 * If sections_meta_key is provided, each page's sections array is written to
 * post_meta under that key. This is how the per-site CC Fields plugin picks
 * up the section data to render.
 *
 * @param WP_REST_Request $request The request object.
 * @return WP_REST_Response|WP_Error
 */
function voa_endpoint_recreate( WP_REST_Request $request ) {
	$auth = voa_validate_request();
	if ( is_wp_error( $auth ) ) {
		return $auth;
	}

	$rate_limit = voa_check_rate_limit();
	if ( is_wp_error( $rate_limit ) ) {
		return $rate_limit;
	}

	$pages_in = $request->get_param( 'pages' );
	if ( ! is_array( $pages_in ) ) {
		return new WP_Error( 'voa_missing_pages', 'pages must be an array.', array( 'status' => 400 ) );
	}

	$home_slug            = sanitize_title( (string) $request->get_param( 'home_slug' ) );
	$menu_name            = sanitize_text_field( $request->get_param( 'menu_name' ) ?: 'Primary' );
	$menu_location        = sanitize_key( $request->get_param( 'menu_location' ) ?: 'primary' );
	$include_home_in_menu = (bool) $request->get_param( 'include_home_in_menu' );

	// Optional: meta-key to store each page's CC Fields sections array under.
	// Allow word chars, dash, and leading underscore (WP "protected" meta convention).
	$raw_meta_key      = (string) $request->get_param( 'sections_meta_key' );
	$sections_meta_key = '';
	if ( $raw_meta_key !== '' && preg_match( '/^_?[A-Za-z0-9_\-]{1,64}$/', $raw_meta_key ) ) {
		$sections_meta_key = $raw_meta_key;
	}

	$created = array();
	$slug_to_id = array();

	foreach ( $pages_in as $p ) {
		$slug    = sanitize_title( (string) ( $p['slug'] ?? '' ) );
		$title   = sanitize_text_field( (string) ( $p['title'] ?? '' ) );
		$content = (string) ( $p['content'] ?? '' );
		$sections = ( isset( $p['sections'] ) && is_array( $p['sections'] ) ) ? $p['sections'] : null;
		if ( empty( $slug ) ) {
			continue;
		}
		$existing = get_page_by_path( $slug, OBJECT, 'page' );
		$args = array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_name'    => $slug,
			'post_title'   => $title !== '' ? $title : $slug,
			'post_content' => wp_kses_post( $content ),
		);
		if ( $existing ) {
			$args['ID'] = $existing->ID;
			$pid = wp_update_post( $args, true );
			$action = 'updated';
		} else {
			$pid = wp_insert_post( $args, true );
			$action = 'created';
		}
		if ( is_wp_error( $pid ) ) {
			$created[] = array( 'slug' => $slug, 'error' => $pid->get_error_message() );
			continue;
		}
		$slug_to_id[ $slug ] = (int) $pid;

		$entry = array( 'slug' => $slug, 'id' => (int) $pid, 'action' => $action );

		// Sections -> post_meta for the per-site CC Fields plugin to render.
		if ( $sections !== null && $sections_meta_key !== '' ) {
			// sanitize: keep only scalar/array values, cap depth implicitly.
			$clean_sections = voa_sanitize_sections( $sections );
			update_post_meta( (int) $pid, $sections_meta_key, $clean_sections );
			$entry['sections_saved'] = count( $clean_sections );
		}

		$created[] = $entry;
	}

	// Static front page.
	$home_set = false;
	if ( ! empty( $home_slug ) && isset( $slug_to_id[ $home_slug ] ) ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $slug_to_id[ $home_slug ] );
		$home_set = true;
	}

	// Primary menu.
	$menu_result = array();
	if ( ! empty( $slug_to_id ) ) {
		$menu = wp_get_nav_menu_object( $menu_name );
		if ( ! $menu ) {
			$menu_id = wp_create_nav_menu( $menu_name );
			if ( ! is_wp_error( $menu_id ) ) {
				$menu = wp_get_nav_menu_object( $menu_id );
			}
		}
		if ( $menu && ! is_wp_error( $menu ) ) {
			$menu_id = (int) $menu->term_id;

			// Clear existing items so we rebuild cleanly.
			$existing_items = wp_get_nav_menu_items( $menu_id );
			if ( is_array( $existing_items ) ) {
				foreach ( $existing_items as $item ) {
					wp_delete_post( $item->ID, true );
				}
			}

			$position = 1;
			foreach ( $pages_in as $p ) {
				$slug  = sanitize_title( (string) ( $p['slug'] ?? '' ) );
				if ( empty( $slug ) || ! isset( $slug_to_id[ $slug ] ) ) {
					continue;
				}
				if ( ! $include_home_in_menu && $slug === $home_slug ) {
					continue;
				}
				$pid = $slug_to_id[ $slug ];
				$title = sanitize_text_field( (string) ( $p['title'] ?? $slug ) );
				wp_update_nav_menu_item( $menu_id, 0, array(
					'menu-item-title'     => $title,
					'menu-item-object'    => 'page',
					'menu-item-object-id' => $pid,
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
					'menu-item-position'  => $position++,
				) );
			}

			// Assign menu to theme location.
			$locations = get_theme_mod( 'nav_menu_locations' );
			if ( ! is_array( $locations ) ) {
				$locations = array();
			}
			$locations[ $menu_location ] = $menu_id;
			set_theme_mod( 'nav_menu_locations', $locations );

			$menu_result = array( 'menu_id' => $menu_id, 'name' => $menu->name, 'location' => $menu_location );
		}
	}

	return rest_ensure_response( array(
		'success'     => true,
		'pages'       => $created,
		'home_set'    => $home_set,
		'home_slug'   => $home_slug,
		'menu'        => $menu_result,
	) );
}

/**
 * Endpoint: POST /voa/v1/media-sideload
 *
 * Body:
 *   {
 *     "url":      "https://cc-api.example.com/api/sites/xyz/assets/abc.jpeg",
 *     "filename": "hero-bg.jpeg",   // optional, inferred from URL path if omitted
 *     "alt_text": "Fleet vehicle"   // optional
 *   }
 *
 * Fetches the remote URL with wp_remote_get, writes it to the uploads dir,
 * then registers it as a WP attachment. Returns { success, wp_url,
 * attachment_id, mime_type }. Lets the recreate pipeline migrate images off
 * the Cloudflare worker origin into the WP Media Library so the site
 * doesn't depend on the worker staying online forever.
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response|WP_Error
 */
function voa_endpoint_media_sideload( WP_REST_Request $request ) {
	$auth = voa_validate_request();
	if ( is_wp_error( $auth ) ) {
		return $auth;
	}

	$rate_limit = voa_check_rate_limit();
	if ( is_wp_error( $rate_limit ) ) {
		return $rate_limit;
	}

	$url = esc_url_raw( (string) $request->get_param( 'url' ) );
	if ( empty( $url ) || ! preg_match( '#^https?://#i', $url ) ) {
		return new WP_Error( 'voa_bad_url', 'A valid http(s) url is required.', array( 'status' => 400 ) );
	}

	$requested_filename = sanitize_file_name( (string) $request->get_param( 'filename' ) );
	$alt_text           = sanitize_text_field( (string) $request->get_param( 'alt_text' ) );

	// 1. Fetch the remote URL. 25s timeout so a slow origin doesn't hang
	// the whole request indefinitely.
	$response = wp_remote_get( $url, array(
		'timeout'    => 25,
		'user-agent' => 'voa-media-sideload/1.0',
	) );
	if ( is_wp_error( $response ) ) {
		return new WP_Error( 'voa_fetch_failed', 'Could not fetch remote url: ' . $response->get_error_message(), array( 'status' => 502 ) );
	}
	$status = wp_remote_retrieve_response_code( $response );
	if ( $status < 200 || $status >= 300 ) {
		return new WP_Error( 'voa_fetch_status', 'Remote url returned HTTP ' . (int) $status, array( 'status' => 502 ) );
	}
	$body = wp_remote_retrieve_body( $response );
	if ( empty( $body ) ) {
		return new WP_Error( 'voa_empty_body', 'Remote url returned empty body.', array( 'status' => 502 ) );
	}

	// 2. Work out filename + extension. Prefer the caller-supplied filename,
	// then the URL path's basename, then a hash fallback. Enforce an
	// allowlist of common image/doc extensions to avoid arbitrary types.
	$allowed_exts = array( 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico', 'avif', 'mp4', 'webm', 'pdf' );

	$filename = $requested_filename;
	if ( empty( $filename ) ) {
		$path = wp_parse_url( $url, PHP_URL_PATH );
		if ( is_string( $path ) ) {
			$filename = sanitize_file_name( basename( $path ) );
		}
	}
	if ( empty( $filename ) ) {
		$filename = 'sideload-' . substr( md5( $url ), 0, 10 );
	}

	$ext = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
	if ( empty( $ext ) || ! in_array( $ext, $allowed_exts, true ) ) {
		// Try to infer from Content-Type header.
		$ctype = wp_remote_retrieve_header( $response, 'content-type' );
		if ( is_string( $ctype ) ) {
			$ctype = strtolower( trim( explode( ';', $ctype )[0] ) );
			$ctype_map = array(
				'image/jpeg'      => 'jpg',
				'image/pjpeg'     => 'jpg',
				'image/png'       => 'png',
				'image/gif'       => 'gif',
				'image/webp'      => 'webp',
				'image/svg+xml'   => 'svg',
				'image/avif'      => 'avif',
				'image/x-icon'    => 'ico',
				'image/vnd.microsoft.icon' => 'ico',
				'video/mp4'       => 'mp4',
				'video/webm'      => 'webm',
				'application/pdf' => 'pdf',
			);
			if ( isset( $ctype_map[ $ctype ] ) ) {
				$ext = $ctype_map[ $ctype ];
				// Strip any current extension then append the sniffed one.
				$filename = preg_replace( '/\.[A-Za-z0-9]{1,5}$/', '', $filename );
				$filename .= '.' . $ext;
			}
		}
	}
	if ( empty( $ext ) || ! in_array( $ext, $allowed_exts, true ) ) {
		return new WP_Error( 'voa_bad_ext', 'Remote url does not resolve to an allowed media type.', array( 'status' => 400 ) );
	}

	// 3. Short-circuit: if we've already sideloaded this exact source URL,
	// return the existing attachment instead of creating a duplicate. Key
	// stored as post_meta on the attachment.
	$existing = get_posts( array(
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'posts_per_page' => 1,
		'meta_key'       => '_voa_source_url',
		'meta_value'     => $url,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	) );
	if ( ! empty( $existing ) ) {
		$existing_id  = (int) $existing[0];
		$existing_url = wp_get_attachment_url( $existing_id );
		return rest_ensure_response( array(
			'success'       => true,
			'wp_url'        => $existing_url,
			'attachment_id' => $existing_id,
			'mime_type'     => get_post_mime_type( $existing_id ),
			'reused'        => true,
		) );
	}

	// 4. Write to uploads dir.
	$upload_dir = wp_upload_dir();
	if ( ! empty( $upload_dir['error'] ) ) {
		return new WP_Error( 'voa_upload_dir', (string) $upload_dir['error'], array( 'status' => 500 ) );
	}
	if ( ! file_exists( $upload_dir['path'] ) ) {
		wp_mkdir_p( $upload_dir['path'] );
	}
	$filename = wp_unique_filename( $upload_dir['path'], $filename );
	$target   = trailingslashit( $upload_dir['path'] ) . $filename;
	$bytes    = file_put_contents( $target, $body );
	if ( false === $bytes ) {
		return new WP_Error( 'voa_write_failed', 'Failed to write media file.', array( 'status' => 500 ) );
	}

	// 5. Register as WP attachment + generate metadata (sizes etc.).
	$filetype = wp_check_filetype( $filename );
	if ( empty( $filetype['type'] ) ) {
		// wp_check_filetype can fail for a few formats (ico, avif on old WP) -
		// fall back to a sensible mime from ext.
		$ext_to_mime = array(
			'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
			'png' => 'image/png', 'gif' => 'image/gif',
			'webp' => 'image/webp', 'svg' => 'image/svg+xml',
			'avif' => 'image/avif', 'ico' => 'image/x-icon',
			'mp4' => 'video/mp4', 'webm' => 'video/webm',
			'pdf' => 'application/pdf',
		);
		$filetype = array(
			'ext'  => $ext,
			'type' => isset( $ext_to_mime[ $ext ] ) ? $ext_to_mime[ $ext ] : 'application/octet-stream',
		);
	}

	$attach_args = array(
		'post_mime_type' => $filetype['type'],
		'post_title'     => sanitize_text_field( pathinfo( $filename, PATHINFO_FILENAME ) ),
		'post_content'   => '',
		'post_status'    => 'inherit',
	);
	$attachment_id = wp_insert_attachment( $attach_args, $target );
	if ( is_wp_error( $attachment_id ) ) {
		@unlink( $target );
		return $attachment_id;
	}

	if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
	}
	$metadata = wp_generate_attachment_metadata( $attachment_id, $target );
	if ( ! is_wp_error( $metadata ) ) {
		wp_update_attachment_metadata( $attachment_id, $metadata );
	}

	update_post_meta( $attachment_id, '_voa_source_url', $url );
	if ( ! empty( $alt_text ) ) {
		update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt_text );
	}

	return rest_ensure_response( array(
		'success'       => true,
		'wp_url'        => wp_get_attachment_url( $attachment_id ),
		'attachment_id' => (int) $attachment_id,
		'mime_type'     => $filetype['type'],
		'reused'        => false,
	) );
}

/**
 * Endpoint: POST /voa/v1/plugin-options
 *
 * Body:
 *   {
 *     "option_key":   "ccf_global_vars",      // must end in f_global_vars
 *     "option_value": { "company_name": ... } // associative array
 *   }
 *
 * Scoped setter for the recreate pipeline. Only accepts option keys that
 * end in "f_global_vars" (covers ccf_global_vars and any per-site variant
 * like {prefix}f_global_vars). Refuses everything else so an attacker with
 * a leaked VOA key still can't stomp on arbitrary options. Values are
 * coerced to a flat associative array of strings before update_option.
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response|WP_Error
 */
function voa_endpoint_plugin_options( WP_REST_Request $request ) {
	$auth = voa_validate_request();
	if ( is_wp_error( $auth ) ) {
		return $auth;
	}

	$rate_limit = voa_check_rate_limit();
	if ( is_wp_error( $rate_limit ) ) {
		return $rate_limit;
	}

	$option_key = sanitize_key( (string) $request->get_param( 'option_key' ) );
	if ( empty( $option_key ) || ! preg_match( '/^[a-z][a-z0-9_]{1,60}f_global_vars$/', $option_key ) ) {
		return new WP_Error( 'voa_bad_option_key', 'option_key must match <prefix>f_global_vars.', array( 'status' => 400 ) );
	}

	$raw_value = $request->get_param( 'option_value' );
	if ( ! is_array( $raw_value ) ) {
		return new WP_Error( 'voa_bad_option_value', 'option_value must be an associative array.', array( 'status' => 400 ) );
	}

	// Coerce every value to a trimmed string. Enforce an allowlist of keys
	// so a caller can't smuggle executable HTML into an option storing PHP
	// settings. These match the ccf_global_vars activation shape plus
	// logo_url (our recreate-pipeline addition).
	$allowed_keys = array(
		'company_name', 'company_phone', 'company_email', 'company_address',
		'company_abn',
		'facebook_url', 'instagram_url', 'youtube_url', 'linkedin_url',
		'logo_url',
	);

	$existing = get_option( $option_key, array() );
	if ( ! is_array( $existing ) ) {
		$existing = array();
	}

	foreach ( $raw_value as $k => $v ) {
		if ( ! in_array( $k, $allowed_keys, true ) ) {
			continue;
		}
		if ( is_array( $v ) || is_object( $v ) ) {
			continue;
		}
		$s = (string) $v;
		// URL-valued keys get stricter sanitizing.
		if ( $k === 'logo_url' || substr( $k, -4 ) === '_url' ) {
			$s = esc_url_raw( trim( $s ) );
		} elseif ( $k === 'company_email' ) {
			$s = sanitize_email( trim( $s ) );
		} else {
			$s = sanitize_text_field( trim( $s ) );
		}
		$existing[ $k ] = $s;
	}

	$updated = update_option( $option_key, $existing, false );

	return rest_ensure_response( array(
		'success'    => true,
		'option_key' => $option_key,
		'updated'    => (bool) $updated,
		'value'      => $existing,
	) );
}

/**
 * Endpoint: POST /voa/v1/elementor-update
 *
 * Patches widgets inside Elementor's _elementor_data structure so changes
 * actually reflect on Elementor-built pages. Without this, edits via
 * post_content are silently ignored by Elementor's renderer.
 *
 * Body shape:
 * {
 *   pageId: 42,
 *   edits: [
 *     { match: { widgetType: "heading", contains: "Transform" }, set: { settings: { title: "New title" } } },
 *     { match: { widgetType: "heading", headerSize: "h1" }, append: { field: "title", value: " today" } },
 *     { match: { widgetType: "button", contains: "Get Started" }, set: { settings: { text: "Start Now" } } },
 *     { match: { widgetType: "text-editor", contains: "Streamline" }, replace: { field: "editor", from: "Streamline operations", to: "Boost productivity" } }
 *   ]
 * }
 *
 * Match criteria (any combination):
 *   widgetType  - exact match on widget_type (heading, text-editor, button, image, etc.)
 *   headerSize  - for heading widgets, the heading level (h1..h6)
 *   contains    - text content match against any settings string field
 *   id          - exact match on the Elementor element id
 *   index       - the Nth occurrence (0-based) of an otherwise-matching widget
 *
 * Edit ops (one per edit):
 *   set     - { settings: {...} } merge into widget settings
 *   append  - { field, value } concat string to settings[field]
 *   prepend - { field, value } prepend string to settings[field]
 *   replace - { field, from, to } literal string replace inside settings[field]
 */
function voa_endpoint_elementor_update( $req ) {
	$body    = $req->get_json_params();
	$page_id = isset( $body['pageId'] ) ? intval( $body['pageId'] ) : 0;
	$edits   = isset( $body['edits'] ) && is_array( $body['edits'] ) ? $body['edits'] : array();

	if ( ! $page_id ) {
		return new WP_Error( 'voa_bad_request', 'pageId is required', array( 'status' => 400 ) );
	}
	if ( empty( $edits ) ) {
		return new WP_Error( 'voa_bad_request', 'edits array is required', array( 'status' => 400 ) );
	}

	$post = get_post( $page_id );
	if ( ! $post ) {
		return new WP_Error( 'voa_not_found', 'Page not found', array( 'status' => 404 ) );
	}

	$raw = get_post_meta( $page_id, '_elementor_data', true );
	if ( empty( $raw ) ) {
		return new WP_Error( 'voa_not_elementor', 'Page is not built with Elementor (no _elementor_data)', array( 'status' => 422 ) );
	}

	// Elementor stores _elementor_data as JSON in postmeta. WordPress's
	// get_post_meta returns the raw stored string (no slashing) so json_decode
	// usually works directly. On some hosts (or after legacy migrations) the
	// stored value can carry an extra slash layer, so we try clean first and
	// fall back to wp_unslash if that fails. Mirrors how Elementor itself
	// reads its own data.
	$data = json_decode( $raw, true );
	if ( ! is_array( $data ) ) {
		// Fallback: maybe the value was double-slashed at some point
		$data = json_decode( wp_unslash( $raw ), true );
	}
	if ( ! is_array( $data ) ) {
		// Last-ditch: try stripping single backslashes that some setups inject
		$cleaned = stripslashes( $raw );
		$data    = json_decode( $cleaned, true );
	}
	if ( ! is_array( $data ) ) {
		return new WP_Error(
			'voa_parse_error',
			'Could not parse _elementor_data (tried raw, unslashed, and stripslashed). First 200 chars: ' . substr( $raw, 0, 200 ),
			array( 'status' => 500 )
		);
	}

	// Track per-edit match counts so we can honour "index" and report what
	// happened. Cloned to avoid feeding the walker stale state across edits.
	$edit_match_counts = array_fill( 0, count( $edits ), 0 );
	$applied_log       = array();

	voa_walk_elementor_tree( $data, $edits, $edit_match_counts, $applied_log );

	// Re-encode and save. Elementor expects unescaped slashes/unicode.
	$new_json = wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	if ( false === $new_json ) {
		return new WP_Error( 'voa_encode_error', 'Could not re-encode _elementor_data', array( 'status' => 500 ) );
	}
	// update_post_meta auto-slashes - feed wp_slash to keep round-trip clean.
	update_post_meta( $page_id, '_elementor_data', wp_slash( $new_json ) );

	// Bump data version + wipe page CSS cache so Elementor regenerates on next render.
	update_post_meta( $page_id, '_elementor_data_time', time() );
	delete_post_meta( $page_id, '_elementor_css' );

	// If Elementor's runtime is loaded, ask it to regenerate CSS for this post.
	if ( class_exists( '\\Elementor\\Plugin' ) ) {
		try {
			$el = \Elementor\Plugin::$instance;
			if ( isset( $el->files_manager ) ) {
				$el->files_manager->clear_cache();
			}
		} catch ( \Throwable $e ) { /* non-fatal */ }
	}

	// Bump post modified time so caches downstream invalidate.
	wp_update_post( array(
		'ID'                => $page_id,
		'post_modified'     => current_time( 'mysql' ),
		'post_modified_gmt' => current_time( 'mysql', 1 ),
	) );

	$total_applied = array_sum( $edit_match_counts );

	return rest_ensure_response( array(
		'success'      => true,
		'pageId'       => $page_id,
		'editsCount'   => count( $edits ),
		'matchesPerEdit' => $edit_match_counts,
		'totalApplied' => $total_applied,
		'log'          => array_slice( $applied_log, 0, 30 ),
	) );
}

/**
 * Recursively walk Elementor's element tree applying edits to matching widgets.
 * Reference passed by &-prefix so mutations land in the original tree.
 */
function voa_walk_elementor_tree( &$nodes, $edits, &$edit_match_counts, &$log ) {
	if ( ! is_array( $nodes ) ) {
		return;
	}
	foreach ( $nodes as &$node ) {
		if ( ! is_array( $node ) ) {
			continue;
		}

		// A widget is a leaf with elType=widget. Sections/columns/containers
		// don't match our edits (we only change widget settings) but we still
		// recurse into them.
		$is_widget = isset( $node['elType'] ) && $node['elType'] === 'widget';

		if ( $is_widget ) {
			foreach ( $edits as $idx => $edit ) {
				if ( voa_elementor_matches( $node, isset( $edit['match'] ) ? $edit['match'] : array(), $edit_match_counts[ $idx ] ) ) {
					$applied = voa_elementor_apply( $node, $edit );
					if ( $applied ) {
						$edit_match_counts[ $idx ]++;
						$log[] = array(
							'editIndex' => $idx,
							'widgetType' => isset( $node['widgetType'] ) ? $node['widgetType'] : '?',
							'id'         => isset( $node['id'] ) ? $node['id'] : '?',
						);
					}
				}
			}
		}

		// Recurse into children
		if ( isset( $node['elements'] ) && is_array( $node['elements'] ) ) {
			voa_walk_elementor_tree( $node['elements'], $edits, $edit_match_counts, $log );
		}
	}
}

/**
 * Return true if a widget node matches the given criteria.
 * $current_match_index lets callers honour {match: {index: 1}} semantics.
 */
function voa_elementor_matches( $node, $match, $current_match_index ) {
	if ( empty( $match ) ) {
		return false; // no criteria = no match (avoid clobbering everything)
	}

	if ( isset( $match['widgetType'] ) && ( ! isset( $node['widgetType'] ) || $node['widgetType'] !== $match['widgetType'] ) ) {
		return false;
	}
	if ( isset( $match['id'] ) && ( ! isset( $node['id'] ) || $node['id'] !== $match['id'] ) ) {
		return false;
	}
	if ( isset( $match['headerSize'] ) ) {
		$hs = isset( $node['settings']['header_size'] ) ? $node['settings']['header_size'] : 'h2';
		if ( strtolower( $hs ) !== strtolower( $match['headerSize'] ) ) {
			return false;
		}
	}
	if ( isset( $match['contains'] ) ) {
		$needle = strtolower( $match['contains'] );
		$found  = false;
		if ( isset( $node['settings'] ) && is_array( $node['settings'] ) ) {
			foreach ( $node['settings'] as $val ) {
				if ( is_string( $val ) && strpos( strtolower( $val ), $needle ) !== false ) {
					$found = true;
					break;
				}
			}
		}
		if ( ! $found ) {
			return false;
		}
	}
	if ( isset( $match['index'] ) ) {
		// index is the Nth match; we use the running counter from the walker
		if ( intval( $match['index'] ) !== $current_match_index ) {
			return false;
		}
	}
	return true;
}

/**
 * Apply one edit op to a matched widget. Mutates $node by reference.
 * Returns true if an op actually fired.
 *
 * For HTML widgets (widgetType="html"), settings live in the `html` field as
 * raw HTML. We auto-route text ops there. We also auto-detect and update raw
 * heading tags within HTML so "append today to H1" works even when the H1
 * lives inside an HTML widget rather than a heading widget.
 */
function voa_elementor_apply( &$node, $edit ) {
	if ( ! isset( $node['settings'] ) || ! is_array( $node['settings'] ) ) {
		$node['settings'] = array();
	}

	$widget_type = isset( $node['widgetType'] ) ? $node['widgetType'] : '';

	// Pick the canonical text field for this widget type so callers don't have
	// to know the exact key.
	$text_field_for_widget = function( $type ) {
		switch ( $type ) {
			case 'heading':
				return 'title';
			case 'text-editor':
				return 'editor';
			case 'button':
				return 'text';
			case 'html':
				return 'html';
			default:
				return null;
		}
	};
	$default_field = $text_field_for_widget( $widget_type );

	if ( isset( $edit['set'] ) && is_array( $edit['set'] ) ) {
		if ( isset( $edit['set']['settings'] ) && is_array( $edit['set']['settings'] ) ) {
			foreach ( $edit['set']['settings'] as $k => $v ) {
				$node['settings'][ $k ] = $v;
			}
		}
		return true;
	}

	// For text ops, fall back to the widget's canonical text field if the
	// caller named the wrong field (e.g. "title" on an HTML widget).
	$resolve_field = function( $req_field ) use ( $node, $default_field ) {
		if ( $req_field && isset( $node['settings'][ $req_field ] ) ) {
			return $req_field;
		}
		return $default_field;
	};

	if ( isset( $edit['append'] ) && isset( $edit['append']['field'] ) ) {
		$f = $resolve_field( $edit['append']['field'] );
		if ( ! $f ) return false;
		$v = isset( $edit['append']['value'] ) ? $edit['append']['value'] : '';
		$current = isset( $node['settings'][ $f ] ) ? $node['settings'][ $f ] : '';
		if ( $widget_type === 'html' && $f === 'html' ) {
			// Append to the LAST heading tag inside the HTML, not the whole blob.
			// If no heading found, append to the last text node before </body> /
			// last text inside the markup.
			$node['settings'][ $f ] = voa_html_append_inside_heading( $current, $v );
			return true;
		}
		$node['settings'][ $f ] = is_string( $current ) ? $current . $v : $v;
		return true;
	}

	if ( isset( $edit['prepend'] ) && isset( $edit['prepend']['field'] ) ) {
		$f = $resolve_field( $edit['prepend']['field'] );
		if ( ! $f ) return false;
		$v = isset( $edit['prepend']['value'] ) ? $edit['prepend']['value'] : '';
		$current = isset( $node['settings'][ $f ] ) ? $node['settings'][ $f ] : '';
		$node['settings'][ $f ] = is_string( $current ) ? $v . $current : $v;
		return true;
	}

	if ( isset( $edit['replace'] ) && isset( $edit['replace']['field'] ) ) {
		$f    = $resolve_field( $edit['replace']['field'] );
		if ( ! $f ) return false;
		$from = isset( $edit['replace']['from'] ) ? $edit['replace']['from'] : '';
		$to   = isset( $edit['replace']['to'] ) ? $edit['replace']['to'] : '';
		$current = isset( $node['settings'][ $f ] ) ? $node['settings'][ $f ] : '';
		if ( is_string( $current ) && $from !== '' ) {
			$node['settings'][ $f ] = str_replace( $from, $to, $current );
			return true;
		}
	}

	return false;
}

/**
 * Endpoint: GET /voa/v1/elementor-read?pageId=X
 *
 * Returns a flat list of widgets on a page so the AI can plan precise
 * multi-element edits. Without this the AI is half-blind: it has to guess
 * widget types and counts when asked to "update all icons" or "change every
 * heading on the page".
 *
 * Response shape:
 * {
 *   pageId: 42,
 *   pageTitle: "Home",
 *   widgetCount: 18,
 *   widgets: [
 *     { id: "abc123", widgetType: "heading", headerSize: "h1", textPreview: "Welcome",
 *       settingsKeys: ["title","header_size","align","title_color"] },
 *     { id: "def456", widgetType: "icon-box", textPreview: "Fast service",
 *       settingsKeys: ["icon","title_text","description_text","primary_color"],
 *       iconLibrary: "fa-solid", iconValue: "fa-bolt" },
 *     ...
 *   ],
 *   widgetCounts: { heading: 4, "icon-box": 3, button: 2, ... }
 * }
 */
function voa_endpoint_elementor_read( $req ) {
	$page_id = isset( $req['pageId'] ) ? intval( $req['pageId'] ) : 0;
	if ( ! $page_id ) {
		return new WP_Error( 'voa_bad_request', 'pageId is required', array( 'status' => 400 ) );
	}

	$post = get_post( $page_id );
	if ( ! $post ) {
		return new WP_Error( 'voa_not_found', 'Page not found', array( 'status' => 404 ) );
	}

	$raw = get_post_meta( $page_id, '_elementor_data', true );
	if ( empty( $raw ) ) {
		return new WP_Error( 'voa_not_elementor', 'Page is not built with Elementor', array( 'status' => 422 ) );
	}

	$data = json_decode( $raw, true );
	if ( ! is_array( $data ) ) {
		$data = json_decode( wp_unslash( $raw ), true );
	}
	if ( ! is_array( $data ) ) {
		$data = json_decode( stripslashes( $raw ), true );
	}
	if ( ! is_array( $data ) ) {
		return new WP_Error( 'voa_parse_error', 'Could not parse _elementor_data', array( 'status' => 500 ) );
	}

	$widgets = array();
	voa_collect_elementor_widgets( $data, $widgets );

	$counts = array();
	foreach ( $widgets as $w ) {
		$t = isset( $w['widgetType'] ) ? $w['widgetType'] : 'unknown';
		if ( ! isset( $counts[ $t ] ) ) {
			$counts[ $t ] = 0;
		}
		$counts[ $t ]++;
	}

	return rest_ensure_response( array(
		'pageId'       => $page_id,
		'pageTitle'    => $post->post_title,
		'widgetCount'  => count( $widgets ),
		'widgetCounts' => $counts,
		'widgets'      => $widgets,
	) );
}

/**
 * Recursively collect a flat description of every widget in an Elementor tree.
 * We only return enough to plan edits: id, type, key text preview, settings
 * keys (so AI knows which fields exist), and a couple of common style hints
 * for icon widgets where colour edits are common.
 */
function voa_collect_elementor_widgets( $nodes, &$out ) {
	if ( ! is_array( $nodes ) ) {
		return;
	}
	foreach ( $nodes as $node ) {
		if ( ! is_array( $node ) ) {
			continue;
		}
		$is_widget = isset( $node['elType'] ) && $node['elType'] === 'widget';
		if ( $is_widget ) {
			$widget_type = isset( $node['widgetType'] ) ? $node['widgetType'] : 'unknown';
			$settings    = isset( $node['settings'] ) && is_array( $node['settings'] ) ? $node['settings'] : array();

			// Pick a preview text field by widget type
			$preview = '';
			if ( $widget_type === 'heading' && isset( $settings['title'] ) ) {
				$preview = (string) $settings['title'];
			} elseif ( $widget_type === 'text-editor' && isset( $settings['editor'] ) ) {
				$preview = (string) $settings['editor'];
			} elseif ( $widget_type === 'button' && isset( $settings['text'] ) ) {
				$preview = (string) $settings['text'];
			} elseif ( $widget_type === 'html' && isset( $settings['html'] ) ) {
				$preview = (string) $settings['html'];
			} elseif ( ( $widget_type === 'icon-box' || $widget_type === 'icon-list' ) && isset( $settings['title_text'] ) ) {
				$preview = (string) $settings['title_text'];
			} elseif ( isset( $settings['title'] ) ) {
				$preview = (string) $settings['title'];
			} elseif ( isset( $settings['text'] ) ) {
				$preview = (string) $settings['text'];
			}
			$preview = trim( wp_strip_all_tags( $preview ) );
			if ( strlen( $preview ) > 120 ) {
				$preview = substr( $preview, 0, 117 ) . '...';
			}

			$entry = array(
				'id'           => isset( $node['id'] ) ? $node['id'] : '',
				'widgetType'   => $widget_type,
				'textPreview'  => $preview,
				'settingsKeys' => array_keys( $settings ),
			);

			// Heading size is a common targeting hint
			if ( $widget_type === 'heading' && isset( $settings['header_size'] ) ) {
				$entry['headerSize'] = $settings['header_size'];
			}

			// For icon and icon-box widgets, surface the icon library + value
			// + colour so the AI can plan colour swaps.
			if ( in_array( $widget_type, array( 'icon', 'icon-box', 'icon-list' ), true ) ) {
				if ( isset( $settings['selected_icon']['library'] ) ) {
					$entry['iconLibrary'] = $settings['selected_icon']['library'];
				}
				if ( isset( $settings['selected_icon']['value'] ) ) {
					$entry['iconValue'] = $settings['selected_icon']['value'];
				}
				if ( isset( $settings['icon'] ) && is_string( $settings['icon'] ) ) {
					$entry['iconClass'] = $settings['icon'];
				}
				if ( isset( $settings['primary_color'] ) ) {
					$entry['primaryColor'] = $settings['primary_color'];
				}
				if ( isset( $settings['icon_color'] ) ) {
					$entry['iconColor'] = $settings['icon_color'];
				}
			}

			// For images, surface the URL so AI can swap or describe them
			if ( $widget_type === 'image' && isset( $settings['image']['url'] ) ) {
				$entry['imageUrl'] = $settings['image']['url'];
			}

			$out[] = $entry;
		}

		if ( isset( $node['elements'] ) && is_array( $node['elements'] ) ) {
			voa_collect_elementor_widgets( $node['elements'], $out );
		}
	}
}

/**
 * Endpoint: GET /voa/v1/elementor-widget?pageId=X&widgetId=abc123
 *
 * Returns the full settings for ONE widget so the AI can inspect exactly
 * what's there before planning an edit. Crucial for visual changes like
 * icon color: the AI can see whether the icon uses `selected_icon`
 * (FontAwesome inline SVG, color editable via primary_color setting),
 * an icon library setting, an image URL, or a custom HTML widget - and
 * pick the right edit strategy instead of guessing CSS.
 */
function voa_endpoint_elementor_widget( $req ) {
	$page_id   = isset( $req['pageId'] ) ? intval( $req['pageId'] ) : 0;
	$widget_id = isset( $req['widgetId'] ) ? sanitize_text_field( $req['widgetId'] ) : '';
	if ( ! $page_id || ! $widget_id ) {
		return new WP_Error( 'voa_bad_request', 'pageId and widgetId are required', array( 'status' => 400 ) );
	}

	$raw = get_post_meta( $page_id, '_elementor_data', true );
	if ( empty( $raw ) ) {
		return new WP_Error( 'voa_not_elementor', 'Page is not built with Elementor', array( 'status' => 422 ) );
	}

	$data = json_decode( $raw, true );
	if ( ! is_array( $data ) ) $data = json_decode( wp_unslash( $raw ), true );
	if ( ! is_array( $data ) ) $data = json_decode( stripslashes( $raw ), true );
	if ( ! is_array( $data ) ) {
		return new WP_Error( 'voa_parse_error', 'Could not parse _elementor_data', array( 'status' => 500 ) );
	}

	$found = null;
	voa_find_elementor_widget( $data, $widget_id, $found );
	if ( ! $found ) {
		return new WP_Error( 'voa_not_found', 'Widget not found on this page', array( 'status' => 404 ) );
	}

	return rest_ensure_response( array(
		'pageId'     => $page_id,
		'widgetId'   => $widget_id,
		'widgetType' => isset( $found['widgetType'] ) ? $found['widgetType'] : null,
		'settings'   => isset( $found['settings'] ) ? $found['settings'] : array(),
	) );
}

function voa_find_elementor_widget( $nodes, $target_id, &$found ) {
	if ( $found ) return;
	if ( ! is_array( $nodes ) ) return;
	foreach ( $nodes as $node ) {
		if ( ! is_array( $node ) ) continue;
		if ( isset( $node['elType'] ) && $node['elType'] === 'widget' && isset( $node['id'] ) && $node['id'] === $target_id ) {
			$found = $node;
			return;
		}
		if ( isset( $node['elements'] ) && is_array( $node['elements'] ) ) {
			voa_find_elementor_widget( $node['elements'], $target_id, $found );
			if ( $found ) return;
		}
	}
}

/**
 * GET /voa/v1/kit - Returns Elementor's "kit" (global colors, fonts, defaults)
 * so the AI knows the brand palette and can suggest matching colours.
 */
function voa_endpoint_kit( $req ) {
	$kit_id = (int) get_option( 'elementor_active_kit', 0 );
	if ( ! $kit_id ) return rest_ensure_response( array( 'kitId' => 0, 'available' => false ) );
	$raw = get_post_meta( $kit_id, '_elementor_page_settings', true );
	$data = is_array( $raw ) ? $raw : array();
	$out = array(
		'kitId'         => $kit_id,
		'available'     => true,
		'systemColors'  => array(),
		'customColors'  => array(),
		'systemFonts'   => array(),
		'customFonts'   => array(),
	);
	foreach ( array( 'system_colors' => 'systemColors', 'custom_colors' => 'customColors' ) as $src => $dst ) {
		if ( isset( $data[ $src ] ) && is_array( $data[ $src ] ) ) {
			foreach ( $data[ $src ] as $c ) {
				$out[ $dst ][] = array(
					'id'    => $c['_id']   ?? null,
					'title' => $c['title'] ?? '',
					'color' => $c['color'] ?? '',
				);
			}
		}
	}
	foreach ( array( 'system_typography' => 'systemFonts', 'custom_typography' => 'customFonts' ) as $src => $dst ) {
		if ( isset( $data[ $src ] ) && is_array( $data[ $src ] ) ) {
			foreach ( $data[ $src ] as $f ) {
				$out[ $dst ][] = array(
					'id'         => $f['_id']                   ?? null,
					'title'      => $f['title']                 ?? '',
					'fontFamily' => $f['typography_font_family'] ?? '',
					'fontWeight' => $f['typography_font_weight'] ?? '',
				);
			}
		}
	}
	return rest_ensure_response( $out );
}

/**
 * POST /voa/v1/kit-update
 *
 * Body: {
 *   systemColors?:  [{id, color, title?}, ...],
 *   customColors?:  [{id, color, title?}, ...],
 *   systemFonts?:   [{id, fontFamily, fontWeight?, title?}, ...],
 *   customFonts?:   [{id, fontFamily, fontWeight?, title?}, ...],
 * }
 *
 * Patches matching entries in the kit post's _elementor_page_settings by
 * `_id`. Entries you don't include are left alone. To create a new color
 * with id="custom-1" simply include it - if the id isn't found, the
 * entry is appended.
 *
 * Triggers Elementor's per-post CSS regen for every Elementor-built page
 * so global color refs propagate immediately. Added in 1.10.0.
 */
function voa_endpoint_kit_update( $req ) {
	$kit_id = (int) get_option( 'elementor_active_kit', 0 );
	if ( ! $kit_id ) {
		return new WP_Error( 'voa_no_kit', 'No active Elementor kit', array( 'status' => 422 ) );
	}
	$body = $req->get_json_params();
	$raw  = get_post_meta( $kit_id, '_elementor_page_settings', true );
	$data = is_array( $raw ) ? $raw : array();

	$applied = array();

	$apply_color_set = function( $key_in_data, $patches ) use ( &$data, &$applied ) {
		if ( ! is_array( $patches ) || empty( $patches ) ) return;
		if ( ! isset( $data[ $key_in_data ] ) || ! is_array( $data[ $key_in_data ] ) ) {
			$data[ $key_in_data ] = array();
		}
		foreach ( $patches as $patch ) {
			$id    = isset( $patch['id'] ) ? (string) $patch['id'] : '';
			$color = isset( $patch['color'] ) ? (string) $patch['color'] : '';
			$title = isset( $patch['title'] ) ? (string) $patch['title'] : '';
			if ( $id === '' || $color === '' ) continue;
			$found_idx = -1;
			foreach ( $data[ $key_in_data ] as $i => $existing ) {
				if ( isset( $existing['_id'] ) && $existing['_id'] === $id ) { $found_idx = $i; break; }
			}
			if ( $found_idx >= 0 ) {
				$data[ $key_in_data ][ $found_idx ]['color'] = $color;
				if ( $title !== '' ) $data[ $key_in_data ][ $found_idx ]['title'] = $title;
				$applied[] = array( 'group' => $key_in_data, 'id' => $id, 'action' => 'updated', 'color' => $color );
			} else {
				$data[ $key_in_data ][] = array( '_id' => $id, 'title' => $title ?: $id, 'color' => $color );
				$applied[] = array( 'group' => $key_in_data, 'id' => $id, 'action' => 'created', 'color' => $color );
			}
		}
	};

	$apply_font_set = function( $key_in_data, $patches ) use ( &$data, &$applied ) {
		if ( ! is_array( $patches ) || empty( $patches ) ) return;
		if ( ! isset( $data[ $key_in_data ] ) || ! is_array( $data[ $key_in_data ] ) ) {
			$data[ $key_in_data ] = array();
		}
		foreach ( $patches as $patch ) {
			$id     = isset( $patch['id'] ) ? (string) $patch['id'] : '';
			$family = isset( $patch['fontFamily'] ) ? (string) $patch['fontFamily'] : '';
			$weight = isset( $patch['fontWeight'] ) ? (string) $patch['fontWeight'] : '';
			$title  = isset( $patch['title'] ) ? (string) $patch['title'] : '';
			if ( $id === '' || $family === '' ) continue;
			$found_idx = -1;
			foreach ( $data[ $key_in_data ] as $i => $existing ) {
				if ( isset( $existing['_id'] ) && $existing['_id'] === $id ) { $found_idx = $i; break; }
			}
			if ( $found_idx >= 0 ) {
				$data[ $key_in_data ][ $found_idx ]['typography_font_family'] = $family;
				if ( $weight !== '' ) $data[ $key_in_data ][ $found_idx ]['typography_font_weight'] = $weight;
				if ( $title  !== '' ) $data[ $key_in_data ][ $found_idx ]['title'] = $title;
				$applied[] = array( 'group' => $key_in_data, 'id' => $id, 'action' => 'updated', 'fontFamily' => $family );
			} else {
				$entry = array( '_id' => $id, 'title' => $title ?: $id, 'typography_font_family' => $family );
				if ( $weight !== '' ) $entry['typography_font_weight'] = $weight;
				$data[ $key_in_data ][] = $entry;
				$applied[] = array( 'group' => $key_in_data, 'id' => $id, 'action' => 'created', 'fontFamily' => $family );
			}
		}
	};

	$apply_color_set( 'system_colors',     $body['systemColors']  ?? null );
	$apply_color_set( 'custom_colors',     $body['customColors']  ?? null );
	$apply_font_set(  'system_typography', $body['systemFonts']   ?? null );
	$apply_font_set(  'custom_typography', $body['customFonts']   ?? null );

	if ( empty( $applied ) ) {
		return new WP_Error( 'voa_no_patches', 'No matching patches in body', array( 'status' => 400 ) );
	}

	update_post_meta( $kit_id, '_elementor_page_settings', $data );
	update_post_meta( $kit_id, '_elementor_data_time', time() );

	// Force regenerate per-post CSS for every Elementor-built page so the
	// new global tokens propagate immediately. Without this the kit data
	// is updated but cached page CSS keeps the old colors.
	$elementor_pages = get_posts( array(
		'post_type'      => array( 'page', 'post' ),
		'posts_per_page' => -1, 'fields' => 'ids',
		'meta_key'       => '_elementor_data',
	) );
	$cleared = 0;
	foreach ( $elementor_pages as $pid ) {
		delete_post_meta( $pid, '_elementor_css' );
		update_post_meta( $pid, '_elementor_data_time', time() );
		$cleared++;
	}
	if ( class_exists( '\\Elementor\\Plugin' ) ) {
		try {
			$el = \Elementor\Plugin::$instance;
			if ( isset( $el->files_manager ) ) $el->files_manager->clear_cache();
			// Also wipe the kit's own CSS file so the kit re-emits.
			delete_post_meta( $kit_id, '_elementor_css' );
		} catch ( \Throwable $e ) { /* non-fatal */ }
	}

	return rest_ensure_response( array(
		'success'      => true,
		'kitId'        => $kit_id,
		'applied'      => $applied,
		'cssCleared'   => $cleared,
	) );
}

/**
 * POST /voa/v1/elementor-clear-cache - Forces Elementor to regenerate CSS for
 * all pages. Without this, widget edits sometimes don't visually appear until
 * the cached per-post CSS file expires.
 */
function voa_endpoint_elementor_clear_cache( $req ) {
	$body = $req->get_json_params();
	$page_ids = isset( $body['pageIds'] ) && is_array( $body['pageIds'] ) ? array_map( 'intval', $body['pageIds'] ) : array();
	$cleared = 0;
	if ( $page_ids ) {
		foreach ( $page_ids as $pid ) {
			delete_post_meta( $pid, '_elementor_css' );
			update_post_meta( $pid, '_elementor_data_time', time() );
			$cleared++;
		}
	} else {
		// Clear ALL elementor pages
		$pages = get_posts( array( 'post_type' => array( 'page', 'post' ), 'posts_per_page' => -1, 'fields' => 'ids', 'meta_key' => '_elementor_data' ) );
		foreach ( $pages as $pid ) {
			delete_post_meta( $pid, '_elementor_css' );
			update_post_meta( $pid, '_elementor_data_time', time() );
			$cleared++;
		}
	}
	if ( class_exists( '\\Elementor\\Plugin' ) ) {
		try {
			$el = \Elementor\Plugin::$instance;
			if ( isset( $el->files_manager ) ) $el->files_manager->clear_cache();
		} catch ( \Throwable $e ) {}
	}
	return rest_ensure_response( array( 'success' => true, 'cleared' => $cleared ) );
}

/**
 * GET /voa/v1/elementor-raw?pageId=X
 *
 * Returns the raw on-disk state of a page so the worker can capture a
 * pre-edit snapshot for undo. Includes:
 *   - elementorData : raw _elementor_data postmeta string (JSON, possibly slashed)
 *   - postContent   : the WP post_content (used by non-Elementor pages and
 *                     by Elementor's update-page redesign path)
 *   - pageTemplate  : the active page template slug (e.g. elementor_full_width)
 *
 * The worker stores these as one snapshot row, and on restore replays via
 * /elementor-set-raw.
 */
function voa_endpoint_elementor_raw( $req ) {
	$page_id = isset( $req['pageId'] ) ? intval( $req['pageId'] ) : 0;
	if ( ! $page_id ) {
		return new WP_Error( 'voa_bad_request', 'pageId is required', array( 'status' => 400 ) );
	}

	$post = get_post( $page_id );
	if ( ! $post ) {
		return new WP_Error( 'voa_not_found', 'Page not found', array( 'status' => 404 ) );
	}

	$raw      = get_post_meta( $page_id, '_elementor_data', true );
	$template = get_post_meta( $page_id, '_wp_page_template', true );

	return rest_ensure_response( array(
		'pageId'        => $page_id,
		'pageTitle'     => get_the_title( $post ),
		'elementorData' => is_string( $raw ) ? $raw : '',
		'isElementor'   => ! empty( $raw ),
		'postContent'   => $post->post_content,
		'pageTemplate'  => $template,
		'capturedAt'    => time(),
	) );
}

/**
 * POST /voa/v1/elementor-set-raw
 *
 * Body: { pageId, elementorData?, postContent?, pageTemplate? }
 *
 * Replaces _elementor_data wholesale (when elementorData provided) and/or
 * post_content / template. Mirrors the cache-invalidation done by
 * voa_endpoint_elementor_update so a restored page renders fresh.
 *
 * This is the ONLY way to fully restore an Elementor page from a captured
 * snapshot - the existing /elementor-update endpoint is for surgical edits.
 */
function voa_endpoint_elementor_set_raw( $req ) {
	$body    = $req->get_json_params();
	$page_id = isset( $body['pageId'] ) ? intval( $body['pageId'] ) : 0;
	if ( ! $page_id ) {
		return new WP_Error( 'voa_bad_request', 'pageId is required', array( 'status' => 400 ) );
	}

	$post = get_post( $page_id );
	if ( ! $post ) {
		return new WP_Error( 'voa_not_found', 'Page not found', array( 'status' => 404 ) );
	}

	$updated = array();

	// Replace Elementor data if provided. Empty string is a valid value
	// (means "this page used to be Elementor and is now not"). null/missing
	// means "leave alone".
	if ( array_key_exists( 'elementorData', $body ) && is_string( $body['elementorData'] ) ) {
		$ed = $body['elementorData'];
		if ( $ed === '' ) {
			delete_post_meta( $page_id, '_elementor_data' );
		} else {
			// Validate JSON before writing - refuse to corrupt _elementor_data.
			$decoded = json_decode( $ed, true );
			if ( ! is_array( $decoded ) ) {
				$decoded = json_decode( wp_unslash( $ed ), true );
			}
			if ( ! is_array( $decoded ) ) {
				return new WP_Error( 'voa_bad_request', 'elementorData is not valid JSON', array( 'status' => 400 ) );
			}
			update_post_meta( $page_id, '_elementor_data', wp_slash( $ed ) );
		}
		update_post_meta( $page_id, '_elementor_data_time', time() );
		delete_post_meta( $page_id, '_elementor_css' );
		$updated[] = '_elementor_data';
	}

	if ( isset( $body['postContent'] ) && is_string( $body['postContent'] ) ) {
		wp_update_post( array(
			'ID'           => $page_id,
			'post_content' => $body['postContent'],
		) );
		$updated[] = 'post_content';
	}

	if ( isset( $body['pageTemplate'] ) && is_string( $body['pageTemplate'] ) ) {
		if ( $body['pageTemplate'] === '' ) {
			delete_post_meta( $page_id, '_wp_page_template' );
		} else {
			update_post_meta( $page_id, '_wp_page_template', $body['pageTemplate'] );
		}
		$updated[] = 'page_template';
	}

	// Ask Elementor's runtime to flush its CSS cache (matches the pattern
	// used by voa_endpoint_elementor_update).
	if ( class_exists( '\\Elementor\\Plugin' ) ) {
		try {
			$el = \Elementor\Plugin::$instance;
			if ( isset( $el->files_manager ) ) {
				$el->files_manager->clear_cache();
			}
		} catch ( \Throwable $e ) { /* non-fatal */ }
	}

	wp_update_post( array(
		'ID'                => $page_id,
		'post_modified'     => current_time( 'mysql' ),
		'post_modified_gmt' => current_time( 'mysql', 1 ),
	) );

	return rest_ensure_response( array(
		'success' => true,
		'pageId'  => $page_id,
		'updated' => $updated,
	) );
}

/**
 * GET /voa/v1/option?key=K
 *
 * Returns { key, value, exists }. value is always a string (or null when
 * not set). Whitelisted to options the AI agent legitimately reads/writes
 * - refuses to leak arbitrary WP options like auth tokens or salts.
 */
function voa_endpoint_option_get( $req ) {
	$key = isset( $req['key'] ) ? (string) $req['key'] : '';
	if ( ! voa_is_safe_option_key( $key ) ) {
		return new WP_Error( 'voa_forbidden_option', 'Option key not permitted', array( 'status' => 403 ) );
	}
	$exists  = false;
	$default = '__voa_missing_' . wp_generate_uuid4();
	$value   = get_option( $key, $default );
	if ( $value !== $default ) {
		$exists = true;
	} else {
		$value = '';
	}
	return rest_ensure_response( array(
		'key'    => $key,
		'value'  => is_string( $value ) ? $value : wp_json_encode( $value ),
		'exists' => $exists,
	) );
}

/**
 * POST /voa/v1/option
 *
 * Body: { key, value?, delete? }
 *
 * Sets an option to a string value, or deletes it when delete=true. Same
 * key whitelist as the GET handler.
 */
function voa_endpoint_option_set( $req ) {
	$body   = $req->get_json_params();
	$key    = isset( $body['key'] ) ? (string) $body['key'] : '';
	$delete = ! empty( $body['delete'] );
	$value  = isset( $body['value'] ) ? (string) $body['value'] : '';
	if ( ! voa_is_safe_option_key( $key ) ) {
		return new WP_Error( 'voa_forbidden_option', 'Option key not permitted', array( 'status' => 403 ) );
	}
	if ( $delete ) {
		delete_option( $key );
		return rest_ensure_response( array( 'success' => true, 'key' => $key, 'deleted' => true ) );
	}
	update_option( $key, $value );
	return rest_ensure_response( array( 'success' => true, 'key' => $key, 'bytes' => strlen( $value ) ) );
}

/**
 * Whitelist of options the connector is allowed to read/write through the
 * /option endpoint. Keep this tight - anything not in here MUST go through
 * /execute (which already has the heavier audit trail).
 */
function voa_is_safe_option_key( $key ) {
	if ( ! is_string( $key ) || $key === '' ) return false;
	$allowed = array(
		'voa_custom_css',
		// Add more as we surface typed setters for them.
	);
	return in_array( $key, $allowed, true );
}

/**
 * GET /voa/v1/menu-raw?menuId=X
 *
 * Returns one menu's full structure (id, name, items with their IDs,
 * titles, urls, parents, classes, types, object_ids) so the worker can
 * snapshot it before an update_menu call and replay on undo.
 */
function voa_endpoint_menu_raw( $req ) {
	$menu_id = isset( $req['menuId'] ) ? intval( $req['menuId'] ) : 0;
	if ( ! $menu_id ) {
		return new WP_Error( 'voa_bad_request', 'menuId is required', array( 'status' => 400 ) );
	}
	$term = wp_get_nav_menu_object( $menu_id );
	if ( ! $term || is_wp_error( $term ) ) {
		return new WP_Error( 'voa_not_found', 'Menu not found', array( 'status' => 404 ) );
	}
	$items = wp_get_nav_menu_items( $menu_id );
	$out   = array();
	if ( is_array( $items ) ) {
		foreach ( $items as $it ) {
			$out[] = array(
				'id'         => $it->ID,
				'title'      => $it->title,
				'url'        => $it->url,
				'parent'     => $it->menu_item_parent,
				'menu_order' => $it->menu_order,
				'type'       => $it->type,
				'object'     => $it->object,
				'object_id'  => $it->object_id,
				'classes'    => is_array( $it->classes ) ? array_values( array_filter( $it->classes ) ) : array(),
				'attr_title' => $it->attr_title,
				'target'     => $it->target,
				'description'=> $it->description,
				'xfn'        => $it->xfn,
			);
		}
	}
	return rest_ensure_response( array(
		'menuId' => $menu_id,
		'name'   => $term->name,
		'slug'   => $term->slug,
		'items'  => $out,
	) );
}

/**
 * POST /voa/v1/upload-image - Accepts {dataBase64, filename, mimeType}, writes
 * to the WP media library, returns {id, url, mediaSize}. Lets the AI swap
 * images / upload SVG icons without the user having to manage assets.
 */
function voa_endpoint_upload_image( $req ) {
	$body = $req->get_json_params();
	$b64 = $body['dataBase64'] ?? '';
	$name = isset( $body['filename'] ) ? sanitize_file_name( $body['filename'] ) : 'voa-upload-' . time() . '.png';
	$mime = $body['mimeType'] ?? 'image/png';
	if ( ! $b64 ) return new WP_Error( 'voa_bad_request', 'dataBase64 required', array( 'status' => 400 ) );
	$bytes = base64_decode( $b64, true );
	if ( $bytes === false ) return new WP_Error( 'voa_bad_request', 'Invalid base64', array( 'status' => 400 ) );

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$upload = wp_upload_bits( $name, null, $bytes );
	if ( ! empty( $upload['error'] ) ) return new WP_Error( 'voa_upload_failed', $upload['error'], array( 'status' => 500 ) );

	$attachment = array(
		'post_mime_type' => $mime,
		'post_title'     => pathinfo( $name, PATHINFO_FILENAME ),
		'post_content'   => '',
		'post_status'    => 'inherit',
		'guid'           => $upload['url'],
	);
	$attachment_id = wp_insert_attachment( $attachment, $upload['file'] );
	if ( ! $attachment_id || is_wp_error( $attachment_id ) ) return new WP_Error( 'voa_upload_failed', 'wp_insert_attachment failed', array( 'status' => 500 ) );

	// Generate metadata for non-SVG images
	if ( strpos( $mime, 'svg' ) === false ) {
		$meta = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );
		wp_update_attachment_metadata( $attachment_id, $meta );
	}

	return rest_ensure_response( array(
		'id'   => $attachment_id,
		'url'  => $upload['url'],
		'file' => $upload['file'],
		'size' => strlen( $bytes ),
	) );
}

/**
 * GET /voa/v1/media-list - Returns recent media library entries (id, url, alt, mime)
 * so the AI can reuse existing assets instead of always uploading new ones.
 */
function voa_endpoint_media_list( $req ) {
	$per_page = isset( $req['per_page'] ) ? min( 100, intval( $req['per_page'] ) ) : 25;
	$attachments = get_posts( array(
		'post_type' => 'attachment', 'post_status' => 'inherit',
		'posts_per_page' => $per_page, 'orderby' => 'date', 'order' => 'DESC',
	) );
	$out = array();
	foreach ( $attachments as $a ) {
		$out[] = array(
			'id'   => $a->ID,
			'url'  => wp_get_attachment_url( $a->ID ),
			'alt'  => get_post_meta( $a->ID, '_wp_attachment_image_alt', true ),
			'mime' => $a->post_mime_type,
			'title' => $a->post_title,
		);
	}
	return rest_ensure_response( array( 'media' => $out ) );
}

/**
 * GET /voa/v1/site-profile - One-shot snapshot of theme, page builder, key
 * plugins (FA Pro, Elementor Pro, ACF, WooCommerce). Pre-loaded into the
 * agent so it knows what's available before planning.
 */
function voa_endpoint_site_profile( $req ) {
	$active_plugins = (array) get_option( 'active_plugins', array() );
	$has = function( $needle ) use ( $active_plugins ) {
		foreach ( $active_plugins as $p ) { if ( stripos( $p, $needle ) !== false ) return true; }
		return false;
	};
	$theme = wp_get_theme();
	return rest_ensure_response( array(
		'theme'           => $theme->get( 'Name' ),
		'themeVersion'    => $theme->get( 'Version' ),
		'wpVersion'       => get_bloginfo( 'version' ),
		'siteUrl'         => get_site_url(),
		'siteName'        => get_bloginfo( 'name' ),
		'language'        => get_bloginfo( 'language' ),
		'pageBuilders'    => array(
			'elementor'      => $has( 'elementor/elementor.php' ),
			'elementorPro'   => $has( 'elementor-pro' ),
			'divi'           => stripos( $theme->get( 'Template' ), 'divi' ) !== false,
			'beaverBuilder'  => $has( 'beaver-builder' ) || $has( 'bb-plugin' ),
			'wpbakery'       => $has( 'js_composer' ),
			'oxygen'         => $has( 'oxygen' ),
			'bricks'         => stripos( $theme->get( 'Template' ), 'bricks' ) !== false,
		),
		'fontAwesomePro'  => $has( 'font-awesome-pro' ) || class_exists( 'Font_Awesome_Pro_Loader' ),
		'fontAwesomeFree' => $has( 'font-awesome' ),
		'acf'             => $has( 'advanced-custom-fields' ),
		'woocommerce'     => class_exists( 'WooCommerce' ),
		'yoast'           => $has( 'wordpress-seo' ),
		'rankMath'        => $has( 'seo-by-rank-math' ),
	) );
}

/**
 * Append text inside the last heading tag of an HTML blob, or to the end if
 * no heading tag is present. Lets "append today to H1" Just Work even when
 * the H1 lives in an HTML widget.
 */
function voa_html_append_inside_heading( $html, $append ) {
	if ( ! is_string( $html ) || $html === '' ) {
		return $append;
	}
	// Match any heading tag, capture inner content, append before closing tag.
	if ( preg_match( '/<h([1-6])([^>]*)>(.*?)<\/h\1>/is', $html, $m, PREG_OFFSET_CAPTURE ) ) {
		$inner_close_pos = $m[0][1] + strlen( $m[0][0] ) - strlen( '</h' . $m[1][0] . '>' );
		return substr( $html, 0, $inner_close_pos ) . $append . substr( $html, $inner_close_pos );
	}
	// No heading found - just append to the end
	return $html . $append;
}
