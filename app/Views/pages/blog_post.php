<?php /** @var array<string,mixed> $post */ ?>
<?= partial('partials/hero', ['eyebrow' => $post['category'] ?: 'Insight', 'title' => (string) $post['title'], 'description' => $post['excerpt'] ?? null]) ?>
<section class="section section-3xl">
  <p class="meta-mono"><?= e($post['author_name']) ?><?php if (!empty($post['published_at'])): ?> · <?= fmt_date((string) $post['published_at']) ?><?php endif; ?></p>
  <div class="mt-6 pre-line text-base muted prose-body"><?= e($post['body']) ?></div>
</section>
