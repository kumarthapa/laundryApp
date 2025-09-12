<!-- Large Modal -->
<div class="modal fade" id="viewRowDetails" tabindex="-1" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header text-end">
                <h4 class="modal-title">Permissions and role info </h4>
                <button type="button" class="btn btn-label-danger p-2" data-bs-dismiss="modal" aria-label="Close">
                    <i class='bx bx-x'></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card h-100 shadow-none">
                            <div class="card-header d-flex justify-content-between">


                                <div class="card-title py-3 d-flex">
                                    <div class="avatar flex-shrink-0 me-3">
                                        <span class="avatar-initial rounded-circle bg-label-warning"><i
                                                class="bx bx-user"></i></span>
                                    </div>
                                    <div
                                        class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-1">
                                        <div class="me-2">
                                            <h5 class="mb-0" id="row-title">Role Name</h5>
                                            <small class="text-muted" id="row-id">example@123.com</small>
                                        </div>
                                    </div>
                                </div>

                            </div>
                            <div class="card-body pb-0" style="position: relative;">
                                <div class="row">
                                    <div class="col-md-6 col-12">
                                        <ul class="p-0 m-0">
                                            <li class="d-flex mb-4 pb-1">
                                                <div
                                                    class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                                    <div class="me-2">
                                                        <h6 class="mb-0">Role Code</h6>
                                                    </div>
                                                    <div class="">
                                                        <h6 class="mb-0 text-muted" id="role_code"></h6>
                                                    </div>
                                                </div>
                                            </li>
                                        </ul>
                                    </div>

                                    <div class="col-md-6 col-12">
                                        <ul class="p-0 m-0">
                                            <li class="d-flex mb-4 pb-1">
                                                <div
                                                    class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                                    <div class="me-2">
                                                        <h6 class="mb-0">Created Date</h6>
                                                    </div>
                                                    <div class="">
                                                        <h6 class="mb-0 text-muted" id="created_at"></h6>
                                                    </div>
                                                </div>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6 col-12">
                                        <ul class="p-0 m-0">
                                            <li class="d-flex mb-4 pb-1">
                                                <div
                                                    class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                                    <div class="me-2">
                                                        <h6 class="mb-0">Status</h6>
                                                    </div>
                                                    <div class="">
                                                        <h6 class="mb-0 text-muted" id="status"></h6>
                                                    </div>
                                                </div>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="col-12 border-top py-4">
                                        <div class="me-2">
                                            <h5 class="mb-0 py-3">Assigned Permissions</h5>
                                        </div>
                                        <!-- Permission table -->
                                        <div class="table-responsive">
                                            <table class="table table-flush-spacing">
                                                <tbody id="permissionRoleViewTbody">


                                                </tbody>
                                            </table>
                                        </div>
                                        <!-- Permission table -->
                                    </div>
                                </div>
                                <div class="row">

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer text-end">
                    <input type="hidden" id="edit_role_id_2">
                    <a href="javascript:;" class="btn btn-label-primary" onclick="EditPermissions2()">Edit
                        Permissions</a>
                    {{-- <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button> --}}
                    <button type="button" class="btn btn-label-primary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
