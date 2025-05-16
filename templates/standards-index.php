<?php $this->layout('layout', ['title' => 'Time Standards']) ?>

<nav aria-label="breadcrumb">
  <ol class="breadcrumb mb-4">
    <li class="breadcrumb-item"><a href="<?= $base_url ?>/">Home</a></li>
    <li class="breadcrumb-item active" aria-current="page">All Standards</li>
  </ol>
</nav>

<h1>Time Standards</h1>
<p class="text-muted">
  Browse time standards for swim meets, sorted by meet date. Use the search to find a specific meet or sort by recently added. Missing one? Help the community by uploading it below.
</p>

<a href="<?= $base_url ?>/upload-file.php" class="btn btn-sm btn-primary mb-4">
  Upload a Hytek Event Setup File
</a>


<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
  <?php foreach ($meets as $item): ?>
    <div class="col">
      <div class="card h-100 shadow-sm">
        <div class="card-body">
          <h5 class="card-title line-clamp-2">
            <a href="<?= $base_url ?>/standards/<?= $item['slug'] ?>" class="stretched-link text-decoration-none text-blue">
              <?= htmlspecialchars($item['meet_name']) ?>
            </a>
          </h5>
          <?php if (!empty($item['start_date']) && !empty($item['end_date'])): ?>
            <p class="card-text mb-1"><i class="bi bi-calendar"></i> <strong>Date:</strong> <?= $item['start_date'] ?> to <?= $item['end_date'] ?></p>
          <?php endif; ?>
          <?php if (!empty($item['venue'])): ?>
            <p class="card-text mb-1"><i class="bi bi-geo-alt"></i> <strong>Venue:</strong> <?= htmlspecialchars($item['venue']) ?></p>
          <?php endif; ?>
          <?php if (!empty($item['course'])): ?>
            <p class="card-text"><i class="bi bi-info-circle"></i> Course: <?= $item['course'] === 'Y' ? 'SCY' : ($item['course'] === 'L' ? 'LCM' : $item['course']) ?></p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>