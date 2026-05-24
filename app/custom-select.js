/**
 * Modern Custom Select Dropdown
 * Auto-transforms all native <select> elements into modern dropdowns
 * with clean list items and blue checkmark for selected option.
 */
(function() {
    'use strict';

    function initCustomSelects() {
        document.querySelectorAll('select:not([data-custom-initialized])').forEach(function(nativeSelect) {
            // Skip selects that are inside already-built custom wrappers
            if (nativeSelect.closest('.custom-select-wrapper')) return;
            // Skip hidden selects
            if (nativeSelect.type === 'hidden') return;

            nativeSelect.setAttribute('data-custom-initialized', 'true');

            // Get options from native select
            var options = [];
            nativeSelect.querySelectorAll('option').forEach(function(opt) {
                options.push({
                    value: opt.value,
                    text: opt.textContent.trim(),
                    selected: opt.selected
                });
            });

            if (options.length === 0) return;

            // Find current selected
            var currentSelected = options.find(function(o) { return o.selected; }) || options[0];

            // Determine sizing from existing classes
            var isSmall = nativeSelect.classList.contains('text-xs');
            var isFullWidth = nativeSelect.classList.contains('w-full');

            // Build wrapper
            var wrapper = document.createElement('div');
            wrapper.className = 'cs-wrapper relative' + (isFullWidth ? ' w-full' : ' inline-block');

            // Build trigger button
            var trigger = document.createElement('button');
            trigger.type = 'button';
            
            // Start with base functional classes
            var baseClasses = 'cs-trigger flex items-center justify-between gap-5 cursor-pointer transition-all focus:outline-none custom-select';
            
            // Inherit styles from native select (bg, border, p, rounded, text size, width)
            var nativeClasses = Array.from(nativeSelect.classList).join(' ');
            
            // Combine and set
            trigger.className = baseClasses + ' ' + nativeClasses;
            
            // Force remove background images (chevrons) inherited from native classes
            trigger.style.backgroundImage = 'none';
            trigger.style.padding = '10px 10px 10px 16px';
            
            // Ensure width is handled if not in native classes
            if (isFullWidth && !trigger.className.includes('w-full')) trigger.className += ' w-full';
            
            var labelSpan = document.createElement('span');
            labelSpan.className = 'cs-label text-gray-900 truncate text-left';
            labelSpan.textContent = currentSelected.text;
            
            var arrowSvg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            arrowSvg.setAttribute('class', 'cs-arrow w-4 h-4 text-gray-400 shrink-0 transition-transform');
            arrowSvg.setAttribute('fill', 'none');
            arrowSvg.setAttribute('stroke', 'currentColor');
            arrowSvg.setAttribute('viewBox', '0 0 24 24');
            var arrowPath = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            arrowPath.setAttribute('stroke-linecap', 'round');
            arrowPath.setAttribute('stroke-linejoin', 'round');
            arrowPath.setAttribute('stroke-width', '2');
            arrowPath.setAttribute('d', 'M19 9l-7 7-7-7');
            arrowSvg.appendChild(arrowPath);

            trigger.appendChild(labelSpan);
            trigger.appendChild(arrowSvg);

            // Build dropdown panel
            var dropdown = document.createElement('div');
            dropdown.className = 'cs-dropdown hidden absolute top-full left-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-lg z-[100] py-1 max-h-[280px] overflow-y-auto'
                + (isFullWidth ? ' w-full min-w-full' : ' min-w-[180px]');

            // Build option items
            options.forEach(function(opt) {
                var optDiv = document.createElement('div');
                optDiv.className = 'cs-option flex items-center justify-between px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 cursor-pointer transition-colors';
                optDiv.setAttribute('data-value', opt.value);

                var optText = document.createElement('span');
                optText.textContent = opt.text;
                optDiv.appendChild(optText);

                if (opt.value === currentSelected.value) {
                    optDiv.appendChild(createCheckmark());
                }

                optDiv.addEventListener('click', function() {
                    selectOption(nativeSelect, wrapper, opt.value, opt.text);
                });

                dropdown.appendChild(optDiv);
            });

            // Toggle dropdown on trigger click
            trigger.addEventListener('click', function(e) {
                e.stopPropagation();
                closeAllDropdowns(dropdown);
                var isHidden = dropdown.classList.contains('hidden');
                dropdown.classList.toggle('hidden');
                if (!isHidden) {
                    arrowSvg.style.transform = '';
                } else {
                    arrowSvg.style.transform = 'rotate(180deg)';
                }
            });

            // Assemble
            wrapper.appendChild(trigger);
            wrapper.appendChild(dropdown);

            // Hide native select and insert custom one
            nativeSelect.style.display = 'none';
            nativeSelect.parentNode.insertBefore(wrapper, nativeSelect.nextSibling);
        });
    }

    function createCheckmark() {
        var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('class', 'cs-check w-5 h-5 text-blue-600 shrink-0');
        svg.setAttribute('fill', 'currentColor');
        svg.setAttribute('viewBox', '0 0 24 24');
        var circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        circle.setAttribute('cx', '12');
        circle.setAttribute('cy', '12');
        circle.setAttribute('r', '10');
        var path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path.setAttribute('d', 'M9 12l2 2 4-4');
        path.setAttribute('stroke', 'white');
        path.setAttribute('stroke-width', '2');
        path.setAttribute('fill', 'none');
        svg.appendChild(circle);
        svg.appendChild(path);
        return svg;
    }

    function selectOption(nativeSelect, wrapper, value, text) {
        // Update native select
        nativeSelect.value = value;

        // Trigger change event
        var event = new Event('change', { bubbles: true });
        nativeSelect.dispatchEvent(event);

        // Update label
        var label = wrapper.querySelector('.cs-label');
        label.textContent = text;

        // Update checkmarks
        wrapper.querySelectorAll('.cs-option').forEach(function(opt) {
            var existing = opt.querySelector('.cs-check');
            if (existing) existing.remove();

            if (opt.getAttribute('data-value') === value) {
                opt.appendChild(createCheckmark());
            }
        });

        // Close dropdown
        var dropdown = wrapper.querySelector('.cs-dropdown');
        dropdown.classList.add('hidden');
        var arrow = wrapper.querySelector('.cs-arrow');
        if (arrow) arrow.style.transform = '';

        // If native select had onchange attribute, it was already fired via dispatchEvent
        // Also handle form auto-submit for selects with onchange="this.form.submit()"
        var onchangeAttr = nativeSelect.getAttribute('onchange');
        if (onchangeAttr && onchangeAttr.includes('this.form.submit()')) {
            var form = nativeSelect.closest('form');
            if (form) form.submit();
        }
    }

    function closeAllDropdowns(except) {
        document.querySelectorAll('.cs-dropdown').forEach(function(d) {
            if (d !== except) {
                d.classList.add('hidden');
                var arrow = d.parentNode.querySelector('.cs-arrow');
                if (arrow) arrow.style.transform = '';
            }
        });
    }

    // Close all on outside click
    document.addEventListener('click', function() {
        closeAllDropdowns(null);
    });

    // Init on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCustomSelects);
    } else {
        initCustomSelects();
    }

    // Expose for dynamic content
    window.initCustomSelects = initCustomSelects;
})();
