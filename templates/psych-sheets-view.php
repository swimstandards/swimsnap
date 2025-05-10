<?php $this->layout('layout', ['title' => $meet_info['meet_name'] ?? 'Psych Sheet']) ?>

<nav aria-label="breadcrumb">
  <ol class="breadcrumb mb-4">
    <li class="breadcrumb-item"><a href="<?= $base_url ?>/">Home</a></li>
    <li class="breadcrumb-item"><a href="<?= $base_url ?>/psych-sheets">Psych Sheets</a></li>
    <li class="breadcrumb-item active" aria-current="page">
      <?= htmlspecialchars(shorten_title($meet_info['meet_name'] ?? $slug)) ?>
    </li>
  </ol>
</nav>

<h1>Psych Sheet</h1>

<?php include __DIR__ . '/shared/meta-block.php' ?>

<div class="mb-3 d-flex">
  <div class="input-group" style="max-width: 400px;">
    <span class="input-group-text"><i class="bi bi-search"></i></span>
    <input
      type="text"
      id="searchInput"
      class="form-control"
      placeholder="Search by swimmer or team..."
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

<div id="psychSheetAccordionContainer"></div>

<?php $this->start('scripts') ?>
<script>
  const parsedEvents = <?= json_encode($parsed_events) ?>;
  let currentSearchTerm = '';
  const allTables = new Map();

  function normalizeName(name) {
    let norm = name.toLowerCase();
    if (norm.includes(",")) {
      const parts = norm.split(",").map(s => s.trim());
      norm = parts[1] + " " + parts[0];
    }
    return norm;
  }

  function highlightMatch(text) {
    if (!currentSearchTerm || !text) return text;
    const safeTerm = currentSearchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const regex = new RegExp(`(${safeTerm})`, 'ig');
    return text.replace(regex, '<mark>$1</mark>');
  }

  function updateClearIcon() {
    const clearBtn = document.getElementById('clearSearchBtn');
    const inputVal = document.getElementById('searchInput').value.trim();
    clearBtn.style.display = inputVal.length > 0 ? '' : 'none';
  }

  function showLoading() {
    const container = document.getElementById('psychSheetAccordionContainer');
    container.innerHTML = '<div class="text-center p-4">Loading...</div>';
  }

  function buildAccordion(events) {
    const container = document.getElementById('psychSheetAccordionContainer');
    container.innerHTML = '';
    allTables.clear();

    if (!events.length) {
      container.innerHTML = '<div class="alert alert-warning">No results found.</div>';
      return;
    }

    const accordion = document.createElement('div');
    accordion.className = 'accordion';
    accordion.id = 'psychSheetAccordion';

    events.forEach((event, idx) => {
      const card = document.createElement('div');
      card.className = 'accordion-item mb-3';

      const header = document.createElement('h2');
      header.className = 'accordion-header';
      header.id = `heading${idx}`;

      const button = document.createElement('button');
      button.className = 'accordion-button collapsed';
      button.type = 'button';
      button.setAttribute('data-bs-toggle', 'collapse');
      button.setAttribute('data-bs-target', `#collapse${idx}`);
      button.setAttribute('aria-expanded', 'false');
      button.setAttribute('aria-controls', `collapse${idx}`);
      button.textContent = `${event.event_number ? `Event ${event.event_number}: ` : ''}${event.gender} ${event.event_name}`;

      header.appendChild(button);
      card.appendChild(header);

      const collapse = document.createElement('div');
      collapse.id = `collapse${idx}`;
      collapse.className = 'accordion-collapse collapse';
      collapse.setAttribute('aria-labelledby', `heading${idx}`);

      const body = document.createElement('div');
      body.className = 'accordion-body p-2';

      if (event.seeds && event.seeds.length) {
        const table = document.createElement('table');
        table.className = 'table table-sm table-striped align-top';
        table.id = `eventTable${idx}`;

        const thead = document.createElement('thead');
        thead.className = 'table-light';
        thead.innerHTML = `<tr>
          <th>Rank</th>
          ${event.seeds[0].name ? `
            <th>Name</th>
            <th>Age</th>
            <th>Team</th>
            <th>Seed Time</th>` : `
            <th>Team</th>
            <th>Seed Time</th>`
          }
        </tr>`;
        table.appendChild(thead);

        const tbody = document.createElement('tbody');
        event.seeds.forEach(s => {
          const tr = document.createElement('tr');
          tr.innerHTML = `
            <td>${s.rank}</td>
            ${s.name ? `
              <td>${highlightMatch(s.name)}</td>
              <td>${s.age}</td>
              <td>${highlightMatch(s.team)}</td>
              <td>${s.seed_time}</td>` : `
              <td>${highlightMatch(s.team)}</td>
              <td>${s.seed_time}</td>`
            }
          `;
          tbody.appendChild(tr);
        });
        table.appendChild(tbody);

        body.appendChild(table);
      } else {
        body.innerHTML = '<p class="text-muted">No seed data available.</p>';
      }

      collapse.appendChild(body);
      card.appendChild(collapse);
      accordion.appendChild(card);
    });

    container.appendChild(accordion);

    if (!currentSearchTerm) {
      document.querySelectorAll('table[id^="eventTable"]').forEach((table) => {
        const rowCount = table.querySelectorAll('tbody tr').length;
        const usePaging = rowCount > 25;
        const dt = $(table).DataTable({
          responsive: {
            details: false
          },
          paging: usePaging,
          pageLength: 25,
          lengthChange: usePaging,
          searching: true,
          ordering: true,
          info: usePaging,
          autoWidth: false,
          columnDefs: [{
            targets: 0,
            type: 'num'
          }]
        });
        allTables.set(table, dt);
        dt.columns.adjust().draw(false);
      });

      document.querySelectorAll('.accordion-collapse').forEach(panel => {
        panel.addEventListener('shown.bs.collapse', function() {
          document.querySelectorAll('table[id^="eventTable"]').forEach(table => {
            const dt = allTables.get(table);
            if (dt) dt.columns.adjust().responsive.recalc();
          });
        });
      });
    }
  }

  function handleSearch() {
    const raw = document.getElementById('searchInput').value.trim().toLowerCase();
    currentSearchTerm = raw;
    updateClearIcon();
    const tokens = raw.split(/\s+/).filter(Boolean);
    if (!tokens.length) {
      showLoading();
      setTimeout(() => {
        buildAccordion(parsedEvents);
      }, 50);
      return;
    }

    const filteredEvents = parsedEvents.map(event => {
      const matchedSeeds = (event.seeds || []).filter(seed => {
        const text = (seed.name || seed.team || '').toLowerCase();
        const normName = normalizeName(text);
        return tokens.every(t => normName.includes(t) || text.includes(t));
      });

      if (matchedSeeds.length) {
        return {
          ...event,
          seeds: matchedSeeds
        };
      }
      return null;
    }).filter(Boolean);

    showLoading();
    setTimeout(() => {
      buildAccordion(filteredEvents);
    }, 50);
  }

  function debounce(func, wait) {
    let timeout;
    return function(...args) {
      clearTimeout(timeout);
      timeout = setTimeout(() => func.apply(this, args), wait);
    };
  }

  document.addEventListener("DOMContentLoaded", function() {
    showLoading();
    setTimeout(() => {
      buildAccordion(parsedEvents);
    }, 50);


    const searchInput = document.getElementById('searchInput');
    const clearBtn = document.getElementById('clearSearchBtn');
    searchInput.addEventListener('input', debounce(handleSearch, 300));
    clearBtn.addEventListener('click', function() {
      searchInput.value = '';
      currentSearchTerm = '';
      updateClearIcon();
      handleSearch();
    });
  });
</script>
<?php $this->end() ?>