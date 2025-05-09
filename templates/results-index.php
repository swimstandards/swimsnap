<?php $this->layout('layout', ['title' => 'Results']) ?>

<nav aria-label="breadcrumb">
  <ol class="breadcrumb mb-4">
    <li class="breadcrumb-item"><a href="<?= $base_url ?>/">Home</a></li>
    <li class="breadcrumb-item active" aria-current="page">Results</li>
  </ol>
</nav>

<h1 class="mb-2"><i class="bi bi-clipboard-check me-1"></i> Results</h1>
<p class="text-muted mb-4">View session-based results — prelims, finals, and live updates from the meet.</p>

<a href="<?= $base_url ?>/upload-data.php" class="btn btn-sm btn-primary mb-4">Upload Results</a>

<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
  <?php foreach ($meets as $m): ?>
    <div class="col">
      <div class="card h-100 shadow-sm">
        <div class="card-body d-flex flex-column">
          <h5 class="card-title mb-1">
            <a href="<?= $base_url ?>/results/<?= $m['slug'] ?>" class="stretched-link text-decoration-none text-dark">
              <?= htmlspecialchars($m['meet_name']) ?>
            </a>
          </h5>

          <p class="card-subtitle text-muted mb-2">
            <?= !empty($m['sheet_name']) ? htmlspecialchars($m['sheet_name']) : 'Full Meet Results' ?>
          </p>

          <div class="mt-auto">
            <p class="card-text mb-1">
              <i class="bi bi-calendar-range me-1"></i>
              <?php if (!empty($m['meet_start_date']) && !empty($m['meet_end_date'])): ?>
                <?= date('m/d/Y', strtotime($m['meet_start_date'])) ?> to <?= date('m/d/Y', strtotime($m['meet_end_date'])) ?>
              <?php else: ?>
                <?= htmlspecialchars($m['meet_date'] ?? '') ?>
              <?php endif; ?>
            </p>

            <p class="card-text mb-0">
              <i class="bi bi-clock me-1"></i>
              <strong>Updated:</strong> <?= $m['file_datetime'] ?>
            </p>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>