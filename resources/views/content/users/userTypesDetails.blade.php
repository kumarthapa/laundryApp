{{-- ====================================== Employee details ======================================== ************ ==================================== --}}
<div class="card shadow-none bg-transparent border border-secondary mb-4 details_div" id="employees_details_div"
    style="display: none;">
    <div class="card-header card_title">Employees details <span class="text-primary"><i
                class='bx bxs-message-alt-detail'></i></span></div>
    <div class="card-body text-secondary">
        <div class="mb-0">
            <p class="card-text">
                Select the Employee for whom you want to create login details
            </p>
            <select id="employees_details" class="select2 form-select form-select-lg" name="user_code[employees]"
                data-allow-clear="true">
                <option value="">Select an option</option>
            </select>
        </div>
    </div>
</div>
{{-- ====================================== Drivers details ======================================== ************ ==================================== --}}
<div class="card shadow-none bg-transparent border border-secondary mb-4 details_div" id="driver_details_div"
    style="display: none;">
    <div class="card-header card_title">Drivers details <span class="text-primary"><i
                class='bx bxs-message-alt-detail'></i></span></div>
    <div class="card-body text-secondary">
        <p class="card-text">
            Select the drivers for whom you want to create login details
        </p>

        <div class="mb-3">
            <label for="driver_details" class="form-label">Search and select driver</label>
            <select id="driver_details" class="select2 form-select form-select-lg" name="user_code[drivers]"
                data-allow-clear="true">
                <option value="">Select an option</option>
            </select>
        </div>
    </div>
</div>



{{-- ====================================== Suppliers details ======================================== ************ ==================================== --}}
<div class="card shadow-none bg-transparent border border-secondary mb-4 details_div" id="suppliers_details_div"
    style="display: none;">
    <div class="card-header card_title">Suppliers details <span class="text-primary"><i
                class='bx bxs-message-alt-detail'></i></span></div>
    <div class="card-body text-secondary">
        <p class="card-text">
            Select the suppliers for whom you want to create login details
        </p>

        <div class="mb-3">
            <label for="suppliers_details" class="form-label">Search and select supplier</label>
            <select id="suppliers_details" class="select2 form-select form-select-lg" name="user_code[suppliers]"
                data-allow-clear="true">
                <option value="">Select an option</option>
            </select>
        </div>
    </div>
</div>

{{-- ====================================== Customers details ======================================== ************ ==================================== --}}
<div class="card shadow-none bg-transparent border border-secondary mb-4 details_div" id="customers_details_div"
    style="display: none;">
    <div class="card-header card_title">Customers details <span class="text-primary"><i
                class='bx bxs-message-alt-detail'></i></span></div>
    <div class="card-body text-secondary">
        <div class="mb-0">
            <p class="card-text">
                Select the customer for whom you want to create login details
            </p>
            <select id="customers_details" class="select2 form-select form-select-lg" name="user_code[customers]"
                data-allow-clear="true">
                <option value="">Select an option</option>
            </select>
        </div>
    </div>
</div>
