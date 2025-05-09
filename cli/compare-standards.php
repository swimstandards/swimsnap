<?php

if ($argc < 3) {
  echo "Usage: php compare_hyv.php old_file.hyv new_file.hyv\n";
  exit(1);
}

$oldFile = $argv[1];
$newFile = $argv[2];

if (!file_exists($oldFile) || !file_exists($newFile)) {
  echo "One or both files not found.\n";
  exit(1);
}

function parseHyvLines($lines)
{
  $parsed = [];
  foreach ($lines as $line) {
    $fields = explode(';', $line);
    if (count($fields) < 18) continue;
    // Use gender|type|distance|strokeCode as key
    $key = implode('|', [$fields[2], $fields[3], $fields[6], $fields[7]]);
    $parsed[$key] = [
      'lcm' => $fields[9],
      'scy' => $fields[15]
    ];
  }
  return $parsed;
}

// Skip metadata (first line)
$old = parseHyvLines(array_slice(file($oldFile, FILE_IGNORE_NEW_LINES), 1));
$new = parseHyvLines(array_slice(file($newFile, FILE_IGNORE_NEW_LINES), 1));

foreach ($new as $key => $times) {
  if (!isset($old[$key])) {
    echo "🆕 New event added: $key\n";
    echo "     LCM: {$times['lcm']} | SCY: {$times['scy']}\n\n";
    continue;
  }
  if ($old[$key] !== $times) {
    echo "✏️  Changed: $key\n";
    echo "     Old → LCM: {$old[$key]['lcm']} | SCY: {$old[$key]['scy']}\n";
    echo "     New → LCM: {$times['lcm']} | SCY: {$times['scy']}\n\n";
  }
}

foreach ($old as $key => $times) {
  if (!isset($new[$key])) {
    echo "❌ Removed event: $key\n";
    echo "     LCM: {$times['lcm']} | SCY: {$times['scy']}\n\n";
  }
}
