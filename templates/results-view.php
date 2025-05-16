<?php $this->layout('layout', [
  'title' => 'Results',
  'meta_title' => ($meet_info['sheet_name'] ?? 'Results') . ' – ' . ($meet_info['meet_name'] ?? 'Swim Meet'),
  'meta_description' => 'View detailed results for ' . ($meet_info['meet_name'] ?? 'this meet') . ', including prelims, finals, times, and placements.',
  'meta_canonical_url' => $base_url . '/results/' . $slug
]) ?>

<nav aria-label="breadcrumb">
  <ol class="breadcrumb mb-4">
    <li class="breadcrumb-item"><a href="<?= $base_url ?>/">Home</a></li>
    <li class="breadcrumb-item"><a href="<?= $base_url ?>/results">Results</a></li>
    <li class="breadcrumb-item active" aria-current="page">
      <?= htmlspecialchars($meet_info['meet_name'] ?? $slug) ?>
    </li>
  </ol>
</nav>

<h1><?= htmlspecialchars($meet_info['sheet_name'] ? 'Meet Results – ' . $meet_info['sheet_name'] : 'Meet Results') ?></h1>

<?php include __DIR__ . '/shared/note-report-block.php' ?>
<?php include __DIR__ . '/shared/meta-block.php' ?>


<div class="mb-3 d-flex">
  <div class="input-group" style="max-width: 400px;">
    <span class="input-group-text"><i class="bi bi-search"></i></span>
    <input
      type="text"
      id="searchInput"
      class="form-control"
      placeholder="Search by event, swimmer or team..."
      autocomplete="off">
    <button
      type="button"
      class="btn btn-outline-secondary"
      id="clearSearchBtn"
      title="Clear"
      style="display: none;">
      <i class="bi bi-x-lg"></i>
    </button>
  </div>
</div>

<div class="accordion resultsContainer" id="resultsAccordion"></div>

