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
          <tr>
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