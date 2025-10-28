<script type="text/javascript">
    function getReportsDataTable(options, filterData = {}, tableHeaders, getStats = null) {
        // Generate Select2 filters
        getFilterDropdownButtons(filterData);

        // Initialize DataTable
        var dataTable = $("#DataTables2025").DataTable({
            ajax: {
                url: options.url,
                // data: {
                //     default_dateRange: $("#selectedDaterange").val() || '',
                // },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error("AJAX Error: ", textStatus, errorThrown);
                    $('#DataTables2025').append(
                        `<tr>
                        <td colspan="100%" class="text-center">
                            <div class="alert alert-danger" role="alert">Error while loading data!</div>
                        </td>
                     </tr>`
                    );
                }
            },
            columns: tableHeaders,
            order: false,
            // ====== language settings: remove "Search:" and set placeholder ======
            language: {
                search: "", // removes the "Search:" label text
                searchPlaceholder: "Search ..." // sets the placeholder (DataTables >=1.10.11)
            },
            dom: '<"card-header"<"head-label text-center"><"dt-action-buttons text-end"B>><"d-flex justify-content-between align-items-center row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>t<"d-flex justify-content-between row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
            displayLength: options?.displayLength ?? 10,
            lengthMenu: [7, 10, 25, 50, 75, 100],
            buttons: [{
                    text: '<i class="bx bx-export me-1"></i>' + (options?.is_export ?? "Export"),
                    className: 'create-new btn btn-primary mx-2 exportBtn ' + (options.is_export ?
                        'd-block' : 'd-none'),
                },
                {
                    text: '<i class="bx bx-import me-1"></i>' + (options?.is_import ?? "Import"),
                    className: 'create-new btn btn-primary mx-2 bulkImportBtn ' + (options
                        .createPermissions && options.is_import ? 'd-block' : 'd-none'),
                }
            ],
            // run once when table ready: remove small class, remove label text node, set placeholder
            initComplete: function() {
                var $filter = $('#DataTables2025_filter');
                var $label = $filter.find('label');

                // remove plain text nodes inside label (the "Search:" text)
                $label.contents().filter(function() {
                    return this.nodeType === 3; // text node
                }).remove();

                var $input = $filter.find('input[type="search"]');

                // remove the Bootstrap small class (form-control-sm) if present
                $input.removeClass('form-control-sm');

                // ensure the input has form-control so styling remains consistent
                if (!$input.hasClass('form-control')) {
                    $input.addClass('form-control');
                }

                // set placeholder if not already set by DataTables' searchPlaceholder
                if (!$input.attr('placeholder') || $input.attr('placeholder') === '') {
                    $input.attr('placeholder', 'search ...');
                }

                // accessibility: keep aria-controls intact (no change)
            },
            success: function(response) {
                if (typeof getStats === 'function') {
                    getStats(response);
                }
            }
        });



        // =====Update total rows dynamically Center "Total Rows" between info & pagination ===== START ========
        dataTable.on('init.dt', function() {
            const wrapperRow = $('#DataTables2025_info').closest('.row');

            // Ensure the layout is 3 equal columns: info | total | pagination
            if (!$('#totalRowsCard').length) {
                const colInfo = wrapperRow.find('.col-md-6').first();
                const colPaginate = wrapperRow.find('.col-md-6').last();

                // Change both side columns to col-md-4 for symmetry
                colInfo.removeClass('col-md-6').addClass('col-md-4');
                colPaginate.removeClass('col-md-6').addClass('col-md-4');

                // Insert center column dynamically
                colPaginate.before(`
            <div class="col-sm-12 col-md-4 text-center text-light">
                <div id="totalRowsCard" class="card fw-semibold bg-label-primary text-light py-2">Total Records: 0</div>
            </div>
        `);
            }
        });

        // ===== Update total rows dynamically =====
        dataTable.on('draw.dt', function() {
            setTimeout(() => {
                const total = dataTable.rows({
                    search: 'applied'
                }).count();
                // console.log("Total records:", total);
                $('#totalRowsCard').text('Total Records: ' + total);
            }, 50); // small delay fixes the "0 then 127" flicker
        });
        // =====Update total rows dynamically Center "Total Rows" between info & pagination ===== END ========



        $('div.head-label').html('<h4 class="card-title mb-0">' + (options?.title ?? ' ') + '</h4>');
        // Apply filter based on Select2 dropdown changes
        $(document).on('change', '.filter-selected-data', function() {
            let filters = {};
            $('.filter-selected-data').each(function() {
                const filterKey = $(this).attr('id').replace('Filter', '');
                filters[filterKey] = $(this).val();
            });
            const queryString = $.param(filters);
            $('#DataTables2025').DataTable().ajax.url(options.url + '?' + queryString).load();
        });
        $(document).on('keyup', '.dataTables_filter input[type="search"]', function() {
            let filters2 = {};
            filters2['search'] = $(this).val();
            filters2['default_dateRange'] = $("#selectedDaterange").val() || '';
            const queryString2 = $.param(filters2);
            $('#DataTables2025').DataTable().ajax.url(options.url + '?' + queryString2).load();
        });
    }

    function getFilterDropdownButtons(filterData = {}) {
        let dropdownButtonsHtml = '';
        for (const key in filterData) {
            if (filterData.hasOwnProperty(key)) {
                const filter = filterData[key];
                dropdownButtonsHtml += `
                <select id="${key}Filter" class="form-control filter-selected-data select2-filter" style="width: 200px;">
                    <option value="">${filter.filter_name}</option>
                    ${Object.entries(filter.data)
                        .map(([filterKey, filterValue]) => `<option value="${filterKey}">${filterValue}</option>`)
                        .join('')}
                </select>
            `;
            }
        }
        // Inject into an existing element, e.g., with id 'filter-container'
        $('#filter-container').prepend(dropdownButtonsHtml);
        $('.select2-filter').select2(); // Initialize Select2 on these dropdowns
    }
</script>
