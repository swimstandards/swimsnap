<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <title><?= $this->e($meta_title ?? 'SwimSnap') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="description" content="<?= $this->e($meta_description ?? 'SwimSnap is a fast, community-driven swim meet platform to browse time standards, schedules, and results.') ?>">
  <meta name="keywords" content="<?= $this->e($meta_keywords ?? 'swim meet, swim results, time standards, psych sheet, meet mobile alternative') ?>">

  <?php if (isset($meta_canonical_url)): ?>
    <link rel="canonical" href="<?= $this->e($meta_canonical_url) ?>">
  <?php endif; ?>

  <!-- Open Graph -->
  <meta property="og:title" content="<?= $this->e($meta_title ?? 'SwimSnap') ?>">
  <meta property="og:description" content="<?= $this->e($meta_description ?? '') ?>">
  <meta property="og:type" content="website">
  <meta property="og:image" content="<?= $this->e($meta_og_image ?? $base_url . '/images/og/default.png') ?>">
  <meta property="og:url" content="<?= $this->e($base_url . $_SERVER['REQUEST_URI']) ?>">

  <!-- Twitter -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= $this->e($meta_title ?? 'SwimSnap') ?>">
  <meta name="twitter:description" content="<?= $this->e($meta_description ?? '') ?>">
  <meta name="twitter:image" content="<?= $this->e($meta_og_image ?? $base_url . '/images/og/default.png') ?>">

  <!-- Favicon / CSS -->
  <link rel="icon" href="<?= $base_url ?>/favicon.ico">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" />
  <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css" />
  <link rel="stylesheet" href="<?= $base_url ?>/css/style.css?<?= rawurlencode($build_version) ?>">
</head>

