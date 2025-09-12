    {{-- Approve customer modal --}}

    <div class="modal fade" id="advance_approve_modal" tabindex="-1" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="modalCenterTitle"> Approve Vendor Advance</h4>
                    <button type="button" class="btn btn-label-danger p-2" data-bs-dismiss="modal" aria-label="Close">
                        <i class='bx bx-x'></i>
                    </button>
                </div>
                <div class="modal-body">
                    Are You sure to approve ?
                </div>
                <div class="modal-footer">
                    <input type="hidden" id="approved_id">
                    <input type="hidden" id="approved_amount">
                    <button type="button" class="btn btn-label-danger vendor_advance_approve_cancel"
                        data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="vendor_advance_approve" class="btn btn-label-primary">Approve</button>
                </div>

            </div>
        </div>
    </div>
