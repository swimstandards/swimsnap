<?php
$urlToShare = $base_url . $_SERVER['REQUEST_URI'];
?>

<div class="mb-4 d-flex align-items-center flex-wrap gap-3">
  <div id="qrcode"></div>
  <button class="btn btn-outline-primary" id="copyLinkBtn">
    <i class="bi bi-clipboard"></i> Copy Link
  </button>
  <a
    href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($urlToShare) ?>"
    target="_blank"
    class="btn btn-outline-secondary">
    <i class="bi bi-facebook"></i>
  </a>
  <a
    href="https://twitter.com/intent/tweet?url=<?= urlencode($urlToShare) ?>"
    target="_blank"
    class="btn btn-outline-secondary">
    <i class="bi bi-twitter-x"></i>
  </a>
</div>