(function () {
    const ROWS_PER_PAGE = 5;
    const paginatedTables = new WeakMap();

    function debounce(fn, delay) {
        let timeout;
        return function (...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => fn.apply(this, args), delay);
        };
    }

    function getDataRows(tbody) {
        return Array.from(tbody.querySelectorAll(":scope > tr")).filter((row) => {
            const cell = row.querySelector("td");
            const text = row.textContent.trim().toLowerCase();
            return !(row.children.length === 1 && cell && cell.hasAttribute("colspan") && (
                text.includes("no ") || text.includes("empty") || text.includes("found")
            ));
        });
    }

    function renderTablePage(table) {
        const state = paginatedTables.get(table);
        if (!state) return;

        const rows = getDataRows(state.tbody);
        const totalPages = Math.max(1, Math.ceil(rows.length / ROWS_PER_PAGE));
        state.page = Math.min(Math.max(1, state.page), totalPages);

        if (rows.length <= ROWS_PER_PAGE) {
            rows.forEach((row) => row.hidden = false);
            state.controls.hidden = true;
            return;
        }

        const start = (state.page - 1) * ROWS_PER_PAGE;
        const end = start + ROWS_PER_PAGE;

        rows.forEach((row, index) => {
            row.hidden = index < start || index >= end;
        });

        state.controls.hidden = false;
        state.label.textContent = state.page + " of " + totalPages;
        state.prev.disabled = state.page === 1;
        state.next.disabled = state.page === totalPages;
    }

    function ensureTablePagination(table) {
        if (!table || table.dataset.noPagination === "true") return;

        const tbody = table.querySelector("tbody");
        if (!tbody) return;

        const rows = getDataRows(tbody);
        if (!rows.length) return;

        let state = paginatedTables.get(table);
        if (!state) {
            const controls = document.createElement("div");
            controls.className = "qyd-table-pagination";
            controls.innerHTML =
                '<button type="button" class="qyd-page-btn qyd-page-prev" aria-label="Previous page"><i class="fa-solid fa-chevron-left"></i></button>' +
                '<span class="qyd-page-label">1 of 1</span>' +
                '<button type="button" class="qyd-page-btn qyd-page-next" aria-label="Next page"><i class="fa-solid fa-chevron-right"></i></button>';

            table.insertAdjacentElement("afterend", controls);

            state = {
                tbody,
                controls,
                label: controls.querySelector(".qyd-page-label"),
                prev: controls.querySelector(".qyd-page-prev"),
                next: controls.querySelector(".qyd-page-next"),
                page: 1,
                observer: null
            };

            state.prev.addEventListener("click", () => {
                state.page -= 1;
                renderTablePage(table);
            });

            state.next.addEventListener("click", () => {
                state.page += 1;
                renderTablePage(table);
            });

            state.observer = new MutationObserver(debounce(() => {
                state.page = 1;
                renderTablePage(table);
            }, 60));
            state.observer.observe(tbody, { childList: true, subtree: false });

            paginatedTables.set(table, state);
        } else {
            state.tbody = tbody;
        }

        renderTablePage(table);
    }

    function initPagination(root = document) {
        root.querySelectorAll(".table-container table").forEach(ensureTablePagination);
    }

    function initLiveSearchForms(root = document) {
        root.querySelectorAll('form[method="GET"], form[method="get"]').forEach((form) => {
            const search = form.querySelector('input[name="search"]');
            if (!search || form.dataset.qydSearchReady === "true") return;

            form.dataset.qydSearchReady = "true";
            search.autocomplete = search.autocomplete || "off";

            search.addEventListener("input", debounce(() => {
                if (search.value.trim().length === 0 || search.value.trim().length >= 2) {
                    if (form.requestSubmit) {
                        form.requestSubmit();
                    } else {
                        form.submit();
                    }
                }
            }, 650));
        });
    }

    function isPlainPageLink(link) {
        if (!link.href || link.target || link.hasAttribute("download")) return false;
        if (link.href.startsWith("javascript:") || link.getAttribute("href") === "#") return false;
        if (link.closest("[data-no-transition]")) return false;

        const url = new URL(link.href, window.location.href);
        return url.origin === window.location.origin && url.pathname !== window.location.pathname + "#";
    }

    function initPageTransitions() {
        document.body.classList.add("page-ready");

        document.addEventListener("click", function (event) {
            const link = event.target.closest("a");
            if (!link || event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
            if (!isPlainPageLink(link)) return;

            event.preventDefault();
            document.body.classList.add("page-leaving");
            setTimeout(() => {
                window.location.href = link.href;
            }, 120);
        });
    }

    document.addEventListener("DOMContentLoaded", function () {
        initPageTransitions();
        initPagination();
        initLiveSearchForms();

        window.QydentraUI = {
            refreshPagination: initPagination,
            refreshSearchForms: initLiveSearchForms
        };
    });
})();