<body>

  <?php if (!isset($full_width)): ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
      <div class="container px-4">
        <a class="navbar-brand d-flex align-items-center" href="<?= $base_url ?>">
          <img src="<?= $base_url ?>/images/logo.png" alt="SwimSnap" height="32" class="me-2">
          <span class="fw-bold text-white">SwimSnap</span>
        </a>

        <!-- Mobile toggle -->
        <button class="bg-transparent border-0 d-lg-none ms-auto text-white" type="button" id="toggleSearchBtn" aria-label="Toggle search">
          <i class="bi bi-search" id="searchIcon"></i>
          <i class="bi bi-x-lg d-none" id="closeIcon"></i>
        </button>

        <!-- Desktop Search -->
        <div class="ms-auto d-none d-lg-flex align-items-center gap-2">
          <!-- Upload Buttons -->
          <a href="<?= $base_url ?>/upload-file.php" class="btn btn-outline-light btn-sm ms-3">
            <i class="bi bi-file-earmark-zip me-1"></i> Event File
          </a>
          <a href="<?= $base_url ?>/upload-data.php" class="btn btn-light btn-sm">
            <i class="bi bi-file-earmark-text me-1"></i> Meet Doc
          </a>

          <div class="position-relative" style="min-width: 280px;">
            <div class="input-group">
              <span class="input-group-text bg-white border-end-0 rounded-start-pill">
                <i class="bi bi-search text-muted"></i>
              </span>
              <input id="search-box" type="search" class="form-control border-start-0 rounded-end-pill" placeholder="Search...">
            </div>
            <ul id="search-results" class="list-group d-none search-results"></ul>
          </div>


        </div>

        <!-- Desktop Search -->
        <!-- <div class="ms-auto d-none d-lg-block position-relative" style="min-width: 280px;">
          <div class="input-group">
            <span class="input-group-text bg-white border-end-0 rounded-start-pill">
              <i class="bi bi-search text-muted"></i>
            </span>
            <input id="search-box" type="search" class="form-control border-start-0 rounded-end-pill" placeholder="Search...">
          </div>
          <ul id="search-results" class="list-group d-none search-results"></ul>
        </div> -->
    </nav>

    <!-- Mobile Search -->
    <div class="bg-primary d-lg-none px-3 pb-3 collapse" id="mobileSearch">
      <div class="container-fluid">
        <div class="position-relative">
          <div class="input-group">
            <span class="input-group-text bg-white border-end-0 rounded-start-pill">
              <i class="bi bi-search text-muted"></i>
            </span>
            <input id="search-box-mobile" type="search" class="form-control border-start-0 rounded-end-pill" placeholder="Search...">
          </div>

          <!-- MUST be inside the same .position-relative container -->
          <ul id="search-results-mobile" class="list-group d-none search-results"></ul>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <main>
    <?php if (isset($full_width) && $full_width): ?>
      <a href="https://github.com/swimstandards/swimsnap" target="_blank" rel="noopener"
        class="position-absolute top-0 end-0 mt-3 me-3 text-white text-decoration-none d-flex align-items-center gap-1"
        title="View or contribute on GitHub">
        <i class="bi bi-github fs-3"></i>
        <span class="d-none d-md-inline">GitHub</span>
      </a>
      <?= $this->section('content') ?>
    <?php else: ?>
      <div class="container p-4">
        <?= $this->section('content') ?>
      </div>
    <?php endif; ?>
  </main>

  <footer class="text-center text-muted small py-3 border-top mt-auto">
    <div class="container">
      <span>
        &copy; <?= date('Y') ?> SwimSnap (<?= htmlspecialchars($build_version) ?>) ·
        Open source on <a href="https://github.com/swimstandards/swimsnap" target="_blank" rel="noopener">GitHub</a> ·
        Built by the team behind <a href="https://swimstandards.com" target="_blank" rel="noopener">SwimStandards</a>
      </span>
    </div>
  </footer>

  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
  <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
  <script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    $(function() {
      $('#toggleSearchBtn').on('click', function() {
        const $mobileSearch = $('#mobileSearch');
        const $searchIcon = $('#searchIcon');
        const $closeIcon = $('#closeIcon');

        $mobileSearch.toggleClass('show');

        if ($mobileSearch.hasClass('show')) {
          $searchIcon.addClass('d-none');
          $closeIcon.removeClass('d-none');
          $mobileSearch.find('input[type="search"]').focus();
        } else {
          $searchIcon.removeClass('d-none');
          $closeIcon.addClass('d-none');
        }
      });
    });

    // Shared AJAX search logic
    function setupLiveSearch(inputId, resultId) {
      const input = document.getElementById(inputId);
      const resultBox = document.getElementById(resultId);

      if (!input || !resultBox) return; // stop if either is missing

      input?.addEventListener('input', function() {
        const q = this.value.trim();
        if (q.length < 2) {
          resultBox.classList.add('d-none');
          resultBox.innerHTML = '';
          return;
        }

        fetch('<?= $base_url ?>/search.php?q=' + encodeURIComponent(q))
          .then(res => res.json())
          .then(data => {
            if (!data.length) {
              resultBox.innerHTML = `
    <li class="list-group-item text-muted small">
      No results found. You can help grow SwimSnap by uploading missing meet documents.
    </li>
  `;
              resultBox.classList.remove('d-none');
              return;
            }

            resultBox.innerHTML = '';
            data.forEach(item => {
              const li = document.createElement('li');
              li.className = 'list-group-item';
              li.innerHTML = `<a href="${item.slug}">${item.meet_name} (${item.meet_start_date})</a>`;
              resultBox.appendChild(li);
            });
            resultBox.classList.remove('d-none');
          });
      });

      document.addEventListener('click', function(e) {
        if (!input.contains(e.target) && !resultBox.contains(e.target)) {
          resultBox.classList.add('d-none');
        }
      });
    }

    setupLiveSearch('search-box', 'search-results');
    setupLiveSearch('search-box-mobile', 'search-results-mobile');
    setupLiveSearch('search-box', 'search-results-home');
  </script>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Initialize Bootstrap tooltip
      const tooltipTrigger = document.querySelectorAll('[data-bs-toggle="tooltip"]');
      tooltipTrigger.forEach(el => new bootstrap.Tooltip(el));

      // Copy URL handler
      const copyLink = document.getElementById('copyUrlInline');
      const feedback = document.getElementById('copyUrlFeedback');

      if (copyLink) {
        copyLink.addEventListener('click', function(e) {
          e.preventDefault();
          navigator.clipboard.writeText(window.location.href).then(() => {
            feedback.style.display = 'inline';
            setTimeout(() => feedback.style.display = 'none', 2000);
          });
        });
      }
    });
  </script>

  <?= $this->section('scripts') ?>
</body>

</html>