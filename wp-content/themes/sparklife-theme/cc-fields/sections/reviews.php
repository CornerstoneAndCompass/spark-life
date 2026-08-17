<?php
/**
 * Section: reviews — grid of review cards, one optionally highlighted dark.
 * Also emits Review schema so the stars can surface in search results.
 */
if (!defined('ABSPATH')) exit;

$bg        = sl_field($section_data, 'bg', 'paper');
$kicker    = sl_field($section_data, 'kicker');
$heading   = sl_field($section_data, 'heading');
$highlight = sl_field($section_data, 'heading_highlight');
$intro     = sl_field($section_data, 'intro');
$is_ink    = ($bg === 'ink' || $bg === 'blue');

$reviews = (isset($section_data['reviews']) && is_array($section_data['reviews'])) ? $section_data['reviews'] : array();
if (!$reviews) return;

/** "Sarah K." → "SK" for the avatar chip. */
$initials = function ($name) {
    $out = '';
    foreach (preg_split('/\s+/', trim((string) $name)) as $part) {
        if ($part !== '') $out .= strtoupper(substr($part, 0, 1));
    }
    return substr($out, 0, 2);
};

$schema = array();
?>
<section class="section section--<?php echo esc_attr($bg); ?> reviews" id="reviews">
  <div class="wrap">
    <div class="section__head">
      <?php if ($kicker !== '') : ?>
      <span class="kicker<?php echo $is_ink ? ' kicker--light' : ''; ?>"><?php echo esc_html($kicker); ?></span>
      <?php endif; ?>
      <?php if ($heading !== '') : ?>
      <h2 class="section__title<?php echo $is_ink ? ' section__title--light' : ''; ?>"><?php echo wp_kses_post(sl_highlight($heading, $highlight)); ?></h2>
      <?php endif; ?>
      <?php if ($intro !== '') : ?><p class="section__sub"><?php echo esc_html($intro); ?></p><?php endif; ?>
    </div>
    <div class="review-grid">
      <?php foreach ($reviews as $r) :
        $text     = isset($r['text']) ? $r['text'] : '';
        $name     = isset($r['name']) ? $r['name'] : '';
        $subtitle = isset($r['subtitle']) ? $r['subtitle'] : '';
        if ($text === '') continue;
        $hl = sl_on(isset($r['highlight']) ? $r['highlight'] : '0');
        $schema[] = array(
            '@type'         => 'Review',
            'reviewRating'  => array('@type' => 'Rating', 'ratingValue' => '5', 'bestRating' => '5'),
            'author'        => array('@type' => 'Person', 'name' => $name ?: 'Customer'),
            'reviewBody'    => wp_strip_all_tags($text),
        );
      ?>
      <figure class="review<?php echo $hl ? ' review--hl' : ''; ?>">
        <div class="stars" aria-label="<?php esc_attr_e('5 out of 5 stars', 'sparklife'); ?>">★★★★★</div>
        <blockquote><?php echo esc_html($text); ?></blockquote>
        <?php if ($name !== '') : ?>
        <figcaption>
          <span class="avatar avatar--sm"><?php echo esc_html($initials($name)); ?></span>
          <?php echo esc_html($name); ?><?php echo $subtitle !== '' ? ' · ' . esc_html($subtitle) : ''; ?>
        </figcaption>
        <?php endif; ?>
      </figure>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php if ($schema) :
  $score = sl_get_var('review_score');
  $count = sl_get_var('review_count');
  $ld = array(
      '@context' => 'https://schema.org',
      '@type'    => 'LocalBusiness',
      '@id'      => home_url('/') . '#business',
      'name'     => sl_get_var('company_name', get_bloginfo('name')),
      'review'   => $schema,
  );
  if ($score && $count) {
      $ld['aggregateRating'] = array(
          '@type'       => 'AggregateRating',
          'ratingValue' => $score,
          'reviewCount' => preg_replace('/[^0-9]/', '', $count),
          'bestRating'  => '5',
      );
  }
?>
<script type="application/ld+json"><?php echo wp_json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
<?php endif; ?>
