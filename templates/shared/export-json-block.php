<?php
$export_payload = [
  'schema_version' => 1,
  'document' => [
    'slug' => $slug,
    'type' => $export_type,
    'metadata' => $meet_info,
    'data' => $export_data,
  ],
];

$export_json = json_encode(
  $export_payload,
  JSON_UNESCAPED_SLASHES |
    JSON_HEX_TAG |
    JSON_HEX_AMP |
    JSON_HEX_APOS |
    JSON_HEX_QUOT
);
?>

<?php if ($export_json !== false): ?>
  <script type="application/json" id="exportJsonData"><?= $export_json ?></script>
  <script>
    (function() {
      const actions = document.getElementById('documentActions');
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'btn btn-sm btn-outline-secondary';
      button.id = 'exportJsonBtn';
      button.title = 'Export JSON';
      button.setAttribute('aria-label', 'Export JSON');
      button.innerHTML = '<i class="bi bi-download"></i>';

      if (actions) {
        const copyButton = document.getElementById('copyLinkBtn');
        copyButton?.insertAdjacentElement('afterend', button);
      } else {
        const fallbackActions = document.createElement('div');
        fallbackActions.className = 'mb-4';
        fallbackActions.appendChild(button);
        document.getElementById('exportJsonData').insertAdjacentElement('beforebegin', fallbackActions);
      }

      button.addEventListener('click', function() {
        const dataElement = document.getElementById('exportJsonData');
        const payload = JSON.parse(dataElement.textContent);
        payload.exported_at = new Date().toISOString();
        payload.source_url = window.location.href;

        const json = JSON.stringify(payload, null, 2) + '\n';
        const blob = new Blob([json], {
          type: 'application/json;charset=utf-8'
        });
        const downloadUrl = URL.createObjectURL(blob);
        const link = document.createElement('a');
        const safeSlug = String(payload.document.slug || 'swimsnap-document')
          .replace(/[^a-z0-9-]+/gi, '-')
          .replace(/^-+|-+$/g, '');

        link.href = downloadUrl;
        link.download = `${safeSlug || 'swimsnap-document'}.json`;
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(downloadUrl);
      });
    })();
  </script>
<?php endif; ?>
