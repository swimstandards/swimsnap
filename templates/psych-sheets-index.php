<?php $this->layout('layout', ['title' => 'Psych Sheets']) ?>

<nav aria-label="breadcrumb">
  <ol class="breadcrumb mb-4">
    <li class="breadcrumb-item"><a href="<?= $base_url ?>/">Home</a></li>
    <li class="breadcrumb-item active" aria-current="page">Psych Sheets</li>
  </ol>
</nav>

<h1 class="mb-2"><i class="bi bi-people-fill me-1"></i> Psych Sheets</h1>
<p class="text-muted mb-4">Preview seeded entries and swimmer rankings before the meet.</p>

<a href="<?= $base_url ?>/upload-data.php" class="btn btn-sm btn-primary mb-4">Upload Psych Sheets</a>


<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
  <?php foreach ($meets as $m): ?>
    <div class="col">
      <div class="card h-100 shadow-sm">
        <div class="card-body d-flex flex-column">
          <h5 class="card-title line-clamp-2">
            <a href="<?= $base_url ?>/psych-sheets/<?= $m['slug'] ?>" class="stretched-link text-decoration-none text-dark">
              <?= htmlspecialchars($m['title']) ?>
            </a>
          </h5>
          <p class="card-text mb-1"><i class="bi bi-calendar-event me-1"></i><strong>Date:</strong>
            <?= $m['start_date'] ?> to <?= $m['end_date'] ?>
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