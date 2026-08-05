<?php

/** Resource-link helpers. Kept separate from meet metadata so this feature also
 * works in the local JSON fallback used by development installs. */
function normalize_resource_url(string $url): ?string
{
    $url = trim($url);
    if (!preg_match('#^https?://#i', $url)) $url = 'https://' . $url;
    $parts = parse_url($url);
    if (!$parts || !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true) || empty($parts['host'])) return null;
    if (!empty($parts['user']) || !empty($parts['pass'])) return null;

    $scheme = strtolower($parts['scheme']);
    $host = strtolower(rtrim($parts['host'], '.'));
    $port = isset($parts['port']) ? ':' . (int) $parts['port'] : '';
    $path = $parts['path'] ?? '/';
    $path = preg_replace('#/+#', '/', $path) ?: '/';
    $query = isset($parts['query']) && $parts['query'] !== '' ? '?' . $parts['query'] : '';
    return $scheme . '://' . $host . $port . $path . $query;
}

function resource_url_is_public(string $url): bool
{
    $host = parse_url($url, PHP_URL_HOST);
    if (!$host || in_array(strtolower($host), ['localhost', 'localhost.localdomain'], true)) return false;
    $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : (gethostbynamel($host) ?: []);
    if (!$ips) return false;
    foreach ($ips as $ip) {
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) return false;
    }
    return true;
}

function resource_meta_content(DOMXPath $xpath, string $property): string
{
    $nodes = $xpath->query("//meta[translate(@property, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz')='{$property}' or translate(@name, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz')='{$property}']/@content");
    return $nodes && $nodes->length ? trim($nodes->item(0)->nodeValue) : '';
}

function fetch_resource_preview(string $url): array
{
    if (!function_exists('curl_init')) throw new RuntimeException('Preview lookup requires the PHP cURL extension.');
    if (!resource_url_is_public($url)) throw new RuntimeException('Please use a publicly accessible website URL.');

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_USERAGENT => 'SwimSnap Resource Preview/1.0',
        CURLOPT_MAXREDIRS => 0,
        CURLOPT_HTTPHEADER => ['Accept: text/html,application/xhtml+xml'],
    ]);
    $html = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $contentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $error = curl_error($ch);
    curl_close($ch);
    if ($html === false || $status < 200 || $status >= 400) {
        throw new RuntimeException($error ?: 'Could not read a public HTML page at that URL.');
    }

    $contentType = strtolower(trim(explode(';', $contentType)[0]));
    if ($contentType === 'application/pdf' || str_starts_with($contentType, 'image/')) {
        $path = (string) parse_url($url, PHP_URL_PATH);
        $filename = urldecode(basename($path)) ?: parse_url($url, PHP_URL_HOST);
        $kind = $contentType === 'application/pdf' ? 'PDF document' : 'Image';
        return ['url' => $url, 'title' => mb_substr($filename, 0, 180), 'description' => $kind . ' shared from ' . parse_url($url, PHP_URL_HOST), 'image_url' => str_starts_with($contentType, 'image/') ? $url : ''];
    }
    if (stripos($contentType, 'text/html') === false && stripos($contentType, 'application/xhtml+xml') === false) {
        throw new RuntimeException('Please use a public webpage, PDF, or image URL.');
    }

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    // The XML declaration tells DOMDocument the input is UTF-8 without using
    // mb_convert_encoding's deprecated HTML-ENTITIES mode (which leaks a PHP
    // warning into AJAX JSON responses on newer PHP versions).
    $dom->loadHTML('<?xml encoding="UTF-8">' . substr($html, 0, 1000000));
    $xpath = new DOMXPath($dom);
    $title = resource_meta_content($xpath, 'og:title');
    if (!$title) {
        $titles = $xpath->query('//title');
        $title = $titles && $titles->length ? trim($titles->item(0)->textContent) : parse_url($url, PHP_URL_HOST);
    }
    $description = resource_meta_content($xpath, 'og:description') ?: resource_meta_content($xpath, 'description');
    $image = resource_meta_content($xpath, 'og:image');
    if ($image && !parse_url($image, PHP_URL_SCHEME)) {
        $base = parse_url($url);
        $image = ($base['scheme'] ?? 'https') . '://' . $base['host'] . (str_starts_with($image, '/') ? $image : '/' . $image);
    }
    return ['url' => $url, 'title' => mb_substr($title, 0, 180), 'description' => mb_substr($description, 0, 500), 'image_url' => $image];
}

function resource_screenshot_url(string $url): string
{
    // A dependable local fallback. Website preview images use their Open Graph
    // image when available, avoiding paid third-party screenshot services.
    return BASE_URL . '/images/resource-bg.png';
}
