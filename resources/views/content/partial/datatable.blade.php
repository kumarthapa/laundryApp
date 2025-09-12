<script type="text/javascript">
    function getDataTableS(options, filterData = {}, tableHeaders, getStats = null) {
        // Generate Select2 filters
        getFilterDropdownButtons(filterData);

        // Initialize DataTable
        var dataTable = $("#DataTables2024").DataTable({
            ajax: {
                url: options.url,
                // data: {
                //     default_dateRange: $("#selectedDaterange").val() || '',
                // },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error("AJAX Error: ", textStatus, errorThrown);
                    $('#DataTables2024').append(
                        `<tr>
                        <td colspan="100%" class="text-center">
                            <div class="alert alert-danger" role="alert">Error while loading data!</div>
                        </td>
                     </tr>`
                    );
                }
            },
            columns: tableHeaders,
            // columnDefs: [{
            //     // For Checkboxes
            //     targets: 1,
            //     orderable: false,
            //     responsivePriority: 3,
            //     searchable: false,
            //     checkboxes: true,
            //     render: function() {
            //         return '<input type="checkbox" class="dt-checkboxes form-check-input">';
            //     },
            //     checkboxes: {
            //         selectAllRender: '<input type="checkbox" class="form-check-input">'
            //     }
            // }, ],
            order: false,
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
                },
                {
                    text: '<i class="bx bx-plus me-1"></i>' + (options?.createTitle ?? "Add New Record"),
                    className: 'create-new btn btn-primary addNewRecordBtn ' + (options.createPermissions ?
                        'd-block' : 'd-none'),
                }
            ],
            success: function(response) {
                if (typeof getStats === 'function') {
                    getStats(response);
                }
            }
        });
        $('div.head-label').html('<h4 class="card-title mb-0">' + (options?.title ?? ' ') + '</h4>');
        // Apply filter based on Select2 dropdown changes
        $(document).on('change', '.filter-selected-data', function() {
            let filters = {};
            $('.filter-selected-data').each(function() {
                const filterKey = $(this).attr('id').replace('Filter', '');
                filters[filterKey] = $(this).val();
            });
            const queryString = $.param(filters);
            $('#DataTables2024').DataTable().ajax.url(options.url + '?' + queryString).load();
        });
        $(document).on('keyup', '.dataTables_filter input[type="search"]', function() {
            let filters2 = {};
            filters2['search'] = $(this).val();
            filters2['default_dateRange'] = $("#selectedDaterange").val() || '';
            const queryString2 = $.param(filters2);
            $('#DataTables2024').DataTable().ajax.url(options.url + '?' + queryString2).load();
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
