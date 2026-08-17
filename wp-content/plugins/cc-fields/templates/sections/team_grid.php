<?php if (!defined('ABSPATH')) exit;
/**
 * Section: Team Grid
 * Section header (eyebrow, heading, intro) then a responsive grid of team
 * member cards: photo (rounded), name (h3), role (muted) and bio.
 * NEW section for the About page — preserves the old-site team bios.
 * Fields (from class-cc-sections.php): bg (default paper), eyebrow, heading,
 * intro, members[ name, role, bio, photo (image), photo_url (fallback) ].
 *
 * Card styling uses the design tokens (--r-card, --shadow-soft) and .reveal,
 * matching the section header conventions in calltheplumberguy/build.py.
 */

$bg      = isset($section_data['bg']) ? $section_data['bg'] : 'paper';
$eyebrow = isset($section_data['eyebrow']) ? $section_data['eyebrow'] : '';
$heading = isset($section_data['heading']) ? $section_data['heading'] : '';
$intro   = isset($section_data['intro']) ? $section_data['intro'] : '';
$members = (isset($section_data['members']) && is_array($section_data['members'])) ? $section_data['members'] : array();

// Honour bg: ink lights the eyebrow + section title (mirrors build.py).
$is_ink      = ($bg === 'ink');
$eyebrow_cls = 'eyebrow' . ($is_ink ? ' eyebrow--lime' : '');
$title_cls   = 'section-title' . ($is_ink ? ' section-title--light' : '');
?>
<section class="section section--<?php echo esc_attr($bg); ?> reveal" id="team">
  <div class="wrap">
    <div class="section__head reveal">
      <?php if ($eyebrow !== '') : ?>
      <p class="<?php echo esc_attr($eyebrow_cls); ?>"><?php echo esc_html($eyebrow); ?></p>
      <?php endif; ?>
      <?php if ($heading !== '') : ?>
      <h2 class="<?php echo esc_attr($title_cls); ?>"><?php echo esc_html($heading); ?></h2>
      <?php endif; ?>
      <?php if ($intro !== '') : ?>
      <p class="section-intro"><?php echo esc_html($intro); ?></p>
      <?php endif; ?>
    </div>
    <?php if (!empty($members)) : ?>
    <div class="team__grid">
      <?php foreach ($members as $member) :
        $name = isset($member['name']) ? $member['name'] : '';
        $role = isset($member['role']) ? $member['role'] : '';
        $bio  = isset($member['bio'])  ? $member['bio']  : '';
        // Photo: attachment id first, then the *_url fallback.
        $photo = !empty($member['photo'])
            ? Ccf_Renderer::get_image_url($member['photo'])
            : (isset($member['photo_url']) ? $member['photo_url'] : '');
      ?>
      <article class="tmember reveal">
        <?php if ($photo !== '') : ?>
        <div class="tmember__photo">
          <img src="<?php echo esc_url($photo); ?>" alt="<?php echo esc_attr($name); ?>" loading="lazy">
        </div>
        <?php endif; ?>
        <div class="tmember__body">
          <?php if ($name !== '') : ?>
          <h3 class="tmember__name"><?php echo esc_html($name); ?></h3>
          <?php endif; ?>
          <?php if ($role !== '') : ?>
          <p class="tmember__role"><?php echo esc_html($role); ?></p>
          <?php endif; ?>
          <?php if ($bio !== '') : ?>
          <p class="tmember__bio"><?php echo esc_html($bio); ?></p>
          <?php endif; ?>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
