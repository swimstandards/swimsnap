<?php $this->layout('layout', ['title' => 'Heat Sheets']) ?>

<nav aria-label="breadcrumb">
  <ol class="breadcrumb mb-4">
    <li class="breadcrumb-item"><a href="<?= $base_url ?>/">Home</a></li>
    <li class="breadcrumb-item active" aria-current="page">Heat Sheets</li>
  </ol>
</nav>

<h1 class="mb-2"><i class="bi bi-list-ol me-1"></i> Heat Sheets</h1>
<p class="text-muted mb-4">See lane assignments and heats once the meet is underway.</p>

<a href="<?= $base_url ?>/upload-data.php" class="btn btn-sm btn-primary mb-4">Upload Meet Document</a>

<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
  <?php foreach ($meets as $m): ?>
    <div class="col">
      <div class="card h-100 shadow-sm">
        <div class="card-body d-flex flex-column">
          <h5 class="card-title mb-1">
            <a href="<?= $base_url ?>/heat-sheets/<?= $m['slug'] ?>" class="stretched-link text-decoration-none text-dark">
              <?= htmlspecialchars($m['sheet_name']) ?>
            </a>
          </h5>
          <p class="card-subtitle text-muted mb-2"><?= htmlspecialchars($m['meet_name']) ?></p>
          <p class="card-text mb-1"><i class="bi bi-calendar-event me-1"></i><strong>Date:</strong>
            <?php if (!empty($m['meet_start_date']) && !empty($m['meet_end_date'])): ?>
              <?= date('m/d/Y', strtotime($m['meet_start_date'])) ?> to <?= date('m/d/Y', strtotime($m['meet_end_date'])) ?><br>
            <?php else: ?>
              <?= htmlspecialchars($m['meet_date'] ?? '') ?><br>
            <?php endif; ?>
          </p>
          <?php if (!empty($m['organization'])): ?>
            <p class="card-text mb-1"><i class="bi bi-building me-1"></i><strong>Org:</strong> <?= htmlspecialchars($m['organization']) ?></p>
          <?php endif; ?>
          <p class="card-text"><i class="bi bi-clock me-1"></i><strong>Updated:</strong> <?= $m['file_datetime'] ?></p>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>