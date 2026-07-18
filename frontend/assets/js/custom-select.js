function initCustomSelects() {
    document.querySelectorAll('select.custom-select-init').forEach(selectElement => {
        if (selectElement.nextElementSibling && selectElement.nextElementSibling.classList.contains('custom-select-wrapper')) {
            selectElement.nextElementSibling.remove();
        }

        const wrapper = document.createElement('div');
        wrapper.className = 'custom-select-wrapper';
        
        const customSelect = document.createElement('div');
        customSelect.className = 'custom-select';
        
        const trigger = document.createElement('div');
        trigger.className = 'custom-select__trigger';
        
        const span = document.createElement('span');
        span.textContent = selectElement.options[selectElement.selectedIndex]?.textContent || 'Select...';
        
        const arrow = document.createElement('div');
        arrow.className = 'arrow';
        
        trigger.appendChild(span);
        trigger.appendChild(arrow);
        
        const optionsContainer = document.createElement('div');
        optionsContainer.className = 'custom-options';
        
        Array.from(selectElement.options).forEach(option => {
            if(option.value === "" && option.disabled) return;
            const customOption = document.createElement('div');
            customOption.className = 'custom-option' + (option.selected ? ' selected' : '');
            customOption.dataset.value = option.value;
            customOption.textContent = option.textContent;
            
            customOption.addEventListener('click', function(e) {
                // Update native select
                selectElement.value = this.dataset.value;
                // Trigger change event for listeners
                selectElement.dispatchEvent(new Event('change'));
                
                // Update UI
                span.textContent = this.textContent;
                optionsContainer.querySelectorAll('.custom-option').forEach(opt => opt.classList.remove('selected'));
                this.classList.add('selected');
                
                customSelect.classList.remove('open');
            });
            
            optionsContainer.appendChild(customOption);
        });
        
        customSelect.appendChild(trigger);
        customSelect.appendChild(optionsContainer);
        wrapper.appendChild(customSelect);
        
        // Hide native select
        selectElement.style.display = 'none';
        selectElement.parentNode.insertBefore(wrapper, selectElement.nextSibling);
        
        trigger.addEventListener('click', function(e) {
            e.stopPropagation();
            document.querySelectorAll('.custom-select').forEach(cs => {
                if(cs !== customSelect) cs.classList.remove('open');
            });
            customSelect.classList.toggle('open');
        });
    });
}

document.addEventListener('click', function() {
    document.querySelectorAll('.custom-select').forEach(cs => {
        cs.classList.remove('open');
    });
});
