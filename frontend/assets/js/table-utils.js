function renderTable(tableBodyId, data, columns, actionsConfig = null) {
  const tbody = document.getElementById(tableBodyId);
  if (!tbody) return;
  tbody.innerHTML = '';
  if (data.length === 0) {
    const tr = document.createElement('tr');
    const td = document.createElement('td');
    td.colSpan = columns.length + (actionsConfig ? 1 : 0);
    td.innerText = 'No records found.';
    td.style.textAlign = 'center';
    td.style.padding = '20px';
    td.style.color = '#94A3B8';
    tr.appendChild(td);
    tbody.appendChild(tr);
    return;
  }
  data.forEach((row, index) => {
    const tr = document.createElement('tr');
    columns.forEach(col => {
      const td = document.createElement('td');
      if (col.render) {
        td.innerHTML = col.render(row[col.key], row, index);
      } else {
        td.innerText = row[col.key] !== null ? row[col.key] : '';
      }
      tr.appendChild(td);
    });
    if (actionsConfig) {
      const td = document.createElement('td');
      td.style.display = 'flex';
      td.style.gap = '8px';
      actionsConfig.forEach(act => {
        const btn = document.createElement('button');
        btn.className = `btn btn-sm btn-${act.type || 'primary'}`;
        btn.innerText = act.label;
        btn.onclick = () => act.handler(row);
        td.appendChild(btn);
      });
      tr.appendChild(td);
    }
    tbody.appendChild(tr);
  });
}
