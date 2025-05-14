<?php $this->layout('layout', ['title' => 'Event Order']) ?>

<nav aria-label="breadcrumb">
  <ol class="breadcrumb mb-4">
    <li class="breadcrumb-item"><a href="<?= $base_url ?>/">Home</a></li>
    <li class="breadcrumb-item"><a href="<?= $base_url ?>/events">Event Schedules</a></li>
    <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars(shorten_title($meet_info["meet_name"])) ?></li>
  </ol>
</nav>

<h1>Event Schedule</h1>
<?php include __DIR__ . '/shared/meta-block.php' ?>

<div class="row mb-4">
  <div class="col-md mb-2">
    <select id="strokeFilter" class="form-select" aria-label="Filter by stroke">
      <option value="">All Strokes</option>
      <?php foreach ($all_strokes as $s): ?>
        <option value="<?= htmlspecialchars($s) ?>"><?= htmlspecialchars($s) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md mb-2">
    <select id="ageFilter" class="form-select" aria-label="Filter by age group">
      <option value="">All Age Groups</option>
      <?php foreach ($all_age_groups as $a): ?>
        <option value="<?= htmlspecialchars($a) ?>"><?= htmlspecialchars($a) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md">
    <select id="genderFilter" class="form-select" aria-label="Filter by gender">
      <option value="">All Genders</option>
      <?php foreach ($all_genders as $g): ?>
        <option value="<?= htmlspecialchars($g) ?>"><?= htmlspecialchars($g) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
</div>

<?php
// Determine if any event has cuts to show
$show_cuts = false;
foreach ($event_sessions as $events) {
  foreach ($events as $e) {
    if (!empty($e['lcm_cut']) && $e['lcm_cut'] !== '-' || !empty($e['scy_cut']) && $e['scy_cut'] !== '-') {
      $show_cuts = true;
      break 2;
    }
  }
}
?>

<?php foreach ($event_sessions as $session => $events): ?>
  <h4>Session <?= htmlspecialchars($session) ?></h4>
  <div class="table-responsive">
    <table class="table table-sm table-striped table-bordered w-100">
      <thead>
        <tr>
          <th>#</th>
          <th>Age Group</th>
          <th>Gender</th>
          <th>Distance</th>
          <th>Stroke</th>
          <th>Type</th>
          <th>Time</th>
          <?php if ($show_cuts): ?>
            <th>LCM Cut</th>
            <th>SCY Cut</th>
          <?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($events as $e): ?>
          <tr
            data-stroke="<?= htmlspecialchars($e['stroke']) ?>"
            data-age="<?= htmlspecialchars($e['age_group']) ?>"
            data-gender="<?= htmlspecialchars($e['gender']) ?>">
            <td><?= $e['event_number'] ?></td>
            <td><?= $e['age_group'] ?></td>
            <td><?= $e['gender'] ?></td>
            <td><?= $e['distance'] ?>m</td>
            <td><?= $e['stroke'] ?></td>
            <td><?= $e['type'] ?></td>
            <td><?= $e['event_time'] ?></td>
            <?php if ($show_cuts): ?>
              <td><?= htmlspecialchars($e['lcm_cut'] ?? '-') ?></td>
              <td><?= htmlspecialchars($e['scy_cut'] ?? '-') ?></td>
            <?php endif; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endforeach; ?>

<?php $this->start('scripts') ?>

<script>
  document.addEventListener("DOMContentLoaded", function() {
    const strokeSelect = document.getElementById('strokeFilter');
    const ageSelect = document.getElementById('ageFilter');
    const genderSelect = document.getElementById('genderFilter');

    [strokeSelect, ageSelect, genderSelect].forEach(select => {
      select.addEventListener('change', applyFilters);
    });

    function applyFilters() {
      const stroke = strokeSelect.value;
      const age = ageSelect.value;
      const gender = genderSelect.value;

      document.querySelectorAll('tbody tr').forEach(row => {
        const rowStroke = row.getAttribute('data-stroke');
        const rowAge = row.getAttribute('data-age');
        const rowGender = row.getAttribute('data-gender');

        const show =
          (!stroke || rowStroke === stroke) &&
          (!age || rowAge === age) &&
          (!gender || rowGender === gender);

        row.style.display = show ? '' : 'none';
      });
    }
  });
</script>

<?php $this->end() ?>