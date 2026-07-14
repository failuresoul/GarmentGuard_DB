const TableUtils = {
  currentSortCol: null,
  currentSortOrder: 'asc',

  // Returns a sorted copy of the data array
  sortData(data, col, order) {
    if (!Array.isArray(data)) return [];
    const sorted = [...data];
    const isAsc = order === 'asc';

    sorted.sort((a, b) => {
      let valA = a[col];
      let valB = b[col];

      // Handle null or undefined values: always put them at the end of the table
      if (valA === undefined || valA === null || valA === '') {
        return valB === undefined || valB === null || valB === '' ? 0 : 1;
      }
      if (valB === undefined || valB === null || valB === '') {
        return -1;
      }

      // Check if values represent Date objects or date strings
      const isDate = (val) => {
        if (val instanceof Date) return true;
        if (typeof val !== 'string') return false;
        // Match Oracle format e.g. "12-Jul-2024" or standard "YYYY-MM-DD"
        return /^\d{1,2}-[A-Za-z]{3}-\d{4}$/.test(val) || /^\d{4}-\d{2}-\d{2}/.test(val);
      };

      const parseDate = (val) => {
        if (val instanceof Date) return val.getTime();
        const str = String(val).trim();
        const months = {
          jan:0, feb:1, mar:2, apr:3, may:4, jun:5,
          jul:6, aug:7, sep:8, oct:9, nov:10, dec:11
        };
        const m = str.match(/^(\d{1,2})-([A-Za-z]{3})-(\d{4})$/);
        if (m) {
          const d = parseInt(m[1], 10);
          const month = months[m[2].toLowerCase()];
          const y = parseInt(m[3], 10);
          if (!isNaN(d) && month !== undefined && !isNaN(y)) {
            return new Date(y, month, d).getTime();
          }
        }
        const parsed = Date.parse(str);
        return isNaN(parsed) ? 0 : parsed;
      };

      if (isDate(valA) && isDate(valB)) {
        const timeA = parseDate(valA);
        const timeB = parseDate(valB);
        return isAsc ? timeA - timeB : timeB - timeA;
      }

      // Check if values represent numbers or numeric strings
      const isNumeric = (val) => {
        if (typeof val === 'number') return true;
        if (typeof val !== 'string') return false;
        return !isNaN(val) && !isNaN(parseFloat(val));
      };

      if (isNumeric(valA) && isNumeric(valB)) {
        const numA = parseFloat(valA);
        const numB = parseFloat(valB);
        return isAsc ? numA - numB : numB - numA;
      }

      // Default string comparison using localeCompare
      const strA = String(valA).toLowerCase();
      const strB = String(valB).toLowerCase();
      return isAsc 
        ? strA.localeCompare(strB, undefined, { numeric: true, sensitivity: 'base' })
        : strB.localeCompare(strA, undefined, { numeric: true, sensitivity: 'base' });
    });

    return sorted;
  },

  // Initialize sorting click listeners on table headers
  initSortHeaders(tableId, data, renderFn) {
    const table = document.getElementById(tableId);
    if (!table) return;

    // Support both data-col and fallback data-sort attributes
    const headers = table.querySelectorAll('th[data-col], th[data-sort]');
    headers.forEach(header => {
      header.classList.add('sortable-header');
      header.style.cursor = 'pointer';

      // Ensure a sort indicator element exists
      let indicator = header.querySelector('.sort-indicator');
      if (!indicator) {
        indicator = document.createElement('span');
        indicator.className = 'sort-indicator';
        indicator.innerHTML = ' ▲▼';
        header.appendChild(indicator);
      }

      // Remove existing sort handlers to prevent double triggering
      if (header._sortHandler) {
        header.removeEventListener('click', header._sortHandler);
      }

      const handler = () => {
        const col = header.getAttribute('data-col') || header.getAttribute('data-sort');
        
        if (TableUtils.currentSortCol === col) {
          TableUtils.currentSortOrder = TableUtils.currentSortOrder === 'asc' ? 'desc' : 'asc';
        } else {
          TableUtils.currentSortCol = col;
          TableUtils.currentSortOrder = 'asc';
        }

        // Reset indicators and classes on all header elements
        headers.forEach(h => {
          h.classList.remove('active');
          const ind = h.querySelector('.sort-indicator');
          if (ind) ind.textContent = ' ▲▼';
        });

        // Highlight current active sorting column
        header.classList.add('active');
        if (indicator) {
          indicator.textContent = TableUtils.currentSortOrder === 'asc' ? ' ▲' : ' ▼';
        }

        // Perform sorting and execute render callback with sorted copy
        const sorted = TableUtils.sortData(data, col, TableUtils.currentSortOrder);
        renderFn(sorted);
      };

      header._sortHandler = handler;
      header.addEventListener('click', handler);
    });
  },

  // Pagination data slice
  paginate(data, page, pageSize) {
    if (!Array.isArray(data)) return { rows: [], total: 0, page, pageSize, totalPages: 0 };
    const total = data.length;
    const totalPages = Math.ceil(total / pageSize);
    const start = (page - 1) * pageSize;
    const end = start + pageSize;
    const rows = data.slice(start, end);
    return { rows, total, page, pageSize, totalPages };
  },

  // Renders the pagination footer layout
  renderPagination(containerId, paginationObj, onPageChange) {
    const container = document.getElementById(containerId);
    if (!container) return;

    const { total, page, pageSize, totalPages } = paginationObj;
    const start = total === 0 ? 0 : (page - 1) * pageSize + 1;
    const end = Math.min(page * pageSize, total);

    let html = `
      <div class="pagination-bar">
        <span class="pagination-info">Showing ${start}–${end} of ${total} results</span>
        <div class="pagination-controls">
    `;

    // Prev button
    const prevDisabled = page <= 1 ? 'disabled' : '';
    html += `<button class="page-btn" ${prevDisabled} data-page="${page - 1}">← Prev</button>`;

    // Page buttons
    // Show direct page buttons
    for (let i = 1; i <= totalPages; i++) {
      const activeClass = i === page ? 'active' : '';
      html += `<button class="page-btn num-btn ${activeClass}" data-page="${i}">${i}</button>`;
    }

    // Next button
    const nextDisabled = page >= totalPages ? 'disabled' : '';
    html += `<button class="page-btn" ${nextDisabled} data-page="${page + 1}">Next →</button>`;

    html += `
        </div>
      </div>
    `;

    container.innerHTML = html;

    // Attach click triggers to buttons
    container.querySelectorAll('.page-btn').forEach(btn => {
      if (btn.hasAttribute('disabled')) return;
      btn.addEventListener('click', () => {
        const newPage = parseInt(btn.getAttribute('data-page'), 10);
        if (!isNaN(newPage)) {
          onPageChange(newPage);
        }
      });
    });
  },

  // Renders the rows-per-page selector
  pageSizeSelector(selectId, onSizeChange) {
    const el = document.getElementById(selectId);
    if (!el) return;

    const options = [10, 25, 50];
    const optionsHtml = options.map(val => `<option value="${val}">${val}</option>`).join('');

    if (el.tagName.toLowerCase() === 'select') {
      el.innerHTML = optionsHtml;
      el.addEventListener('change', (e) => {
        onSizeChange(parseInt(e.target.value, 10));
      });
    } else {
      el.innerHTML = `
        <select class="per-page-select" id="${selectId}-select">
          ${optionsHtml}
        </select>
      `;
      const select = el.querySelector('select');
      select.addEventListener('change', (e) => {
        onSizeChange(parseInt(e.target.value, 10));
      });
    }
  },

  // General filtering handler
  filterData(data, filters) {
    if (!Array.isArray(data)) return [];
    return data.filter(item => {
      for (const key in filters) {
        const filterVal = filters[key];
        // Skip empty filters
        if (filterVal === undefined || filterVal === null || filterVal === '') {
          continue;
        }

        if (key === 'search') {
          const searchStr = String(filterVal).toLowerCase();
          let match = false;
          for (const itemKey in item) {
            const itemVal = item[itemKey];
            if (itemVal !== null && itemVal !== undefined && typeof itemVal !== 'object') {
              if (String(itemVal).toLowerCase().includes(searchStr)) {
                match = true;
                break;
              }
            }
          }
          if (!match) return false;
        } else {
          // Exact matches on other fields
          if (item[key] === undefined || item[key] === null) {
            return false;
          }
          if (String(item[key]).toLowerCase() !== String(filterVal).toLowerCase()) {
            return false;
          }
        }
      }
      return true;
    });
  }
};
