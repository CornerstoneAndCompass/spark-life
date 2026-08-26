<?php
/**
 * Header — top bar, sticky header (brand + nav + CTAs) and the full-screen
 * mobile nav. Markup mirrors the original static build (assets/css/main.css).
 *
 * The Services item carries a mega-menu built from live Service posts, so
 * publishing or trashing a service updates the navigation with no edits here.
 */
if (!defined('ABSPATH')) exit;

$tel      = sl_tel();
$phone    = sl_get_var('company_phone');
$rec      = sl_get_var('rec_license');
$region   = sl_get_var('company_region');
$name     = sl_get_var('company_name', get_bloginfo('name'));
$short    = sl_get_var('company_short', $name);
$logo     = sl_logo_url();
$quote_url = home_url('/contact/');

$nav_items = sl_nav_items();
$services  = sl_get_services();
$current   = trailingslashit(strtok($_SERVER['REQUEST_URI'], '?'));
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main"><?php esc_html_e('Skip to content', 'sparklife'); ?></a>

<div class="topbar">
  <div class="wrap topbar__inner">
    <p class="topbar__msg">
      <span class="dot"></span>
      <?php esc_html_e('Licensed & insured electricians', 'sparklife'); ?>
      <?php if ($rec) : ?>· <strong><?php echo esc_html($rec); ?></strong><?php endif; ?>
    </p>
    <div class="topbar__links">
      <?php if ($region) : ?><span class="topbar__item"><?php echo esc_html($region); ?></span><?php endif; ?>
      <?php if ($phone) : ?>
      <a class="topbar__item topbar__call" href="tel:<?php echo esc_attr($tel); ?>">
        <?php echo sl_icon('phone', 14); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — static inline SVG ?>
        <?php echo esc_html($phone); ?>
      </a>
      <?php endif; ?>
    </div>
  </div>
</div>

<header class="header" id="header">
  <div class="wrap header__inner">
    <a class="brand" href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php echo esc_attr($name); ?>">
      <img class="brand__mark" src="<?php echo esc_url($logo); ?>" alt="" width="44" height="44">
      <span class="brand__text">
        <span class="brand__name"><?php echo esc_html($short); ?></span>
        <span class="brand__sub"><?php esc_html_e('Electrical Contractors', 'sparklife'); ?></span>
      </span>
    </a>

    <nav class="nav" id="nav" aria-label="<?php esc_attr_e('Primary', 'sparklife'); ?>">
      <?php foreach ($nav_items as $it) :
        $href    = trailingslashit(sl_link($it['url']));
        $is_cur  = ($current === parse_url($href, PHP_URL_PATH));
        $classes = 'nav__link' . ($is_cur ? ' is-current' : '');
        $has_dd  = !empty($it['services']) && $services;
      ?>
        <?php if ($has_dd) : ?>
        <div class="nav__group">
          <a class="<?php echo esc_attr($classes); ?> nav__link--dd" href="<?php echo esc_url($href); ?>">
            <?php echo esc_html($it['label']); ?>
            <?php echo sl_icon('chevron', 13); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
          </a>
          <div class="megamenu">
            <div class="megamenu__grid">
              <?php foreach ($services as $s) : ?>
              <a class="megamenu__item" href="<?php echo esc_url($s['url']); ?>">
                <span class="megamenu__ico"><?php echo sl_icon($s['icon'], 22); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                <span class="megamenu__txt">
                  <strong><?php echo esc_html($s['title']); ?></strong>
                  <?php if ($s['excerpt']) : ?><span><?php echo esc_html(wp_trim_words($s['excerpt'], 12)); ?></span><?php endif; ?>
                </span>
              </a>
              <?php endforeach; ?>
            </div>
            <div class="megamenu__foot">
              <div class="megamenu__cta">
                <strong><?php esc_html_e('Not sure what you need?', 'sparklife'); ?></strong>
                <span><?php esc_html_e('Tell us what’s going on and we’ll point you the right way.', 'sparklife'); ?></span>
              </div>
              <div class="megamenu__actions">
                <a class="btn btn--dark btn--sm" href="tel:<?php echo esc_attr($tel); ?>"><?php echo esc_html($phone); ?></a>
                <a class="megamenu__all" href="<?php echo esc_url($href); ?>">
                  <?php esc_html_e('All services', 'sparklife'); ?>
                  <?php echo sl_icon('arrow', 16); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </a>
              </div>
            </div>
          </div>
          <div class="nav__sub">
            <?php foreach ($services as $s) : ?>
            <a class="nav__sublink" href="<?php echo esc_url($s['url']); ?>"><?php echo esc_html($s['title']); ?></a>
            <?php endforeach; ?>
          </div>
        </div>
        <?php else : ?>
        <a class="<?php echo esc_attr($classes); ?>" href="<?php echo esc_url($href); ?>"><?php echo esc_html($it['label']); ?></a>
        <?php endif; ?>
      <?php endforeach; ?>

      <?php if ($phone) : ?>
      <a class="btn btn--ghost nav__cta-call" href="tel:<?php echo esc_attr($tel); ?>"><?php echo esc_html($phone); ?></a>
      <?php endif; ?>
      <a class="btn btn--primary" href="<?php echo esc_url($quote_url); ?>"><?php esc_html_e('Get a quote', 'sparklife'); ?></a>
    </nav>

    <button class="burger" id="burger" aria-label="<?php esc_attr_e('Open menu', 'sparklife'); ?>" aria-expanded="false" aria-controls="nav">
      <span></span><span></span><span></span>
    </button>
  </div>
</header>

<main id="main">
