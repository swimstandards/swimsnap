<?php $this->layout('layout', [
  'title' => 'Event Schedules',
  'meta_title' => 'Swim Meet Event Schedules',
  'meta_description' => 'View session timelines and event orders for recent swim meets across the country.',
  'meta_canonical_url' => $base_url . '/events'
]) ?>

<nav aria-label="breadcrumb">
  <ol class="breadcrumb mb-4">
    <li class="breadcrumb-item"><a href="<?= $base_url ?>/">Home</a></li>
    <li class="breadcrumb-item active" aria-current="page">Event Schedules</li>
  </ol>
</nav>

<h1 class="mb-2"><i class="bi bi-calendar-event me-1"></i> Event Schedules</h1>
<p class="text-muted mb-4">Explore session timelines and event orders by meet.</p>

<a href="<?= $base_url ?>/upload-file.php" class="btn btn-sm btn-primary mb-4">Upload a Hytek Event Setup File</a>

<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
  <?php foreach ($meets as $m): ?>
    <div class="col">
      <div class="card h-100 shadow-sm">
        <div class="card-body">
          <h5 class="card-title line-clamp-2">
            <a href="<?= $base_url ?>/events/<?= $m['slug'] ?>" class="stretched-link text-decoration-none text-blue">
              <?= htmlspecialchars($m['title']) ?>
            </a>
          </h5>
          <p class="card-text mb-1">
            <i class="bi bi-calendar-event"></i>
            <strong>Date:</strong>
            <?= date('m/d/Y', strtotime($m['start_date'])) ?> to <?= date('m/d/Y', strtotime($m['end_date'])) ?><br>
          </p>
          <?php if (!empty($m['venue'])): ?>
            <p class="card-text mb-1">
              <i class="bi bi-geo-alt-fill"></i>
              <strong>Venue:</strong> <?= htmlspecialchars($m['venue']) ?>
            </p>
          <?php endif; ?>
          <?php if (!empty($m['course'])): ?>
            <p class="card-text mb-1">
              <i class="bi bi-stopwatch"></i>
              <strong>Course:</strong>
              <?= $m['course'] === 'Y' ? 'SCY' : ($m['course'] === 'L' ? 'LCM' : $m['course']) ?>
            </p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>