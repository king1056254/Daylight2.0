<?php
use App\Assistant\AiClient;
use App\Core\Config;
use App\Core\Csrf;

if (!Config::bool('ASSISTANT_ENABLED', true)) {
    return;
}
$mode = AiClient::enabled() ? 'ai' : 'faq';
$suggestions = ['What services do you offer?', 'How long does a website take?', 'How do I use the client portal?', 'How can I contact the team?'];
?>
<div id="daylight-assistant" class="assistant" data-endpoint="<?= e(url('/assistant/message')) ?>" data-csrf="<?= e(Csrf::token()) ?>" data-mode="<?= e($mode) ?>"
     data-greeting="Hi, I'm Daylight's automated assistant. Ask me about our services, how projects work, or the client portal.">
  <button type="button" class="assistant-launch" data-assistant-toggle aria-expanded="false" aria-controls="assistant-panel">
    <?= icon('message', 20) ?><span>Ask Daylight</span>
  </button>
  <section id="assistant-panel" class="assistant-panel" role="dialog" aria-modal="false" aria-labelledby="assistant-title" hidden>
    <header class="assistant-head">
      <div>
        <h2 id="assistant-title">Daylight assistant</h2>
        <p class="assistant-mode"><?= $mode === 'ai' ? 'AI assistant · answers can contain mistakes' : 'Automatic answers from our FAQ and website' ?></p>
      </div>
      <div class="assistant-tools">
        <button type="button" class="assistant-text-btn" data-assistant-reset>New chat</button>
        <button type="button" class="icon-btn" data-assistant-toggle aria-label="Close assistant"><?= icon('x', 16) ?></button>
      </div>
    </header>
    <div class="assistant-log" role="log" aria-live="polite" aria-relevant="additions" tabindex="0"></div>
    <div class="assistant-suggest">
      <?php foreach ($suggestions as $q): ?>
        <button type="button" class="assistant-chip" data-assistant-suggest="<?= e($q) ?>"><?= e($q) ?></button>
      <?php endforeach; ?>
    </div>
    <form class="assistant-form" novalidate>
      <label class="sr-only" for="assistant-input">Your question</label>
      <textarea id="assistant-input" class="assistant-input" rows="1" maxlength="500" placeholder="Type your question…" autocomplete="off"></textarea>
      <button type="submit" class="btn assistant-send" aria-label="Send question"><?= icon('send', 16) ?></button>
    </form>
    <p class="assistant-note">Automated assistant, not a person. It cannot see your account or quote prices. Please don't share passwords, card numbers or ID numbers. Not legal or financial advice. <a href="<?= e(url('/privacy#assistant')) ?>">How chats are handled</a></p>
  </section>
</div>
