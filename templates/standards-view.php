<?php $this->layout('layout', ['title' => $meet_info['meet_name']]) ?>

<nav aria-label="breadcrumb">
  <ol class="breadcrumb mb-4">
    <li class="breadcrumb-item"><a href="<?= $base_url ?>/">Home</a></li>
    <li class="breadcrumb-item"><a href="<?= $base_url ?>/standards">All Standards</a></li>
    <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars(shorten_title($meet_info['meet_name'])) ?></li>
  </ol>
</nav>

<h1><?= $meet_info['meet_name'] ?></h1>
<p><strong>Date:</strong> <?= $meet_info['start_date'] ?> to <?= $meet_info['end_date'] ?></p>

<?php
$show_age_group = array_reduce($parsed_rows, fn($carry, $row) => $carry || isset($row['age_group']), false);
?>

<table id="standardsTable" class="table table-bordered table-hover display responsive nowrap" style="width: 100%;">
  <thead class="table-light">
    <tr>
      <th>Event #</th>
      <?php if ($show_age_group): ?><th>Age Group</th><?php endif; ?>
      <th>Gender</th>
      <th>Distance</th>
      <th>Stroke</th>
      <th>LCM Cut</th>
      <th>SCY Cut</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($parsed_rows as $row): ?>
      <tr>
        <td><?= $row['event_number'] ?></td>
        <?php if ($show_age_group): ?><td><?= $row['age_group'] ?? '-' ?></td><?php endif; ?>
        <td><?= $row['gender'] ?></td>
        <td><?= $row['distance'] ?></td>
        <td><?= $row['stroke'] ?></td>
        <td><?= $row['lcm_cut'] ?></td>
        <td><?= $row['scy_cut'] ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php $this->start('scripts') ?>
<script>
  $(document).ready(function() {
    $('#standardsTable').DataTable({
      responsive: true,
      paging: true,
      pageLength: 25,
      lengthChange: true,
      searching: true,
      ordering: true,
      info: true
    });
  });
</script>
<?php $this->end() ?>