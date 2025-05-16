<?php $this->layout('layout', ['title' => 'SwimSnap', 'full_width' => true]) ?>

<div class="hero-banner-simple text-center">
  <div class="bg-light border-top border-bottom py-3">
    <div class="container text-center">
      <a href="https://swimsnap.com/meet/2025-ez-lc-speedo-super-sectional-2025-05-15" rel="noopener" class="text-decoration-none">
        <strong>🚨 Meet in Progress:</strong> <span class="text-primary">2025 EZ LC Speedo Super Sectional (May 15–18)</span> · View Meet Central →
      </a>
    </div>
  </div>

  <div class="container-lg py-5">
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
  <div class="row row-cols-1 row-cols-md-2 g-4">
    <div class="col">
      <div class="card h-100 shadow-sm">
        <div class="card-body">
          <h5 class="card-title"><i class="bi bi-calendar-event me-1"></i> Event Schedules</h5>
          <p class="card-text">Explore session timelines and event orders by meet.</p>
          <a href="<?= $base_url ?>/events" class="btn btn-outline-primary">View Events</a>
        </div>
      </div>
    </div>

    <div class="col">
      <div class="card h-100 shadow-sm">
        <div class="card-body">
          <h5 class="card-title"><i class="bi bi-people-fill me-1"></i> Psych Sheets</h5>
          <p class="card-text">Preview seeded entries and swimmer rankings before the meet.</p>
          <a href="<?= $base_url ?>/psych-sheets" class="btn btn-outline-primary">View Psych Sheets</a>
        </div>
      </div>
    </div>

    <div class="col">
      <div class="card h-100 shadow-sm">
        <div class="card-body">
          <h5 class="card-title"><i class="bi bi-list-ol me-1"></i> Heat Sheets</h5>
          <p class="card-text">See lane assignments and heats once the meet is underway.</p>
          <a href="<?= $base_url ?>/heat-sheets" class="btn btn-outline-primary">View Heat Sheets</a>
        </div>
      </div>
    </div>

    <div class="col">
      <div class="card h-100 shadow-sm">
        <div class="card-body">
          <h5 class="card-title"><i class="bi bi-clipboard-check me-1"></i> Results</h5>
          <p class="card-text">View session-based results — prelims, finals, and live updates from the meet.</p>
          <a href="<?= $base_url ?>/results" class="btn btn-outline-primary">View Results</a>
        </div>
      </div>
    </div>
  </div>
</div>