<?php $this->start('scripts') ?>
<script>
  document.addEventListener("DOMContentLoaded", function() {
    const accordion = document.getElementById('resultsAccordion');
    const searchInput = document.getElementById('searchInput');
    const clearSearchBtn = document.getElementById('clearSearchBtn');
    let activePopover = null;
    const allTables = new Map();
    const originalData = <?= json_encode($results['events']) ?>;
    let currentData = [...originalData];
    let currentSearchTerm = '';

    function showLoading() {
      accordion.innerHTML = '<div class="text-center p-4">Loading...</div>';
    }

    function timeToSeconds(time) {
      if (time.includes(':')) {
        const [min, sec] = time.split(':').map(parseFloat);
        return min * 60 + sec;
      }
      return parseFloat(time);
    }

    function formatTime(seconds) {
      if (seconds >= 60) {
        const min = Math.floor(seconds / 60);
        const sec = (seconds % 60).toFixed(2).padStart(5, '0');
        return `${min}:${sec}`;
      }
      return seconds.toFixed(2);
    }

    function highlightMatch(text) {
      if (!currentSearchTerm || !text) return text;
      const safeTerm = currentSearchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
      const regex = new RegExp(`(${safeTerm})`, 'ig');
      return text.replace(regex, '<mark>$1</mark>');
    }

    function renderAccordion(events) {
      $('.dataTable').DataTable?.().destroy?.();
      accordion.innerHTML = '';
      allTables.clear();

      if (events.length === 0) {
        accordion.innerHTML = '<div class="text-center p-5 text-muted">No results found. Please try a different search.</div>';
        return;
      }

      const isFiltered = currentSearchTerm.length > 0;

      events.forEach((event, idx) => {
        const rounds = {};
        event.results.forEach(r => {
          const round = r.round || 'Prelim';
          if (!rounds[round]) rounds[round] = [];
          rounds[round].push(r);
        });

        let item = `
      <div class="accordion-item mb-3">
        <h2 class="accordion-header" id="heading${idx}">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse${idx}" aria-expanded="false" aria-controls="collapse${idx}">
            ${event.event_number ? `Event ${event.event_number}: ` : ''}${event.gender} ${event.event_name}
          </button>
        </h2>
        <div id="collapse${idx}" class="accordion-collapse collapse" aria-labelledby="heading${idx}">
          <div class="accordion-body p-2">
      `;

        for (const [roundName, results] of Object.entries(rounds)) {
          item += `
        <h5 class="mt-1 mb-3">${roundName}</h5>
        <div class="table-responsive">
          <table class="table table-sm table-striped align-middle" id="eventTable${idx}-${roundName.replace(/\s/g, '')}">
            <thead class="table-light">
              <tr><th>Place</th>`;

          if (results[0].relay) {
            item += `<th>Team</th><th>Relay</th><th>Seed Time</th><th>Finals Time</th><th>Points</th>`;
          } else if (roundName === "Finals") {
            item += `<th>Name</th><th>Age</th><th>Team</th><th>Seed Time</th><th>${roundName} Time</th><th>Points</th>`;
          } else {
            item += `<th>Name</th><th>Age</th><th>Team</th><th>Seed Time</th><th>${roundName} Time</th><th>Note</th>`;
          }

          item += `</tr></thead><tbody>`;

          results.forEach(r => {
            item += `<tr><td>${r.rank ?? '—'}</td>`;

            if (r.relay) {
              item += `<td>${highlightMatch(r.team ?? '')}</td><td>${r.relay ?? ''}</td><td>${r.seed_time ?? ''}</td><td>`;
              if (r.splits?.length) {
                item += `<button type="button" class="show-splits" data-splits='${JSON.stringify(r.splits)}' style="all: unset; cursor: pointer; text-decoration: underline; color: inherit;">${r.finals_time ?? r.result_time ?? ''}</button>`;
              } else {
                item += `${r.finals_time ?? r.result_time ?? ''}`;
              }
              item += `</td><td>${r.points ?? ''}</td>`;
            } else {
              item += `<td>${highlightMatch(r.name ?? '')}</td><td>${r.age ?? ''}</td><td>${highlightMatch(r.team ?? '')}</td><td>${r.seed_time ?? ''}</td><td>`;
              if (r.splits?.length) {
                item += `<button type="button" class="show-splits" data-splits='${JSON.stringify(r.splits)}' style="all: unset; cursor: pointer; text-decoration: underline; color: inherit;">${r.result_time ?? ''}</button>`;
              } else {
                item += `${r.result_time ?? ''}`;
              }
              item += `</td><td>${r.points ?? (r.qualified ? 'q' : (r.note ?? ''))}</td>`;
            }
            item += `</tr>`;
          });

          item += `</tbody></table></div>`;
        }

        item += `</div></div></div>`;
        accordion.innerHTML += item;
      });

      if (!isFiltered) {
        document.querySelectorAll('table[id^="eventTable"]').forEach((table) => {
          const rowCount = table.querySelectorAll('tbody tr').length;
          const enablePagination = rowCount >= 25;

          const dt = $(table).DataTable({
            responsive: true,
            paging: enablePagination,
            searching: enablePagination,
            lengthChange: enablePagination,
            info: enablePagination,
            pageLength: 25,
            ordering: true,
            autoWidth: false,
            columnDefs: [{
                targets: 0,
                type: 'num'
              },
              {
                targets: 1,
                type: 'num'
              }
            ]
          });

          allTables.set(table, dt);
          dt.columns.adjust().draw(false);
        });
      }
    }

    function updateClearIcon() {
      if (searchInput.value.trim() === '') {
        clearSearchBtn.style.display = 'none';
      } else {
        clearSearchBtn.style.display = '';
      }
    }

    function handleSearch() {
      const term = searchInput.value.trim().toLowerCase();
      currentSearchTerm = term;
      updateClearIcon();
      showLoading();
      setTimeout(() => {
        if (term === '') {
          currentData = [...originalData];
          renderAccordion(currentData);
          return;
        }

        const tokens = term.split(/\s+/).filter(Boolean);

        const filteredEvents = originalData.map(event => {
          const eventText = `${event.gender || ''} ${event.event_name || ''}`.toLowerCase();
          const eventMatches = tokens.every(t => eventText.includes(t));

          const filteredResults = event.results.filter(r => {
            const rawName = (r.name ?? '').toLowerCase();
            let normalizedName = rawName;
            if (normalizedName.includes(",")) {
              const [last, first] = normalizedName.split(",").map((s) => s.trim());
              normalizedName = `${first} ${last}`;
            }
            const team = (r.team ?? '').toLowerCase();
            return tokens.every(t =>
              normalizedName.includes(t) || team.includes(t)
            );
          });

          if (filteredResults.length || eventMatches) {
            return {
              ...event,
              results: eventMatches && !filteredResults.length ? event.results : filteredResults
            };
          }
          return null;
        }).filter(Boolean);

        currentData = filteredEvents;
        renderAccordion(filteredEvents);
      }, 200);
    }

    function debounce(func, wait) {
      let timeout;
      return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
      };
    }

    searchInput.addEventListener('input', debounce(handleSearch, 300));

    clearSearchBtn.addEventListener('click', function() {
      searchInput.value = '';
      updateClearIcon();
      handleSearch();
    });

    document.body.addEventListener('click', function(e) {
      const btn = e.target.closest('.show-splits');
      if (btn) {
        e.preventDefault();
        if (activePopover && activePopover !== btn) {
          bootstrap.Popover.getInstance(activePopover)?.dispose();
          activePopover = null;
        }

        const splitsData = btn.getAttribute('data-splits');
        if (!splitsData) return;

        let splits = [];
        try {
          splits = JSON.parse(splitsData);
        } catch (err) {
          console.error('Invalid splits JSON', err);
          return;
        }
        if (!splits.length) return;

        let html = `
        <div style="max-width:250px; max-height:200px; overflow-y:scroll; overflow-x:auto;">
          <table class="table table-sm table-bordered mb-0">
            <thead><tr><th>Distance</th><th>Split</th><th>Cumulative</th></tr></thead>
            <tbody>
      `;
        let cumulative = 0;
        let distanceCounter = 0;

        splits.forEach((split) => {
          if (/^\d{1,2}(\.\d{2})?$/.test(split)) {
            const splitSeconds = timeToSeconds(split);
            cumulative += splitSeconds;
            distanceCounter += 50;
            html += `<tr><td>${distanceCounter}</td><td>${formatTime(splitSeconds)}</td><td>${formatTime(cumulative)}</td></tr>`;
          }
        });

        html += '</tbody></table></div>';

        bootstrap.Popover.getInstance(btn)?.dispose();
        new bootstrap.Popover(btn, {
          content: html,
          html: true,
          placement: 'top',
          trigger: 'focus',
          container: 'body',
          sanitize: false
        }).show();

        activePopover = btn;
        btn.focus();
      } else {
        if (activePopover) {
          bootstrap.Popover.getInstance(activePopover)?.dispose();
          activePopover = null;
        }
      }
    });

    showLoading();
    setTimeout(() => {
      renderAccordion(originalData);
    }, 50);

  });
</script>
<?php $this->end() ?>