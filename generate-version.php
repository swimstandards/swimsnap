<?php
// generate-version.php

$version = 'local'; // default fallback

if (is_dir(__DIR__ . '/.git')) {
    // Try tag first
    $tag = shell_exec('git describe --tags --abbrev=0 2>/dev/null');
    if (is_string($tag) && trim($tag)) {
        $version = trim($tag);
    } else {
        // Fall back to branch name
        $branch = shell_exec('git rev-parse --abbrev-ref HEAD 2>/dev/null');
        if (is_string($branch) && trim($branch)) {
            $version = 'branch:' . trim($branch);
        }
    }
}

$path = __DIR__ . '/version.php';
$content = "<?php\n\$build_version = '" . addslashes($version) . "';\n";

if (file_put_contents($path, $content) !== false) {
    echo "✅ version.php generated with version: $version\n";
} else {
    fwrite(STDERR, "❌ Failed to write version.php\n");
    exit(1);
}
