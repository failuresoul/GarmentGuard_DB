// Toast Notification System
function showToast(message, type = 'info') {
  const container = document.getElementById('toast-container') || (() => {
    const el = document.createElement('div');
    el.id = 'toast-container';
    el.style.position = 'fixed';
    el.style.top = '20px';
    el.style.right = '20px';
    el.style.zIndex = '9999';
    el.style.display = 'flex';
    el.style.flexDirection = 'column';
    el.style.gap = '8px';
    el.style.maxWidth = '350px';
    el.style.pointerEvents = 'none';
    document.body.appendChild(el);
    return el;
  })();

  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;
  toast.style.pointerEvents = 'auto';
  toast.style.background = '#ffffff';
  toast.style.color = '#1e293b';
  toast.style.padding = '14px 16px';
  toast.style.borderRadius = '6px';
  toast.style.boxShadow = '0 10px 15px -3px rgba(0, 0, 0, 0.15), 0 4px 6px -2px rgba(0, 0, 0, 0.05)';
  toast.style.display = 'flex';
  toast.style.justifyContent = 'space-between';
  toast.style.alignItems = 'center';
  toast.style.gap = '12px';
  toast.style.fontFamily = "'Outfit', sans-serif";
  toast.style.fontSize = '14px';
  toast.style.fontWeight = '500';
  toast.style.opacity = '0';
  toast.style.transform = 'translateY(-10px)';
  toast.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';

  // Left border color based on type
  const colors = {
    success: '#1D9E75', // green
    error: '#E24B4A',   // red
    warning: '#BA7517', // amber
    info: '#378ADD'     // blue
  };
  toast.style.borderLeft = `4px solid ${colors[type] || colors.info}`;

  // Content span
  const textSpan = document.createElement('span');
  textSpan.innerText = message;
  toast.appendChild(textSpan);

  // Close button
  const closeBtn = document.createElement('button');
  closeBtn.innerHTML = '&times;';
  closeBtn.style.background = 'none';
  closeBtn.style.border = 'none';
  closeBtn.style.fontSize = '18px';
  closeBtn.style.lineHeight = '1';
  closeBtn.style.cursor = 'pointer';
  closeBtn.style.color = '#94a3b8';
  closeBtn.style.transition = 'color 0.2s';
  closeBtn.style.padding = '0';
  closeBtn.style.marginTop = '-2px';
  closeBtn.addEventListener('mouseenter', () => closeBtn.style.color = '#475569');
  closeBtn.addEventListener('mouseleave', () => closeBtn.style.color = '#94a3b8');
  closeBtn.addEventListener('click', () => closeToast(toast));
  toast.appendChild(closeBtn);

  container.appendChild(toast);

  // Trigger animation frame
  requestAnimationFrame(() => {
    toast.style.opacity = '1';
    toast.style.transform = 'translateY(0)';
  });

  // Auto dismiss
  const autoTimeout = setTimeout(() => {
    closeToast(toast);
  }, 4000);

  // Keep track of timeout in case manually closed
  toast.dataset.timeoutId = autoTimeout;
}

function closeToast(el) {
  if (!el) return;
  if (el.dataset.timeoutId) {
    clearTimeout(parseInt(el.dataset.timeoutId, 10));
  }
  el.style.opacity = '0';
  el.style.transform = 'translateY(-10px)';
  el.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
  setTimeout(() => {
    el.remove();
  }, 300);
}

// Replace window.alert with showToast
window.alert = function (message) {
  showToast(message, 'info');
};

// Global Search Bar Injection & Logic
document.addEventListener('DOMContentLoaded', () => {
  const sidebar = document.getElementById('sidebar');
  if (!sidebar) return;

  const brand = sidebar.querySelector('.brand');
  if (brand) {
    const searchContainer = document.createElement('div');
    searchContainer.className = 'global-search-container';
    searchContainer.innerHTML = `
      <input type="text" id="global-search-input" class="global-search-input" placeholder="🔍 Search..." autocomplete="off">
      <div id="global-search-results" class="global-search-results" style="display: none;"></div>
    `;
    brand.parentNode.insertBefore(searchContainer, brand.nextSibling);

    const input = document.getElementById('global-search-input');
    const resultsDiv = document.getElementById('global-search-results');

    function escHtml(s) {
      return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function debounce(func, delay) {
      let timeoutId;
      return function (...args) {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => {
          func.apply(this, args);
        }, delay);
      };
    }

    input.addEventListener('keyup', debounce((e) => {
      if (e.key === 'Escape') {
        resultsDiv.style.display = 'none';
        return;
      }
      const q = input.value.trim();
      if (q.length < 2) {
        resultsDiv.innerHTML = '';
        resultsDiv.style.display = 'none';
        return;
      }

      resultsDiv.innerHTML = '<div class="global-search-loading">Searching...</div>';
      resultsDiv.style.display = 'block';

      fetch(`/backend/api/search.php?q=${encodeURIComponent(q)}`)
        .then(res => res.json())
        .then(data => {
          if (!Array.isArray(data) || data.length === 0) {
            resultsDiv.innerHTML = '<div class="global-search-no-results">No results found</div>';
            return;
          }

          resultsDiv.innerHTML = data.map(item => {
            let icon = '';
            if (item.type === 'factory') icon = '🏢';
            else if (item.type === 'worker') icon = '👤';
            else if (item.type === 'audit') icon = '📋';

            return `
              <div class="global-search-item" data-type="${item.type}" data-id="${item.id}" data-name="${escHtml(item.name)}">
                <div class="title-row">
                  <span class="search-icon ${item.type}-icon">${icon}</span>
                  <span class="name">${escHtml(item.name)}</span>
                </div>
                <div class="subtitle">${escHtml(item.subtitle)}</div>
              </div>
            `;
          }).join('');

          // Attach click listeners to results
          resultsDiv.querySelectorAll('.global-search-item').forEach(el => {
            el.addEventListener('click', () => {
              const type = el.dataset.type;
              const id = el.dataset.id;
              const name = el.dataset.name;
              
              const path = window.location.pathname;
              let prefix = '';
              const parts = path.split('/');
              const hasSubfolder = parts.length > 4 && parts[3] !== 'shared';

              if (!hasSubfolder) {
                prefix = 'admin/';
              }

              if (type === 'factory') {
                window.location.href = prefix + `factory_detail.php?id=${id}`;
              } else if (type === 'worker') {
                window.location.href = prefix + `workers.php?search=${encodeURIComponent(name)}`;
              } else if (type === 'audit') {
                window.location.href = prefix + `audits.php?search=${id}`;
              }
            });
          });
        })
        .catch(() => {
          resultsDiv.innerHTML = '<div class="global-search-error">Error loading results</div>';
        });
    }, 400));

    // Close dropdown on Escape press
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        resultsDiv.style.display = 'none';
      }
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
      if (!input.contains(e.target) && !resultsDiv.contains(e.target)) {
        resultsDiv.style.display = 'none';
      }
    });
  }
});
