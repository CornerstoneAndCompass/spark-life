<?php
/**
 * Section: hero — the home page hero.
 *
 * Ports the .hero block from the static build: glow, eyebrow with pulse dot,
 * skewed display headline with a blue highlighted word, lead, two CTAs, a trust
 * row, and the floating "fast quote" card with the logo badge.
 *
 * $section_data keys come from the 'hero' definition in inc/sections.php.
 */
if (!defined('ABSPATH')) exit;

$eyebrow   = sl_field($section_data, 'eyebrow');
$title     = sl_field($section_data, 'title');
$highlight = sl_field($section_data, 'title_highlight');
$lead      = sl_field($section_data, 'lead');

$p_text = sl_field($section_data, 'primary_btn_text');
$p_url  = sl_link(sl_field($section_data, 'primary_btn_url'), '/contact/');
$s_text = sl_field($section_data, 'secondary_btn_text');
$s_url  = sl_field($section_data, 'secondary_btn_url');

$phone = sl_get_var('company_phone');
$owner = sl_get_var('owner_name');
// A blank secondary URL means "call us" — the static design's "Call Liam now".
$s_is_tel = ($s_url === '');
$s_url    = $s_is_tel ? sl_tel_href() : sl_link($s_url);
if ($s_text === '' && $phone) {
    $s_text = $owner ? sprintf(__('Call %s now', 'sparklife'), $owner) : __('Call us now', 'sparklife');
}

$trust = (isset($section_data['trust']) && is_array($section_data['trust'])) ? $section_data['trust'] : array();

$card_tag   = sl_field($section_data, 'card_tag');
$card_title = sl_field($section_data, 'card_title');
$card_text  = sl_field($section_data, 'card_text');
$card_btn   = sl_field($section_data, 'card_btn_text', __('Book a job', 'sparklife'));
$card_url   = sl_link(sl_field($section_data, 'card_btn_url'), '/contact/');
$card_foot  = sl_field($section_data, 'card_foot');

$logo     = SL_URL . '/assets/img/logo.png';
$initials = sl_get_var('owner_initials');
?>
<section class="hero">
  <div class="hero__glow" aria-hidden="true"></div>
  <div class="wrap hero__inner">
    <div class="hero__copy">
      <?php if ($eyebrow !== '') : ?>
      <span class="eyebrow">
        <span class="eyebrow__pulse"></span>
        <?php echo esc_html($eyebrow); ?>
      </span>
      <?php endif; ?>

      <?php if ($title !== '') : ?>
      <h1 class="hero__title"><?php echo wp_kses_post(sl_highlight($title, $highlight)); ?></h1>
      <?php endif; ?>

      <?php if ($lead !== '') : ?>
      <p class="hero__lead"><?php echo esc_html($lead); ?></p>
      <?php endif; ?>

      <?php if ($p_text !== '' || $s_text !== '') : ?>
      <div class="hero__actions">
        <?php if ($p_text !== '') : ?>
        <a class="btn btn--primary btn--lg" href="<?php echo esc_url($p_url); ?>">
          <?php echo esc_html($p_text); ?>
          <?php echo sl_icon('arrow', 20); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </a>
        <?php endif; ?>
        <?php if ($s_text !== '' && $s_url !== '') : ?>
        <a class="btn btn--dark btn--lg" href="<?php echo esc_attr($s_url); ?>">
          <?php if ($s_is_tel) echo sl_icon('phone', 20); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
          <?php echo esc_html($s_text); ?>
        </a>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <?php if ($trust) : ?>
      <ul class="hero__trust">
        <?php foreach ($trust as $t) :
          $value = isset($t['value']) ? $t['value'] : '';
          $label = isset($t['label']) ? $t['label'] : '';
          $stars = sl_on(isset($t['stars']) ? $t['stars'] : '0');
          if ($value === '' && $label === '' && !$stars) continue;
        ?>
        <li>
          <?php if ($stars) : ?>
            <div class="stars" aria-label="<?php esc_attr_e('5 out of 5 stars', 'sparklife'); ?>">★★★★★</div>
          <?php elseif ($value !== '') : ?>
            <strong><?php echo esc_html($value); ?></strong>
          <?php endif; ?>
          <span><?php echo esc_html($label); ?></span>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>

    <div class="hero__card">
      <div class="card-badge" aria-hidden="true">
        <img src="<?php echo esc_url($logo); ?>" alt="" width="120" height="120">
      </div>
      <div class="quote-card">
        <?php if ($card_tag !== '') : ?>
        <span class="quote-card__tag"><?php echo esc_html($card_tag); ?></span>
        <?php endif; ?>
        <?php if ($card_title !== '') : ?>
        <h2 class="quote-card__title"><?php echo esc_html($card_title); ?></h2>
        <?php endif; ?>
        <?php if ($card_text !== '') : ?>
        <p class="quote-card__text"><?php echo esc_html($card_text); ?></p>
        <?php endif; ?>
        <a class="btn btn--primary btn--block" href="<?php echo esc_url($card_url); ?>"><?php echo esc_html($card_btn); ?></a>
        <?php if ($card_foot !== '') : ?>
        <div class="quote-card__foot">
          <?php if ($initials) : ?><span class="avatar"><?php echo esc_html($initials); ?></span><?php endif; ?>
          <p><?php echo wp_kses($card_foot, array('strong' => array(), 'em' => array(), 'br' => array())); ?></p>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
