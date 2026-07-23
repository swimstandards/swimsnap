<?php $this->layout('layout', ['title' => 'SwimSnap', 'full_width' => true]) ?>

<div class="hero-banner-simple text-center">
  <div class="container-lg py-5 position-relative">
    <a href="https://github.com/swimstandards/swimsnap" target="_blank" rel="noopener"
      class="position-absolute top-0 end-0 mt-3 me-3 text-white text-decoration-none d-flex align-items-center gap-1"
      title="View or contribute on GitHub">
      <i class="bi bi-github fs-3"></i>
      <span class="d-none d-md-inline">GitHub</span>
    </a>

    <img src="<?= $base_url ?>/images/logo.png" alt="SwimSnap Logo" style="height: 100px; margin-bottom: 0.5rem;">
    <h1 class="display-5 fw-bold mb-2">SwimSnap</h1>
    <p class="lead mb-4">Turn Swim Meet Files Into Web Pages — In a Snap!</p>

    <div class="mx-auto" style="max-width: 600px;">
      <!-- Search box with dropdown -->
      <div style="position: relative;" class="mb-4">
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-search"></i></span>
          <input type="text" id="search-box" class="form-control form-control-lg" placeholder="Search a Meet...">
        </div>
        <ul id="search-results-home"
          class="list-group position-absolute w-100 z-3 d-none"
          style="top: calc(100% + 0.25rem); max-height: 300px; overflow-y: auto; background-color: #f8f9fa; box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.05); text-align: left">
        </ul>
      </div>

      <!-- Upload buttons -->
      <div class="d-flex justify-content-between">
        <a href="<?= $base_url ?>/upload-file.php" class="btn btn-secondary btn-lg w-100 me-2">
          <i class="bi bi-file-earmark-zip me-1"></i> Upload Event File (.zip)
        </a>
        <a href="<?= $base_url ?>/upload-data.php" class="btn btn-primary btn-lg w-100">
          <i class="bi bi-file-earmark-text me-1"></i> Upload Meet Doc (Text)
        </a>
      </div>
    </div>
  </div>
</div>

<div class="container-lg py-5">
  <?php if (!empty($active_meets)): ?>
    <section class="mb-5" aria-labelledby="active-meets-heading">
      <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
        <div>
          <h2 class="h3 mb-1" id="active-meets-heading"><i class="bi bi-calendar2-week me-2 text-primary"></i>Upcoming &amp; In Progress</h2>
          <p class="text-muted mb-0">Meet Central pages for meets happening now or starting in the next week.</p>
        </div>
      </div>
      <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3">
        <?php foreach ($active_meets as $meet): ?>
          <div class="col">
            <a href="<?= htmlspecialchars($meet['url']) ?>" class="card h-100 shadow-sm text-decoration-none text-body">
              <div class="card-body">
                <div class="d-flex justify-content-between gap-2 align-items-start mb-2">
                  <span class="badge text-bg-<?= $meet['is_in_progress'] ? 'success' : 'primary' ?>"><?= $meet['is_in_progress'] ? 'In Progress' : 'Upcoming' ?></span>
                  <span class="small text-muted text-nowrap"><?= htmlspecialchars($meet['dates']) ?></span>
                </div>
                <h3 class="h5 mb-1"><?= htmlspecialchars($meet['name']) ?></h3>
                <span class="small text-primary">View Meet Central <i class="bi bi-arrow-right"></i></span>
              </div>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <section aria-labelledby="explore-meet-files-heading">
    <h2 class="h3 mb-3" id="explore-meet-files-heading"><i class="bi bi-folder2-open me-2 text-primary"></i>Explore Meet Files</h2>
    <div class="row row-cols-1 row-cols-md-2 g-4">
    <div class="col">
      <div class="card h-100 shadow-sm position-relative">
        <a href="<?= $base_url ?>/events" class="stretched-link"></a>
        <div class="card-body">
          <h5 class="card-title"><i class="bi bi-calendar-event me-1"></i> Event Schedules</h5>
          <p class="card-text">Explore session timelines and event orders by meet.</p>
          <span class="btn btn-outline-primary btn-fake-hover">View Events</span>
        </div>
      </div>
    </div>

    <div class="col">
      <div class="card h-100 shadow-sm position-relative">
        <a href="<?= $base_url ?>/psych-sheets" class="stretched-link"></a>
        <div class="card-body">
          <h5 class="card-title"><i class="bi bi-people-fill me-1"></i> Psych Sheets</h5>
          <p class="card-text">Preview seeded entries and swimmer rankings before the meet.</p>
          <span class="btn btn-outline-primary btn-fake-hover">View Psych Sheets</span>
        </div>
      </div>
    </div>

    <div class="col">
      <div class="card h-100 shadow-sm position-relative">
        <a href="<?= $base_url ?>/heat-sheets" class="stretched-link"></a>
        <div class="card-body">
          <h5 class="card-title"><i class="bi bi-list-ol me-1"></i> Heat Sheets</h5>
          <p class="card-text">See lane assignments and heats once the meet is underway.</p>
          <span class="btn btn-outline-primary btn-fake-hover">View Heat Sheets</span>
        </div>
      </div>
    </div>

    <div class="col">
      <div class="card h-100 shadow-sm position-relative">
        <a href="<?= $base_url ?>/results" class="stretched-link"></a>
        <div class="card-body">
          <h5 class="card-title"><i class="bi bi-clipboard-check me-1"></i> Results</h5>
          <p class="card-text">View session-based results — prelims, finals, and live updates from the meet.</p>
          <span class="btn btn-outline-primary btn-fake-hover">View Results</span>
        </div>
      </div>
    </div>
    </div>
  </section>
</div>
