<?php $this->layout('layout', ['title' => $meet_info['sheet_name'] ?? 'Heat Sheet']) ?>

<nav aria-label="breadcrumb">
  <ol class="breadcrumb mb-4">
    <li class="breadcrumb-item"><a href="<?= $base_url ?>/">Home</a></li>
    <li class="breadcrumb-item"><a href="<?= $base_url ?>/heat-sheets">Heat Sheets</a></li>
    <li class="breadcrumb-item active" aria-current="page">
      <?= htmlspecialchars(shorten_title($meet_info['sheet_name'] ?? $meet_info['meet_name'])) ?>
    </li>
  </ol>
</nav>

<h1><?= htmlspecialchars($meet_info['sheet_name'] ? 'Heat Sheets – ' . $meet_info['sheet_name'] : 'Meet Sheets') ?></h1>
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

<div id="meetProgramAccordionContainer"></div>

<?php $this->start('scripts') ?>
<script>
  const parsedEvents = <?= json_encode($parsed_data['events']) ?>;
  let currentSearchTerm = '';

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

  function buildAccordion(events) {
    const container = document.getElementById('meetProgramAccordionContainer');
    container.innerHTML = '';

    if (!events.length) {
      container.innerHTML = '<div class="alert alert-warning">No results found.</div>';
      return;
    }

    const accordion = document.createElement('div');
    accordion.className = 'accordion';
    accordion.id = 'eventAccordion';

    events.forEach((event, index) => {
      const card = document.createElement('div');
      card.className = 'accordion-item';

      const header = document.createElement('h2');
      header.className = 'accordion-header';
      header.id = `heading${index}`;

      const button = document.createElement('button');
      button.className = 'accordion-button collapsed';
      button.type = 'button';
      button.setAttribute('data-bs-toggle', 'collapse');
      button.setAttribute('data-bs-target', `#collapse${index}`);
      button.setAttribute('aria-expanded', 'false');
      button.setAttribute('aria-controls', `collapse${index}`);
      button.textContent = `Event ${event.event_number}: ${event.gender} ${event.event_name}`;
      header.appendChild(button);
      card.appendChild(header);

      const collapse = document.createElement('div');
      collapse.id = `collapse${index}`;
      collapse.className = 'accordion-collapse collapse';
      collapse.setAttribute('aria-labelledby', `heading${index}`);

      const body = document.createElement('div');
      body.className = 'accordion-body';

      event.heats.forEach(heat => {
        let swimmers = heat.swimmers || [];
        let teams = heat.teams || [];

        if (currentSearchTerm) {
          swimmers = swimmers.filter(s =>
            normalizeName(s.name).includes(currentSearchTerm) ||
            (s.team && s.team.toLowerCase().includes(currentSearchTerm))
          );
          teams = teams.filter(t =>
            t.team_name && t.team_name.toLowerCase().includes(currentSearchTerm)
          );
        }

        if (!swimmers.length && !teams.length) return;

        const heatTitle = document.createElement('h5');
        heatTitle.className = 'mt-3';
        heatTitle.textContent = `Heat ${heat.heat_number}`;
        body.appendChild(heatTitle);

        if (swimmers.length) {
          const table = document.createElement('table');
          table.className = 'table table-bordered table-sm';

          table.innerHTML = `
            <thead class="table-light">
              <tr>
                <th>Lane</th>
                <th>Name</th>
                <th>Age</th>
                <th>Team</th>
                <th>Seed Time</th>
              </tr>
            </thead>
            <tbody>
              ${swimmers.map(s => `
                <tr>
                  <td>${s.lane}</td>
                  <td>${highlightMatch(s.name)}</td>
                  <td>${s.age}</td>
                  <td>${highlightMatch(s.team)}</td>
                  <td>${s.seed_time}</td>
                </tr>
              `).join('')}
            </tbody>
          `;
          body.appendChild(table);
        } else if (teams.length) {
          const table = document.createElement('table');
          table.className = 'table table-bordered table-sm';

          table.innerHTML = `
            <thead class="table-light">
              <tr>
                <th>Lane</th>
                <th>Team Name</th>
                <th>Relay</th>
                <th>Seed Time</th>
              </tr>
            </thead>
            <tbody>
              ${teams.map(t => `
                <tr>
                  <td>${t.lane}</td>
                  <td>${highlightMatch(t.team_name)}</td>
                  <td>${t.relay_team}</td>
                  <td>${t.seed_time}</td>
                </tr>
              `).join('')}
            </tbody>
          `;
          body.appendChild(table);
        }
      });

      collapse.appendChild(body);
      card.appendChild(collapse);
      accordion.appendChild(card);
    });

    container.appendChild(accordion);
  }

  function handleSearch() {
    const raw = document.getElementById('searchInput').value.trim().toLowerCase();
    currentSearchTerm = raw;
    updateClearIcon();

    if (!raw) {
      buildAccordion(parsedEvents);
      return;
    }

    const tokens = raw.split(/\s+/).filter(Boolean);
    const filteredEvents = parsedEvents.map(event => {
      const filteredHeats = (event.heats || []).map(heat => {
        const swimmers = (heat.swimmers || []).filter(s => {
          const name = normalizeName(s.name);
          const team = s.team ? s.team.toLowerCase() : '';
          return tokens.every(t => name.includes(t) || team.includes(t));
        });
        const teams = (heat.teams || []).filter(t => {
          const team = t.team_name ? t.team_name.toLowerCase() : '';
          return tokens.every(tk => team.includes(tk));
        });
        return (swimmers.length || teams.length) ? {
          ...heat,
          swimmers,
          teams
        } : null;
      }).filter(Boolean);

      return filteredHeats.length ? {
        ...event,
        heats: filteredHeats
      } : null;
    }).filter(Boolean);

    buildAccordion(filteredEvents);
  }

  function debounce(func, wait) {
    let timeout;
    return function(...args) {
      clearTimeout(timeout);
      timeout = setTimeout(() => func.apply(this, args), wait);
    };
  }

  document.addEventListener("DOMContentLoaded", function() {
    buildAccordion(parsedEvents);
    const searchInput = document.getElementById('searchInput');
    const clearBtn = document.getElementById('clearSearchBtn');
    searchInput.addEventListener('input', debounce(handleSearch, 300));
    clearBtn.addEventListener('click', function() {
      searchInput.value = '';
      currentSearchTerm = '';
      updateClearIcon();
      buildAccordion(parsedEvents);
    });
  });
</script>
<?php $this->end() ?>