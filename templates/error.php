<?php $this->layout('layout', ['title' => 'Error']) ?>

<div class="alert alert-danger">
  <h4 class="alert-heading">Not Found</h4>
  <p><?= $message ?? 'The page you are looking for could not be found.' ?></p>
</div>

<p>
  <a href="<?= $base_url ?>/" class="btn btn-sm btn-secondary mt-2">Back to Home</a>
</p>