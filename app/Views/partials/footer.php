<?php
use App\Models\Content;

$groups = [
    ['Company', [['/about', 'About'], ['/careers', 'Careers'], ['/blog', 'Insights'], ['/contact', 'Contact']]],
    ['What we do', [['/services', 'Services'], ['/work', 'Our work'], ['/products', 'SaaS products'], ['/technologies', 'Technologies']]],
    ['Resources', [['/industries', 'Industries'], ['/faq', 'FAQ'], ['/quote', 'Request a quote'], ['/privacy', 'Privacy policy']]],
];
$settings = Content::settings();
$email = (string) ($settings['email'] ?? 'daylightcompany13@gmail.com');
?>
<footer class="site-footer">
  <div class="footer-inner">
    <div class="footer-grid">
      <div>
        <div class="brand">
          <span class="brand-mark">D</span>
          <span class="brand-name">Daylight</span>
        </div>
        <p class="footer-about">A Kenyan software company building custom business systems, web and mobile applications, and our own SaaS products.</p>
        <div class="footer-contact">
          <p><?= icon('mail', 16) ?> <?= e($email) ?></p>
          <p><?= icon('map-pin', 16) ?> Nairobi, Kenya</p>
        </div>
      </div>
      <?php foreach ($groups as [$title, $links]): ?>
        <div>
          <h3><?= e($title) ?></h3>
          <ul>
            <?php foreach ($links as [$to, $label]): ?>
              <li><a href="<?= e(url($to)) ?>"><?= e($label) ?></a></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="footer-bottom">
      <p>&copy; <span data-year><?= e(gmdate('Y')) ?></span> Daylight. All rights reserved.</p>
      <div class="links">
        <a href="<?= e(url('/privacy')) ?>">Privacy</a>
        <a href="<?= e(url('/terms')) ?>">Terms</a>
      </div>
    </div>
  </div>
</footer>
