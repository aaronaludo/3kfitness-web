document.addEventListener("DOMContentLoaded", function () {
    let sortState = {};

    document.querySelectorAll(".sortable").forEach(th => {
        th.addEventListener("click", function () {
            let column = this.dataset.column;
            let icon = this.querySelector("i");

            if (!sortState[column]) {
                sortState[column] = 'asc';
                icon.className = "text-primary fa fa-sort-up";
            } else if (sortState[column] === 'asc') {
                sortState[column] = 'desc';
                icon.className = "text-primary fa fa-sort-down";
            } else {
                sortState[column] = null;
                icon.className = "fa fa-sort";
            }

            sortTable(column, sortState[column]);
            console.log(column, sortState[column]);
            
        });
    });

    function sortTable(column, order) {
        let tbody = document.getElementById("table-body");
        let rows = Array.from(tbody.querySelectorAll("tr"));
    
        if (!order) {
            rows.sort((a, b) => a.dataset.index - b.dataset.index);
        } else {
            rows.sort((a, b) => {
                let aValue = a.querySelector(`td[data-column="${column}"]`)?.textContent.trim();
                let bValue = b.querySelector(`td[data-column="${column}"]`)?.textContent.trim();

                if (!isNaN(aValue) && !isNaN(bValue)) {
                    aValue = parseFloat(aValue);
                    bValue = parseFloat(bValue);
                }

                return order === 'asc' ? aValue > bValue ? 1 : -1 : aValue < bValue ? 1 : -1;
            });
        }

        // Reattach sorted rows
        tbody.innerHTML = "";
        rows.forEach(row => tbody.appendChild(row));
    }
});